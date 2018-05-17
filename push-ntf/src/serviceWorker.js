
self.addEventListener('push', function (event) {
	if (!(self.Notification && self.Notification.permission === 'granted')) {
		return;
	}

	const sendNotification = (msg, addr) => {
		console.warn('sendNotification: msg ' + msg + " addr " + addr);
		return self.registration.showNotification("OpenLieroX", {
			"body": msg,
			"icon": "https://liero.1337.cx/openlierox.png",
			"badge": "https://liero.1337.cx/openlierox-badge.png",
			"tag": "OpenLieroX",
			"data": addr,
		});
	};

	if (event.data) {
		const data = event.data.json();
		event.waitUntil(sendNotification(data.msg, data.addr));
	}
});

self.addEventListener('notificationclick', function(event) {
	console.warn('notificationclick:');
	console.warn(event);
	console.warn('notificationclick: event.notification.data:');
	console.warn(event.notification.data);
	event.notification.close();
	event.waitUntil(clients.openWindow("openlierox://" + event.notification.data));
}, false);
