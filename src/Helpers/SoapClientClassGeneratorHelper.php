<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Helpers;


use EasyWsdl\ToPHP\Patterns\SoapClientPattern;
use EasyWsdl\ToPHP\Types\Method;
use EasyWsdl\ToPHP\Types\Parameter;
use EasyWsdl\ToPHP\Types\Property;
use EasyWsdl\ToPHP\Types\TypesClassType;
use EasyWsdl\ToPHP\Types\TypesClassTypes;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Parameter as NetteParameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Strings;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use SoapClient;


class SoapClientClassGeneratorHelper
{
    private const CALL_METHOD_PATTERN = 'callMethod';
    private const CALL_METHOD_PATTERT_WITHOUT_REQUEST = 'callMethodWithoutRequest';
    private const CALL_METHOD_RETURN_TYPE_SUFFIX = 'Response';

    /** @var string */
    public $soapClientName;
    /** @var string */
    public $namespaceName;
    /** @var PhpNamespace */
    protected $namespace;
    /** @var ClassType */
    protected $class;
    /** @var ReflectionClass */
    protected $patternClass;
    /** @var array */
    protected $patternMethods;
    /** @var array */
    protected $patternStaticProperties;
    /** @var array */
    protected $patternProperties;
    /** @var PhpFile */
    protected $file;
    /** @var array */
    protected $skippedPatternMethods = [self::CALL_METHOD_PATTERN, self::CALL_METHOD_PATTERT_WITHOUT_REQUEST];

    /**
     * SoapClientClassGeneratorHelper constructor.
     * @param string $soapClientName
     * @param string $namespaceName
     * @param string $extends
     * @throws ReflectionException
     */
    public function __construct(string $soapClientName, string $namespaceName, string $extends)
    {

        $this->soapClientName = $soapClientName;
        $this->namespaceName = $namespaceName;
        $this->createClassFromPattern($extends);
    }

    public function addCallMethod(string $name, ?TypesClassType $typeHintClass, TypesClassType $returnTypeClass, ?string $argument): void
    {
        $method = $this->class->addMethod($name);
        $argument = NormalizeHelper::generateValidNameOfClassOrProperty($argument, false);
        $this->setNamespace();

        if (!empty($typeHintClass))
        {
            $typeHintNamespaceEnd = $typeHintClass->getNamespaceEnd() ? '\\' . $typeHintClass->getSoapClientClassName() : '';
            $typeHintNamespace = $typeHintClass->getNamespace() . '\\' . $typeHintClass->getTypesNamespace() . $typeHintNamespaceEnd;
            $this->namespace->addUse($typeHintNamespace);
        }
        if (!empty($returnTypeClass))
        {
            $returnTypeNamespaceEnd = $returnTypeClass->getNamespaceEnd() ? '\\' . $returnTypeClass->getSoapClientClassName() : '';
            $returnTypeNamespace = $returnTypeClass->getNamespace() . '\\' . $returnTypeClass->getTypesNamespace() . $returnTypeNamespaceEnd;
            $this->namespace->addUse($returnTypeNamespace);
        }
        if (isset($typeHintNamespace) && $typeHintNamespace != $this->namespaceName)
        {
            $this->namespace->addUse($typeHintNamespace);
        }
        if (isset($returnTypeNamespace) && $returnTypeNamespace != $this->namespaceName)
        {
            $this->namespace->addUse($returnTypeNamespace);
        }
        if (isset($argument) && isset($typeHintNamespace))
        {
            $method->addParameter($argument)->setTypeHint($typeHintNamespace . '\\' . $typeHintClass->getClassName());
        }
        if (isset($returnTypeNamespace))
        {
            $method->setReturnType($returnTypeNamespace . '\\' . $returnTypeClass->getClassName());
        }
        if (empty($typeHintClass))
        {
            $reflection = new ReflectionMethod(SoapClientPattern::class, self::CALL_METHOD_PATTERT_WITHOUT_REQUEST);
        } else
        {
            $reflection = new ReflectionMethod(SoapClientPattern::class, self::CALL_METHOD_PATTERN);
        }

        $loadBody = $this->getMethodBody($reflection);
        $body = str_replace(self::CALL_METHOD_PATTERN, $name, $loadBody);
        if (isset($typeHintClass))
        {
            $body = str_replace('arguments', $argument, $body);
        }

        $method->setBody($body);
    }

