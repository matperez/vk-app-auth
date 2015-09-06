<?php
/**
 * Created by PhpStorm.
 * Filename: AuthServiceTest.php
 * User: andrey
 * Date: 05.09.15
 * Time: 1:07
 */

namespace Vk\AppAuth\tests;

use Vk\AppAuth\AuthPageParser;
use Vk\AppAuth\AuthService;
use Vk\AppAuth\GrantPageParser;

class AuthServiceTest extends TestCase
{
    public function test_it_can_be_created()
    {
        $this->assertInstanceOf('Vk\AppAuth\AuthService', $this->service);
    }

    /**
     * @var AuthService
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
        $this->service = new AuthService($this->authPageParser, $this->grantPageParser);
    }
}
