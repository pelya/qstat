#!/bin/sh

git push
#scp -r . [2607:fcd0:100:1903::5da2:f18e]:qstat/push-ntf/
ssh 2607:fcd0:100:1903::5da2:f18e git -C qstat pull
