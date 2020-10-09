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
		window.location.hash = $(e.target).attr("href").substr(1);
	});
	// on load of the page: switch to the currently selected tab
	const hash = window.location.hash;
	$('#main-tab a[href="' + hash + '"]').tab('show');
});
