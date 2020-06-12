#!/usr/bin/env bash

VERSION=$1
CONFIG_PATH=$2

docker run --name stomp-rabbitmq --rm -d -p 127.0.0.1:61030:61030 -v "${CONFIG_PATH}"/rabbit/:/etc/rabbitmq/ rabbitmq:"${VERSION}"