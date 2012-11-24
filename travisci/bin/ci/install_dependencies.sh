#!/bin/bash

AMQ_VERSION="5.7.0"
wget http://mirror.synyx.de/apache/activemq/apache-activemq/${AMQ_VERSION}/apache-activemq-${AMQ_VERSION}-bin.tar.gz
tar -xzf apache-activemq-${AMQ_VERSION}-bin.tar.gz

cp travisci/conf/amq/activemq.xml apache-activemq-${AMQ_VERSION}/conf/activemq.xml

apache-activemq-${AMQ_VERSION}/bin/linux-x86-32/activemq start

