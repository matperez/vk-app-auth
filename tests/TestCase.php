<?php
/**
 * Created by PhpStorm.
 * Filename: TestCase.php
 * User: andrey
 * Date: 05.09.15
 * Time: 1:02
 */

namespace Vk\AppAuth\tests;

use Faker\Factory;
use Faker\Generator;
use Guzzle\Http\Client;

class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client|\Mockery\Mock
     */
    protected $client;

    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @param string $name
     * @return string
     */
    protected function getStoredResponse($name)
    {
        $path = __DIR__.'/responses/'.$name.'.html';
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('Requested file %s not found!', $path));
        }
        return file_get_contents($path);
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->client = \Mockery::mock('Guzzle\Http\Client');
        $this->faker = Factory::create();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }
}
