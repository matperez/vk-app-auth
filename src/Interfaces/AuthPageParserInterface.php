<?php
/**
 * Created by PhpStorm.
 * Filename: AuthPageParserInterface.php
 * User: andrey
 * Date: 05.09.15
 * Time: 12:39
 */
namespace Vk\AppAuth\Interfaces;

interface AuthPageParserInterface
{
    /**
     * Extract hidden input values from content
     * @param string $content
     * @return array
     */
    public function getAuthParams($content);

    /**
     * Extract auth form url from content
     * @param string $content
     * @return string
     */
    public function getAuthUrl($content);
}