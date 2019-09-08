<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Options;


class RunOptions
{
    /** @var string */
    protected $wsdl;
    /** @var string */
    protected $namespace;
    /** @var string|null */
    protected $typesFolderName = null;
    /** @var string */
    protected $savePath;
    /** @var string */
    protected $soapClientName;
    /** @var SoapOptions|null */
    protected $options;
    /** @var string */
    protected $extends;

    /**
     * @return string
     */
    public function getExtends(): string
    {
        return $this->extends;
    }

    /**
     * @param string $extends
     */
    public function setExtends(string $extends): void
    {
        $this->extends = $extends;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    /**
     * @return SoapOptions|null
     */
    public function getOptions(): ?SoapOptions
    {
        return $this->options;
    }

    /**
     * @param SoapOptions $options
     */
    public function setOptions(SoapOptions $options): void
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getSavePath(): string
    {
        return $this->savePath;
    }

    /**
     * @param string $savePath
     */
    public function setSavePath(string $savePath): void
    {
        $this->savePath = $savePath;
    }

    /**
     * @return string
     */
    public function getSoapClientName(): string
    {
        return $this->soapClientName;
    }

    /**
     * @param string $soapClientName
     */
    public function setSoapClientName(string $soapClientName): void
    {
        $this->soapClientName = $soapClientName;
    }

    /**
     * @return string|null
     */
    public function getTypesFolderName(): ?string
    {
        return $this->typesFolderName;
    }

    /**
     * @param string|null $typesFolderName
     */
    public function setTypesFolderName(?string $typesFolderName): void
    {
        $this->typesFolderName = $typesFolderName;
    }

    /**
     * @return string
     */
    public function getWsdl(): string
    {
        return $this->wsdl;
    }

    /**
     * @param string $wsdl
     */
    public function setWsdl(string $wsdl): void
    {
        $this->wsdl = $wsdl;
    }


}