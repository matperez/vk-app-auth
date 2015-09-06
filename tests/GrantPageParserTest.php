<?php
/**
 * Created by PhpStorm.
 * Filename: GrantPageParserTest.php
 * User: andrey
 * Date: 05.09.15
 * Time: 14:17
 */

namespace Vk\AppAuth\tests;

use Vk\AppAuth\GrantPageParser;
use Vk\AppAuth\Interfaces\GrantPageParserInterface;

class GrantPageParserTest extends TestCase
{
    public function test_it_can_get_grant_url_from_content()
    {
        $content = $this->getStoredResponse('grant-page');
        $grantUrl = 'https://login.vk.com/?act=grant_access&client_id=3713774&settings=73736&redirect_uri=https%3A%2F%2Foauth.vk.com%2Fblank.html&response_type=token&direct_hash=3bae723423423490ee&token_type=0&v=&state=&display=wap&ip_h=8e29e2340ca4f20&hash=a93ea7af234e252&https=1';
        $this->assertEquals($grantUrl, $this->parser->getGrantUrl($content));
    }

    public function test_it_will_throw_an_exception_if_can_not_find_form()
    {
        $content = $this->getStoredResponse('invalid-grant-page');
        $this->setExpectedException('Vk\AppAuth\Exceptions\GrantPageParserException', 'Unable to find grant page form!');
        $this->parser->getGrantUrl($content);
    }

    /**
     * @var GrantPageParserInterface
     */
    protected $parser;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->parser = new GrantPageParser();
    }
}
