(function () {
	'use strict';

	function syncEmptyState(group) {
		var rows = group.querySelectorAll('.poc-rtl-repeatable-row');
		var empty = group.querySelector('[data-poc-rtl-repeatable-empty]');

		if (empty) {
			empty.hidden = rows.length > 0;
		}
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
	});

	document.querySelectorAll('[data-poc-rtl-repeatable]').forEach(syncEmptyState);
}());
