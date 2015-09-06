<?php
/**
 * Created by PhpStorm.
 * Filename: GrantPageParserInterface.php
 * User: andrey
 * Date: 05.09.15
 * Time: 14:27
 */
namespace Vk\AppAuth\Interfaces;

use Vk\AppAuth\Exceptions\GrantPageParserException;

interface GrantPageParserInterface
{
    /**
     * @param string $content
     * @return bool|string
     * @throws GrantPageParserException
     */
    public function getGrantUrl($content);
}