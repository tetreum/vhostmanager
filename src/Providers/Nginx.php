<?php

namespace VHostManager\Providers;

class Nginx extends ProviderBase implements ProviderInterface 
{
	private $root = "/etc/nginx/";
	
	// services to be restarted
	private $services = ['nginx'];
	
	public function __construct (array $config = [])
	{
		if (isset($config["root"])) {
			$this->root = $config["root"];
		}
		
		if (isset($config["services"])) {
			$this->services = $config["services"];
		}
	}
	
	public function findDomain ($domain)
	{
		$sitesEnabledPath = $this->root . "sites-enabled";
		$domains = Utils::listFilesFrom($sitesEnabledPath);
		
		if (in_array($domain, $domains)) {
			return [
				"file" => "$sitesEnabledPath/$domain"
			];
		}
	}
	
	public function addDomain (array $config)
	{
		$this->validateConfig($config, true);
		
		$exists = $this->findDomain($config["domain"]);
		
		if ($exists) {
			return false;
		}
		
		$serverScope = Scope::create()
			->addDirective(Directive::create('listen', $config["port"]))
			->addDirective(Directive::create('server_name', $config["domain"]))
			->addDirective(Directive::create('root', $config["root"]));
		
		foreach ($config["locations"] as $location => $directives)
		{
			$scope = Scope::create();
			
			foreach ($directives as $k => $v) {
				$scope->addDirective(Directive::create($k, $v));
			}
			
			$serverScope->addDirective(Directive::create('location', $location, $scope));
		}
		
		Scope::create()
			->addDirective(Directive::create('server')
				->setChildScope($serverScope)
			)
			->saveToFile($config["domain"]);
		
		$this->restartServices();
	}
	
	public function addLocation ($domain, array $config)
	{
	}
}