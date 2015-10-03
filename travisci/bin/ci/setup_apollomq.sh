#!/bin/bash

APLO_VERSION=$1

wget http://mirror.netcologne.de/apache.org/activemq/activemq-apollo/${APLO_VERSION}/apache-apollo-${APLO_VERSION}-unix-distro.tar.gz


tar -xzf apache-apollo-${APLO_VERSION}-unix-distro.tar.gz

./apache-apollo-${APLO_VERSION}/bin/apollo create apollo-stomp-php


cp travisci/conf/aplo/apollo.xml ./apollo-stomp-php/etc/apollo.xml

./apollo-stomp-php/bin/apollo-broker-service start