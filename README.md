# BotMan Viber Driver
Connect Viber with [BotMan](http://botman.io/)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/polyskalov/botman-viber-driver.svg?style=flat-square)](https://packagist.org/packages/polyskalov/botman-viber-driver)


## Installation & Setup
First you need to pull in the Viber Driver.

```console
composer require polyskalov/botman-viber-driver
```
Then load the driver before creating the BotMan instance (only when you don't use BotMan Studio):

```php
DriverManager::loadDriver(\TheArdent\Drivers\Viber\ViberDriver::class);

// Create BotMan instance
BotManFactory::create($config);
```
This driver requires a valid and secure URL in order to set up webhooks and receive events and information from the chat users. This means your application should be accessible through an HTTPS URL.

To connect BotMan with your Viber Bot, you first need to follow the [official guide](https://partners.viber.com/account/create-bot-account) to create your Viber Bot and an access token.

Once you have obtained the access token, place it in your .env file like VIBER_TOKEN=YOUR-VIBER-TOKEN-HERE. There it gets automatically loaded to your config/botman/viber.php file.

If you don't use BotMan Studio, add these line to $config array that you pass when you create the object from BotManFactory.

```
'viber' => [
    'token' => 'YOUR-VIBER-TOKEN-HERE',
]
```

## Register Your Webhook
To let your Viber Bot know, how it can communicate with your BotMan bot, you have to register the URL where BotMan is running at, with Viber.

You can do this by sending a `POST` request to this URL:

```https://chatapi.viber.com/pa/set_webhook```

This POST request needs parameter called url with the URL that points to your BotMan logic / controller. If you use [BotMan Studio](https://botman.io/2.0/botman-studio) it will be: `https://yourapp.domain/botman`. HTTPS is a must, because of security reasons.

```json
{
   "url":"https://my.host.com",
   "event_types":[
      "delivered",
      "seen",
      "failed",
      "subscribed",
      "unsubscribed",
      "conversation_started"
   ],
   "send_name": true,
   "send_photo": true
}
```
You can read about other fields in the request in the [official documentation](https://developers.viber.com/docs/api/rest-bot-api/#setting-a-webhook).

Instead of manually sending the request to Viber you can use a console command to register your Webhook.

``` php artisan botman:viber:register```

## Example
```php
<?php

/** @var \BotMan\BotMan\BotMan $botman */
$botman = resolve('botman');

$botman->on(
    'conversation_started',
    static function (array $payload, BotMan $bot) {
        $bot->reply('Hi, ' . $bot->getUser()->getUsername());
    }
);

$botman->hears('hi', static function (BotMan $bot) {
    $bot->reply('👋');
});

$botman->fallback(static function (BotMan $bot) {
    $bot->reply('I do not understand');
});

$botman->listen();
```