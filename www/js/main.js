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

/**
 * Check, if value can be converted to number
 *
 * @param {*} numeric
 * @returns {boolean}
 */
function isNumeric(numeric) {
	if (Array.isArray(numeric)) {
		return false;
	}
	return !isNaN(parseFloat(numeric)) && isFinite(numeric);
}

/*!
 * Settings
 * Keep settings in localStorage
 */
(function (window) {
	const prefix = 'better-location-';

	// Defined settings with default values
	let settingsValues = {
		dynamicMapBaseLayer: 'OSM default',
	};
	const Settings = {
		/**
		 * Return saved (or default) value from localstorage
		 *
		 * @param {string} name
		 */
		load: function (name) {
			// return all values
			if (typeof name !== 'string') {
				throw new Error('Param "name" has to be string.');
			}
			// return specific value
			let value = settingsValues[name];
			if (typeof value === 'undefined') {
				throw new Error('Settings value with name "' + name + '" is not defined.');
			}
			if (isNumeric(value)) {
				return parseInt(value);
			} else if (value === 'true') {
				return true;
			} else if (value === 'false') {
				return false;
			}
			try {
				return JSON.parse(value)
			} catch (error) {
				// do nothing, probably is not JSON
			}
			return value;
		},

		/**
		 * Save value into localstorage
		 *
		 * @param {string} name
		 * @param {string|int|boolean} value
		 * @returns {string|int|boolean}
		 */
		save: function (name, value) {
			if (typeof name === 'undefined' || typeof value === 'undefined') {
				throw new Error('Settings.save() require two parameters.');
			}
			if (typeof settingsValues[name] === 'undefined') {
				throw new Error('Settings with name "' + name + '" does not exists, cant save.')
			}
			// is Array or JSON
			if (Array.isArray(value) || value && value.constructor === ({}).constructor) {
				value = JSON.stringify(value);
			}
			settingsValues[name] = value;
			localStorage.setItem(prefix + name, value);
		},
	};

	// Save all settings to localStorage if not saved before
	for (const settingsName in settingsValues) {
		let savedValue = localStorage.getItem(prefix + settingsName);
		if (savedValue) {
			settingsValues[settingsName] = savedValue;
		} else {
			console.log('Settings "' + settingsName + '"  was not saved. Saved with default value "' + settingsValues[settingsName] + '"');
			Settings.save(settingsName, settingsValues[settingsName])
		}
	}

	window.Settings = Settings;
})(window);
