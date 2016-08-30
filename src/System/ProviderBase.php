<?php

namespace VHostManager\System;

class ProviderBase
{
    private $services = [];

	private $requiredAttrs = [
		"domain",
		"port"
	];

    public function __construct (array $config = [])
    {
        if (isset($this->simpleConversions)) {
            $this->simpleConversionsReverted = array_flip($this->simpleConversions);
        }

        $this->sitesEnabledPath = $this->root . "sites-enabled";

        if (isset($config["root"])) {
            $this->root = $config["root"];
        }

        if (isset($config["services"])) {
            $this->services = $config["services"];
        }
    }

    public function addLocation($domain, $location, array $data)
    {
        $config = $this->getDomain($domain);

        $config["locations"][$location] = $data;

        $this->addDomain($config);
    }

    /**
     * Checks if given config has all required params and they are set correctly
     * @param array $config
     * @param bool $forInsert
     * @throws \Exception if there is a missing param or a wrongly set one
     */
	public function validateConfig ($config, $forInsert = false)
	{
		if ($forInsert) {
			foreach ($this->requiredAttrs as $attr) {
				if (!isset($config[$attr])) {
					throw new \Exception("Missing $attr attribute");
				}
			}
		}
		
		if (isset($config["root"]) && substr($config["root"], 0, 1) != "/") {
			throw new \Exception("root must be absolute path");
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

    /**
     * Gets domain path for this provider
     * @param string $domain
     * @return bool|string false if domain does not exist
     */
    public function getDomainPath ($domain)
    {
        $domains = Utils::listFilesFrom($this->sitesEnabledPath);

        if (in_array($domain, $domains)) {
            return $this->sitesEnabledPath . "/$domain";
        }

        return false;
    }

    /**
     * Since there may be multiple (space separated) domains, we need to choose one as file's name
     * Ex: mongo.dev mongo.lol mongolo.com -> mongo.dev will be vhost file's name
     *
     * @param string $domain
     * @return mixed
     */
    public function decideFileName ($domain)
    {
        if (strpos($domain, " ") !== false) {
            $domain = explode(" ", $domain)[0];
        }

        return $domain;
    }
}
