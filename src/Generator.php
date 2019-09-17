<?php declare(strict_types=1);

namespace EasyWsdl\ToPHP;

use EasyWsdl\ToPHP\Exceptions\NamespaceNameBlacklisted;
use EasyWsdl\ToPHP\Helpers\NormalizeHelper;
use EasyWsdl\ToPHP\Helpers\SoapClientClassGeneratorHelper;
use EasyWsdl\ToPHP\Helpers\TypesClassGeneratorHelper;
use EasyWsdl\ToPHP\Helpers\ValidationHelper;
use EasyWsdl\ToPHP\Options\GeneratorOptions;
use EasyWsdl\ToPHP\Options\WsdlOptions;
use EasyWsdl\ToPHP\Types\TypesClassType;
use EasyWsdl\ToPHP\Types\TypesClassTypes;
use Nette\PhpGenerator\Property;
use ReflectionException;
use SoapClient;
use SoapFault;


/**
 * Class Generator
 * @package EasyWsdl\ToPHP
 */
class Generator
{
    /** @var TypesClassTypes */
    protected $typesClasses = [];
    /** @var array */
    protected $originalProperties = [];
    /** @var array */
    protected $renamedProperties = [];
    /** @var array */
    protected $soapMethodsWithoutProperties = [];
    /** @var GeneratorOptions */
    protected $generatorOptions;
    /** @var array */
    protected $sameTypesClases = [];
    /** @var array */
    protected $subnamedClasses = [];

    /**
     * @param GeneratorOptions $options
     * @throws Exceptions\OptionsException
     * @throws NamespaceNameBlacklisted
     * @throws ReflectionException
     * @throws SoapFault
     */
    public function generate(GeneratorOptions $options): void
    {
        $this->setDefaults();
        ValidationHelper::validateOptions($options);
        $this->generatorOptions = $options;
        $this->generateTypeClasses();
        $this->generateSoapClients();
    }

    /**
     * @param string $original
     * @param string $renamed
     * @param string $soapClassName
     */
    private function addRenamedItem(string $original, string $renamed, string $soapClassName): void
    {
        if ($original != $renamed)
        {
            $this->renamedProperties[$soapClassName][] = $renamed;
            $this->originalProperties[$soapClassName][] = $original;
        }
    }

    /**
     * @param string         $member
     * @param TypesClassType $typesClass
     */
    private function createProperty(string $member, TypesClassType &$typesClass): void
    {
        $member = trim($member);
        if (strlen($member) < 1)
        {
            return;
        }
        list($dataType, $memberName) = explode(' ', $member);
        $normalizedMemberName = NormalizeHelper::generateValidNameOfClassOrProperty($memberName, false);
        $this->addRenamedItem($memberName, $normalizedMemberName, $typesClass->getSoapClientClassName());
        $normalizedDataType = NormalizeHelper::generateValidNameOfClassOrProperty($dataType);
        $this->addRenamedItem($dataType, $normalizedDataType, $typesClass->getSoapClientClassName());
        if ($normalizedDataType == 'DateTime')
        {
            $typesClass->addUseStatement('DateTime');
        }
        $property = new Property($normalizedMemberName);
        $property->addComment('@var ' . $normalizedDataType . (!empty($memberName) && $normalizedMemberName != $memberName ? ' Generates ' . $memberName . ' instead of this.' : ''));

        $typesClass->addProperty($property);
    }

    /**
     * @param string $type
     * @param string $soapClientName
     * @param string $namespace
     * @param string $typeClassNamespace
     * @return TypesClassType|null
     */
    private function createTypeClass(string $type, string $soapClientName, string $namespace, string $typeClassNamespace): ?TypesClassType
    {
        $typesClass = null;
        if (substr($type, 0, 6) == 'struct')
        {
            $typesClass = new TypesClassType;
            $data = trim(str_replace(['{', '}',], '', substr($type, strpos($type, '{') + 1)));
            $dataMembers = explode(';', $data);
            $className = trim(substr($type, 6, strpos($type, '{') - 6));
            $normalizedClassName = NormalizeHelper::generateValidNameOfClassOrProperty($className);
            $this->addRenamedItem($className, $normalizedClassName, $soapClientName);
            $typesClass->setClassName($normalizedClassName);
            $classesArr [] = $className;
            $typesClass->setSoapClientClassName($soapClientName);
            $typesClass->setNamespace($namespace);
            $typesClass->setTypesNamespace($typeClassNamespace);
            foreach ($dataMembers as $member)
            {
                $this->createProperty($member, $typesClass);
            }
            if (count($typesClass->getProperties()) == 0)
            {
                $this->soapMethodsWithoutProperties[] = $normalizedClassName;

                return null;
            }
        }

        return $typesClass;
    }

