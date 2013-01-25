#!/bin/bash
foo=1
while [ "$foo" -le 100 ] 
do
  ((foo++))
  ./toastagentnoheartbeat &
done

exit 0
