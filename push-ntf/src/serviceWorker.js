self.addEventListener('notificationclick', function(event) {
	event.notification.close();
	clients.openWindow("openlierox://" + event.data.json().addr);
}, false);

self.addEventListener('push', function (event) {
	if (!(self.Notification && self.Notification.permission === 'granted')) {
		return;
	}

	const sendNotification = (msg) => {
		return self.registration.showNotification("OpenLieroX", {
			"body": msg,
			"icon": "https://liero.1337.cx/openlierox.png",
			"badge": "https://liero.1337.cx/openlierox-badge.png",
			"tag": "OpenLieroX",
		});
	};

	if (event.data) {
		event.waitUntil(sendNotification(event.data.json().msg));
	}
});
