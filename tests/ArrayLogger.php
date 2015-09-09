<?php
/**
 * Created by PhpStorm.
 * Filename: ArrayLogger.php
 * User: andrey
 * Date: 09.09.15
 * Time: 13:02
 */

namespace Vk\AppAuth\tests;

use Psr\Log\LoggerInterface;

class ArrayLogger implements LoggerInterface
{
    /**
     * @var array
     */
    private $messages = [];

    /**
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array())
    {
        $this->messages[] = $message;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
