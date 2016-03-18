#!/usr/bin/env php
<?php

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require __DIR__.'/../vendor/autoload.php';

$container = new ContainerBuilder();

$extension = new \RokkaCli\DependencyInjection\RokkaCliExtension();
$container->registerExtension($extension);

if (file_exists(getcwd().'/rokka.yml')) {
    $configLoader = new YamlFileLoader($container, new FileLocator(getcwd()));
} else {
    // Try to load the defaul configuration
    $configLoader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../src/config'));
}

$configLoader->load('rokka.yml');

// Loading the services
$serviceLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../src/config'));
$serviceLoader->load('services.xml');

$container->compile();

$output = $container->get('symfony.console_output');
$application = $container->get('symfony.application');
$application->run(null, $output);
