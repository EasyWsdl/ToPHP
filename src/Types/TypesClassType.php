<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Types;


use Nette\PhpGenerator\Property as NetteProperty;


class TypesClassType
{

    /** @var string */
    protected $className;
    /** @var array */
    protected $properties = [];
    /** @var array */
    protected $useStatements = [];
    /** @var bool */
    protected $namespaceEnd = false;
    /** @var string|null */
    protected $soapClientClassName = null;
    /** @var null|string */
    protected $namespace = null;
    /** @var null|string */
    protected $typesNamespace = null;

    /**
     * @param NetteProperty $property
     */
    public function addProperty(NetteProperty $property): void
    {
        $this->properties[$property->getName()] = $property;
    }

    /**
     * @param string $statement
     */
    public function addUseStatement(string $statement): void
    {
        $this->useStatements[] = $statement;
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

    /**
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    /**
     * @param string|null $namespace
     */
    public function setNamespace(?string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @return bool
     */
    public function getNamespaceEnd(): bool
    {
        return $this->namespaceEnd;
    }

    /**
     * @param bool $set
     */
    public function setNamespaceEnd(bool $set = true): void
    {
        $this->namespaceEnd = $set;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param string $name
     * @return NetteProperty|null
     */
    public function getProperty(string $name): ?NetteProperty
    {
        if (isset($this->properties[$name]))
        {
            return $this->properties[$name];
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getSoapClientClassName(): ?string
    {
        return $this->soapClientClassName;
    }

    /**
     * @param string|null $soapClientClassName
     */
    public function setSoapClientClassName(?string $soapClientClassName): void
    {
        $this->soapClientClassName = $soapClientClassName;
    }

    /**
     * @return string|null
     */
    public function getTypesNamespace(): ?string
    {
        return $this->typesNamespace;
    }

    /**
     * @param string|null $typesNamespace
     */
    public function setTypesNamespace(?string $typesNamespace): void
    {
        $this->typesNamespace = $typesNamespace;
    }

    /**
     * @return array
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    /**
     * @param array $useStatements
     */
    public function setUseStatements(array $useStatements): void
    {
        $this->useStatements = $useStatements;
    }


}