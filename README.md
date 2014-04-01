A simple PHP [Stomp](http://stomp.github.com) Client

Version choice
--------------
There are 2 Versions of stomp-php. 1.x is compatible with PHP <= PHP-5.2 as it
does not use PHP-5.3 specific features.

The master branch uses features such as namespaces and newer constants and will
become the 2.x release in the near future.

For versioning [semantic versioning](http://semver.org/) is used.

The different Versions can be found at the [tags](stomp-php/tags)
section.

Installing
----------

The source is PSR-0 compliant. So just donwload the source and add the Namespace
"FuseSource" to your autoloader configuration with the path pointing to
src/.

As an alternate you have the possibility to make use of composer to manage your
project dependencies.

Just add

    "require": {
        "fusesource/stomp-php" : "2.0.*"
    }

to your project composer.json.

Running Examples
----------------

Examples are located in `src/examples` folder. Before running them, be sure
you have installed this library properly and you have started ActiveMQ broker
(recommended version 5.5.0 or above) with [Stomp connector enabled]
(http://activemq.apache.org/stomp.html).

You can start by running

    cd examples
    php first.php

Also, be sure to check comments in the particular examples for some special
configuration steps (if needed).

Documentation
-------------

* [Web Site](http://stomp.fusesource.org/documentation/php/)

Step by Step: Certificate based Authentication
----------------------------------------------
https://github.com/rethab/php-stomp-cert-example

Tests
-----

The tests at the moment need a running instance of activeMQ listening on the
default STOMP Port 61613.

To run the tests you first need to fetch the dependencies for the test suite
via composer:

    $ php composer.phar install
