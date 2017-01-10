Yii2-oddsgg-api
=================

Yii2 client for Odds.gg eSports Odds API

Requirements:
=================

PHP5.5 and Yii2 Framework.

Installation
------------

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

Usage
-----

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

How to use API:
=================

See http://www.odds.gg/UserAccount/UserAccount for API documentation.


Cache setup:
=================

This extension allows to store and update data in a local cache to facilitate quicker access and easier data updates. 
To enable this features, several database tables have to be created using the following migration script:

```
./yii migrate --migrationPath=./vendor/drsdre/yii2-oddsgg-api/migrations
```



That's all!
-----------