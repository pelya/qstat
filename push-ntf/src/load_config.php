<?php
// subject / public key / private key, each on a new line
$key = file('/var/lib/openlierox/vapid-push-key.txt', FILE_IGNORE_NEW_LINES) or die('Cannot read VAPID key file');
echo 'function getServerPublicKey() { return "' . $key[1] . '"; }';
echo "\n";

$serverListRaw = file('/run/shm/openlierox.log', FILE_IGNORE_NEW_LINES) or die('Cannot read server list');

echo 'function getServerList() { return new Array(';

$serverList = array();
$first = true;
foreach ($serverListRaw as $s) {
	$a = explode(' ', $s, 8);
	array_push($serverList, array($a[1], $a[7]));
	if (!$first) {
		echo  ', ';
	}
	$first = false;
	echo  '["' . $a[1] . '", "' . $a[7] . '"]';
}

echo  '); }';
echo "\n";
