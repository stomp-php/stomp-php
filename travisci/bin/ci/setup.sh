#!/bin/bash

if [ ! -d "./travisci/conf/" ]; then
    echo "Check your pwd, you need to run this script from project root."
    exit 1
fi

AMQ_VERSION=$(<./travisci/conf/AMQ_VERSION)
APLO_VERSION=$(<./travisci/conf/APLO_VERSION)
RABBIT_VERSION=$(<./travisci/conf/RABBIT_VERSION)

if [ ! -d ./travisci/tmp ]; then
    mkdir ./travisci/tmp
fi

CONFIG_PATH=$(readlink -f ./travisci/conf)
EXTRACT_PATH=$(readlink -f ./travisci/tmp)


./travisci/bin/ci/setup_activemq.sh $AMQ_VERSION $CONFIG_PATH $EXTRACT_PATH
./travisci/bin/ci/setup_apollomq.sh $APLO_VERSION $CONFIG_PATH $EXTRACT_PATH
./travisci/bin/ci/setup_rabbitmq.sh $RABBIT_VERSION $CONFIG_PATH $EXTRACT_PATH

echo ""
echo "Brokers have been started for you, stop them by running ./travisci/bin/ci/stop.sh"
echo "You can clean your environment by running ./travisci/bin/ci/clean.sh"
echo "Run ./travisci/bin/ci/setup.sh again to resume / restore environment."
