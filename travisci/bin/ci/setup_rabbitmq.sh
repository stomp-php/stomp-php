#!/bin/bash

RABBIT_VERSION=$1

wget https://www.rabbitmq.com/releases/rabbitmq-server/v${RABBIT_VERSION}/rabbitmq-server-generic-unix-${RABBIT_VERSION}.tar.gz


tar -xzf rabbitmq-server-generic-unix-${RABBIT_VERSION}.tar.gz


cp travisci/conf/rabbit/enabled_plugins ./rabbitmq_server-${RABBIT_VERSION}/etc/rabbitmq/enabled_plugins
cp travisci/conf/rabbit/rabbitmq.config ./rabbitmq_server-${RABBIT_VERSION}/etc/rabbitmq/rabbitmq.config

./rabbitmq_server-${RABBIT_VERSION}/sbin/rabbitmq-server -detached
