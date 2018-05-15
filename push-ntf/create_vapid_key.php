<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ .'/vendor/minishlink/web-push/src/VAPID.php';
use Minishlink\WebPush;

$key = Minishlink\WebPush\VAPID::createVapidKeys();
print_r($key);
