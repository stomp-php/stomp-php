#!/bin/bash

function waitForService()
{
    ATTEMPTS=0
    until nc -z $1 $2; do
        printf "wait for service %s:%s\n" $1 $2
        ((ATTEMPTS++))
        if [ $ATTEMPTS -ge $3 ]; then
            printf "service is not running %s:%s\n" $1 $2
            exit 1
        fi
        if [ "$FORCE_EXIT" = true ]; then
            exit;
        fi

        sleep 1
    done

    printf "service is online %s:%s\n" $1 $2
}

waitForService 127.0.0.1 61010 50
waitForService 127.0.0.1 61020 50
waitForService 127.0.0.1 61030 50
waitForService 127.0.0.1 61040 50
