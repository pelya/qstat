<?php
$subscription = json_decode(file_get_contents('php://input'), true);

if (!isset($subscription['endpoint']) ||
	!isset($subscription['servers']) ||
	!isset($subscription['numplayers']) ||
	!isset($subscription['updateperiod']) ||
	!isset($subscription['key']) ||
	!isset($subscription['token']) ||
	!isset($subscription['silent']) ||
	intval($subscription['numplayers']) < 1 ||
	intval($subscription['updateperiod']) < 3600 ||
	intval($subscription['silent']) < 0) {
	echo 'Error: not a subscription';
	return;
}

$dbpath = '/var/lib/openlierox/push-subscribers.db';

$db = new SQLite3($dbpath) or die('Cannot open database');

$method = $_SERVER['REQUEST_METHOD'];
$now = time();

switch ($method) {
	case 'POST':
	case 'PUT':
		// Create a new subscription entry in your database (endpoint is unique)
		// Replace existing subscription if it already exists
		$query = "INSERT OR REPLACE INTO subscribers (endpoint, key, token, " .
					"updatetime, updateperiod, expiretime, numplayers, silent, servers) VALUES ('" .
					SQLite3::escapeString($subscription['endpoint']) . "', '" .
					SQLite3::escapeString($subscription['key']) . "', '" .
					SQLite3::escapeString($subscription['token']) . "', 0, " .
					strval(intval($subscription['updateperiod'])) . ", " .
					strval($now + 2592000) . ", " .
					strval(intval($subscription['numplayers'])) . ", " .
					strval(intval($subscription['silent'])) .  ", '" .
					SQLite3::escapeString($subscription['servers']) . "');";
		echo str_replace(",", ",\n", $query);
		echo "\n";
		$db->query($query);
		break;
	case 'DELETE':
		// Delete the subscription corresponding to the endpoint
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
