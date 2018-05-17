self.addEventListener('push', function (event) {
	if (!(self.Notification && self.Notification.permission === 'granted')) {
		return;
	}

	const sendNotification = (msg, addr) => {
		console.log('sendNotification: msg ' + msg);
		console.log('sendNotification: addr ' + addr);
		let status = self.registration.showNotification("OpenLieroX", {
			"body": msg,
			"icon": "https://liero.1337.cx/openlierox.png",
			"badge": "https://liero.1337.cx/openlierox-badge.png",
			"tag": "OpenLieroX",
		});
		self.addEventListener('notificationclick', function(event) {
			event.notification.close();
			clients.openWindow("openlierox://" + addr);
		}, false);
		return status;
	};

	if (event.data) {
		const data = JSON.parse(event.data.text());
		console.log('event.data.text(): ' + event.data.text());
		console.log('data:');
		console.log(data);
		console.log('data.msg:');
		console.log(data.msg);
		console.log('data.addr:');
		console.log(data.addr);
		event.waitUntil(sendNotification(data.msg, data.addr));
	}
});