    /**
     * @throws NamespaceNameBlacklisted
     * @throws ReflectionException
     * @throws SoapFault
     */
    private function generateSoapClients(): void
    {
        /** @var WsdlOptions $wsdlOption */
        foreach ($this->generatorOptions->getWsdls() as $wsdlOption)
        {
            $wsdl = trim($wsdlOption->getWsdl());
            $namespace = trim(NormalizeHelper::namespaceName($this->generatorOptions->getCentralizedNamespace()));
            $clientSavePath = trim(NormalizeHelper::pathFromNamespace($namespace));
            $soapClientName = trim($wsdlOption->getSoapClientName());
            $soapOptions = $wsdlOption->getOptions();
            $soapOptions = NormalizeHelper::options(isset($soapOptions) ? $soapOptions->getOptions() : null);
            $extends = trim($this->generatorOptions->getSoapClientExtender());
            $soapClientClass = new SoapClientClassGeneratorHelper($soapClientName, $namespace, $extends);
            $connection = new SoapClient($wsdl, $soapOptions);
            $functions = $connection->__getFunctions();
            $functions = array_unique($functions);
            foreach ($functions as $func)
            {
                $this->processAndSaveClientClass($func, $clientSavePath, $soapClientClass, $soapClientName);
            }
        }
    }

    /**
     * @throws NamespaceNameBlacklisted
     * @throws SoapFault
     */
    private function generateTypeClasses(): void
    {
        $typesClasses = [];
        /** @var WsdlOptions $wsdlOption */
        foreach ($this->generatorOptions->getWsdls() as $wsdlOption)
        {
            $wsdl = trim($wsdlOption->getWsdl());
            $namespace = trim(NormalizeHelper::namespaceName($this->generatorOptions->getCentralizedNamespace()));
            $soapClientName = trim($wsdlOption->getSoapClientName());
            $soapOptions = $wsdlOption->getOptions();
            $soapOptions = NormalizeHelper::options(isset($soapOptions) ? $soapOptions->getOptions() : null);
            $typesFolderName = trim($this->generatorOptions->getTypesFolderName());
            $connection = new SoapClient($wsdl, $soapOptions);
            $types = $connection->__getTypes();
            foreach ($types as $type)
            {
                $typesClasses[] = $this->createTypeClass($type, $soapClientName, $namespace, $typesFolderName);
            }
        }
        $this->typesClasses = new TypesClassTypes;
        $this->typesClasses->setClasses($typesClasses);
        $this->typesClasses->retypeNamespaces();
        $this->printTypeClasses();
    }

    /**
     *  Prints type classes into file
     */
    private function printTypeClasses(): void
    {
        /** @var TypesClassType $class */
        foreach ($this->typesClasses->getClasses() as $class)
        {
            (new TypesClassGeneratorHelper($class->getNamespace(), $class->getTypesNamespace()))->createClass($class);
        }
    }

    private function processAndSaveClientClass(string $function, string $clientSavePath, SoapClientClassGeneratorHelper $soapClientClass, string $soapClientName): void
    {
        $explode = explode(' ', $function, 2);
        $replace = str_replace(')', '', $explode[1]);
        $replace = str_replace('(', ':', $replace);
        $t2 = explode(':', $replace);
        $function = $t2[0];
        $par = $t2[1];
        $params = explode(' ', $par);
        $type = $params[0];
        $typeClass = null;
        if (!in_array($explode[0], $this->soapMethodsWithoutProperties))
        {
            $typeClass = $this->typesClasses->getClassByClassNameAndSoapName($explode[0], $soapClientName);
        }
        $returnTypeClass = null;
        if (!empty($type) && !in_array($function, $this->soapMethodsWithoutProperties))
        {
            $returnTypeClass = $this->typesClasses->getClassByClassNameAndSoapName($type, $soapClientName);
        }
        $soapClientClass->addCallMethod($function, $returnTypeClass, $typeClass, $type);
        $soapClientClass->addClassmapMethod($this->typesClasses, $soapClientName);
        $soapClientClass->addClassmapRenamedPropertiesMethod($this->renamedProperties, $this->originalProperties, $soapClientName);
        Printer::generateToFile($clientSavePath, $soapClientName, $soapClientClass->getFile());
    }

    /**
     *  Sets default values
     */
    private function setDefaults(): void
    {
        $this->typesClasses = [];
        $this->originalProperties = [];
        $this->renamedProperties = [];
        $this->soapMethodsWithoutProperties = [];
        $this->generatorOptions = null;
        $this->sameTypesClases = [];
        $this->subnamedClasses = [];
    }

}