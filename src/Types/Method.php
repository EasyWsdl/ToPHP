<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Types;


use ReflectionMethod;


class Method
{
    /** @var string */
    public $visibility = false;
    /** @var bool */
    public $static = false;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $value;
    /** @var string|null */
    public $docComment;
    /** @var array */
    public $parameters;
    /** @var string */
    public $body;
    /** @var string|null */
    public $returnType;
    /** @var bool */
    public $returnTypeNullable = false;

    public static function getVisibilityFromReflection(ReflectionMethod $reflection): string
    {
        if ($reflection->isPublic() === true)
        {
            return 'public';
        }
        if ($reflection->isProtected() === true)
        {
            return 'protected';
        }
        if ($reflection->isPrivate() === true)
        {
            return 'private';
        }

        return 'public';
    }
}