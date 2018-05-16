self.addEventListener('push', function (event) {
	if (!(self.Notification && self.Notification.permission === 'granted')) {
		return;
	}

	const sendNotification = body => {
		return self.registration.showNotification("OpenLieroX", {
			body,
			"icon": "https://liero.1337.cx/openlierox.png",
			"badge": "https://liero.1337.cx/openlierox-badge.png",
			"tag": "OpenLieroX",
		});
	};

	if (event.data) {
		const message = event.data.text();
		event.waitUntil(sendNotification(message));
	}
});
