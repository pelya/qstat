document.addEventListener("DOMContentLoaded", () => {
	const serverListDiv = document.querySelector('#server-list');
	if (!serverListDiv) {
		return;
	}
	const serverListArray = getServerList();
	for (var i = 0, len = serverListArray.length; i < len; i++) {
		var checkbox = document.createElement("input");
		checkbox.setAttribute("id", "server-" + i.toString());
		checkbox.setAttribute("type", "checkbox");
		checkbox.setAttribute("value", serverListArray[i][0]);
		checkbox.setAttribute("checked", "1");
		checkbox.disabled = true;
		checkbox.onclick = push_updateSubscription;
		serverListDiv.appendChild(document.createTextNode("\u00A0\u00A0"));
		serverListDiv.appendChild(checkbox);
		serverListDiv.appendChild(document.createTextNode("\u00A0\u00A0\u00A0\u00A0" + serverListArray[i][1] + " (" + serverListArray[i][0] + ")"));
		serverListDiv.appendChild(document.createElement("br"));
		serverListDiv.appendChild(document.createElement("br"));
	}
	for (let i = 1; i <= 4; i++) {
		const elem = document.querySelector('#numplayers-' + i.toString());
		elem.onclick = push_updateSubscription;
	}
	for (let i = 0; i <= 4; i++) {
		const elem = document.querySelector('#updateperiod-' + i.toString());
		elem.onclick = push_updateSubscription;
	}

	const applicationServerKey = getServerPublicKey();
	let isPushEnabled = false;

	const pushButton = document.querySelector('#push-subscription-button');
	if (!pushButton) {
		return;
	}

	pushButton.addEventListener('click', function() {
		if (isPushEnabled) {
			push_unsubscribe();
		} else {
			push_subscribe();
		}
	});

	if (!('serviceWorker' in navigator)) {
		console.warn("Service workers are not supported by this browser");
		changePushButtonState('incompatible');
		return;
	}

	if (!('PushManager' in window)) {
		console.warn('Push notifications are not supported by this browser');
		changePushButtonState('incompatible');
		return;
	}

	if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
		console.warn('Notifications are not supported by this browser');
		changePushButtonState('incompatible');
		return;
	}

	// Check the current Notification permission.
	// If its denied, the button should appears as such, until the user changes the permission manually
	if (Notification.permission === 'denied') {
		console.warn('Notifications are denied by the user');
		changePushButtonState('incompatible');
		return;
	}

	navigator.serviceWorker.register("serviceWorker.js")
	.then(() => {
		console.log('[SW] Service worker has been registered');
		push_updateSubscription();
	}, e => {
		console.error('[SW] Service worker registration failed', e);
		changePushButtonState('incompatible');
	});

	function changePushButtonState (state) {
		switch (state) {
			case 'enabled':
				pushButton.textContent = "Disable Push notifications";
				isPushEnabled = true;
				push_enableControls(true);
				break;
			case 'disabled':
				pushButton.textContent = "Enable Push notifications";
				isPushEnabled = false;
				push_enableControls(true);
				break;
			case 'computing':
				pushButton.textContent = "Loading...";
				push_enableControls(false);
				break;
			case 'incompatible':
				pushButton.textContent = "Push notifications are not compatible with this browser";
				push_enableControls(false);
				break;
			default:
				console.error('Unhandled push button state', state);
				push_enableControls(false);
				break;
		}
	}

	function urlBase64ToUint8Array(base64String) {
		const padding = '='.repeat((4 - base64String.length % 4) % 4);
		const base64 = (base64String + padding)
			.replace(/\-/g, '+')
			.replace(/_/g, '/');

		const rawData = window.atob(base64);
		const outputArray = new Uint8Array(rawData.length);

		for (let i = 0; i < rawData.length; ++i) {
			outputArray[i] = rawData.charCodeAt(i);
		}
		return outputArray;
	}

	function push_subscribe() {
		changePushButtonState('computing');

		navigator.serviceWorker.ready
		.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.subscribe({
			userVisibleOnly: true,
			applicationServerKey: urlBase64ToUint8Array(applicationServerKey),
		}))
		.then(subscription => {
			 // Subscription was successful
			// create subscription on your server
			return push_sendSubscriptionToServer(subscription, 'POST');
		})
		.then(subscription => subscription && changePushButtonState('enabled')) // update your UI
		.catch(e => {
			if (Notification.permission === 'denied') {
				// The user denied the notification permission which
				// means we failed to subscribe and the user will need
				// to manually change the notification permission to
				// subscribe to push messages
				console.warn('Notifications are denied by the user.');
				changePushButtonState('incompatible');
			} else {
				// A problem occurred with the subscription; common reasons
				// include network errors or the user skipped the permission
				console.error('Impossible to subscribe to push notifications', e);
				changePushButtonState('disabled');
			}
		});
	}

	function push_updateSubscription() {
		navigator.serviceWorker.ready.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
		.then(subscription => {
			changePushButtonState('disabled');

			if (!subscription) {
				// We aren't subscribed to push, so set UI to allow the user to enable push
				return;
			}

			// Keep your server in sync with the latest endpoint
			return push_sendSubscriptionToServer(subscription, 'PUT');
		})
		.then(subscription => subscription && changePushButtonState('enabled')) // Set your UI to show they have subscribed for push messages
		.catch(e => {
			console.error('Error when updating the subscription', e);
		});
	}

	function push_unsubscribe() {
		changePushButtonState('computing');

		// To unsubscribe from push messaging, you need to get the subscription object
		navigator.serviceWorker.ready
		.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
		.then(subscription => {
			// Check that we have a subscription to unsubscribe
			if (!subscription) {
				// No subscription object, so set the state
				// to allow the user to subscribe to push
				changePushButtonState('disabled');
				return;
			}

			// We have a subscription, unsubscribe
			// Remove push subscription from server
			return push_sendSubscriptionToServer(subscription, 'DELETE');
		})
		.then(subscription => subscription.unsubscribe())
		.then(() => changePushButtonState('disabled'))
		.catch(e => {
			// We failed to unsubscribe, this can lead to
			// an unusual state, so  it may be best to remove
			// the users data from your data store and
			// inform the user that you have done so
			console.error('Error when unsubscribing the user', e);
			changePushButtonState('disabled');
		});
	}

	function push_sendSubscriptionToServer(subscription, method) {
		const key = subscription.getKey('p256dh');
		const token = subscription.getKey('auth');
		let serverListDb = "";
		let numplayers = 2;
		const updateperiodTable = [ 3600, 3600 * 3, 3600 * 6, 3600 * 23, 3600 * 71 ];
		let updateperiod = updateperiodTable[3];

		for (let i = 0; ; i++) {
			const server = document.querySelector('#server-' + i.toString());
			if (!server || !server.hasAttribute("checked")) {
				break;
			}
			serverListDb += "=" + server.getAttribute("value") + "=";
		}

		for (let i = 1; i <= 4; i++) {
			const elem = document.querySelector('#numplayers-' + i.toString());
			if (!elem || !elem.hasAttribute("checked")) {
				break;
			}
			numplayers = i;
		}

		for (let i = 0; i <= 4; i++) {
			const elem = document.querySelector('#updateperiod-' + i.toString());
			if (!elem || !elem.hasAttribute("checked")) {
				break;
			}
			updateperiod = updateperiodTable[i];
		}

		return fetch('push_subscription.php', {
			method,
			body: JSON.stringify({
				endpoint: subscription.endpoint,
				key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : "",
				token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : "",
				servers: serverListDb,
				numplayers: numplayers,
				updateperiod: updateperiod,
			}),
		}).then(() => subscription);
	}

	function push_enableControls(enable) {
		pushButton.disabled = !enable;
		for (let i = 0; ; i++) {
			const elem = document.querySelector('#server-' + i.toString());
			if (!elem) {
				break;
			}
			elem.disabled = !enable;
		}
		for (let i = 1; i <= 4; i++) {
			const elem = document.querySelector('#numplayers-' + i.toString());
			elem.disabled = !enable;
		}
		for (let i = 0; i <= 4; i++) {
			const elem = document.querySelector('#updateperiod-' + i.toString());
			elem.disabled = !enable;
		}
	}
});
