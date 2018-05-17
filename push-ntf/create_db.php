<?php

mkdir('/var/lib/openlierox');
chown('/var/lib/openlierox', 'www-data');
chgrp('/var/lib/openlierox', 'munin');
chmod('/var/lib/openlierox', 0775);

$dbpath = '/var/lib/openlierox/push-subscribers.db';

unlink($dbpath);

$db = new SQLite3($dbpath) or die('Cannot create database, run as root');

$db->exec('CREATE TABLE subscribers (endpoint TEXT PRIMARY KEY NOT NULL, key TEXT NOT NULL, token TEXT NOT NULL, ' .
			'updatetime INTEGER NOT NULL, updateperiod INTEGER NOT NULL, expiretime INTEGER NOT NULL, ' .
			'numplayers INTEGER NOT NULL, silent INTEGER NOT NULL, servers TEXT NOT NULL);') or die('Cannot create table, run as root');

$db->close() or die('Cannot write changes, run as root');

chown($dbpath, 'www-data');
chgrp($dbpath, 'munin');
chmod($dbpath, 0664);

echo "\n";
echo "Database is created at " . $dbpath;
echo "\n";
echo "Using 'www-data' as file owner, adjust file permissions if necessary to match apache2 user name";
echo "\n";
