<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Helpers;


use UnexpectedValueException;


class DirCreator
{
    public static function createDir(string $path, int $chmod = 0666, bool $recursive = false): void
    {
        if (!is_dir($path))
        {
            $isCreated = mkdir($path, $chmod, $recursive);
            if ($isCreated == false)
            {
                throw new UnexpectedValueException(printf('Creating path %s failure.', $path));
            }
        }
    }
}