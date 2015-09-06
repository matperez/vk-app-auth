<?php
/**
 * Created by PhpStorm.
 * Filename: Token.php
 * User: andrey
 * Date: 06.09.15
 * Time: 15:37
 */

namespace Vk\AppAuth;

class TokenInfo
{
    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var string
     */
    protected $expiresIn;

    /**
     * @param string $redirectUrl
     * @return self
     */
    public static function createFromRedirect($redirectUrl)
    {
        $query = explode('#', $redirectUrl)[1];
        parse_str($query, $params);
        return new TokenInfo($params);
    }

    /**
     * @param array $queryParams
     */
    public function __construct($queryParams)
    {
        if (array_key_exists('access_token', $queryParams)) {
            $this->accessToken = $queryParams['access_token'];
        }
        if (array_key_exists('expires_in', $queryParams)) {
            $this->expiresIn = $queryParams['expires_in'];
        }
        if (array_key_exists('user_id', $queryParams)) {
            $this->userId = $queryParams['user_id'];
        }
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
