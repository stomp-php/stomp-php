#!/usr/bin/env bash

VERSION=$1

docker run --name stomp-apollomq -d --rm -p 61020:61020 finsn/stomp-apollo:${VERSION}