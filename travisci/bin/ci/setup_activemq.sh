#!/bin/bash

AMQ_VERSION=$1
CONFIG_PATH=$2
EXTRACT_PATH=$3

cd "$EXTRACT_PATH"

if [ ! -e "$EXTRACT_PATH/apache-activemq-${AMQ_VERSION}-bin.tar.gz" ]; then
    wget "http://archive.apache.org/dist/activemq/${AMQ_VERSION}/apache-activemq-${AMQ_VERSION}-bin.tar.gz"
fi

if [ ! -d "$EXTRACT_PATH/apache-activemq-${AMQ_VERSION}" ]; then
    tar -xzf "apache-activemq-${AMQ_VERSION}-bin.tar.gz"
    cp "$CONFIG_PATH/amq/activemq.xml" "apache-activemq-${AMQ_VERSION}/conf/activemq.xml"
fi



"$EXTRACT_PATH/apache-activemq-${AMQ_VERSION}/bin/activemq" start