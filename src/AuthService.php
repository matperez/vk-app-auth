<?php
/**
 * Created by PhpStorm.
 * Filename: AuthService.php
 * User: andrey
 * Date: 05.09.15
 * Time: 1:00
 */

namespace Vk\AppAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;
use Vk\AppAuth\Interfaces\AuthPageParserInterface;
use Vk\AppAuth\Interfaces\GrantPageParserInterface;

class AuthService
{
    const CONNECT_TIMEOUT = 5;

    const STATE_AUTH = 1;
    const STATE_INVALID_ACCOUNT = 2;
    const STATE_CAPTCHA_REQUIRED = 3;
    const STATE_PHONE_NUMBER_REQUIRED = 4;
    const STATE_GRANT = 5;
    const STATE_TOKEN_PAGE = 6;
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
     * @var AuthPageParserInterface
     */
    protected $formPageParser;

    /**
     * @var ResponseInterface
     */
    protected $lastResponse;

    /**
     * @var array
     */
    protected $logMessages = [];

    /**
     * @param AuthPageParserInterface $formPageParser
     * @param GrantPageParserInterface $grantPageParser
     */
    public function __construct(AuthPageParserInterface $formPageParser, GrantPageParserInterface $grantPageParser)
    {
        $this->formPageParser = $formPageParser;
        $this->grantPageParser = $grantPageParser;
    }

    /**
     * Запросить страницу авторизации. Авторизоваться.
     * Если получили редирект на страницу с разрешениями, перейти в состояние STATE_GRANT_PAGE
     * Если снова получили редирект на страницу авторизации, проверить что не так и перейти в
     * одно из состояний: STATE_INVALID_ACCOUNT, STATE_CAPTCHA_REQUIRED, STATE_PHONE_NUMBER_REQUIRED.
     * Если не знаем, что не так, переходим сразу в состояние STATE_FAULT.
     * @param string $email
     * @param string $password
     * @param int $appId
     * @param string $scope
     */
    public function handleAuthState($email, $password, $appId, $scope)
    {
        $this->addLogMessage('Cостояние аутентификации:');
        $authPage = $this->getAuthPage($appId, $scope);
        $content = $authPage->getBody();
        $auth = $this->authenticate($email, $password, $content);
        $this->setLastResponse($auth);
        $redirectUrl = $auth->getEffectiveUrl();
        if (preg_match('/access_token=[\d\w]+/', $redirectUrl)) {
            // https://oauth.vk.com/blank.html#access_token=e01fb069ba1e0f0e1ec23...9ae3bdf0a3&expires_in=0&user_id=55...64
            $this->setState(self::STATE_TOKEN_PAGE);
        } elseif (preg_match('/__q_hash=[\d\w]+/', $redirectUrl)) {
            // https://oauth.vk.com/authorize?client_id=3713774&redirect_uri=https%3A%2F%2Foauth.vk.com%2Fblank.html&response_type=token&scope=73736&v=&state=&display=wap&__q_hash=d08ddd42...2df41e7f477699c
            $this->setState(self::STATE_GRANT);
        } else {
            $this->setState(self::STATE_FAULT);
        }
    }

    /**
     * Состояние нерабочего аккаунта. Добавить сообщение в лог и перейти в состояние ошибки.
     */
    public function handleInvalidAccountState()
    {
        $this->addLogMessage('Состояние нерабочего аккаунта:');
        $this->setState(self::STATE_FAULT);
    }

    /**
     * Состояние получения разрешения от пользователя.
     * Если получилось получить ссылку на разрешение, запросить ее, получить
     * редирект на токен и перейти в состояние STATE_TOKEN_PAGE.
     * Если не удалось получить ссылку на разрешение, перейти в состояние STATE_FAULT
     */
    public function handleGrantState()
    {
        $this->addLogMessage('Состояние получения разрешения:');
        $grantPageUrl = $this->getLastResponse()->getEffectiveUrl();
        $grantPage = $this->getClient()->get($grantPageUrl);
        $grantUrl = $this->grantPageParser->getGrantUrl($grantPage->getBody());
        $grant = $this->getClient()->get($grantUrl);
        $this->setLastResponse($grant);
        if (preg_match('/access_token=[\d\w]+/', $grant->getEffectiveUrl())) {
            // https://oauth.vk.com/blank.html#access_token=e01fb069ba1e0f0e1ec23528...ae3bdf0a3&expires_in=0&user_id=5....64
            $this->setState(self::STATE_TOKEN_PAGE);
        } else {
            $this->addLogMessage('Не удалось получить редиректа на токен при подтверждении разрешений.');
            $this->setState(self::STATE_FAULT);
        }
    }

    /**
     * @param string $email
     * @param string $password
     * @param string $appId
     * @param string $scope
     * @return TokenInfo
     */
    public function createToken($email, $password, $appId, $scope = 'audio,offline,wall')
    {
        do {
            switch ($this->getState()) {
                case self::STATE_AUTH:
                    $this->handleAuthState($email, $password, $appId, $scope);
                    break;
                case self::STATE_INVALID_ACCOUNT:
                    $this->handleInvalidAccountState();
                    break;
                case self::STATE_CAPTCHA_REQUIRED:
                    break;
                case self::STATE_PHONE_NUMBER_REQUIRED:
                    break;
                case self::STATE_GRANT:
                    $this->handleGrantState();
                    break;
            }
        } while ($this->getState() !== self::STATE_TOKEN_PAGE && $this->getState() !== self::STATE_FAULT);

        if ($this->getState() === self::STATE_TOKEN_PAGE) {
            $redirect = $this->getLastResponse()->getEffectiveUrl();
            $this->addLogMessage(sprintf('Получен редирект на токен %s', $redirect));
            return TokenInfo::createFromRedirect($redirect);
        }

        $this->addLogMessage('Токен получить не удалось.');
        return null;
    }

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
     * @param int $appId
     * @param string $scope
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    protected function getAuthPage($appId, $scope)
    {
        $authPageUrl = sprintf('https://oauth.vk.com/oauth/authorize?redirect_uri=https://oauth.vk.com/blank.html&response_type=token&client_id=%s&scope=%s&display=wap', $appId, $scope);
        $authPageRequest = $this->getClient()->get($authPageUrl);
        return $authPageRequest;
    }

    /**
     * @param string $email
     * @param string $password
     * @param string $authPageContent
     * @return \GuzzleHttp\Message\ResponseInterface
     */
    protected function authenticate($email, $password, $authPageContent)
    {
        $authUrl = $this->formPageParser->getAuthUrl($authPageContent);
        $authParams = array_merge($this->formPageParser->getAuthParams($authPageContent), [
            'email' => $email,
            'pass' => $password,
        ]);
        $authRequest = $this->getClient()->post($authUrl, [
            'body' => $authParams
        ]);
        return $authRequest;
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
