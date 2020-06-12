#!/bin/bash

if [ ! -d "./travisci/conf/" ]; then
    echo "Check your pwd, you need to run this script from project root."
    exit 1
fi

ARTEMIS_VERSION=$(<./travisci/conf/ARTEMIS_VERSION)
AMQ_VERSION=$(<./travisci/conf/AMQ_VERSION)
APLO_VERSION=$(<./travisci/conf/APLO_VERSION)
RABBIT_VERSION=$(<./travisci/conf/RABBIT_VERSION)

if [ ! -d ./travisci/tmp ]; then
    mkdir ./travisci/tmp
fi

CONFIG_PATH=$(readlink -f ./travisci/conf)

./travisci/bin/artemis.sh "$ARTEMIS_VERSION" "$CONFIG_PATH"
./travisci/bin/active-mq.sh "$AMQ_VERSION" "$CONFIG_PATH"
./travisci/bin/rabbit-mq.sh "$RABBIT_VERSION" "$CONFIG_PATH"
./travisci/bin/apollo-mq.sh "$APLO_VERSION"

echo ""
echo "Brokers have been started for you, stop them by running ./travisci/bin/stop.sh"
