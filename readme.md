Vk.com application authenticator
================================

Библиотека для автоматизации аутентификации приложений vk.com. Навеяно https://github.com/speechkey/VKAppAuth.

Установка
---------

Установка выполняется через [Composer](http://getcomposer.org). 

```bash

    composer.phar require matperez/vk-app-auth
    
```

Использование
-------------

Пример использования:

```php

    require_once(__DIR__.'/vendor/autoload.php');
    
    $username = 'n@n.com';
    $password = 'god';
    $appId = '3713774';
    
    $authenticator = new \Vk\AppAuth\Authenticator(new \Vk\AppAuth\AuthPageParser());
    $service = new \Vk\AppAuth\AuthService(new \Vk\AppAuth\GrantPageParser(), $authenticator);
    
    $tokenInfo = $service->createToken($username, $password, $appId);
    
    var_export($tokenInfo);
    
    var_export($service->getLogMessages());
    
    
```

В настоящее время код не умеет обрабатывать требование ввести номер телефона для подтверждения входа из незнакомого места.

TODO
----

- Добавить обработку требования номера телефона

Тестирование
------------

Тесты можно запускать через composer:

```bash

    composer test

```
