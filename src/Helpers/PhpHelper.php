<?php


namespace theStormwinter\EasyWsdl2Php\Helpers;


use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;


class PhpHelper
{
	/** @var string */
	private $newLine = "\n";
	/** @var string */
	private $namespaceSeparator = '';
	/** @var PhpNamespace */
	private $namespace;
	/** @var ClassType */
	private $class;

	/** @var array */

	public function __construct(string $namespace)
	{
		$this->namespace = new PhpNamespace($namespace);
	}

	public function createClass(string $className): ClassType
	{
		$this->namespaceSeparator = $className == NormalizeHelper::DEFAULT_NAMESPACE_NAME ? '\\' : '';

		return $this->class = $this->namespace->addClass($className);
	}

	public function addUse(string $namespace): ?PhpNamespace
	{
		if (!empty($this->namespaceSeparator)) {
			return null;
		}

		//		return null;
		return $this->namespace->addUse($namespace);
	}

	public function setSoapClientProperty(): Property
	{
		return $this->class->addProperty('soapClient')->setVisibility('protected')->addComment('@var ' . $this->namespaceSeparator . 'SoapClient');
	}

	public function setOptionsProperty(): Property
	{
		return $this->class->addProperty('options')->setVisibility('protected')->addComment('@var array');
	}

	public function setClassmapProperty(): Property
	{
		return $this->class->addProperty('classmap')->setVisibility('private')->setStatic()->addComment('@var array');
	}

	public function createSoapClientConstructor(): Method
	{
		$constructor = $this->class->addMethod('__construct');
		$constructor->addParameter('wsdl')->setTypeHint('string');
		$constructor->addParameter('options')->setTypeHint('array')->setDefaultValue([])->setNullable();
		$constructor->setBody('self::$classmap = self::loadClassMap();' . $this->newLine
			. '$this->options = self::normalizeOptions($options);' . $this->newLine
			. '$this->soapClient = new ' . $this->namespaceSeparator . 'SoapClient($wsdl,$this->options' . ');');

		return $constructor;
	}

	public function setNormalizeOptionsMethod(): Method
	{
		$options = $this->class->addMethod('normalizeOptions');
		$options->setReturnType('array');
		$options->addParameter('options')->setTypeHint('array')->setNullable();
		$options->setVisibility('protected')->setStatic(true)->setBody('   $options[\'classmap\'] = self::$classmap;' . $this->newLine . '   return $options;');

		return $options;
	}

	public function addFunctionMethod(string $methodName, string $typesNs, string $paramName, string $propName): Method
	{
		$method = $this->class->addMethod($methodName);
		$method->setReturnType('\\stdClass');
		$method->addParameter($paramName)->setTypeHint($typesNs . '\\' . $paramName);
		$method->setBody('/** @noinspection PhpUndefinedMethodInspection */' . $this->newLine .
			'$' . $propName . ' = $this->soapClient->' . $methodName . '($' . $paramName . ');' . $this->newLine .
			'return $' . $propName . ';');

		return $method;
	}

	public function addClassmapMethod(array $classMap, string $typesNs): Method
	{
		$values = [];
		foreach ($classMap as $map) {

			$values[] = '    "' . $map . '" => "' . $typesNs . '\\' . $map . '"';
		}
		$string = implode(',' . $this->newLine, $values);

		$method = $this->class->addMethod('loadClassMap');
		$method->setVisibility('protected')->setStatic()->setBody('$classmap = [' . $this->newLine . $string . $this->newLine . '];' . $this->newLine . $this->newLine . 'return $classmap;');

		return $method;
	}

	public function addCommentedProperty(string $propName, string $commentName): Property
	{
		return $this->class->addProperty($propName)->addComment('@var ' . $commentName);
	}

	public function generateFile()
	{
		return '<?php' . $this->newLine . $this->namespace;
	}


}