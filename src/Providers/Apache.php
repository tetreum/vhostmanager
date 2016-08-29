<?php

namespace VHostManager\Providers;

use VHostManager\System\ProviderBase;
use VHostManager\System\ProviderInterface;

class Apache extends ProviderBase implements ProviderInterface
{
    private $root = "/etc/apache2/";
    protected $sitesEnabledPath = null;

    // services to be restarted
    private $services = ['apache2'];

    private $simpleConversions = [
        "ServerName" => "domain",
        "DocumentRoot" => "root",
        "DirectoryIndex" => "index",
        "ServerAdmin" => "email",
        "Options" => "options",
        "AllowOverride" => "overwrite",
        "Order" => "order",
        "Allow" => "allow",
        "Deny" => "deny",
        "ScriptAlias" => "alias",
        "Alias" => "alias",
        "LogLevel" => "loglevel",
    ];

    public function __construct (array $config = [])
    {
        $this->sitesEnabledPath = $this->root . "sites-enabled";

        if (isset($config["root"])) {
            $this->root = $config["root"];
        }

        if (isset($config["services"])) {
            $this->services = $config["services"];
        }
    }

    /**
     * @param array $host
     * @param string $directive
     */
    private function parseDirective (&$host, $directive, &$originalHost)
    {
        $tokens = explode(' ', $directive);

        $directiveName = ucfirst($tokens[0]);
        unset($tokens[0]);

        $directiveValue = implode(" ", $tokens);

        if (isset($this->simpleConversions[$directiveName])) {
            $host[$this->simpleConversions[$directiveName]] = $directiveValue;
        } else {
            switch ($directiveName)
            {
                case "#":
                    // its a comment, skip it
                    break;
                case "ErrorLog":
                case "CustomLog":
                    if (!isset($host["logs"])) {
                        $host["logs"] = [];
                    }

                    $host["logs"][strtolower(str_replace("Log", "", $directiveName))] = $directiveValue;
                    break;
                case "<VirtualHost":
                    $host["port"] = (int)str_replace("*:", "", $directiveValue);
                    break;
                case "<Directory":
                    if (!isset($host["locations"])) {
                        $host["locations"] = [];
                    }
                    $insideLocation = substr($directiveValue, 0, -1);

                    // now we will start parsing location's directives
                    // so we move parentHost to $originalHost
                    $originalHost = $host;
                    $host = [
                        "vhostLocation" => $insideLocation
                    ];
                    break;
                case "</Directory>":
                    $locationName = $host["vhostLocation"];
                    unset($host["vhostLocation"]);

                    // revert the switch
                    $originalHost["locations"][$locationName] = $host;
                    $host = $originalHost;
                    break;
                default:
                    p($directiveName, $directiveValue);die;
                    break;
            }
        }
    }

    public function getDomain($domain)
    {
        $file = $this->getDomainPath($domain . ".conf");

        if (!$file) {
            throw new \Exception("Domain not found");
        }

        $content = fopen($file, 'r');
        $hosts = [];
        $host = [];
        $insideLocation = false;

        while(!feof($content))
        {
            $line = trim(fgets($content));

            if (empty($line)) {
                continue;
            }

            // end of host
            if ($line == "</VirtualHost>") {
                $hosts[] = $host;
                $host = [];
                $insideLocation = false;
                continue;
            }

            $this->parseDirective($host, $line, $insideLocation);
        }

        if (sizeof($hosts) == 1) {
            return $hosts[0];
        }

        return $hosts;
    }

    public function addDomain(array $config)
    {
        // TODO: Implement addDomain() method.
    }

    public function addLocation($domain, array $config)
    {
        // TODO: Implement addLocation() method.
    }
}