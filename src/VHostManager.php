<?php

namespace VHostManager;

use VHostManager\System\ProviderInterface;

class VHostManager implements ProviderInterface
{
    const APACHE = "Apache";
    const NGINX = "Nginx";

    private $availableProviders = [
        self::APACHE,
        self::NGINX,
    ];

    /**
     * @var ProviderInterface
     */
    private $connector = null;

    public function __construct($providerName, $options = [])
    {
        if (!in_array($providerName, $this->availableProviders)) {
            throw new \Exception("No connector available for this provider");
        }

        $classPath = "VHostManager\\Providers\\$providerName";
        $this->connector = new $classPath();
    }

    /**
     * Adds a domain
     * @param array $config
     * @return mixed
     */
    public function addDomain(array $config)
    {
        return $this->connector->addDomain($config);
    }

    /**
     * Adds a location to the specified domain
     * @param string $domain
     * @param array $config
     * @return mixed
     */
    public function addLocation($domain, array $config)
    {
        return $this->connector->addLocation($domain, $config);
    }

    /**
     * Gets domain's config
     * @param string $domain
     * @return mixed
     */
    public function getDomain($domain)
    {
        return $this->connector->getDomain($domain);
    }

    /**
     * Returns original provider's class
     * @return mixed
     */
    public function getConnector () {
        return $this->connector;
    }
}