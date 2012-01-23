<?php

$classLoaderFile = '/usr/share/php/Symfony/Component/ClassLoader/UniversalClassLoader.php';
if(file_exists($classLoaderFile)){
    include_once $classLoaderFile;
}else{
    throw new Exception('Missing Symfony ClassLoader '.$filename);
}

include_once '/usr/share/php/PHPUnit/Autoload.php';

$loader = new Symfony\Component\ClassLoader\UniversalClassLoader();

$loader->registerNamespace('Fusesource', __DIR__.'/../main');
$loader->register();
        