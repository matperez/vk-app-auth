<?php
/**
 * Created by PhpStorm.
 * Filename: AuthPageParser.php
 * User: andrey
 * Date: 05.09.15
 * Time: 12:36
 */

namespace Vk\AppAuth;

use Vk\AppAuth\Exceptions\AuthPageParserException;
use Vk\AppAuth\Interfaces\AuthPageParserInterface;

class AuthPageParser implements AuthPageParserInterface
{
    /**
     * @param string $content
     * @return bool|string
     * @throws AuthPageParserException
     */
    public function getAuthUrl($content)
    {
        $form = $this->getForm($content);
        return $form->getAttribute('action');
    }

    /**
     * @param string $content
     * @return array
     * @throws AuthPageParserException
     */
    public function getAuthParams($content)
    {
        $form = $this->getForm($content);
        $params = [];
        $inputs = $form->find('input[type=hidden]');
        foreach ($inputs as $input) {
            /** @var \simple_html_dom_node $input */
            $params[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        return $params;
    }

    /**
     * @param string $content
     * @return \simple_html_dom_node
     * @throws AuthPageParserException
     */
    protected function getForm($content)
    {
        $html = new \simple_html_dom($content);
        $form = $html->find('form', 0);
        if (!$form) {
            throw new AuthPageParserException('Unable to get auth form from content!');
        }
        return $form;
    }
}
