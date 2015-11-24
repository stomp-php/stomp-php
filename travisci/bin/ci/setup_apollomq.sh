#!/bin/bash

APLO_VERSION=$1
CONFIG_PATH=$2
EXTRACT_PATH=$3

cd "$EXTRACT_PATH"

if [ ! -e "$EXTRACT_PATH/apache-apollo-${APLO_VERSION}-unix-distro.tar.gz" ]; then
    wget "http://archive.apache.org/dist/activemq/activemq-apollo/${APLO_VERSION}/apache-apollo-${APLO_VERSION}-unix-distro.tar.gz"

fi

if [ ! -d "$EXTRACT_PATH/apache-apollo-${APLO_VERSION}" ]; then
    tar -xzf "apache-apollo-${APLO_VERSION}-unix-distro.tar.gz"
    "$EXTRACT_PATH/apache-apollo-${APLO_VERSION}/bin/apollo" create apollo-stomp-php
    cp "$CONFIG_PATH/aplo/apollo.xml" "$EXTRACT_PATH/apollo-stomp-php/etc/apollo.xml"
fi





"$EXTRACT_PATH/apollo-stomp-php/bin/apollo-broker-service" start