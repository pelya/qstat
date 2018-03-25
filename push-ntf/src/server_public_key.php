<?php
// subject / public key / private key, each on a new line
$key = file('/var/vapid-push-key.txt', FILE_IGNORE_NEW_LINES) or die('Cannot read VAPID key file')
echo $key[1]
