#!/bin/bash

if [ ! -d "./travisci/conf/" ]; then
    echo "Check your pwd, you need to run this script from project root."
    exit 1
fi

AMQ_VERSION=$(<./travisci/conf/AMQ_VERSION)
APLO_VERSION=$(<./travisci/conf/APLO_VERSION)
RABBIT_VERSION=$(<./travisci/conf/RABBIT_VERSION)

EXTRACT_PATH=$(readlink -f ./travisci/tmp)

./travisci/bin/ci/stop.sh

rm -R -f "$EXTRACT_PATH/apache-activemq-$AMQ_VERSION/"
rm -R -f "$EXTRACT_PATH/apollo-stomp-php/"
rm -R -f "$EXTRACT_PATH/apache-apollo-${APLO_VERSION}/"
rm -R -f "$EXTRACT_PATH/rabbitmq_server-${RABBIT_VERSION}/"


echo "Environment is clean now, you can restart with ./travisci/bin/ci/setup.sh"