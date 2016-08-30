# WIP

Create virtual hosts/vhosts for Nginx/Apache/etc.. via php

# Get an existing domain
```php
use VHostManager\VHostManager;

$manager = new VHostManager(VHostManager::NGINX);
$domain = $manager->getDomain("default");

print_r($domain);
```

# Add a domain
```php
use VHostManager\VHostManager;

$manager = new VHostManager(VHostManager::NGINX);
$manager->addDomain([
	"domain" => "mongo.dev",
	"port" => 80,
	"root" => "/var/www/mongo",
	"locations" => [
		"'^~ /var/'" => [
			"deny" => "all"
		]
	]
]);
```

It will attempt to restart nginx service.

# Universally supported options
```php
[
	"domain" => "mongo.dev",
	"port" => 80,
	"root" => "/var/www/mongo",
	"logs" => [
	    "error" => "/var/log/mongo/error.log",
	    "access" => "/var/log/mongo/access.log",
	],
	"locations" => [
		"'^~ /var/'" => [
			"deny" => "all"
		]
	]
]
```