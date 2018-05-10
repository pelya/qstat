<?php
$subscription = json_decode(file_get_contents('php://input'), true);

if (!isset($subscription['endpoint']) ||
	!isset($subscription['servers']) ||
	!isset($subscription['key']) ||
	!isset($subscription['token'])) {
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
					"updatetime, updateperiod, expiretime, numplayers, servers) VALUES (" .
					"'" . $subscription['endpoint'] . "', '" . $subscription['key'] . "', '" . $subscription['token'] . "', " .
					"0, 82800, " . strval($now + 2592000) . ", 1, '" . $subscription['servers'] . "');";
		echo $query;
		echo "\n";
		$db->query($query);
		break;
	case 'PUT':
		// update the key and token of subscription corresponding to the endpoint
		$query = "UPDATE subscribers SET key = '" . $subscription['key'] . "', " .
					"token = '" . $subscription['token'] . "', " .
					"updatetime = updateperiod + " . strval($now) . ", " .
					"servers = '" . $subscription['servers'] . "' " .
					"WHERE endpoint = '" . $subscription['endpoint'] . "';";
		echo $query;
		echo "\n";
		$db->query($query);
		break;
	case 'DELETE':
		// delete the subscription corresponding to the endpoint
		$query = "DELETE FROM subscribers WHERE endpoint = '" . $subscription['endpoint'] . "';";
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
