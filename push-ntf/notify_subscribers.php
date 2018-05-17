<?php

require __DIR__ . '/vendor/autoload.php';
use Minishlink\WebPush\WebPush;

if (count($argv) < 4) {
	die("\nUsage: " . $argv[0] . " ServerIp NumPlayers ServerName\n");
}

$dbpath = '/var/lib/openlierox/push-subscribers.db';

$key = file('/var/lib/openlierox/vapid-push-key.txt', FILE_IGNORE_NEW_LINES) or die('Cannot read VAPID key file');

// subject / public key / private key, each on a new line
$auth = array(
	'VAPID' => array(
		'subject' => $key[0],
		'publicKey' => $key[1],
		'privateKey' => $key[2], // in the real world, this would be in a secret file
	),
);

$pushOptions = array(
	'TTL' => 3600, // 1 hour
	'urgency' => 'normal', // protocol defaults to "normal"
	'topic' => 'OpenLieroX', // not defined by default
	'batchSize' => 100, // defaults to 1000
);

$webPush = new WebPush($auth, $pushOptions);

$db = new SQLite3($dbpath) or die('Cannot open database');

$now = time();

$query = 'SELECT endpoint, key, token FROM subscribers WHERE updatetime < ' . strval($now) .
			' AND numplayers <= ' . $argv[2] .
			" AND servers LIKE '%=" . $argv[1] . "=%';";

echo $query;
echo "\n";

$results = $db->query($query) or die('Cannot run SQL query');

while ($row = $results->fetchArray()) {
	$message = $argv[2] . ' players on ' . $argv[3];
	if ($argv[2] == '1') {
		$message = $argv[2] . ' player on ' . $argv[3];
	}

	$query = "UPDATE subscribers SET updatetime = updateperiod + " . strval($now) .
				" WHERE endpoint = '" . $row[0] . "';";
	echo $query;
	echo "\n";
	$db->exec($query);

	$webPush->sendNotification(
		$row[0],
		$message,
		$row[1],
		$row[2],
		false,
		$pushOptions
	);
}

$results = $webPush->flush();

if ($results !== true) {
	if ($results !== false) {
		foreach ($results as $res) {
			if ($res['success']) {
				echo "Notification sent";
				echo "\n";
			} else {
				echo "Res: ";
				print_r($res);
				echo "\n";
				if (array_key_exists('expired', $res) && $res['expired']) {
					$query = "DELETE FROM subscribers WHERE endpoint = '" . $res['endpoint'] . "';";
					echo $query;
					echo "\n";
					$db->query($query) or die('Cannot run SQN query');
				} else {
					// Retry notification in 10 minutes
					$query = "UPDATE subscribers SET updatetime = " . strval($now + 600) .
						" WHERE endpoint = '" . $row[0] . "';";
					echo $query;
					echo "\n";
					$db->exec($query);
				}
			}
		}
	}
} else {
	echo "Notification sent";
	echo "\n";
}

// TODO: delete entries which expired by timeout

$db->close();
