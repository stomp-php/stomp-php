#!/usr/bin/env bash

./vendor/bin/phpmd src text cleancode,codesize,controversial,design,naming,unusedcode

# Available formats: xml, text, html.
# Available rulesets: cleancode,codesize,controversial,design,naming,unusedcode.
