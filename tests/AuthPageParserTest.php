<?php
/**
 * Created by PhpStorm.
 * Filename: AuthPageParserTest.php
 * User: andrey
 * Date: 05.09.15
 * Time: 12:48
 */

namespace Vk\AppAuth\tests;

use Vk\AppAuth\AuthPageParser;

class AuthPageParserTest extends TestCase
{
    public function test_it_can_get_auth_form_url()
    {
        $content = $this->getStoredResponse('auth-page');
        $this->assertEquals('https://login.vk.com/?act=login&soft=1&utf8=1', $this->parser->getAuthUrl($content));
    }

    public function test_it_will_throw_an_exception_when_it_can_not_find_the_form()
    {
        $content = $this->getStoredResponse('invalid-auth-page');
        $this->setExpectedException('Vk\AppAuth\Exceptions\AuthPageParserException', 'Unable to get auth form from content!');
        $this->parser->getAuthParams($content);
    }

    public function test_it_can_parse_form_fields()
    {
        $content = $this->getStoredResponse('auth-page');
        $this->assertEquals([
            '_origin' => 'https://oauth.vk.com',
            'ip_h' => '8e29e63c00c0ca4f20',
            'lg_h' => '1b2c06197b914831c8',
            'to' => 'aHR0cHM6Ly9vYXV0aC52ay5jb20vYXV0aG9yaXplP2NsaWVudF9pZD0zNzEzNzc0JnJlZGlyZWN0X3VyaT1odHRwcyUzQSUyRiUyRm9hdXRoLnZrLmNvbSUyRmJsYW5rLmh0bWwmcmVzcG9uc2VfdHlwZT10b2tlbiZzY29wZT04JnY9JnN0YXRlPSZkaXNwbGF5PXdhcA--',
        ], $this->parser->getAuthParams($content));
    }

    /**
     * @var AuthPageParser
     */
    protected $parser;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->parser = new AuthPageParser();
    }
}
