#!/bin/bash

APLO_VERSION=1.7.1
CONFIG_PATH=/config
EXTRACT_PATH=/opt/apollomq

mkdir -p $EXTRACT_PATH
cd $EXTRACT_PATH

wget "http://archive.apache.org/dist/activemq/activemq-apollo/${APLO_VERSION}/apache-apollo-${APLO_VERSION}-unix-distro.tar.gz"

tar -xzf "apache-apollo-${APLO_VERSION}-unix-distro.tar.gz"
rm "apache-apollo-${APLO_VERSION}-unix-distro.tar.gz"

"$EXTRACT_PATH/apache-apollo-${APLO_VERSION}/bin/apollo" create apollo-stomp-php
cp "$CONFIG_PATH/apollo.xml" "$EXTRACT_PATH/apollo-stomp-php/etc/apollo.xml"





