#!/bin/bash

while true
do
    foo=1
    while [ "$foo" -le 300 ] 
    do
      ((foo++))
      ./toastagent
    done

   sleep 3m
   killall toastagent
   echo "hello"
done

exit 0
