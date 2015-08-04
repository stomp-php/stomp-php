#!/usr/bin/env bash

TEXT_RESET="\e[0m"
TEXT_BOLD="\e[1;30m"
TEXT_RED="\e[1;31m"
TEXT_GREEN="\e[1;32m"

function shc {
    printf "${TEXT_BOLD}${1}${TEXT_RESET}"
    sh $2

    if [ $? -ne 0 ]; then
        printf "${TEXT_RED}FAIL${TEXT_RESET}\n"
        exit 1
    else
        printf "${TEXT_GREEN}OK${TEXT_RESET}\n"
    fi
}

shc "PHPCS..." run_phpcs.sh
shc "PHPUnit...\n" run_phpunit.sh

# run_phpcpd.sh
