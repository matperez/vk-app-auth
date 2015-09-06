<?php
/**
 * Created by PhpStorm.
 * Filename: Authenticator.php
 * User: andrey
 * Date: 07.09.15
 * Time: 1:04
 */

namespace Vk\AppAuth;

use GuzzleHttp\Client;
use Vk\AppAuth\Exceptions\AuthenticatorException;
use Vk\AppAuth\Interfaces\AuthenticatorInterface;
use Vk\AppAuth\Interfaces\AuthPageParserInterface;

class Authenticator implements AuthenticatorInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var AuthPageParserInterface
     */
    protected $authPageParser;

    public function __construct(AuthPageParserInterface $authPageParser)
    {
        $this->authPageParser = $authPageParser;
    }

    /**
     * @param string $email
     * @param string $password
     * @param int $appId
     * @param string $scope
     * @return \GuzzleHttp\Message\ResponseInterface
     * @throws AuthenticatorException
     */
    public function authenticate($email, $password, $appId, $scope)
    {
        $authPageUrl = sprintf('https://oauth.vk.com/oauth/authorize?redirect_uri=https://oauth.vk.com/blank.html&response_type=token&client_id=%s&scope=%s&display=wap', $appId, $scope);
        $authPage = $this->getClient()->get($authPageUrl);
        $authUrl = $this->authPageParser->getAuthUrl($authPage->getBody());
        $authParams = array_merge($this->authPageParser->getAuthParams($authPage->getBody()), [
            'email' => $email,
            'pass' => $password,
        ]);
        return $this->getClient()->post($authUrl, ['body' => $authParams]);
    }

    /**
     * @return Client
     * @throws AuthenticatorException
     */
    public function getClient()
    {
        if (!$this->client) {
            throw new AuthenticatorException('You must specify the client!');
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
}
