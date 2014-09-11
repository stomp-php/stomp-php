#!/bin/bash

AMQ_VERSION="5.10.0"
wget http://mirror.netcologne.de/apache.org/activemq/${AMQ_VERSION}/apache-activemq-${AMQ_VERSION}-bin.tar.gz
tar -xzf apache-activemq-${AMQ_VERSION}-bin.tar.gz

cp travisci/conf/amq/activemq.xml apache-activemq-${AMQ_VERSION}/conf/activemq.xml

pwd
dir -l ./apache-activemq-${AMQ_VERSION}/bin/linux-x86-32/
./apache-activemq-${AMQ_VERSION}/bin/linux-x86-32/activemq start

