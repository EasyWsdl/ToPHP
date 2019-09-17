<?php declare(strict_types=1);

namespace EasyWsdl\ToPHP\Helpers;

use EasyWsdl\ToPHP\Exceptions\NamespaceNameBlacklisted;
use EasyWsdl\ToPHP\Types\TypesClassType;
use Nette\Utils\Strings;


class NormalizeHelper
{

    const OPTIONS_TRACE = 'trace';
    const OPTIONS_EXCEPTIONS = 'exceptions';
    const NAMESPACE_SEPARATOR = '\\';
    const DIRECTORY_SEPARATOR = '/';
    const DEFAULT_NAMESPACE_NAME = 'SoapClient';


    public static function className(?string $className): string
    {
        $className = self::newClassName($className);
        if (strpos(mb_strtolower($className), 'client') == false)
        {
            $className = $className . 'Client';
        }

        return $className;
    }

    public static function docCommentForNetteGenerator(string $comment): string
    {
        $vars = ['\/**', '*/'];

        return str_replace($vars, '', $comment);
    }

    public static function generateValidNameOfClassOrProperty(string $value = null, bool $firstUp = true): ?string
    {
        if (empty($value))
        {
            return null;
        }
        if ($firstUp === true)
        {
            $arrayOfNonToRename = ['int', 'array', 'string', 'bool', 'boolean', 'float'];
            if (!in_array($value, $arrayOfNonToRename))
            {
                $value = Strings::firstUpper($value);
            }
        }
        if (Strings::contains($value, '-'))
        {
            $explode = explode('-', $value);
            $uper = [];
            foreach ($explode as $key => $ex)
            {
                if ($key == 0 && $firstUp === false)
                {
                    $uper[] = $ex;
                    continue;
                }
                $uper[] = Strings::firstUpper($ex);
            }
            $value = implode('', $uper);
        }

        return $value;
    }

    /**
     * @param null|string $namespace
     * @return null|string
     * @throws NamespaceNameBlacklisted
     */
    public static function namespaceName(?string $namespace = null): ?string
    {
        $namespace = self::namespaceSeparator($namespace);
        if (empty($namespace))
        {
            $namespace = 'Wsdl' . self::NAMESPACE_SEPARATOR . self::DEFAULT_NAMESPACE_NAME;
        }
        if ((mb_stripos($namespace, self::DEFAULT_NAMESPACE_NAME) !== false))
        {
            $namespace = 'Wsdl' . self::NAMESPACE_SEPARATOR . self::DEFAULT_NAMESPACE_NAME;
        }
        CheckHelper::blacklist($namespace);

        return $namespace;
    }

    public static function namespaceSeparator(?string $namespace = null): ?string
    {
        if ($namespace and mb_substr($namespace, -1) == self::NAMESPACE_SEPARATOR)
        {
            $namespace = mb_substr($namespace, 0, -1);
        }

        return $namespace;
    }

    public static function newClassName(?string $className): string
    {
        if (empty($className))
        {
            $className = 'SoapClient';
        }

        return $className;
    }

    /**
     * @param TypesClassType $typesClass
     * @param bool           $withoutTypeClassName
     * @return TypesClassType
     */
    public static function normalizeTypesClassNamespace(TypesClassType $typesClass, bool $withoutTypeClassName = false): string
    {
        $typeNamespaceEnd = '';
        if (!$withoutTypeClassName)
        {
            $typeNamespaceEnd = $typesClass->getNamespaceEnd() ? '\\' . $typesClass->getSoapClientClassName() : '';
        }

        return $typesClass->getNamespace() . '\\' . $typesClass->getTypesNamespace() . $typeNamespaceEnd;
    }

    public static function options(?array $options = null): array
    {
        if (!isset($options[self::OPTIONS_TRACE]))
        {
            $options[self::OPTIONS_TRACE] = true;
        }
        if (!isset($options[self::OPTIONS_EXCEPTIONS]))
        {
            $options[self::OPTIONS_EXCEPTIONS] = true;
        }

        return $options;
    }

    public static function pathFromNamespace(?string $namespace = null): string
    {
        if ($namespace and mb_substr($namespace, -1) == self::NAMESPACE_SEPARATOR)
        {
            $count = mb_strlen($namespace);
            $namespace = mb_substr($namespace, 0, $count - 1);
        }
        if ($namespace and mb_substr($namespace, 0) == self::NAMESPACE_SEPARATOR)
        {
            $namespace = mb_substr($namespace, 1);
        }
        $path = $namespace;

        return TMP_DIR . self::NAMESPACE_SEPARATOR . 'generated' . self::NAMESPACE_SEPARATOR . $path;
    }

}