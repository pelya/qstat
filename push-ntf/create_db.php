<?php
$db = new SQLite3('/var/push-subscribers.db') or die('Cannot create database, run as root');

$db->exec('CREATE TABLE subscribers (endpoint TEXT PRIMARY KEY NOT NULL, time INTEGER NOT NULL, servers TEXT, players INTEGER)') or die('Cannot create table, run as root');

$db->close() or die('Cannot write changes, run as root');

chown('/var/push-subscribers.db', 'www-data');
chgrp('/var/push-subscribers.db', 'munin');
chmod('/var/push-subscribers.db', 0664);

echo "Database is created at /var/push-subscribers.db";
echo "Using 'www-data' as file owner, adjust file permissions if necessary to match apache2 user name";
