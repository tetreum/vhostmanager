# WIP [![Build Status](https://travis-ci.org/tetreum/vhostmanager.svg?branch=master)](https://travis-ci.org/tetreum/vhostmanager)

Create virtual hosts/vhosts for Nginx/Apache/etc.. via php-

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

# Get conversion
```php
use VHostManager\VHostManager;

$manager = new VHostManager(VHostManager::APACHE);
$manager->getConversion([
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

Result:
```
<VirtualHost *:80>
    ServerName mongo.dev
    DocumentRoot /var/www/mongo
    ErrorLog /var/log/mongo/error.log
    TransferLog /var/log/mongo/access.log

    <Directory '^~ /var/'>
        Deny all
    </Directory>

</VirtualHost>
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
