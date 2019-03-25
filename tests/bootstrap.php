<?php declare(strict_types=1);

use Ninjify\Nunjuck\Environment;
use Tracy\Debugger;


if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}

define('ROOT_DIR', __DIR__);

Environment::setup(__DIR__);
Debugger::$logDirectory = TEMP_DIR;

