#!/bin/sh

phpunit && ./node_modules/jasmine-node/bin/jasmine-node --coffee .
