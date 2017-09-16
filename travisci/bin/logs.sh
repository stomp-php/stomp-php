#!/usr/bin/env bash

docker logs -f -t stomp-activemq &
docker logs -f -t stomp-rabbitmq &
docker logs -f -t stomp-apollomq &