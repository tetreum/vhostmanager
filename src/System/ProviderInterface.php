<?php

namespace VHostManager\System;

interface ProviderInterface {
	public function findDomain ($domain);
	
	public function addDomain (array $config);
	public function addLocation ($domain, array $config);
}