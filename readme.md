Vk.com application authenticator
================================

Библиотека для автоматизации аутентификации приложений vk.com. Навеяно https://github.com/speechkey/VKAppAuth.

Установка
---------

Установка выполняется через composer. 

Использование
-------------

Работать будет как-то так:

```php

    $service = new \Vk\AppAuth\AuthService(new \Vk\AppAuth\AuthPageParser(), new \Vk\AppAuth\GrantPageParser());
    
    $token = $service->createToken($username, $password, $appId);
    
    echo $token->access_token.PHP_EOL;
    
    
```

TODO
----

- Добавить обработку требования номера телефона
- Добавить обработку капчи
- Причесать код


Тестирование
------------

Тесты можно запускать через composer:

```

    composer test

```

Либо руками:

```

    ./vendor/bin/phpunit
    

```
