<?php declare(strict_types=1);

namespace theStormwinter\EasyWsdl2Php\Tests\EasyWsdl2Php;

use Tester\Assert;
use theStormwinter\EasyWsdl2Php\Exceptions\NamespaceNameBlacklisted;
use theStormwinter\EasyWsdl2Php\Generator;
use theStormwinter\EasyWsdl2Php\Tests\Fixtures\BaseTestCase;


require_once __DIR__ . '/../bootstrap.php';

class GeneratorTest extends BaseTestCase
{
	
	protected function getService(string $url, array $options = null, string $namespace = null, string $name = null): Generator
	{
		return new Generator($url, $options, $namespace, $name);
	}
	
	public function testWithNamespaceAndClassName(): void
	{
		$options['trace'] = true;
		$options['ssl_verifyhost'] = false;
		$service = $this->getService($this->url2, $options, 'TryToUseThis', 'SoapTask');
		$generator = $service->generate();
		Assert::true($generator);
	}
	
		public function testOnlyWithUrl(): void
		{
			$service = $this->getService($this->url);
			$generator = $service->generate();
	
			Assert::true($generator);
		}
	
		public function testWithClassName(): void
		{
			$service = $this->getService($this->url, null, null, $this->name);
			$generator = $service->generate();
	
			Assert::true($generator);
		}
	
		public function testWithNamespaceBlacklisted(): void
		{
			Assert::exception(function () {
				$service = $this->getService($this->url, null, 'Test\Namespace', $this->name);
				$service->generate();
			}, NamespaceNameBlacklisted::class);
		}
}

(new GeneratorTest)->run();