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

	function syncUploadList(input) {
		var upload = input.closest('[data-poc-rtl-upload]');
		var list = upload ? upload.querySelector('[data-poc-rtl-upload-list]') : null;

		if (!list) {
			return;
		}

		list.innerHTML = '';

		Array.prototype.forEach.call(input.files || [], function (file) {
			var item = document.createElement('li');
			var size = file.size ? ' · ' + Math.ceil(file.size / 1024) + ' KB' : '';

			item.textContent = file.name + size;
			list.appendChild(item);
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

	document.addEventListener('change', function (event) {
		if (!event.target.matches('.poc-rtl-upload-input')) {
			return;
		}

		syncUploadList(event.target);
	});

	document.querySelectorAll('.poc-rtl-configurator').forEach(syncPanels);

	document.querySelectorAll('.poc-rtl-configurator').forEach(function (configurator) {
		var form = configurator.closest('form');

		if (form) {
			form.enctype = 'multipart/form-data';
		}
	});
}());
