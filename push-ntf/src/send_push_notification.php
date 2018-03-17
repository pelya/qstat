<?php
require __DIR__ . '/../vendor/autoload.php';
use Minishlink\WebPush\WebPush;

// here I'll get the subscription endpoint in the POST parameters
// but in reality, you'll get this information in your database
// because you already stored it (cf. push_subscription.php)
$subscription = json_decode(file_get_contents('php://input'), true);

$auth = array(
    'VAPID' => array(
        'subject' => 'https://liero.1337.cx/push/src',
        'publicKey' => 'BJsrrAM9yRUHEkbhS-CaMhCTK9uQf0zuKPAns_4GCMmr2SqSsVgjyVrJW5jz95kC0e207RTWPrls-ni6u1os6wY',
        'privateKey' => 'SOFaLVAi7OxHU4uK22IewfboqEz9sW7kdtQrmtzOfJ4', // in the real world, this would be in a secret file
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
