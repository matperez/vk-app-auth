<?php
/**
 * Created by PhpStorm.
 * Filename: AuthenticatorInterface.php
 * User: andrey
 * Date: 07.09.15
 * Time: 1:09
 */
namespace Vk\AppAuth\Interfaces;

use GuzzleHttp\Client;
use Vk\AppAuth\Exceptions\AuthenticatorException;

interface AuthenticatorInterface
{
    /**
     * @param string $email
     * @param string $password
     * @param int $appId
     * @param string $scope
     * @return \GuzzleHttp\Message\ResponseInterface
     * @throws AuthenticatorException
     */
    public function authenticate($email, $password, $appId, $scope);

    /**
     * @return Client
     * @throws AuthenticatorException
     */
    public function getClient();

    /**
     * @param Client $client
     */
    public function setClient($client);
}