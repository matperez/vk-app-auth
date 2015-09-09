<?php
/**
 * Created by PhpStorm.
 * Filename: BaseAuthService.php
 * User: andrey
 * Date: 06.09.15
 * Time: 21:18
 */

namespace Vk\AppAuth\tests;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use Vk\AppAuth\AuthPageParser;
use Vk\AppAuth\BaseAuthService;
use Vk\AppAuth\GrantPageParser;

class BaseAuthServiceTest extends TestCase
{
    public function test_it_can_get_and_set_last_response()
    {
        $this->assertNull($this->service->getLastResponse());
        $response = new Response(200);
        $this->service->setLastResponse($response);
        $this->assertSame($response, $this->service->getLastResponse());
    }

    public function test_it_can_get_and_set_state()
    {
        $this->assertEquals(BaseAuthService::STATE_AUTH, $this->service->getState());
        $this->service->setState(BaseAuthService::STATE_FAULT);
        $this->assertEquals(BaseAuthService::STATE_FAULT, $this->service->getState());
    }

    public function test_it_can_get_set_and_create_new_client_if_null()
    {
        $client = new Client();
        $this->assertInstanceOf('GuzzleHttp\Client', $this->service->getClient());
        $this->assertNotSame($client, $this->service->getClient());
        $this->service->setClient($client);
        $this->assertSame($client, $this->service->getClient());
    }

    /**
     * @var BaseAuthService|\Mockery\Mock
     */
    protected $service;

    /**
     * @var AuthPageParser|\Mockery\Mock
     */
    protected $authPageParser;

    /**
     * @var GrantPageParser|\Mockery\Mock
     */
    protected $grantPageParser;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->authPageParser = \Mockery::mock('Vk\AppAuth\AuthPageParser');
        $this->grantPageParser = \Mockery::mock('Vk\AppAuth\GrantPageParser');
        $this->service = \Mockery::mock('Vk\AppAuth\BaseAuthService[createToken]');
    }
}
