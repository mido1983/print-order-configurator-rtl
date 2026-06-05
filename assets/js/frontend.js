(function () {
	'use strict';

	function syncPanels(configurator) {
		var checked = configurator.querySelector('input[name="poc_rtl[design_mode]"]:checked');
		var mode = checked ? checked.value : 'ready';
		var panels = configurator.querySelectorAll('[data-poc-rtl-panel]');

		panels.forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-poc-rtl-panel') !== mode;
		});
	}

	document.addEventListener('change', function (event) {
		if (!event.target.matches('input[name="poc_rtl[design_mode]"]')) {
			return;
		}

		var configurator = event.target.closest('.poc-rtl-configurator');

		if (configurator) {
			syncPanels(configurator);
		}
	});

	document.querySelectorAll('.poc-rtl-configurator').forEach(syncPanels);
}());
