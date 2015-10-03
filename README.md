A simple PHP [Stomp](http://stomp.github.com) Client

[![Build Status](https://travis-ci.org/stomp-php/stomp-php.svg?branch=master)](https://travis-ci.org/stomp-php/stomp-php)

Version choice
--------------
This fork is not compatible to the original stomp from https://github.com/dejanb/stomp-php.
This is caused since the original fork is mostly outdated. The last compatible version is `2.2.2`.

Installing
----------

composer.json

```json
    "require": {
        "stomp-php/stomp-php": "2.2.2"
    }
```

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

Tests
-----

The tests at the moment need a running instance of activeMQ listening on the
default STOMP Port 61613.

To run the tests you first need to fetch the dependencies for the test suite
via composer:

    $ php composer.phar install
