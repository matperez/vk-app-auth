<?php
/**
 * Created by PhpStorm.
 * Filename: AuthService.php
 * User: andrey
 * Date: 05.09.15
 * Time: 1:00
 */

namespace Vk\AppAuth;

use Vk\AppAuth\Interfaces\AuthenticatorInterface;
use Vk\AppAuth\Interfaces\GrantPageParserInterface;

class AuthService extends BaseAuthService
{
    /**
     * @var GrantPageParserInterface
     */
    protected $grantPageParser;

    /**
     * @var AuthenticatorInterface
     */
    protected $authenticator;

    /**
     * @param GrantPageParserInterface $grantPageParser
     * @param AuthenticatorInterface $authenticator
     */
    public function __construct(
        GrantPageParserInterface $grantPageParser,
        AuthenticatorInterface $authenticator
    )
    {
        $this->grantPageParser = $grantPageParser;
        $this->authenticator = $authenticator;
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
                case self::STATE_PHONE_NUMBER_REQUIRED:
                    $this->handlePhoneNumberRequiredState();
                    break;
                case self::STATE_GRANT:
                    $this->handleGrantState();
                    break;
            }
        } while ($this->getState() !== self::STATE_SUCCESS && $this->getState() !== self::STATE_FAULT);

        if ($this->getState() === self::STATE_SUCCESS) {
            $redirect = $this->getLastResponse()->getEffectiveUrl();
            $this->getLogger()->debug(sprintf('Got token redirect %s!', $redirect));
            return TokenInfo::createFromRedirect($redirect);
        }

        $this->getLogger()->debug('Unable to create new token!');
        return null;
    }

    /**
     * Запросить страницу авторизации. Авторизоваться.
     * Если получили редирект на страницу с разрешениями, перейти в состояние STATE_GRANT_PAGE
     * Если снова получили редирект на страницу авторизации, проверить что не так и перейти в
     * одно из состояний: STATE_INVALID_ACCOUNT, STATE_PHONE_NUMBER_REQUIRED.
     * Если не знаем, что не так, переходим сразу в состояние STATE_FAULT.
     * @param string $email
     * @param string $password
     * @param int $appId
     * @param string $scope
     */
    public function handleAuthState($email, $password, $appId, $scope)
    {
        $this->getLogger()->debug('Auth required!');

        $this->authenticator->setClient($this->getClient());
        $auth = $this->authenticator->authenticate($email, $password, $appId, $scope);
        $this->setLastResponse($auth);
        $redirectUrl = $auth->getEffectiveUrl();
        if (preg_match('/access_token=[\d\w]+/', $redirectUrl)) {
            $this->setState(self::STATE_SUCCESS);
        } elseif (preg_match('/__q_hash=[\d\w]+/', $redirectUrl)) {
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
        $this->getLogger()->debug('Invalid username or password!');
        $this->setState(self::STATE_FAULT);
    }

    /**
     * Phone number required handle
     * Not implemented yet
     */
    public function handlePhoneNumberRequiredState()
    {
        $this->getLogger()->debug('Phone number required!');
        $this->setState(self::STATE_FAULT);
    }

    /**
     * Состояние получения разрешения для приложения.
     * Если удалось получить ссылку на разрешение, запросить ее, получить
     * редирект на токен и перейти в состояние STATE_SUCCESS.
     * Если не удалось получить ссылку на разрешение, перейти в состояние STATE_FAULT
     */
    public function handleGrantState()
    {
        $this->getLogger()->debug('Access grant required!');
        $grantPage = $this->getClient()->get($this->getLastResponse()->getEffectiveUrl());
        $grantUrl = $this->grantPageParser->getGrantUrl($grantPage->getBody());
        $grant = $this->getClient()->get($grantUrl);
        $this->setLastResponse($grant);
        if (preg_match('/access_token=[\d\w]+/', $grant->getEffectiveUrl())) {
            $this->setState(self::STATE_SUCCESS);
        } else {
            $this->getLogger()->debug('Unable to fetch token redirect!');
            $this->setState(self::STATE_FAULT);
        }
    }
}
