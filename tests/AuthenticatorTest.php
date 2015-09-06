<?php
/**
 * Created by PhpStorm.
 * Filename: AuthenticatorTest.php
 * User: andrey
 * Date: 07.09.15
 * Time: 1:20
 */

namespace Vk\AppAuth\tests;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use Vk\AppAuth\Authenticator;
use Vk\AppAuth\AuthPageParser;

class AuthenticatorTest extends TestCase
{
    public function testItCanAuthenticate()
    {
        // expectations
        $authPage = $this->getStoredResponse('auth-page');
        $this->client->shouldReceive('get')->andReturn(
            new Response(200, [], Stream::factory($authPage))
        );

        $this->parser->shouldReceive('getAuthUrl')->andReturn('auth url');
        $this->parser->shouldReceive('getAuthParams')->andReturn([]);

        $grantPage = $this->getStoredResponse('grant-page');
        $auth = new Response(200, [], Stream::factory($grantPage));

        $this->client->shouldReceive('post')->with('auth url', \Mockery::any())->andReturn($auth);

        // assertions
        $this->assertEquals($auth, $this->authenticator->authenticate('email', 'password', 1234, 'scope'));

    }

    public function test_it_will_throw_an_exception_if_you_forget_to_set_client()
    {
        $this->setExpectedException('Vk\AppAuth\Exceptions\AuthenticatorException');
        $this->authenticator->setClient(null);
        $this->authenticator->getClient();
    }

    public function test_it_can_set_and_get_client()
    {
        $client = new Client();
        $this->authenticator->setClient($client);
        $this->assertSame($client, $this->authenticator->getClient());
    }

    public function testItCanBeCreated()
    {
        $this->assertInstanceOf('Vk\AppAuth\Authenticator', $this->authenticator);
    }

    /**
     * @var AuthPageParser|\Mockery\Mock
     */
    protected $parser;

    /**
     * @var Authenticator
     */
    protected $authenticator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->parser = \Mockery::mock('Vk\AppAuth\AuthPageParser');
        $this->authenticator = new Authenticator($this->parser);
        $this->authenticator->setClient($this->client);
    }
}
