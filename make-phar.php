<?php

$dir      = __DIR__;
$pharName = 'phpunit-bpc.phar';
$path     = $dir . '/' . $pharName;

@unlink($path);

$phar = new Phar($path);

$phar->startBuffering();

$phar->buildFromDirectory(__DIR__ . '/phpunit');

$phar->delete('loader.php');
$phar->delete('Makefile');

$phar->setStub("#!/usr/bin/php
<?php
define('__PHPUNIT_PHAR__', str_replace(DIRECTORY_SEPARATOR, '/', __FILE__));
define('__PHPUNIT_PHAR_ROOT__', 'phar://phpunit-bpc.phar');
Phar::mapPhar('phpunit-bpc.phar');
include 'phar://phpunit-bpc.phar/phpunit.php';
PHPUnit_TextUI_Command::main();
__HALT_COMPILER();
");

$phar->compressFiles(Phar::GZ);

$phar->stopBuffering();

chmod($path, 0755);
