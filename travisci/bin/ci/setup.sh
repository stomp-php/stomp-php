#!/bin/bash

AMQ_VERSION=$(<./travisci/conf/AMQ_VERSION)
APLO_VERSION=$(<./travisci/conf/APLO_VERSION)
RABBIT_VERSION=$(<./travisci/conf/RABBIT_VERSION)


./travisci/bin/ci/setup_activemq.sh $AMQ_VERSION
./travisci/bin/ci/setup_apollomq.sh $APLO_VERSION
./travisci/bin/ci/setup_rabbitmq.sh $RABBIT_VERSION