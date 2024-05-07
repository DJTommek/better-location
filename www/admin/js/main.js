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

	// Copy beautified JSON log to clipboard
	$(document).on("click", '.copy-to-clipboard-json-log', function () {
		const element = this;
		const logLine = $(this).data('clipboard-text');
		const jsonBeautified = JSON.stringify(logLine['content'], null, '\t');

		if (ClipboardJS.copy(jsonBeautified)) {
			$(element).next('span').show();
			setTimeout(function () {
				$(element).next('span').hide();
			}, 1000);
			return;
		}

		alert('Error: Nothing was added to clipboard.');
	});

	// Enable Bootstrap tooltips everywhere
	$(function () {
		$('[data-toggle="tooltip"]').tooltip();
	});
});
