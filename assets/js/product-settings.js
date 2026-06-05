(function () {
	'use strict';

	function syncEmptyState(group) {
		var rows = group.querySelectorAll('.poc-rtl-repeatable-row');
		var empty = group.querySelector('[data-poc-rtl-repeatable-empty]');

		if (empty) {
			empty.hidden = rows.length > 0;
		}
	}

	var presets = {
		business_cards: {
			sizes: ['90x50 mm', '85x55 mm'],
			quantities: ['100', '250', '500', '1000'],
			paper_types: ['כרומו', 'נטול עץ'],
			paper_weights: ['300 גרם', '350 גרם'],
			print_sides: ['צד אחד', 'דו צדדי'],
			lamination: ['ללא', 'מט', 'מבריק'],
			corners: ['רגילות', 'מעוגלות'],
			finishing_options: ['חיתוך רגיל']
		},
		flyers: {
			sizes: ['A6', 'A5', 'A4'],
			quantities: ['250', '500', '1000', '2500'],
			paper_types: ['כרומו', 'נטול עץ'],
			paper_weights: ['130 גרם', '170 גרם'],
			print_sides: ['צד אחד', 'דו צדדי'],
			lamination: ['ללא'],
			corners: ['רגילות'],
			finishing_options: ['חיתוך רגיל']
		},
		stickers: {
			sizes: ['50x50 mm', '70x70 mm', '100x100 mm', 'מותאם אישית'],
			quantities: ['100', '250', '500', '1000'],
			paper_types: ['מדבקה לבנה', 'מדבקה שקופה'],
			paper_weights: ['סטנדרטי'],
			print_sides: ['צד אחד'],
			lamination: ['ללא', 'מט', 'מבריק'],
			corners: ['רגילות', 'מעוגלות'],
			finishing_options: ['חיתוך צורני', 'חיתוך רגיל']
		},
		magnets: {
			sizes: ['50x90 mm', '70x100 mm', 'A6'],
			quantities: ['100', '250', '500', '1000'],
			paper_types: ['מגנט סטנדרטי'],
			paper_weights: ['סטנדרטי'],
			print_sides: ['צד אחד'],
			lamination: ['ללא', 'מבריק'],
			corners: ['רגילות', 'מעוגלות'],
			finishing_options: ['חיתוך רגיל']
		},
		posters: {
			sizes: ['A3', 'A2', 'A1', '70x100 cm'],
			quantities: ['1', '5', '10', '25'],
			paper_types: ['כרומו', 'פוטו'],
			paper_weights: ['170 גרם', '250 גרם'],
			print_sides: ['צד אחד'],
			lamination: ['ללא', 'מט', 'מבריק'],
			corners: ['רגילות'],
			finishing_options: ['חיתוך רגיל']
		}
	};

	function setRows(field, labels) {
		var group = document.querySelector('[data-poc-rtl-repeatable="' + field + '"]');
		var rows = group ? group.querySelector('[data-poc-rtl-repeatable-rows]') : null;
		var template = group ? group.querySelector('[data-poc-rtl-repeatable-template]') : null;

		if (!group || !rows || !template) {
			return;
		}

		rows.innerHTML = '';

		labels.forEach(function (label) {
			rows.insertAdjacentHTML('beforeend', template.innerHTML);
			rows.querySelector('.poc-rtl-repeatable-row:last-child input').value = label;
		});

		syncEmptyState(group);
	}

	document.addEventListener('click', function (event) {
		var addButton = event.target.closest('[data-poc-rtl-add-option]');
		var removeButton = event.target.closest('.poc-rtl-remove-option');

		if (addButton) {
			var group = addButton.closest('[data-poc-rtl-repeatable]');
			var rows = group ? group.querySelector('[data-poc-rtl-repeatable-rows]') : null;
			var template = group ? group.querySelector('[data-poc-rtl-repeatable-template]') : null;

			if (rows && template) {
				rows.insertAdjacentHTML('beforeend', template.innerHTML);
				syncEmptyState(group);
				rows.querySelector('.poc-rtl-repeatable-row:last-child input').focus();
			}
		}

		if (removeButton) {
			var row = removeButton.closest('.poc-rtl-repeatable-row');
			var parentGroup = removeButton.closest('[data-poc-rtl-repeatable]');

			if (row) {
				row.remove();
			}

			if (parentGroup) {
				syncEmptyState(parentGroup);
			}
		}

		var presetButton = event.target.closest('[data-poc-rtl-preset]');

		if (presetButton) {
			var preset = presets[presetButton.getAttribute('data-poc-rtl-preset')];

			if (preset) {
				Object.keys(preset).forEach(function (field) {
					setRows(field, preset[field]);
				});
			}
		}
	});

	document.querySelectorAll('[data-poc-rtl-repeatable]').forEach(syncEmptyState);
}());
