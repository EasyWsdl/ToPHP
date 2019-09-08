<?php declare(strict_types=1);


namespace EasyWsdl\ToPHP\Types;


class Parameter
{

    /** @var string */
    public $name;
    /** @var string|null */
    public $type;
    /** @var bool */
    public $nullable = false;
    /** @var string|null */
    public $defaultValue;
}