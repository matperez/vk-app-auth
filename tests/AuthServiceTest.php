<?php
/**
 * Created by PhpStorm.
 * Filename: AuthServiceTest.php
 * User: andrey
 * Date: 05.09.15
 * Time: 1:07
 */

namespace Vk\AppAuth\tests;

use GuzzleHttp\Message\Response;
use Vk\AppAuth\Authenticator;
use Vk\AppAuth\AuthService;
use Vk\AppAuth\GrantPageParser;

class AuthServiceTest extends TestCase
{
    public function test_it_will_return_null_if_can_not_create_new_token()
    {
        /** @var AuthService|\Mockery\Mock $service */
        $service = \Mockery::mock('Vk\AppAuth\AuthService[handleAuthState,getLastResponse,getLastResponse]', [$this->grantPageParser, $this->authenticator]);

        $service->setState(AuthService::STATE_FAULT);

        $this->assertNull($service->createToken('email', 'password', 1234, 'scope'));

        $this->assertContains('Unable to create new token!', $service->getLogMessages());
    }

    public function test_it_can_create_tokens()
    {
        /** @var AuthService|\Mockery\Mock $service */
        $service = \Mockery::mock('Vk\AppAuth\AuthService[handleAuthState,getLastResponse,getLastResponse]', [$this->grantPageParser, $this->authenticator]);
        $service->shouldReceive('handleAuthState');

        $service->setState(AuthService::STATE_SUCCESS);

        $redirect = 'https://oauth.vk.com/blank.html#access_token=e01fb069ba1e0f0e1ec23e3bdf0a3&expires_in=0&user_id=5564';
        $response = new Response(200);
        $response->setEffectiveUrl($redirect);
        $service->shouldReceive('getLastResponse')->andReturn($response);

        $token = $service->createToken('email', 'password', 1234, 'scope');

        $this->assertInstanceOf('Vk\AppAuth\TokenInfo', $token);
        $this->assertEquals('e01fb069ba1e0f0e1ec23e3bdf0a3', $token->getAccessToken());
        $this->assertEquals('0', $token->getExpiresIn());
        $this->assertEquals('5564', $token->getUserId());

        $this->assertContains('Got token redirect https://oauth.vk.com/blank.html#access_token=e01fb069ba1e0f0e1ec23e3bdf0a3&expires_in=0&user_id=5564!', $service->getLogMessages());
    }

    public function test_it_can_handle_auth_errors()
    {
        // expectations
        $this->authenticator->shouldReceive('setClient')->with($this->client);
        $auth = new Response(200);
        $auth->setEffectiveUrl('some unknown url.. maybe an auth url again');
        $this->authenticator->shouldReceive('authenticate')->with('email', 'password', 1234, 'scope')->andReturn($auth);

        // run
        $this->service->handleAuthState('email', 'password', 1234, 'scope');

        // assertions
        $this->assertContains('Auth required!', $this->service->getLogMessages());
        $this->assertEquals(AuthService::STATE_FAULT, $this->service->getState());
    }

    public function test_it_can_handle_common_case_auth()
    {
        // expectations
        $this->authenticator->shouldReceive('setClient')->with($this->client);
        $auth = new Response(200);
        $auth->setEffectiveUrl('https://oauth.vk.com/authorize?client_id=3713774&redirect_uri=https%3A%2F%2Foauth.vk.com%2Fblank.html&response_type=token&scope=73736&v=&state=&display=wap&__q_hash=d08ddd422df41e7f477699c');
        $this->authenticator->shouldReceive('authenticate')->with('email', 'password', 1234, 'scope')->andReturn($auth);

        // run
        $this->service->handleAuthState('email', 'password', 1234, 'scope');

        // assertions
        $this->assertContains('Auth required!', $this->service->getLogMessages());
        $this->assertEquals(AuthService::STATE_GRANT, $this->service->getState());
    }

    public function test_it_can_handle_auth_if_access_is_already_granted()
    {
        // expectations
        $this->authenticator->shouldReceive('setClient')->with($this->client);
        $auth = new Response(200);
        $auth->setEffectiveUrl('https://oauth.vk.com/blank.html#access_token=e01fb069ba1e0f0e1ec239ae3bdf0a3&expires_in=0&user_id=5564');
        $this->authenticator->shouldReceive('authenticate')->with('email', 'password', 1234, 'scope')->andReturn($auth);

        // runtime
        $this->service->handleAuthState('email', 'password', 1234, 'scope');

        // assertions
        $this->assertContains('Auth required!', $this->service->getLogMessages());
        $this->assertEquals(AuthService::STATE_SUCCESS, $this->service->getState());
    }

    public function test_it_can_handle_grant_state_and_grant_access()
    {
        // initial service state
        $this->service->setState(AuthService::STATE_GRANT);
        $lastResponse = new Response(200);
        $lastResponse->setEffectiveUrl('grant page url');
        $this->service->setLastResponse($lastResponse);

        // expectations
        $grantPageResponse = new Response(200);
        $this->client->shouldReceive('get')->with('grant page url')->andReturn($grantPageResponse);
        $this->grantPageParser->shouldReceive('getGrantUrl')->andReturn('grant access url');

        $tokenPageResponse = new Response(200);
        $tokenPageResponse->setEffectiveUrl('https://oauth.vk.com/blank.html#access_token=e01fb06f0a3&expires_in=0&user_id=564');
        $this->client->shouldReceive('get')->with('grant access url')->andReturn($tokenPageResponse);

        // asserts
        $this->service->handleGrantState();

        $this->assertEquals(AuthService::STATE_SUCCESS, $this->service->getState());
        $this->assertContains('Access grant required!', $this->service->getLogMessages());
        $this->assertEquals($tokenPageResponse, $this->service->getLastResponse());
    }

    public function test_it_can_handle_phone_number_required_state()
    {
        $this->service->setState(AuthService::STATE_PHONE_NUMBER_REQUIRED);
        $this->service->handlePhoneNumberRequiredState();
        $this->assertEquals(AuthService::STATE_FAULT, $this->service->getState());
        $this->assertContains('Phone number required!', $this->service->getLogMessages());
    }

    public function test_it_can_handle_invalid_account_state()
    {
        $this->service->setState(AuthService::STATE_INVALID_ACCOUNT);
        $this->service->handleInvalidAccountState();
        $this->assertEquals(AuthService::STATE_FAULT, $this->service->getState());
        $this->assertContains('Invalid username or password!', $this->service->getLogMessages());
    }

    public function test_it_can_be_created()
    {
        $this->assertInstanceOf('Vk\AppAuth\AuthService', $this->service);
    }

    /**
     * @var AuthService
     */
    protected $service;

    /**
     * @var GrantPageParser|\Mockery\Mock
     */
    protected $grantPageParser;

    /**
     * @var Authenticator|\Mockery\Mock
     */
    protected $authenticator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->grantPageParser = \Mockery::mock('Vk\AppAuth\GrantPageParser');
        $this->authenticator = \Mockery::mock('Vk\AppAuth\Authenticator');
        $this->service = new AuthService($this->grantPageParser, $this->authenticator);
        $this->service->setClient($this->client);
    }
}
