self.addEventListener('push', function (event) {
	if (!(self.Notification && self.Notification.permission === 'granted')) {
		return;
	}

	const sendNotification = body => {
		// you could refresh a notification badge here with postMessage API
		const title = "OpenLieroX";

		return self.registration.showNotification(title, {
			body,
			"icon": "https://liero.1337.cx/openlierox.png",
			"tag": "OpenLieroX",
		});
	};

	if (event.data) {
		const message = event.data.text();
		event.waitUntil(sendNotification(message));
	}
});
