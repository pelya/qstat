self.addEventListener('push', function (event) {
	if (!(self.Notification && self.Notification.permission === 'granted')) {
		return;
	}

	const sendNotification = (body, address) => {
		let status = self.registration.showNotification("OpenLieroX", {
			body,
			"icon": "https://liero.1337.cx/openlierox.png",
			"badge": "https://liero.1337.cx/openlierox-badge.png",
			"tag": "OpenLieroX",
		});
		self.addEventListener('notificationclick', function(event) {
			event.notification.close();
			clients.openWindow("openlierox://" + address);
		}, false);
		return status;
	};

	if (event.data) {
		const message = JSON.parse(event.data.text());
		event.waitUntil(sendNotification(message.msg, message.addr));
	}
});
