Yii2-oddsgg-api
=================

Yii2 client for Odds.gg eSports Odds API

Requirements:
=================

PHP5.5 and Yii2 Framework.

Installation
=================

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist drsdre/yii2-oddsgg-api "*"
```

or add

```json
"drsdre/yii2-oddsgg-api": "*"
```

to the `require` section of your `composer.json` file.


Cache Setup
-----------

This extension offers the ability to store and updates data in a local cache to circumvent API restrictions. 
Several database tables have to be created using the following migration script:

```
./yii migrate --migrationPath=./vendor/drsdre/yii2-oddsgg-api/migrations
```

Usage
=================

You need to setup the client as application component:

```php
'components' => [
    'oddsGGApi' => [
        'class' => 'drsdre\OddsGG\Client',
        'service_url' => 'zzz',
        'api_key' => 'xxx',
    ]
    ...
]
```

or define the client directly in the code:

```php
$client = new \drsdre\OddsGG\Client([
    'service_url' => 'yyy',
	'api_key' => 'xxx',
]);
```

API documentation
-----------

See http://www.odds.gg/UserAccount/UserAccount for full API documentation.