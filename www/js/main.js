window.onload = function () {
	if (ClipboardJS.isSupported()) {
		const clipboard = new ClipboardJS('.copy-to-clipboard');
		clipboard.on('success', function (event) { // temporary change icon to checkmark if copied successfully
			const classList = event.trigger.classList;
			if (classList.contains('fa-clipboard')) {
				classList.add('fa-check');
				classList.remove('fa-clipboard');
				setTimeout(function () {
					classList.add('fa-clipboard');
					classList.remove('fa-check');
				}, 1000);
			}
		});

		clipboard.on('error', function (event) {
			window.prompt('Error while copying text, you have to copy it manually:', event.text);
		});
	}
};
