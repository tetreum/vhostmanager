<?php

namespace VHostManager\Providers;

use VHostManager\System\ProviderBase;
use VHostManager\System\ProviderInterface;
use RomanPitak\Nginx\Config\Directive;
use RomanPitak\Nginx\Config\Scope;

class Nginx extends ProviderBase implements ProviderInterface
{
	private $root = "/etc/nginx/";
	protected $sitesEnabledPath = null;

	// services to be restarted
	private $services = ['nginx'];

    private $simpleConversions = [
            "listen" => "port",
            "server_name" => "domain",
            "root" => "root",
            "charset" => "charset",
            "sendfile" => "sendfile",
            "rewrite" => "rewrite",
            "expires" => "expires",
            "deny" => "deny",
        ];
    private $simpleConversionsReverted = [];
	
	public function __construct (array $config = [])
	{
        $this->simpleConversionsReverted = array_flip($this->simpleConversions);
        $this->sitesEnabledPath = $this->root . "sites-enabled";

		if (isset($config["root"])) {
			$this->root = $config["root"];
		}
		
		if (isset($config["services"])) {
			$this->services = $config["services"];
		}
	}

	public function getDomain ($domain)
    {
        $file = $this->getDomainPath($domain);

        if (!$file) {
            throw new \Exception("Domain not found");
        }

        $config = Scope::fromFile($file);
        $host = [];

        foreach ($config->getDirectives()[0]->getChildScope()->getDirectives() as $directive) {
            $this->parseDirective($host, $directive);
        }

        return $host;
    }

    /**
     * @param array $host
     * @param Directive $directive
     */
    private function parseDirective (&$host, $directive)
    {
        if (isset($this->simpleConversions[$directive->getName()])) {
            $host[$this->simpleConversions[$directive->getName()]] = $directive->getValue();
        } else {
            switch ($directive->getName())
            {
                case "access_log":
                case "error_log":
                    if (!isset($host["logs"])) {
                        $host["logs"] = [];
                    }

                    $host["logs"][str_replace("_log", "", $directive->getName())] = $directive->getValue();
                    break;
                case "location":
                    if (!isset($host["locations"])) {
                        $host["locations"] = [];
                    }
                    $location = [];

                    foreach ($directive->getChildScope()->getDirectives() as $childDirective) {
                        $this->parseDirective($location, $childDirective);
                    }

                    $host["locations"][$directive->getValue()] = $location;
                    break;
                default:
                    p($directive->getName(), $directive->getValue());
                    throw new \Exception("Directive " . $directive->getName() . " hasnt any conversion set");
                    break;
            }
        }
    }

    /**
     * @param Scope $scope
     * @param string $key
     * @param mixed $value
     */
    private function applyDirective (&$scope, $key, $value)
    {
        if (isset($this->simpleConversionsReverted[$key])) {
            $scope->addDirective(Directive::create($this->simpleConversionsReverted[$key], $value));
        } else {
            switch ($key)
            {
                case "logs":
                    foreach ($value as $logType => $val) {
                        $scope->addDirective(Directive::create($logType . '_log', $val));
                    }
                    break;
                case "locations":
                    foreach ($value as $location => $directives)
                    {
                        $childScope = Scope::create();

                        foreach ($directives as $k => $v) {
                            $this->applyDirective($childScope, $k, $v);
                        }

                        $scope->addDirective(Directive::create('location', $location, $childScope));
                    }
                    break;
            }
        }
    }
	
	public function addDomain (array $config)
	{
		$this->validateConfig($config, true);
		
		$exists = $this->getDomainPath($config["domain"]);
		
		if ($exists) {
			return false;
		}
		
		$serverScope = Scope::create();

        foreach ($config as $k => $v) {
            $this->applyDirective($serverScope, $k, $v);
        }

        $fileName = $config["domain"];

        // it may be mongo.dev mongo.lol mongolo.com
        if (strpos($fileName, " ") !== false) {
            $fileName = explode(" ", $fileName)[0];
        }
		
		Scope::create()
			->addDirective(Directive::create('server')
				->setChildScope($serverScope)
			)
			->saveToFile($this->sitesEnabledPath . $fileName);
		
		$this->restartServices();

        return true;
	}
	
	public function addLocation ($domain, array $config)
	{
	}
}