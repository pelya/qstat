<?php
require __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\WebPush;

// here I'll get the subscription endpoint in the POST parameters
// but in reality, you'll get this information in your database
// because you already stored it (cf. push_subscription.php)
$subscription = json_decode(file_get_contents('php://input'), true);

// subject / public key / private key, each on a new line
$key = file('/var/vapid-push-key.txt', FILE_IGNORE_NEW_LINES) or die('Cannot read VAPID key file');
$auth = array(
    'VAPID' => array(
        'subject' => $key[0],
        'publicKey' => $key[1],
        'privateKey' => $key[2], // in the real world, this would be in a secret file
    ),
);

$webPush = new WebPush($auth);

$res = $webPush->sendNotification(
    $subscription['endpoint'],
    "Hello!",
    $subscription['key'],
    $subscription['token'],
    true
);

// handle eventual errors here, and remove the subscription from your server if it is expired