    public function addClassmapMethod(TypesClassTypes $classTypes, string $soapClassName): void
    {
        $values = [];
        $addedValues = [];
        /** @var TypesClassType $map */
        foreach ($classTypes->getClasses() as $map)
        {

            if ($map->getSoapClientClassName() != $soapClassName || in_array($map->getClassName(), $addedValues))
            {
                continue;
            }
            $typesNs = NormalizeHelper::normalizeTypesClassNamespace($map);
            $addedValues[] = $map->getClassName();
            $values[$map->getClassName()] = '    "' . $map->getClassName() . '" => "' . $typesNs . '\\' . NormalizeHelper::generateValidNameOfClassOrProperty($map->getClassName()) . '"';
        }
        sort($values);
        $this->addMethodWithArrayBody('loadClassMap', 'loadClassMap', $values);
        //        $string = implode(",\n", $values);
        //        $method = $this->class->addMethod('loadClassMap');
        //        $method->setVisibility('protected')->setStatic()->setBody("\$classmap = [\n" . $string . "\n];\n\nreturn \$classmap;");
    }

    public function addClassmapRenamedPropertiesMethod(array $classMap, array $typesNs, string $soapClassName): void
    {
        $classMap[$soapClassName] = array_unique($classMap[$soapClassName]);
        $values = [];
        foreach ($classMap[$soapClassName] as $key => $map)
        {
            $values[$map] = '    "' . $map . '" => "' . $typesNs[$soapClassName][$key] . '"';
        }
        sort($values);
        $this->addMethodWithArrayBody('loadRenamedProperties', 'loadRenamedProperties', $values);
    }

    /**
     * @param string $extends
     * @return SoapClientClassGeneratorHelper
     * @throws ReflectionException
     */
    public function createClassFromPattern(string $extends): SoapClientClassGeneratorHelper
    {
        $this->setNamespace();
        if ($extends != ValidationHelper::DEFAULT_SOAP_CLIENT_NAME)
        {
            $this->namespace->addUse($extends);
        }
        $this->namespace->addUse(SoapClient::class);
        $this->class = $this->namespace->addClass($this->soapClientName);
        $this->class->addExtend($extends);
        $this->setClassProperties();
        $this->setClassMethods();

        return $this;
    }

    /**
     * @return PhpFile
     */
    public function getFile(): PhpFile
    {
        return $this->file;
    }

    private function addMethodWithArrayBody(string $methodName, string $paramName, array $values): void
    {
        $string = implode(",\n", $values);
        $method = $this->class->addMethod($paramName);
        $method->setVisibility('protected')->setStatic()->setBody("\$$methodName = [\n" . $string . "\n];\n\nreturn \$$methodName;");
    }

    private function getMethodBody(ReflectionMethod $method): string
    {
        $fileName = $method->getFileName();
        $startLine = $method->getStartLine() + 1;
        $endLine = $method->getEndLine() - 1;

        $source = file($fileName);
        $source = implode('', array_slice($source, 0, count($source)));
        $source = preg_split("/" . PHP_EOL . "/", $source);

        $body = '';
        for ($i = $startLine; $i < $endLine; $i++)
        {
            $beforeSubstr = "{$source[$i]}\n";
            $length = Strings::length($beforeSubstr);
            $afterSubstr = mb_substr($beforeSubstr, 4, $length - 4);
            if ($afterSubstr == '')
            {
                $afterSubstr = "\n";
            }
            $body .= ($afterSubstr);
        }

        return $body;
    }

    private function getPatternClass(): void
    {
        $this->patternClass = new ReflectionClass(SoapClientPattern::class);
    }

