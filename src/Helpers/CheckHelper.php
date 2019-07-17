<?php declare(strict_types=1);

namespace theStormwinter\EasyWsdl2Php\Helpers;

use theStormwinter\EasyWsdl2Php\Exceptions\NamespaceNameBlacklisted;


class CheckHelper
{
	
	/** @var array */
	public static $namespaces = [
		'__halt_compiler',
		'abstract',
		'and',
		'array',
		'as',
		'break',
		'callable',
		'case',
		'catch',
		'class',
		'clone',
		'const',
		'continue',
		'declare',
		'default',
		'die',
		'do',
		'echo',
		'else',
		'elseif',
		'empty',
		'enddeclare',
		'endfor',
		'endforeach',
		'endif',
		'endswitch',
		'endwhile',
		'eval',
		'exit',
		'extends',
		'final',
		'for',
		'foreach',
		'function',
		'global',
		'goto',
		'if',
		'implements',
		'include',
		'include_once',
		'instanceof',
		'insteadof',
		'interface',
		'isset',
		'list',
		'namespace',
		'new',
		'or',
		'print',
		'private',
		'protected',
		'public',
		'require',
		'require_once',
		'return',
		'static',
		'switch',
		'throw',
		'trait',
		'try',
		'unset',
		'use',
		'var',
		'while',
		'xor',
	];
	
	/**
	 * @param string $namespace
	 *
	 * @throws NamespaceNameBlacklisted
	 */
	public static function blacklist(string $namespace): void
	{
		self::namespace($namespace);
	}
	
	
	/**
	 * @param string $namespace
	 *
	 * @throws NamespaceNameBlacklisted
	 */
	public static function namespace(string $namespace): void
	{
		$pieces = explode('\\', $namespace);
		$errorNames = null;
		foreach ($pieces as $piece) {
			if (in_array(strtolower($piece), self::$namespaces)) {
				$errorNames[] = $piece;
			}
		}
		if (!empty($errorNames)) {
			$error = implode(', ', $errorNames);
			$toString = null;
			if (count($errorNames) > 1) {
				$toString = 'These words "' . $error . '" are in PHP keywords and cannot be used in namespace.';
			} else {
				$toString = '"' . $error . '" is in PHP keywords and cannot be used in namespace.';
			}
			throw new NamespaceNameBlacklisted($toString);
		}
	}
	
	
}