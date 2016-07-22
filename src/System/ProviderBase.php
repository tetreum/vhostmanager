<?php

namespace VHostManager\System;

class ProviderBase
{
	private $requiredAttrs = [
		"domain",
		"port",
		"root"
	];
	
	public function validateConfig ($config, $forInsert = false)
	{
		if ($forInsert) {
			foreach ($this->requiredAttrs as $attr) {
				if (!isset($config[$attr])) {
					throw new Exception("Missing $attr attribute");
				}
			}
		}
		
		if (isset($config["root"]) && substr($config["root"], 0, 1) != "/") {
			throw new Exception("root must be absolute path");
		}
	}
	
	protected function restartServices()
	{	
		foreach($this->services as $service) {
			Utils::restart($service);
		}
	}
	
	protected function getServices() {
		return $this->services;	
	}
}
