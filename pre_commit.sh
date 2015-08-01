#!/usr/bin/env bash

TEXT_RESET="\e[0m"
TEXT_BOLD="\e[1;30m"
TEXT_RED="\e[1;31m"
TEXT_GREEN="\e[1;32m"

printf "${TEXT_BOLD}PHPCS...${TEXT_RESET}"
sh run_phpcs.sh

if [ $? -ne 0 ]; then
    printf "${TEXT_RED}FAIL${TEXT_RESET}\n"
    exit 1
else
    printf "${TEXT_GREEN}OK${TEXT_RESET}\n"
fi


# run_phpcpd.sh ?
# run_tests_unit.sh
