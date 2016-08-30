<?php

namespace VHostManager\System;

interface ProviderInterface {

    public function __construct (array $config = []);

	public function getDomain ($domain);
	
	public function addDomain (array $config);

	public function getConversion (array $config);
}