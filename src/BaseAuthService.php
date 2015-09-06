<?php
/**
 * Created by PhpStorm.
 * Filename: BaseAuthService.php
 * User: andrey
 * Date: 06.09.15
 * Time: 19:44
 */

namespace Vk\AppAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

abstract class BaseAuthService
{
    const CONNECT_TIMEOUT = 5;
    const STATE_AUTH = 1;
    const STATE_INVALID_ACCOUNT = 2;
    const STATE_PHONE_NUMBER_REQUIRED = 4;
    const STATE_GRANT = 5;
    const STATE_SUCCESS = 6;
    const STATE_FAULT = 7;
    /**
     * @var int
     */
    protected $state = self::STATE_AUTH;
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var array
     * @see \Vk\AppAuth\AuthService::createClient
     */
    protected $clientDefaults = [];
    /**
     * @var ResponseInterface
     */
    protected $lastResponse;
    /**
     * @var array
     */
    protected $logMessages = [];

    /**
     * @param string $email
     * @param string $password
     * @param string $appId
     * @param string $scope
     * @return TokenInfo
     */
    abstract public function createToken($email, $password, $appId, $scope);

    /**
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = $this->createClient();
        }
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    protected function createClient()
    {
        $defaults = array_merge_recursive([
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'timeout' => self::CONNECT_TIMEOUT,
            'cookies' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.85 Safari/537.36',
            ],
        ], $this->clientDefaults);
        return new Client([
            'defaults' => $defaults
        ]);
    }

    /**
     * Get FSM state
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set FSM state
     * @param int $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return ResponseInterface
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @param ResponseInterface $lastResponse
     */
    public function setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;
    }

    /**
     * @return array
     */
    public function getLogMessages()
    {
        return $this->logMessages;
    }

    /**
     * @param string $message
     */
    public function addLogMessage($message)
    {
        $this->logMessages[] = $message;
    }
}
