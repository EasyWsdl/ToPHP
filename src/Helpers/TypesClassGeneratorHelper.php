<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Helpers;


use EasyWsdl\ToPHP\Printer;
use EasyWsdl\ToPHP\Types\TypesClassType;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;


class TypesClassGeneratorHelper
{
    /** @var string */
    public $namespaceName;
    /** @var PhpNamespace */
    protected $namespace;
    /** @var ClassType */
    protected $class;
    /** @var PhpFile */
    protected $file;

    /**
     * TypesClassGeneratorHelper constructor.
     * @param string $namespaceName
     * @param string $typesDir
     */
    public function __construct(string $namespaceName, string $typesDir)
    {
        $this->namespaceName = $namespaceName . '\\' . $typesDir;
    }

    /**
     * @param TypesClassType $classType
     */
    public function createClass(TypesClassType $classType): void
    {
        $useStatement = null;
        if ($classType->getNamespaceEnd() === true)
        {
            $this->namespaceName .= '\\' . $classType->getSoapClientClassName();
        }
        $filePath = NormalizeHelper::pathFromNamespace($this->namespaceName);
        $this->setNamespace();
        $this->class = $this->namespace->addClass($classType->getClassName());
        if (count($classType->getUseStatements()) > 0)
        {
            foreach ($classType->getUseStatements() as $use)
            {
                $this->namespace->addUse($use);
            }
        }
        if (count($classType->getProperties()) > 0)
        {
            $this->class->setProperties($classType->getProperties());
        }
        Printer::generateToFile($filePath, $classType->getClassName(), $this->file);
    }

    private function setNamespace(): void
    {
        if (empty($this->file))
        {
            $this->file = Printer::setFile();
        }
        $namespace = $this->file->addNamespace($this->namespaceName);
        $this->namespace = $namespace;
    }


}