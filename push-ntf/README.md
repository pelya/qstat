# Web Push notifier for your OpenLieroX masterserver

## Requirements
- Apache with mod_php and HTTPS configured with SSL certificate - notifications will not work without full HTTPS.
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

After that, replace `https://liero.1337.cx/` inside `serviceWorker.js` with the URL of your own website.

