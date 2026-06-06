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

	function markConfiguratorLayout(configurator) {
		var form = configurator.closest('form');

		if (form) {
			form.enctype = 'multipart/form-data';
			form.classList.add('poc-rtl-cart-form');

			var summary = form.closest('.summary');
			var product = form.closest('.product');

			if (summary) {
				summary.classList.add('pocrtl-summary-panel');
			}

			if (product) {
				product.classList.add('pocrtl-product-layout');
			}
		}

		document.body.classList.add('pocrtl-active-product');

		var productColumn = configurator.closest('.col-xl-6, .col-lg-6, .summary, .entry-summary');
		var actionQuantity = configurator.closest('.tp-product-details-quantity');
		var actionWrapper = configurator.closest('.tp-product-details-action-wrapper');

		if (productColumn) {
			productColumn.classList.add('pocrtl-product-column');

			var productRow = productColumn.parentElement;

			if (productRow) {
				productRow.querySelectorAll('.col-xl-6, .col-lg-6, .col-md-6').forEach(function (column) {
					if (column !== productColumn) {
						column.classList.add('pocrtl-gallery-column');
					}
				});
			}
		}

		if (actionQuantity) {
			actionQuantity.classList.add('pocrtl-configurator-width-host');
		}

		if (actionWrapper) {
			actionWrapper.classList.add('pocrtl-action-wrapper');
		}
	}

	function markAllConfiguratorLayouts() {
		document.querySelectorAll('.poc-rtl-configurator').forEach(markConfiguratorLayout);
	}

	markAllConfiguratorLayouts();

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', markAllConfiguratorLayouts);
	} else {
		window.setTimeout(markAllConfiguratorLayouts, 0);
	}
}());
