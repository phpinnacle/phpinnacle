<?php

$packageAutoload = dirname(__DIR__) . '/vendor/autoload.php';
$monorepoAutoload = dirname(__DIR__, 4) . '/vendor/autoload.php';

require file_exists($packageAutoload) ? $packageAutoload : $monorepoAutoload;
