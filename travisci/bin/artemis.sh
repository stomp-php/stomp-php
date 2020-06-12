#!/usr/bin/env bash

VERSION=$1
CONFIG_PATH=$2

docker run --name stomp-artemis -d --rm -p 127.0.0.1:61040:61613 -e DISABLE_SECURITY=true -v "$CONFIG_PATH"/artemis/broker-00.xml:/var/lib/artemis/etc-override/broker-00.xml:ro vromero/activemq-artemis:"${VERSION}"