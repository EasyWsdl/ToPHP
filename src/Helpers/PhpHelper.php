<?php declare(strict_types=1);

namespace EasyWsdl\ToPHP\Helpers;

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
        if (!empty($this->namespaceSeparator))
        {
            return null;
        }

        return $this->namespace->addUse($namespace);
    }

    public function setExtendsSoapClient()
    {
        return $this->class->setExtends('\\SoapClient');
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

    public function setRenamedPropertiesProperty(): Property
    {
        return $this->class->addProperty('renamedProperties')->setVisibility('private')->setStatic()->addComment('@var array');
    }

    public function createSoapClientConstructor(): Method
    {
        $constructor = $this->class->addMethod('__construct');
        $constructor->addParameter('wsdl')->setTypeHint('string');
        $constructor->addParameter('options')->setTypeHint('array')->setDefaultValue([])->setNullable();
        $constructor->setBody('self::$classmap = self::loadClassMap();' . $this->newLine .
                              'self::$renamedProperties = self::loadRenamedProperties();' . $this->newLine .
                              '$this->options = self::normalizeOptions($options);' . $this->newLine .
                              'parent::__construct($wsdl, $this->options);'
        );

        return $constructor;
    }

    public function createSoapEntityEncoder(): Method
    {
        $method = $this->class->addMethod('encodeEntities');
        $method->setVisibility('private');
        $method->addParameter('entity');
        $method->setBody('foreach (get_object_vars($entity) as $propyName => $propy)' . $this->newLine .
                         '{' . $this->newLine .
                         'if (is_object($propy)) {' . $this->newLine .
                         '$propy = $this->encodeEntities($propy);' . $this->newLine .
                         '}' . $this->newLine .
                         'if (array_key_exists($propyName, self::$renamedProperties)) ' . $this->newLine .
                         '{' . $this->newLine .
                         '$entity->{self::$renamedProperties[ $propyName ]} = $propy;' . $this->newLine .
                         'unset($entity->{$propyName});' . $this->newLine .
                         '}' . $this->newLine .
                         '}' . $this->newLine .
                         'return $entity;'
        );

        return $method;
    }

    public function createSoapEntityDecoder(): Method
    {
        $method = $this->class->addMethod('decodeEntities');
        $method->setVisibility('private');
        $method->addParameter('entity');

        $method->setBody('foreach (get_object_vars($entity) as $propyName => $propy)' . $this->newLine .
                         '{' . $this->newLine .
                         'if (is_object($propy)) {' . $this->newLine .
                         '$propy = $this->decodeEntities($propy);' . $this->newLine .
                         '}' . $this->newLine .
                         'if (is_array($propy))' . $this->newLine .
                         '{' . $this->newLine .
                         'foreach ($propy as $key => $prop)' . $this->newLine .
                         '{' . $this->newLine .
                         'if (is_object($prop)) {' . $this->newLine .
                         '$propy[$key] = $this->decodeEntities($prop);' . $this->newLine .
                         '}' . $this->newLine .
                         '}' . $this->newLine .
                         '}' . $this->newLine .
                         'if ($key = array_search($propyName, self::$renamedProperties))' . $this->newLine .
                         '{' . $this->newLine .
	                        '$key = lcfirst($key);' . $this->newLine .
                         '$entity->{$key} = $propy;' . $this->newLine .
                         'unset($entity->{$propyName});' . $this->newLine .
                         '}' . $this->newLine .
                         '}' . $this->newLine .
                         'return $entity;'
        );

        return $method;
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
        $typeHintClassName = NormalizeHelper::generateValidNameOfClassOrProperty($paramName);
        $method = $this->class->addMethod($methodName);
        $method->setReturnType($typesNs . '\\' . $typeHintClassName . 'Response');
        $method->addParameter($paramName)->setTypeHint($typesNs . '\\' . $typeHintClassName);
        $method->setBody('$' . $paramName . ' = $this->encodeEntities($' . $paramName . ');' . $this->newLine .
                         //		'/** @noinspection PhpUndefinedMethodInspection */' . $this->newLine .
                         '$' . $propName . ' = $this->__soapCall(\'' . $methodName . '\',[$' . $paramName . ']);' . $this->newLine .
	                     '$this->decodeEntities($' . $paramName . ');' . $this->newLine .
                         '$' . $propName . ' = $this->decodeEntities($' . $propName . ');' . $this->newLine .
                         'return $' . $propName . ';'
        );

        return $method;
    }

    public function addClassmapMethod(array $classMap, string $typesNs): Method
    {
        $values = [];
        foreach ($classMap as $map)
        {

            $values[] = '    "' . $map . '" => "' . $typesNs . '\\' . NormalizeHelper::generateValidNameOfClassOrProperty($map) . '"';
        }
        $string = implode(',' . $this->newLine, $values);
        $method = $this->class->addMethod('loadClassMap');
        $method->setVisibility('protected')->setStatic()->setBody('$classmap = [' . $this->newLine . $string . $this->newLine . '];' . $this->newLine . $this->newLine . 'return $classmap;');

        return $method;
    }

    public function addClassmapRenamedPropertiesMethod(array $classMap, array $typesNs): Method
    {
        $classMap = array_unique($classMap);
        $values = [];
        foreach ($classMap as $key => $map)
        {

            $values[] = '    "' . $map . '" => "' . $typesNs[$key] . '"';
        }
        $string = implode(',' . $this->newLine, $values);
        $method = $this->class->addMethod('loadRenamedProperties');
        $method->setVisibility('protected')->setStatic()->setBody('$renamedProperties = [' . $this->newLine . $string . $this->newLine . '];' . $this->newLine . $this->newLine . 'return $renamedProperties;');

        return $method;
    }

    public function addCommentedProperty(string $propName, string $commentName, string $originalPropyName = null): Property
    {
        return $this->class->addProperty($propName)->addComment('@var ' . $commentName . (!empty($originalPropyName) && $propName != $originalPropyName ? ' Generates ' . $originalPropyName . ' instead of this.' : ''));
    }

    public function generateFile()
    {
        return '<?php' . $this->newLine . $this->namespace;
    }


}