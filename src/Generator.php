<?php declare(strict_types=1);

namespace EasyWsdl\ToPHP;

use EasyWsdl\ToPHP\Exceptions\NamespaceNameBlacklisted;
use EasyWsdl\ToPHP\Helpers\NormalizeHelper;
use EasyWsdl\ToPHP\Helpers\SoapClientClassGeneratorHelper;
use EasyWsdl\ToPHP\Helpers\TypesClassGeneratorHelper;
use EasyWsdl\ToPHP\Helpers\ValidationHelper;
use EasyWsdl\ToPHP\Options\GeneratorOptions;
use EasyWsdl\ToPHP\Options\RunOptions;
use EasyWsdl\ToPHP\Options\WsdlOptions;
use EasyWsdl\ToPHP\Types\TypesClassType;
use EasyWsdl\ToPHP\Types\TypesClassTypes;
use Nette\PhpGenerator\Property;
use ReflectionException;
use SoapClient;
use SoapFault;


/**
 * Class Generator
 * @param string      $wsdl              URL to source
 * @param array       $soapClientOptions Options for SoapClient (Example: 'login' => 'root', 'password' => 'MyVeryHardPassword'
 * @param string|null $namespace         Namespace of generated classes
 * @param string      $soapClassName     Name of the main class with SoapClient call
 * @package EasyWsdl\ToPHP
 */