    /**
     * @throws ReflectionException
     */
    private function getPatternMethods(): void
    {
        if (empty($this->patternClass))
        {
            $this->getPatternClass();
        }
        $methods = $this->patternClass->getMethods();
        /** @var ReflectionMethod $method */
        foreach ($methods as $method)
        {
            if (in_array($method->name, $this->skippedPatternMethods))
            {
                continue;
            }
            $info = new ReflectionMethod(SoapClientPattern::class, $method->name);
            $item = new Method;
            $item->visibility = Method::getVisibilityFromReflection($info);
            $item->static = $info->isStatic();
            $item->name = $info->getName();
            $returnType = $info->getReturnType();
            if (!empty($returnType))
            {
                $item->returnType = $returnType->getName();
                $item->returnTypeNullable = $returnType->allowsNull();
            }
            $item->docComment = $info->getDocComment();
            $params = $info->getParameters();
            if (!empty($params))
            {
                foreach ($params as $param)
                {
                    $parameter = new Parameter;
                    $parameter->name = $param->getName();
                    $type = $param->getType();
                    if (!empty($type))
                    {
                        $parameter->type = $type->getName();
                        $parameter->nullable = $type->allowsNull();
                    }
                    if ($param->isDefaultValueAvailable())
                    {
                        $parameter->defaultValue = $param->getDefaultValue();
                    }
                    $item->parameters[] = $parameter;
                }
            }
            $item->body = $this->getMethodBody($info);
            $this->patternMethods[] = $item;
        }
    }

    /**
     * @throws ReflectionException
     */
    private function getPatternProperties(): void
    {
        if (empty($this->patternClass))
        {
            $this->getPatternClass();
        }
        $properties = $this->patternClass->getDefaultProperties();
        foreach ($properties as $key => $property)
        {
            $info = new ReflectionProperty(SoapClientPattern::class, $key);
            $item = new Property;
            $item->visibility = Property::getVisibilityFromReflection($info);
            $item->static = $info->isStatic();
            $item->name = $info->getName();
            $item->docComment = $info->getDocComment();

            $this->patternProperties[] = $item;
        }
    }

    /**
     * @throws ReflectionException
     */
    private function setClassMethods(): void
    {
        if (empty($this->patternMethods))
        {
            $this->getPatternMethods();
        }
        /** @var Method $method */
        foreach ($this->patternMethods as $method)
        {
            $parameters = [];
            if ($method->parameters)
            {
                /** @var Parameter $param */
                foreach ($method->parameters as $param)
                {
                    $parameter = new NetteParameter($param->name);
                    $parameter->setTypeHint($param->type);
                    $parameter->setNullable($param->nullable);
                    $parameter->setDefaultValue($param->defaultValue);
                    $parameters[] = $parameter;
                }
            }
            $this->class->addMethod($method->name)->setVisibility($method->visibility)->setStatic($method->static)
                ->setParameters($parameters)->setReturnType($method->returnType)->setReturnNullable($method->returnTypeNullable)
                ->setBody($method->body);

            if ($method->docComment)
            {
                $this->class->getMethod($method->name)->setComment(NormalizeHelper::docCommentForNetteGenerator($method->docComment));
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function setClassProperties(): void
    {
        if (empty($this->patternProperties))
        {
            $this->getPatternProperties();
        }
        /** @var Property $property */
        foreach ($this->patternProperties as $property)
        {
            $this->class->addProperty($property->name)->setStatic($property->static)->setVisibility($property->visibility)->setComment(NormalizeHelper::docCommentForNetteGenerator($property->docComment));
        }
    }

    private function setFile(): void
    {
        $file = new PhpFile;
        $file->setStrictTypes();
        $file->setComment('This file has autogenerated stub. Please remember if you edit this file.');

        $this->file = $file;
    }

    private function setNamespace(): void
    {
        if (empty($this->file))
        {
            $this->setFile();
        }
        if (empty($this->namespace))
        {
            $namespace = $this->file->addNamespace($this->namespaceName);
            $this->namespace = $namespace;
        }
    }

}