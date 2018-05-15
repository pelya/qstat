# Web Push notifier for your OpenLieroX masterserver

## Requirements
- Chrome or Firefox
- PHP 7
    - sqlite3
    - gmp
    - mbstring
    - curl
    - openssl

## Installation

    sudo php ./create_db.php
    sudo rm -rf /var/www/html
    sudo ln -s `pwd`/src /var/www/html

# Generate VAPID key

    sudo cp vapid-push-key.txt /var/lib/openlierox/vapid-push-key.txt
    php ./create_vapid_key.php

Then edit /var/lib/openlierox/vapid-push-key.txt and put new public key, private key, and subject into this file.

