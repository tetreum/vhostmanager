<?php

namespace VHostManager\Providers;

use VHostManager\System\ProviderBase;
use VHostManager\System\ProviderInterface;
use Apache\Config\VirtualHost;
use Apache\Config\Directive;
use Apache\Config\Directory;

class Apache extends ProviderBase implements ProviderInterface
{
    protected $root = "/etc/apache2/";
    protected $sitesEnabledPath = null;

    // services to be restarted
    protected $services = ['apache2'];

    protected $simpleConversions = [
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
    protected $simpleConversionsReverted = [];

    public function __construct (array $config = []) {
        parent::__construct($config);
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
                case "TransferLog":
                    if (!isset($host["logs"])) {
                        $host["logs"] = [];
                    }
                    if ($directiveName == "TransferLog") {
                        $directiveName = "access";
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

    /**
     * @param VirtualHost|Directory $scope
     * @param string $key
     * @param mixed $value
     */
    private function applyDirective (&$scope, $key, $value)
    {
        if (isset($this->simpleConversionsReverted[$key])) {
            $scope->addDirective(new Directive($this->simpleConversionsReverted[$key], $value));
        } else {
            switch ($key)
            {
                case "logs":
                    foreach ($value as $logName => $path)
                    {
                        if ($logName == "access") {
                            $logName = "transfer";
                        }
                        $logName = ucfirst($logName) . "Log";

                        $scope->addDirective(new Directive($logName, $path));
                    }
                    break;
                case "locations":
                    foreach ($value as $location => $data)
                    {
                        $directory = new Directory($location);

                        foreach ($data as $k => $v) {
                            $this->applyDirective($directory, $k, $v);
                        }
                        $scope->addDirectory($directory);
                    }
                break;
            }
        }
    }

    /**
     * @param $domain
     * @return array
     * @throws \Exception
     */
    public function getDomain($domain)
    {
        $file = $this->getDomainPath($domain . ".conf");

        if (!$file) {
            throw new \Exception("Domain not found");
        }

        $hosts = $this->parseString(file_get_contents($file));

        if (sizeof($hosts) == 1) {
            return $hosts[0];
        }

        return $hosts;
    }

    public function parseString ($content)
    {
        $lines = explode(PHP_EOL, $content);
        $hosts = [];
        $host = [];
        $insideLocation = false;

        foreach ($lines as $line)
        {
            $line = trim($line);

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
        return $hosts;
    }

    /**
     * @param array $config
     * @return VirtualHost
     */
    private function processConfig (array $config)
    {
        $this->validateConfig($config);

        $vhost = new VirtualHost("*", $config["port"]);

        foreach ($config as $k => $v) {
            $this->applyDirective($vhost, $k, $v);
        }

        return $vhost;
    }

    public function addDomain(array $config)
    {
        $exists = $this->getDomainPath($config["domain"]);

        if ($exists) {
            return false;
        }

        $this->processConfig($config)
            ->saveToFile($this->decideFileName($config["domain"]));

        $this->restartServices();
    }

    public function getConversion (array $config)
    {
        return $this->processConfig($config)
            ->toString();
    }
}
