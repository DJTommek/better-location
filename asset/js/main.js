$(function () {
	/**
	 * Keep selected tab on page refresh
	 * @author https://stackoverflow.com/a/19015027/3334403
	 */
	$('#main-tab a').click(function (e) {
		e.preventDefault();
		$(this).tab('show');
	});
	// store the currently selected tab in the hash value
	$("ul.nav-tabs > li > a").on("shown.bs.tab", function (e) {
		// Update hash without scrolling to it's ID
		// @author https://stackoverflow.com/a/14560718/3334403
		history.pushState({}, '', '#' + $(e.target).attr("href").substr(1));
	});
	// on load of the page: switch to the currently selected tab
	const hash = window.location.hash;
	$('#main-tab a[href="' + hash + '"]').tab('show');

	$(document).on("click", '.copy-log-content', function (e) {
		const element = this;
		const logLine = $(this).data('to-copy');

		// const contentToCopy = $(e.target).data('to-copy');
		if (copyToClipboard(JSON.stringify(logLine['content'], null, '\t'))) {
			$(element).next('span').show();
			setTimeout(function () {
				$(element).next('span').hide();
			}, 1000);
		} else {
			alert('Error: Nothing was added to clipboard.');
		}
	});

	// Enable Bootstrap tooltips everywhere
	$(function () {
		$('[data-toggle="tooltip"]').tooltip();
	});
});

/**
 * Copy text into clipboard.
 * Currently there is no javascript API to put text into clipboard so we have to create input text element and run command "copy"
 *
 * @author https://www.w3schools.com/howto/howto_js_copy_clipboard.asp
 * @param {string} text
 * @returns {boolean} true on success, false otherwise
 */
function copyToClipboard(text) {
	let inputDom = document.createElement('pre'); // <pre> to respect whitespace characters
	// element can't be hidden (display: none), select() wouldn't work, but can be out of viewport
	inputDom.setAttribute('style', 'display: block; position: absolute; bottom: -9999em; right: -9999em; color: transparent');
	document.body.appendChild(inputDom);
	inputDom.innerHTML = text;

	// create selection  of HTML element https://stackoverflow.com/a/6150060/3334403
	const range = document.createRange();
	range.selectNodeContents(inputDom);
	const selection = window.getSelection();
	selection.removeAllRanges();
	selection.addRange(range);

	const success = document.execCommand("copy");
	inputDom.parentNode.removeChild(inputDom);
	return success;
}
