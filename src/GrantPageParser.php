<?php
/**
 * Created by PhpStorm.
 * Filename: GrantPageParser.php
 * User: andrey
 * Date: 05.09.15
 * Time: 14:16
 */

namespace Vk\AppAuth;

use Vk\AppAuth\Exceptions\GrantPageParserException;
use Vk\AppAuth\Interfaces\GrantPageParserInterface;

class GrantPageParser implements GrantPageParserInterface
{
    /**
     * @param string $content
     * @return bool|string
     * @throws GrantPageParserException
     */
    public function getGrantUrl($content)
    {
        return $this->getForm($content)->getAttribute('action');
    }

    /**
     * @param string $content
     * @return \simple_html_dom_node
     * @throws GrantPageParserException
     */
    protected function getForm($content)
    {
        $html = new \simple_html_dom($content);
        $form = $html->find('form', 0);
        if (!$form) {
            throw new GrantPageParserException('Unable to find grant page form!');
        }
        return $form;
    }
}
