#!/bin/sh
while inotifywait -e modify -e create ../dados/produtos; do
    print "test"; 
done
