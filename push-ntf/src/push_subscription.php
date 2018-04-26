<?php
$subscription = json_decode(file_get_contents('php://input'), true);

if (!isset($subscription['endpoint']) ||
	!isset($subscription['servers'])) {
	echo 'Error: not a subscription';
	return;
}

$dbpath = '/var/push-subscribers.db';

$db = new SQLite3($dbpath) or die('Cannot open database');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
	case 'POST':
		// create a new subscription entry in your database (endpoint is unique)
		// $subscription['servers']
		break;
	case 'PUT':
		// update the key and token of subscription corresponding to the endpoint
		break;
	case 'DELETE':
		// delete the subscription corresponding to the endpoint
		break;
	default:
		echo "Error: method not handled";
		break;
}

$db->close() or die('Cannot write changes to the database');
