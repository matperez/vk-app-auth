<?php
/**
 * Created by PhpStorm.
 * Filename: AuthService.php
 * User: andrey
 * Date: 05.09.15
 * Time: 1:00
 */

namespace Vk\AppAuth;

use Vk\AppAuth\Interfaces\AuthPageParserInterface;
use Vk\AppAuth\Interfaces\GrantPageParserInterface;

class AuthService extends BaseAuthService
{
    /**
     * @var AuthPageParserInterface
     */
    protected $formPageParser;

    /**
     * @var GrantPageParserInterface
     */
    protected $grantPageParser;

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
        } while ($this->getState() !== self::STATE_TOKEN_PAGE && $this->getState() !== self::STATE_FAULT);

        if ($this->getState() === self::STATE_TOKEN_PAGE) {
            $redirect = $this->getLastResponse()->getEffectiveUrl();
            $this->addLogMessage(sprintf('Got token redirect %s!', $redirect));
            return TokenInfo::createFromRedirect($redirect);
        }

        $this->addLogMessage('Unable to create new token!');
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
        $this->addLogMessage('Auth required!');
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
        $this->addLogMessage('Invalid username or password!');
        $this->setState(self::STATE_FAULT);
    }

    public function handlePhoneNumberRequiredState()
    {
        $this->addLogMessage('Phone number required!');
    }

    /**
     * Состояние получения разрешения от пользователя.
     * Если получилось получить ссылку на разрешение, запросить ее, получить
     * редирект на токен и перейти в состояние STATE_TOKEN_PAGE.
     * Если не удалось получить ссылку на разрешение, перейти в состояние STATE_FAULT
     */
    public function handleGrantState()
    {
        $this->addLogMessage('Access grant required!');
        $grantPageUrl = $this->getLastResponse()->getEffectiveUrl();
        $grantPage = $this->getClient()->get($grantPageUrl);
        $grantUrl = $this->grantPageParser->getGrantUrl($grantPage->getBody());
        $grant = $this->getClient()->get($grantUrl);
        $this->setLastResponse($grant);
        if (preg_match('/access_token=[\d\w]+/', $grant->getEffectiveUrl())) {
            // https://oauth.vk.com/blank.html#access_token=e01fb069ba1e0f0e1ec23528...ae3bdf0a3&expires_in=0&user_id=5....64
            $this->setState(self::STATE_TOKEN_PAGE);
        } else {
            $this->addLogMessage('Unable to fetch token redirect!');
            $this->setState(self::STATE_FAULT);
        }
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
}
