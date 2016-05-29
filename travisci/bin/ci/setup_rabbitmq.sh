#!/bin/bash

RABBIT_VERSION=$1
CONFIG_PATH=$2
EXTRACT_PATH=$3

cd "$EXTRACT_PATH"

if [ ! -e "$EXTRACT_PATH/rabbitmq-server-generic-unix-${RABBIT_VERSION}.tar.xz" ]; then
    wget "https://www.rabbitmq.com/releases/rabbitmq-server/v${RABBIT_VERSION}/rabbitmq-server-generic-unix-${RABBIT_VERSION}.tar.xz"
fi

if [ ! -d "$EXTRACT_PATH/rabbitmq_server-${RABBIT_VERSION}" ]; then
    tar -xJf "rabbitmq-server-generic-unix-${RABBIT_VERSION}.tar.xz"
    cp "$CONFIG_PATH/rabbit/enabled_plugins" "$EXTRACT_PATH/rabbitmq_server-${RABBIT_VERSION}/etc/rabbitmq/enabled_plugins"
    cp "$CONFIG_PATH/rabbit/rabbitmq.config" "$EXTRACT_PATH/rabbitmq_server-${RABBIT_VERSION}/etc/rabbitmq/rabbitmq.config"
fi


"$EXTRACT_PATH/rabbitmq_server-${RABBIT_VERSION}/sbin/rabbitmq-server" -detached
