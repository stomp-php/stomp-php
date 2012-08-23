<?php

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "Please install the dependencies via composer in order to run the tests.\n";
    echo "See http://getcomposer.org for more information.\n";
    exit(1);
}

require __DIR__ . '/../vendor/autoload.php';
