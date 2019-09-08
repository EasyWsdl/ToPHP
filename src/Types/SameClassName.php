<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Types;


use UnexpectedValueException;


class SameClassName
{
    /** @var string */
    protected $className;
    /** @var array */
    protected $classArrayKeys = [];

    public function __construct(string $className)
    {

        $this->className = $className;
    }

    public function addArrayKey(int $key): void
    {
        $this->classArrayKeys[] = $key;
    }

    /**
     * @return array
     */
    public function getClassArrayKeys(): array
    {
        return $this->classArrayKeys;
    }

    /**
     * @param array $classArrayKeys
     */
    public function setClassArrayKeys(array $classArrayKeys): void
    {
        foreach ($classArrayKeys as $key)
        {
            if (!is_int($key))
            {
                throw new UnexpectedValueException(sprintf("Array value %s must be integer in %s", (string)$key, SameClassName::class));
            }
        }
        $this->classArrayKeys = $classArrayKeys;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName(string $className): void
    {
        $this->className = $className;
    }


}