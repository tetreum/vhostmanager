# WIP

Create virtual hosts/vhosts for Nginx/Apache/etc.. via php

# Search for an existing domain
```php
use VHostManager\Provider\Nginx;

$manager = new Nginx();
$domain = $manager->findDomain("default");

print_r($domain);
```

# Add domain
```php
use VHostManager\Provider\Nginx;

$manager = new Nginx();
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