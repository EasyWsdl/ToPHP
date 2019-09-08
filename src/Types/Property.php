<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Types;


use ReflectionProperty;


class Property
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

    public static function getVisibilityFromReflection(ReflectionProperty $reflection): string
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