class Generator
{
    /** @var array */
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
        $this->typesClasses = [];
        $this->originalProperties = [];
        $this->renamedProperties = [];
        $this->soapMethodsWithoutProperties = [];
        $this->generatorOptions = null;
        $this->sameTypesClases = [];
        $this->subnamedClasses = [];
        ValidationHelper::validateOptions($options);
        $this->generatorOptions = $options;
        $this->generateClasses();
    }

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
     * @return void
     */
    private function createTypeClass(string $type, string $soapClientName, string $namespace, string $typeClassNamespace): void
    {
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

                return;
            }
            $this->typesClasses[] = $typesClass;
        }
    }

    /**
     * @throws NamespaceNameBlacklisted
     * @throws ReflectionException
     * @throws SoapFault
     */
    private function generateClasses(): void
    {
        $namespace = null;
        $typesFolderName = null;
        $soapClientClass = null;
        /** @var WsdlOptions $wsdlOption */
        foreach ($this->generatorOptions->getWsdls() as $wsdlOption)
        {


            $wsdl = $wsdlOption->getWsdl();
            $namespace = NormalizeHelper::namespaceName($this->generatorOptions->getCentralizedNamespace());
            $clientSavePath = NormalizeHelper::pathFromNamespace($namespace);
            $soapClientName = $wsdlOption->getSoapClientName();
            $soapOptions = $wsdlOption->getOptions();
            $soapOptions = NormalizeHelper::options(isset($soapOptions) ? $soapOptions->getOptions() : null);
            $typesFolderName = $this->generatorOptions->getTypesFolderName();
            $extends = $this->generatorOptions->getSoapClientExtender();


            $connection = new SoapClient($wsdl, $soapOptions);
            $types = $connection->__getTypes();
            foreach ($types as $type)
            {
                $this->createTypeClass($type, $soapClientName, $namespace, $typesFolderName);
            }
        }
        $classes = new TypesClassTypes;
        $classes->setClasses($this->typesClasses);
        $classes->retypeNamespaces();
        /** @var TypesClassType $class */
        foreach ($classes->getClasses() as $class)
        {
            /** @var RunOptions $runOptions */
            (new TypesClassGeneratorHelper($class->getNamespace(), $class->getTypesNamespace()))->createClass($class);
        }
        unset($class);
        unset($types);
        foreach ($this->generatorOptions->getWsdls() as $wsdlOption)
        {
            $wsdl = $wsdlOption->getWsdl();
            $namespace = NormalizeHelper::namespaceName($this->generatorOptions->getCentralizedNamespace());
            $clientSavePath = NormalizeHelper::pathFromNamespace($namespace);
            $soapClientName = $wsdlOption->getSoapClientName();
            $soapOptions = $wsdlOption->getOptions();
            $soapOptions = NormalizeHelper::options(isset($soapOptions) ? $soapOptions->getOptions() : null);
            $extends = $this->generatorOptions->getSoapClientExtender();

            $soapClientClass = new SoapClientClassGeneratorHelper($soapClientName, $namespace, $extends);

            $connection = new SoapClient($wsdl, $soapOptions);
            $functions = $connection->__getFunctions();
            $functions = array_unique($functions);
            foreach ($functions as $func)
            {
                $explode = explode(' ', $func, 2);
                $replace = str_replace(')', '', $explode[1]);
                $replace = str_replace('(', ':', $replace);
                $t2 = explode(':', $replace);
                $func = $t2[0];
                $par = $t2[1];
                $params = explode(' ', $par);
                $type = $params[0];
                $typeClass = null;
                if (!in_array($explode[0], $this->soapMethodsWithoutProperties))
                {
                    $typeClass = $classes->getClassByClassNameAndSoapName($explode[0], $soapClientName);
                }
                $returnTypeClass = null;
                if (!empty($type) && !in_array($func, $this->soapMethodsWithoutProperties))
                {
                    $returnTypeClass = $classes->getClassByClassNameAndSoapName($type, $soapClientName);
                }
                $soapClientClass->addCallMethod($func, $returnTypeClass, $typeClass, $type); //todo
                $soapClientClass->addClassmapMethod($classes, $soapClientName);
                $soapClientClass->addClassmapRenamedPropertiesMethod($this->renamedProperties, $this->originalProperties, $soapClientName);
                //                $methodNames[] = $func;
                Printer::generateToFile($clientSavePath, $soapClientName, $soapClientClass->getFile());
            }
        }
    }

    /**
     * @return bool
     * @throws SoapFault
     */
    //    public function generate(): bool
    //    {
    //        $this->connection = new SoapClient($this->wsdl, $this->clientOptions);
    //
    //        $this->functions = $this->connection->__getFunctions();
    //        DirCreator::createDir($this->classPath, 0666, true);
    //        DirCreator::createDir($typesDir = $this->classPath . DIRECTORY_SEPARATOR . NormalizeHelper::TYPES_DIR);
    //        $typesNs = $this->namespace . NormalizeHelper::NAMESPACE_SEPARATOR . NormalizeHelper::TYPES_DIR;
    //        $renamedProperties = [];
    //        $originalProperties = [];
    //        $soapClass = new PhpHelper($this->namespace);
    //        $soapClass->createClass($this->className);
    //        $soapClass->setExtendsSoapClient();
    //        $soapClass->addUse(NormalizeHelper::DEFAULT_NAMESPACE_NAME);
    //        $soapClass->setOptionsProperty();
    //        $soapClass->setRenamedPropertiesProperty();
    //        $soapClass->createSoapClientConstructor();
    //        $soapClass->setNormalizeOptionsMethod();
    //        $soapClass->createSoapEntityEncoder();
    //        $soapClass->createSoapEntityDecoder();
    //        foreach ($this->functions as $func)
    //        {
    //            $explode = explode(' ', $func, 2);
    //            $replace = str_replace(')', '', $explode[1]);
    //            $replace = str_replace('(', ':', $replace);
    //            $t2 = explode(':', $replace);
    //            $func = $t2[0];
    //            $par = $t2[1];
    //            $params = explode(' ', $par);
    //            $type = $params[0];
    //            $soapClass->addFunctionMethod($func, $typesNs, $type, $explode[0]);
    //            $methodNames[] = $func;
    //        }
    //        $types = $this->connection->__getTypes();
    //        $classesArr = [];
    //        foreach ($types as $type)
    //        {
    //            if (substr($type, 0, 6) == 'struct')
    //            {
    //                $data = trim(str_replace([
    //                                             '{',
    //                                             '}',
    //                                         ], '', substr($type, strpos($type, '{') + 1)
    //                             )
    //                );
    //                $data_members = explode(';', $data);
    //                $className = trim(substr($type, 6, strpos($type, '{') - 6));
    //                $typeClass = new PhpHelper($typesNs);
    //                $normalizedClassName = NormalizeHelper::generateValidNameOfClassOrProperty($className);
    //                if ($className != $normalizedClassName)
    //                {
    //                    $renamedProperties[] = $normalizedClassName;
    //                    $originalProperties[] = $className;
    //                }
    //                $typeClass->createClass($normalizedClassName);
    //                $classesArr [] = $className;
    //                foreach ($data_members as $member)
    //                {
    //                    $member = trim($member);
    //                    if (strlen($member) < 1)
    //                    {
    //                        continue;
    //                    }
    //                    list($data_type, $member_name) = explode(' ', $member);
    //                    $normalizedMemberName = NormalizeHelper::generateValidNameOfClassOrProperty($member_name, false);
    //                    if ($member_name != $normalizedMemberName)
    //                    {
    //                        $renamedProperties[] = $normalizedMemberName;
    //                        $originalProperties[] = $member_name;
    //                    }
    //                    $normalizedDataType = NormalizeHelper::generateValidNameOfClassOrProperty($data_type);
    //                    if ($data_type != $normalizedDataType)
    //                    {
    //                        $renamedProperties[] = $normalizedDataType;
    //                        $originalProperties[] = $data_type;
    //                    }
    //                    if ($normalizedDataType == 'DateTime')
    //                    {
    //                        $typeClass->addUse('DateTime');
    //                    }
    //                    $typeClass->addCommentedProperty($normalizedMemberName, $normalizedDataType, $member_name);
    //                }
    //                file_put_contents($typesDir . NormalizeHelper::DIRECTORY_SEPARATOR . $normalizedClassName . '.php', $typeClass->generateFile());
    //            }
    //        }
    //        $soapClass->setClassmapProperty();
    //        $soapClass->addClassmapMethod($classesArr, $typesNs);
    //        $soapClass->addClassmapRenamedPropertiesMethod($renamedProperties, $originalProperties);
    //        $createFile = file_put_contents($this->classPath . NormalizeHelper::DIRECTORY_SEPARATOR . $this->className . '.php', $soapClass->generateFile());
    //
    //        return (bool)$createFile;
    //    }


}