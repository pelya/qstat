<?php

require __DIR__ . '/vendor/autoload.php';
use Minishlink\WebPush\WebPush;

$dbpath = '/var/push-subscribers.db'

$key = file('/var/vapid-push-key.txt', FILE_IGNORE_NEW_LINES) or die('Cannot read VAPID key file');

// subject / public key / private key, each on a new line
$auth = array(
	'VAPID' => array(
		'subject' => $key[0],
		'publicKey' => $key[1],
		'privateKey' => $key[2], // in the real world, this would be in a secret file
	),
);

$pushOptions = array(
	'TTL' => 0, // defaults to 4 weeks
	'urgency' => 'normal', // protocol defaults to "normal"
	'topic' => 'OpenLieroX', // not defined by default
);

$webPush = new WebPush($auth, $pushOptions);


$db = new SQLite3($dbpath, SQLITE3_OPEN_READONLY) or die('Cannot open database');

$now = time();

$query = 'SELECT endpoint FROM subscribers WHERE updatetime < ' . strval($now) .
			' AND numplayers <= ' . $argv[2] .
			" AND servers LIKE '=" . $argv[1] .  "=';";

echo $query

$results = $db->query($query) or die('Cannot run SQN query');

while ($row = $results->fetchArray()) {
	$subscription = json_decode($res[0], true);
	$message = $argv[2] . ' players on server ' . $argv[3] . ' ' . $argv[1],
	if ($argv[2] == '1') {
		$message = $argv[2] . ' player on server ' . $argv[3] . ' ' . $argv[1],
	}

	$webPush->sendNotification(
		$subscription['endpoint'],
		$message,
		$subscription['key'],
		$subscription['token']
	);
}

$db->close();

$webPush->flush();

// handle eventual errors here, and remove the subscription from your server if it is expired
