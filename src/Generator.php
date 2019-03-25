<?php

namespace theStormwinter\EasyWsdl2Php;

use SoapClient;
use stdClass;
use theStormwinter\EasyWsdl2Php\Exceptions\NamespaceNameBlacklisted;
use theStormwinter\EasyWsdl2Php\Helpers\NormalizeHelper;
use theStormwinter\EasyWsdl2Php\Helpers\PhpHelper;


/**
 * Class Generator
 * @package theStormwinter\EasyWsdl2Php
 * @param string      $wsdl              URL to source
 * @param array       $soapClientOptions Options for SoapClient (Example: 'login' => 'root', 'password' => 'MyVeryHardPassword'
 * @param string|null $namespace         Namespace of generated classes
 * @param string      $soapClassName     Name of the main class with SoapClient call
 */
class Generator
{
	/** @var string */
	protected $wsdl;
	/** @var  array */
	protected $clientOptions;
	/** @var  string */
	protected $namespace;
	/** @var string */
	protected $className;
	/** @var SoapClient */
	protected $connection;
	/** @var stdClass */
	protected $functions;
	/** @var string */
	protected $classPath;

	/**
	 * Generator constructor.
	 * @param string      $wsdl              URL to source
	 * @param array       $soapClientOptions Options for SoapClient (Example: ['login' => 'root', 'password' => 'MyVeryHardPassword']
	 * @param string|null $namespace         Namespace of generated classes
	 * @param string      $soapClassName     Name of the main class with SoapClient
	 * @throws NamespaceNameBlacklisted
	 */
	public function __construct(string $wsdl, ?array $soapClientOptions = [], ?string $namespace = null, ?string $soapClassName = 'SoapClient')
	{
		$this->wsdl = $wsdl;
		$this->clientOptions = NormalizeHelper::options($soapClientOptions);
		$this->namespace = NormalizeHelper::namespaceName($namespace);
		$this->classPath = NormalizeHelper::pathFromNamespace($this->namespace);
		$this->className = NormalizeHelper::className($soapClassName);
	}

	public function generate(): bool
	{
		$this->connection = new SoapClient($this->wsdl, $this->clientOptions);
		$this->functions = $this->connection->__getFunctions();
		(!is_dir($this->classPath) ? mkdir($this->classPath, null, true) : null);
		$typesDir = $this->classPath . DIRECTORY_SEPARATOR . NormalizeHelper::TYPES_DIR;
		(!is_dir($typesDir) ? mkdir($typesDir) : null);
		$typesNs = $this->namespace . NormalizeHelper::NAMESPACE_SEPARATOR . NormalizeHelper::TYPES_DIR;

		$soapClass = new PhpHelper($this->namespace);
		$soapClass->createClass($this->className);
		$soapClass->addUse(NormalizeHelper::DEFAULT_NAMESPACE_NAME);
		$soapClass->setSoapClientProperty();
		$soapClass->setOptionsProperty();
		$soapClass->createSoapClientConstructor();
		$soapClass->setNormalizeOptionsMethod();

		foreach ($this->functions as $func) {
			$explode = explode(' ', $func, 2);
			$replace = str_replace(')', '', $explode[1]);
			$replace = str_replace('(', ':', $replace);
			$t2 = explode(':', $replace);
			$func = $t2[0];
			$par = $t2[1];
			$params = explode(' ', $par);
			$type = $params[0];

			$soapClass->addFunctionMethod($func, $typesNs, $type, $explode[0]);
			$methodNames[] = $func;
		}
		$types = $this->connection->__getTypes();
		$classesArr = [];
		foreach ($types as $type) {
			if (substr($type, 0, 6) == 'struct') {
				$data = trim(str_replace(['{', '}'], '', substr($type, strpos($type, '{') + 1)));
				$data_members = explode(';', $data);
				$className = trim(substr($type, 6, strpos($type, '{') - 6));

				$typeClass = new PhpHelper($typesNs);
				$typeClass->createClass($className);
				$classesArr [] = $className;
				foreach ($data_members as $member) {
					$member = trim($member);
					if (strlen($member) < 1) {
						continue;
					}
					list($data_type, $member_name) = explode(' ', $member);

					$typeClass->addCommentedProperty($member_name, $data_type);
				}

				file_put_contents($typesDir . NormalizeHelper::DIRECTORY_SEPARATOR . $className . '.php', $typeClass->generateFile());
			}
		}

		$soapClass->setClassmapProperty();
		$soapClass->addClassmapMethod($classesArr, $typesNs);

		$createFile = file_put_contents($this->classPath . NormalizeHelper::DIRECTORY_SEPARATOR . $this->className . '.php', $soapClass->generateFile());

		return (bool)$createFile;
	}
}