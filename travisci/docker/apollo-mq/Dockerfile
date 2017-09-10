FROM java:8

MAINTAINER Jens Radtke <swefl.oss@fin-sn.de>

COPY apollo.xml /config/apollo.xml
COPY setup.sh /tmp/setup.sh

RUN /tmp/setup.sh && rm /tmp/setup.sh


EXPOSE 61020

ENTRYPOINT ["/opt/apollomq/apollo-stomp-php/bin/apollo-broker", "run"]