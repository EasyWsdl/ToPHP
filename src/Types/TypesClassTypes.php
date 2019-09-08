<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Types;


use Nette\PhpGenerator\Property;
use Nette\Utils\Strings;
use UnexpectedValueException;


class TypesClassTypes
{
    /** @var array */
    protected $classes = [];
    /** @var SameClassNames */
    protected $sameClassNames;
    /** @var array */
    protected $namespacedClasses = [];
    protected $unnamspacedClasses = [];
    private $key = 0;

    public function addClass(TypesClassType $class): void
    {
        $this->setClassType($class);
    }

    public function getClassByClassNameAndSoapName(string $className, string $soapClassName): ?TypesClassType
    {
        /** @var TypesClassType $class */
        foreach ($this->classes as $class)
        {
            if (Strings::upper($class->getClassName()) == Strings::upper($className) && $class->getSoapClientClassName() == $soapClassName)
            {
                $retClass = $class;
                break;
            }
        }
        if (!isset($retClass))
        {
            throw new UnexpectedValueException(sprintf('Class "%s" cannot be found in %s Client', $className, $soapClassName));
        }

        return $retClass;
    }

    /**
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @param array $classes
     */
    public function setClasses(array $classes): void
    {
        foreach ($classes as $key => $class)
        {
            if (!$class instanceof TypesClassType)
            {
                throw new UnexpectedValueException(sprintf("Array value with key %s must be instance of %s in %s", (string)$key, TypesClassType::class, TypesClassTypes::class));
            }
            $this->setClassType($class);
        }
    }

    public function retypeNamespaces(): void
    {
        $this->changeNamespaceInClass();
        $rerun = true;
        while ($rerun == true)
        {
            $rerun = $this->changeNamespaceInProperties();
        }
        $this->addUsestatements();
    }

    private function addUsestatements(): void
    {
        $this->getUnnamespacedClasses();
        /** @var TypesClassType $class */
        foreach ($this->classes as $class)
        {
            if (!$class->getNamespaceEnd())
            {
                continue;
            }
            /** @var Property $property */
            foreach ($class->getProperties() as $property)
            {
                $var = $this->generateClassNameFromComment($property->getComment());
                if (array_key_exists($var, $this->unnamspacedClasses))
                {
                    $namespace = $this->unnamspacedClasses[$var];
                    $sameClassNames = $this->getSameClassNames()->findKeysByClassName($class->getClassName());
                    $this->setUseStatements($sameClassNames->getClassArrayKeys(), $namespace);
                }
            }
        }
    }

    /**
     *
     */
    private function changeNamespaceInClass(): void
    {

        $controlledNames = [];
        /** @var TypesClassType $class */
        foreach ($this->classes as $class)
        {
            if ($class->getNamespaceEnd() || in_array($class->getClassName(), $controlledNames))
            {
                continue;
            }
            $properties = [];
            $sameClassNames = $this->getSameClassNames()->findKeysByClassName($class->getClassName());
            //            $classes = $this->findClassesByClassName($class->getClassName());
            foreach ($sameClassNames->getClassArrayKeys() as $key)
            {
                foreach ($sameClassNames->getClassArrayKeys() as $k)
                {
                    if (count($this->classes[$key]->getProperties()) != count($this->classes[$k]->getProperties()))
                    {
                        $this->setNamespaceEnds($sameClassNames->getClassArrayKeys());
                        continue;
                    }
                    /** @var Property $property */
                    foreach ($this->classes[$k]->getProperties() as $p => $property)
                    {
                        /** @var Property $property */
                        $properties[$k][$p] = $property;
                    }
                }
                /** @var Property $property */
                foreach ($this->classes[$key]->getProperties() as $p => $property)
                {
                    foreach ($properties as $prop)
                    {
                        if (!isset($prop[$p]))
                        {
                            $this->setNamespaceEnds($sameClassNames->getClassArrayKeys());
                            continue;
                        }
                        if ($prop[$p] != $property)
                        {
                            $this->setNamespaceEnds($sameClassNames->getClassArrayKeys());

                            continue;
                        }
                    }
                }
            }
        }
    }

    private function changeNamespaceInProperties(): bool
    {
        $rerun = false;
        /** @var TypesClassType $class */
        foreach ($this->classes as $class)
        {
            if ($class->getNamespaceEnd())
            {
                continue;
            }

            /** @var Property $property */
            foreach ($class->getProperties() as $property)
            {
                $var = $this->generateClassNameFromComment($property->getComment());
                if (in_array($var, $this->namespacedClasses))
                {
                    $sameClassNames = $this->getSameClassNames()->findKeysByClassName($class->getClassName());
                    $this->setNamespaceEnds($sameClassNames->getClassArrayKeys());
                    $rerun = true;
                    continue;
                }
            }
        }

        return $rerun;
    }

    private function generateClassNameFromComment(string $comment): string
    {
        $comment = str_replace('@var ', '', $comment);

        return $comment;
    }

    private function getSameClassNames(): SameClassNames
    {
        if (empty($this->sameClassNames))
        {
            $this->sameClassNames = new SameClassNames;
        }

        return $this->sameClassNames;
    }

    private function getUnnamespacedClasses(): void
    {
        /** @var TypesClassType $class */
        foreach ($this->classes as $class)
        {
            if ($class->getNamespaceEnd() || isset($this->unnamspacedClasses[$class->getClassName()]))
            {
                continue;
            }
            if (!isset($this->unnamspacedClasses[$class->getClassName()]))
            {
                $this->unnamspacedClasses[$class->getClassName()] = $class->getNamespace() . '\\' . $class->getTypesNamespace() . '\\' . $class->getClassName();
            }
        }
    }

    private function setClassType(TypesClassType $class): void
    {
        $this->classes[$this->key] = $class;
        $this->getSameClassNames()->addClassName($class, $this->key);
        $this->key++;
    }

    private function setNamespaceEnds(array $arrayKeys): void
    {
        foreach ($arrayKeys as $key)
        {
            $this->classes[$key]->setNamespaceEnd();
            if (!in_array($this->classes[$key]->getClassName(), $this->namespacedClasses))
            {
                $this->namespacedClasses[] = $this->classes[$key]->getClassName();
            }
        }
    }

    private function setUseStatements(array $arrayKeys, string $useStatement): void
    {
        foreach ($arrayKeys as $key)
        {
            $this->classes[$key]->addUseStatement($useStatement);
        }
    }
}