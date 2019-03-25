<?php declare(strict_types=1);

namespace theStormwinter\EasyWsdl2Php\Tests\Fixtures;

use Tester\TestCase;
use theStormwinter\EasyWsdl2Php\Generator;


abstract class BaseTestCase extends TestCase
{
	/** @var Generator */
	protected $service;
	/** @var string */
	//	protected $url = 'https://raw.githubusercontent.com/bet365/soap/master/doc/example.wsdl';
	protected $url = 'https://mojezasielky.posta.sk/integration/webServices/api?wsdl';
	/** @var array */
	protected $options = ['trace' => true];
	/** @var string */
	protected $namespaceWithoutSeparator = 'Test\Testspace';
	/** @var string */
	protected $namespace = 'Test\Testspace\\';
	/** @var string */
	protected $name = 'Test';
}
