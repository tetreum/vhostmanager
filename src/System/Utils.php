<?php

namespace VHostManager\System;

class Utils
{
	public static function restart ($serviceName)
	{
		if (empty($serviceName)) {
			return false;
		}
		
		exec("service $serviceName restart", $out);
		
		
		// probably got permission denied
		if (empty($out)) {
			return false;
		}
		
		if (is_array($out)) {
			$out = implode(" ", $out);
		}
		$out = strtolower($out);
		
		// when restarting services, they usually show FAIL or OK
		if (strpos($out, "fail") !== false || (strpos($out, "fail") === false && strpos($out, "ok") === false)) {
			return false;
		}

		return true;
	}
	
	public static function listFilesFrom ($path)
	{
		$files = scandir($path);
		
		// unset . & ..
		if ($files[0] == '.') {
			unset($files[0]);
			unset($files[1]);
			$files = array_values($files);
		}
		
		return $files;
	}
}