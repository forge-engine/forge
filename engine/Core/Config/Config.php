<?php
declare(strict_types=1);

namespace Forge\Core\Config;

use InvalidArgumentException;

final class Config
{
	private array $config;
	
	public function __construct(protected string $configPath)
	{
		
		$this->configPath = $configPath;
		$this->loadApplicationConfig();
	}
	
	private function loadApplicationConfig(): void
	{
		$files = glob($this->configPath . '/*.php');
		
		foreach($files as $file){
			$filename = basename($file, '.php');
			$configData = require $file;
			if (!is_array($configData)) {
				throw new InvalidArgumentException("Configuration file '{$filename}' must return an array");
			}
			$this->config[$filename] = $configData;
		}
	}
	
	public function get(string $key, mixed $default = null): mixed
	{
		if (str_contains($key, '.')) {
			$keys = explode('.', $key);
			$value = $this->config;
			foreach ($keys as $segment) {
				if (!is_array($value) || !array_key_exists($segment, $value)) {
					return $default;
				}
				$value = $value[$segment];
			}
			return $value;
		}
		
		return $this->config[$key] ?? $default;
	}
	
	public function set(string $key, mixed $value): void
	{
		if (str_contains($key, '.')) {
			$keys = explode('.', $key);
			$current = &$this->config;
			$lastSegment = array_pop($keys);
	
			foreach ($keys as $segment) {
				if (!isset($current[$segment]) || !is_array($current[$segment])) {
					$current[$segment] = [];
				}
				$current = &$current[$segment];
			}
			$current[$lastSegment] = $value;
			return;
		}
	
		$this->config[$key] = $value;
	}
	
	public function merge(string $key, array $data): void
	{
		if (isset($this->config[$key]) && is_array($this->config[$key])) {
			$this->config[$key] = array_merge_recursive($this->config[$key], $data);
		} else {
			$this->config[$key] = $data;
		}
	}
	
	public function mergeModuleDefaults(array $defaults): void
	{
		foreach ($defaults as $key => $value) {
			$this->merge($key, $value);
		}
	}
	
	public function getConfigPath(): string
	{
		return $this->configPath;
	}
}