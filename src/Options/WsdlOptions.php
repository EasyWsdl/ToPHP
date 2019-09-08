<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Options;


class WsdlOptions
{

    /** @var string */
    protected $wsdl;
    /** @var SoapOptions|null */
    protected $options = null;
    /** @var string */
    protected $soapClientName;
    //    /** @var string|null */   //TODO: Uncompleted for now
    //    protected $soapClientNamespace = null;

    /**
     * @return SoapOptions|null
     */
    public function getOptions(): ?SoapOptions
    {
        return !empty($this->options) ? $this->options : null;
    }

    /**
     * @param SoapOptions|null $options
     */
    public function setOptions(?SoapOptions $options): void
    {
        $this->options = $options;
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

    /**
     * @return string|null
     */
    //    public function getSoapClientNamespace(): ?string
    //    {
    //        return $this->soapClientNamespace;
    //    }

    /**
     * @param string|null $soapClientNamespace
     */
    //    public function setSoapClientNamespace(?string $soapClientNamespace): void
    //    {
    //        $this->soapClientNamespace = $soapClientNamespace;
    //    }


}