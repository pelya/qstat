<?php
$subscription = json_decode(file_get_contents('php://input'), true);

if (!isset($subscription['endpoint']) ||
	!isset($subscription['servers']) ||
	!isset($subscription['numplayers']) ||
	!isset($subscription['updateperiod']) ||
	!isset($subscription['key']) ||
	!isset($subscription['token']) ||
	intval($subscription['numplayers']) < 1 ||
	intval($subscription['updateperiod']) < 3600) {
	echo 'Error: not a subscription';
	return;
}

$dbpath = '/var/lib/openlierox/push-subscribers.db';

$db = new SQLite3($dbpath) or die('Cannot open database');

$method = $_SERVER['REQUEST_METHOD'];
$now = time();

echo 'Current script owner: ' . get_current_user();
echo "\n";
echo 'DB path: ' . $dbpath;
echo "\n";
$processUser = posix_getpwuid(posix_geteuid());
echo 'Current process UID: ' . $processUser['name'];
echo "\n";

switch ($method) {
	case 'POST':
		// create a new subscription entry in your database (endpoint is unique)
		$query = "DELETE FROM subscribers WHERE endpoint = '" . $subscription['endpoint'] . "'; \n" .
					"INSERT INTO subscribers (endpoint, key, token, " .
					"updatetime, updateperiod, expiretime, numplayers, servers) VALUES ('" .
					SQLite3::escapeString($subscription['endpoint']) . "', '" .
					SQLite3::escapeString($subscription['key']) . "', '" .
					SQLite3::escapeString($subscription['token']) . "', 0, " .
					strval(intval($subscription['updateperiod'])) . ", " .
					strval($now + 2592000) . ", " .
					strval(intval($subscription['numplayers'])) . ", '" .
					SQLite3::escapeString($subscription['servers']) . "');";
		echo $query;
		echo "\n";
		$db->query($query);
		break;
	case 'PUT':
		// update the key and token of subscription corresponding to the endpoint
		$query = "UPDATE subscribers SET key = '" .
					SQLite3::escapeString($subscription['key']) . "', token = '" .
					SQLite3::escapeString($subscription['token']) . "', updateperiod = " .
					strval(intval($subscription['updateperiod'])) . ", servers = '" .
					SQLite3::escapeString($subscription['servers']) . "', numplayers = " .
					strval(intval($subscription['numplayers'])) . ", expiretime = " .
					strval($now + 2592000) . " WHERE endpoint = '" .
					SQLite3::escapeString($subscription['endpoint']) . "';";
		echo $query;
		echo "\n";
		$db->query($query);
		break;
	case 'DELETE':
		// delete the subscription corresponding to the endpoint
		$query = "DELETE FROM subscribers WHERE endpoint = '" .
					SQLite3::escapeString($subscription['endpoint']) . "';";
		echo $query;
		echo "\n";
		$db->query($query);
		break;
	default:
		echo "Error: method not handled";
		echo "\n";
		break;
}

$db->close() or die('Cannot write changes to the database');
