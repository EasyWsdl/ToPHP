<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Options;


use EasyWsdl\ToPHP\Exceptions\OptionsException;
use Nette\Utils\Strings;


class SoapOptions
{
    public const LOGIN = 'login';
    public const PASSWORD = 'password';
    public const PROXY_HOST = 'proxy_host';
    public const PROXY_PORT = 'proxy_port';
    public const PROXY_LOGIN = 'proxy_login';
    public const PROXY_PASSWORD = 'proxy_password';
    public const LOCAL_CERT = 'local_cert';
    public const LOCATION = 'location';
    public const URI = 'uri';
    public const STYLE = 'style';
    public const USE = 'use';
    public const COMPRESSION = 'compression';
    public const ENCODING = 'encoding';

    /** @var array */
    protected $options = [];

	/**
	 * @param array $streamContext
	 * @return SoapOptions
	 */
	public function addStreamContextOption(array $streamContext): SoapOptions
	{
		$this->options['stream_context'] = stream_context_create($streamContext);

		return $this;
    }

	/**
	 * @param string $name
	 * @param string $value
	 * @return SoapOptions
	 * @throws OptionsException
	 */
    public function addOption(string $name, string $value): SoapOptions
    {
        if (Strings::lower(Strings::trim($name)) == 'classmap')
        {
            throw new OptionsException('Option classmap is autogenerated and cannot be used');
        }
        $this->options[Strings::lower(Strings::trim($name))] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getOption(string $name): ?string
    {
        if (isset($this->options[$name]))
        {
            return $this->options[$name];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

	/**
	 * @param array $options
	 * @return SoapOptions
	 * @throws OptionsException
	 */
    public function setOptions(array $options): SoapOptions
    {
        if ((isset($options['classmap'])))
        {
            throw new OptionsException('Option classmap is autogenerated and cannot be used.');
        }
        foreach ($options as $key => $option)
        {
            if (is_int($key))
            {
                throw new OptionsException(sprintf('Options[%s] must be string value in array key.', $key));
            }
            $this->options[$key] = $option;
        }

        return $this;
    }

}