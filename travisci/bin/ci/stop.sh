#!/bin/bash


if [ ! -d "./travisci/conf/" ]; then
    echo "Check your pwd, you need to run this script from project root."
    exit 1
fi

AMQ_VERSION=$(<./travisci/conf/AMQ_VERSION)
APLO_VERSION=$(<./travisci/conf/APLO_VERSION)
RABBIT_VERSION=$(<./travisci/conf/RABBIT_VERSION)

EXTRACT_PATH=$(readlink -f ./travisci/tmp)

if [ -e "$EXTRACT_PATH/apache-activemq-$AMQ_VERSION/bin/activemq" ]; then
    "$EXTRACT_PATH/apache-activemq-$AMQ_VERSION/bin/activemq" stop
fi

if [ -e "$EXTRACT_PATH/apollo-stomp-php/bin/apollo-broker-service" ]; then
    "$EXTRACT_PATH/apollo-stomp-php/bin/apollo-broker-service" stop
fi

if [ -e "$EXTRACT_PATH/rabbitmq_server-${RABBIT_VERSION}/sbin/rabbitmqctl" ]; then
    "$EXTRACT_PATH/rabbitmq_server-${RABBIT_VERSION}/sbin/rabbitmqctl" stop
fi
