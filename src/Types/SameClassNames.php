<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Types;


class SameClassNames
{
    /** @var array */
    protected $classNames = [];

    public function addClassName(TypesClassType $class, int $key): void
    {
        if (!isset($this->classNames[$class->getClassName()]))
        {
            $this->classNames[$class->getClassName()] = new SameClassName($class->getClassName());
        }

        $this->classNames[$class->getClassName()]->addArrayKey($key);
    }

    /**
     * @return array
     */
    public function findAll(): array
    {
        return $this->classNames;
    }

    public function findKeysByClassName(string $className): ?SameClassName
    {
        if (isset($this->classNames[$className]))
        {
            return $this->classNames[$className];
        }

        return null;
    }


}