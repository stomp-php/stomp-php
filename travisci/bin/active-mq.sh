#!/usr/bin/env bash

VERSION=$1
CONFIG_PATH=$2

docker run --name stomp-activemq -d --rm -p 127.0.0.1:61010:61010 -v "${CONFIG_PATH}"/amq/activemq.xml:/opt/activemq/conf/activemq.xml rmohr/activemq:"${VERSION}"-alpine