<?php
require_once(__DIR__ . '/../../../vendor/autoload.php');

$builder = new \DI\ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/config.php');
$builder->addDefinitions(__DIR__ . '/Mock/tables.php');

$container = $builder->build();