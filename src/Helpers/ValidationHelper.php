<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Helpers;


use EasyWsdl\ToPHP\Exceptions\NamespaceNameBlacklisted;
use EasyWsdl\ToPHP\Exceptions\OptionsException;
use EasyWsdl\ToPHP\Options\GeneratorOptions;
use ReflectionClass;
use ReflectionException;


class ValidationHelper
{
    public const DEFAULT_SOAP_CLIENT_NAME = 'SoapClient';
    public const DEFAULT_TYPES_DIR = 'Types';

    /**
     * @param GeneratorOptions $options
     * @throws NamespaceNameBlacklisted
     * @throws OptionsException
     * @throws ReflectionException
     */
    public static function validateOptions(GeneratorOptions $options): void
    {
        if (empty($options->getWsdls()))
        {
            throw new OptionsException('There are no added WsdlOptions in ' . GeneratorOptions::class . '.');
        }
        if (!empty($options->getCentralizedNamespace()))
        {
            CheckHelper::blacklist($options->getCentralizedNamespace());
        }
        self::validateOptionsNamespaces($options);
    }

    /**
     * @param GeneratorOptions $options
     * @throws NamespaceNameBlacklisted
     * @throws OptionsException
     * @throws ReflectionException
     */
    public static function validateOptionsNamespaces(GeneratorOptions $options): void
    {
        if (empty($options->getCentralizedNamespace()))
        {
            throw new OptionsException(printf('Namespace must be filled in %s.', (new ReflectionClass(GeneratorOptions::class))->getShortName()));
        }
        CheckHelper::blacklist($options->getCentralizedNamespace());
    }

}