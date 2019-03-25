<?php


namespace theStormwinter\EasyWsdl2Php\Helpers;


use theStormwinter\EasyWsdl2Php\Exceptions\NamespaceNameBlacklisted;


class CheckHelper
{

	/** @var array */
	public static $namespaces = ['Namespace'];

	/**
	 * @param string $namespace
	 * @throws NamespaceNameBlacklisted
	 */
	public static function blacklist(string $namespace)
	{
		self::namespace($namespace);
	}


	/**
	 * @param string $namespace
	 * @throws NamespaceNameBlacklisted
	 */
	public static function namespace(string $namespace): void
	{
		foreach (self::$namespaces as $value) {
			if ((mb_stripos(mb_strtolower($namespace), $value) !== false)) {
				throw new NamespaceNameBlacklisted('Namespace "' . $value . '" you have defined is on blacklist. You must choose another name.');
			}
		}
	}


}