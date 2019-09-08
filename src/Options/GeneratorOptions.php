<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Options;


use EasyWsdl\ToPHP\Helpers\ValidationHelper;


class GeneratorOptions
{
    /** @var array */
    protected $wsdls;
    /** @var string|null */
    protected $centralizedNamespace = null;
    /** @var string|null */
    protected $typesFolderName = 'Types';
    /** @var string */
    protected $soapClientExtender = ValidationHelper::DEFAULT_SOAP_CLIENT_NAME;

    public function addWsdl(WsdlOptions $options): void
    {
        $this->wsdls[] = $options;
    }

    /**
     * @return string|null
     */
    public function getCentralizedNamespace(): ?string
    {
        return $this->centralizedNamespace;
    }

    /**
     * @param string|null $centralizedNamespace
     */
    public function setCentralizedNamespace(?string $centralizedNamespace): void
    {
        $this->centralizedNamespace = $centralizedNamespace;
    }

    /**
     * @return string
     */
    public function getSoapClientExtender(): string
    {
        return $this->soapClientExtender;
    }

    /**
     * @param string $soapClientExtender
     */
    public function setSoapClientExtender(string $soapClientExtender): void
    {
        $this->soapClientExtender = $soapClientExtender;
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
     * @return array
     */
    public function getWsdls(): array
    {
        return $this->wsdls;
    }


}