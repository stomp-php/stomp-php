# Stomp PHP

[![Build Status](https://travis-ci.org/stomp-php/stomp-php.svg?branch=master)](https://travis-ci.org/stomp-php/stomp-php)

This project is a PHP [Stomp](http://stomp.github.com) Client that besides it implements the Stomp protocol fully,
brings some ActiveMQ and Apollo specific utils that could make your messaging from PHP easier.

## Credits

This library was initially developed by [Dejan Bosanac](https://github.com/dejanb). 
We would like to thank you for your work and we're happy to continue it.

## Version choice

- For new projects you should use version `4.*` which requires `php-5.6`. Support for `php-5.6` ends with version `5.*`.
- For projects running older php versions you can use version `4.2.*` for `php-5.5` and `3.*` for `php-5.3`, please consider to update php.
- For running projects with `fusesource/stomp-php@2.x` clients you can use version `2.2.2`.
- All version newer that `2.x` won't be compatible with `fusesource/stomp-php`. (https://github.com/dejanb/stomp-php.)  


## Installing

The source is PSR-0 compliant. So just download the source and add the Namespace "Stomp" to your autoloader
configuration with the path pointing to src/.

As an alternate you have the possibility to make use of composer to manage your project dependencies.

Just add

```json
    "require": {
        "stomp-php/stomp-php": "4.*"
    }
```

to your project composer.json.

Or simply run `composer require stomp-php/stomp-php` in your project home.

## Replace

If you used `fusesource/stomp-php` before, you can use our `2.x` versions.

```json
    "require": {
        "stomp-php/stomp-php": "2.*"
    }
```

## Documentation

See our [wiki](https://github.com/stomp-php/stomp-php/wiki).

## Examples

Have a look at our example project https://github.com/stomp-php/stomp-php-examples.

## Contributing

We code in `PSR2`, please use our predefined `pre_commit.sh` hook. 

## Tests

To run the tests you first need to fetch the dependencies for the test suite
via composer:

    $ php composer.phar install

The functional testsuite is divided into three broker versions.
Currently it's running on `ActiveMq` (Port 61010), `Apollo` (61020), `RabbitMq` (61030).
Apollo should be configured to use admin:password and RabbitMq to guest:guest.
While ActiveMq must be configured to use no login at all.

You can setup all brokers by running `travisci/bin/start.sh`. Stop them by `travisci/bin/stop.sh`. (Docker is required.)

If you only like to run the functional generic tests, ensure Apollo is configured. 
A basic setup can be achieved by running `./travisci/bin/apollo-mq.sh 1.7.1`. 
(If you want to create a local running broker, you find the config / setup at `travisci/docker/apollo-mq/`)

## Licence

[Apache License Version 2.0](http://www.apache.org/licenses/LICENSE-2.0)