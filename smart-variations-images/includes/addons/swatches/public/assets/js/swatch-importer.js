/**
 * Swatch importer admin screen.
 *
 * Drives the batched preview/import/undo requests on the SVI Import swatches tab.
 */
(function () {
	'use strict';

	var root = document.querySelector('.svi-importer');

	if (!root) {
		return;
	}

	var nonce = root.getAttribute('data-nonce') || '';
	var batchSize = 50;
	var running = false;

	function selectedSource() {
		var checked = root.querySelector('input[name="svi_import_source"]:checked');

		return checked ? checked.value : '';
	}

	function selectedParts() {
		var parts = [];
		var boxes = root.querySelectorAll('input[name="svi_import_parts[]"]:checked');

		for (var i = 0; i < boxes.length; i++) {
			parts.push(boxes[i].value);
		}

		return parts;
	}

	function overwrite() {
		var box = root.querySelector('input[name="svi_import_overwrite"]');

		return box && box.checked ? 1 : 0;
	}

	function request(action, data) {
		var body = new URLSearchParams();
		body.append('action', 'svi_swatch_import_' + action);
		body.append('nonce', nonce);
		body.append('source', data.source || '');
		body.append('overwrite', data.overwrite ? '1' : '0');
		body.append('offset', String(data.offset || 0));

		if (data.run_id) {
			body.append('run_id', data.run_id);
		}

		(data.parts || []).forEach(function (part) {
			body.append('parts[]', part);
		});

		return fetch(window.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (response) {
			return response.json();
		});
	}

	function message(text, isError) {
		var target = root.querySelector('.svi-importer__scan');

		if (!target) {
			return;
		}

		target.textContent = text || '';
		target.className = 'svi-importer__scan' + (isError ? ' svi-importer__scan--error' : '');
	}

	function setBusy(busy) {
		running = busy;

		var buttons = root.querySelectorAll('[data-svi-import]');
		for (var i = 0; i < buttons.length; i++) {
			buttons[i].disabled = busy;
		}

		var spinner = root.querySelector('.spinner');
		if (spinner) {
			spinner.classList.toggle('is-active', busy);
		}
	}

	function progress(done, total) {
		var wrap = root.querySelector('.svi-importer__progress');
		var bar = root.querySelector('.svi-importer__bar span');
		var text = root.querySelector('.svi-importer__progress-text');

		if (!wrap || !bar || !text) {
			return;
		}

		wrap.hidden = false;

		var percent = total > 0 ? Math.round((done / total) * 100) : 100;
		bar.style.width = Math.min(100, percent) + '%';
		bar.parentElement.setAttribute('data-progress', percent + '%');
		text.textContent = percent + '% (' + done + '/' + total + ')';
	}

	function resultsTable() {
		var host = root.querySelector('.svi-importer__results');
		host.innerHTML = '';

		var table = document.createElement('table');
		table.className = 'widefat striped svi-importer__table';
		table.innerHTML = '<thead><tr>' +
			'<th>' + (window.sviImporterStrings ? window.sviImporterStrings.item : 'Item') + '</th>' +
			'<th>' + (window.sviImporterStrings ? window.sviImporterStrings.status : 'Status') + '</th>' +
			'<th>' + (window.sviImporterStrings ? window.sviImporterStrings.details : 'Details') + '</th>' +
			'</tr></thead>';

		var body = document.createElement('tbody');
		table.appendChild(body);
		host.appendChild(table);

		return body;
	}

	function appendRows(body, rows) {
		rows.forEach(function (row) {
			var tr = document.createElement('tr');
			tr.className = 'svi-importer__row svi-importer__row--' + row.status;

			var title = document.createElement('td');
			title.textContent = row.title;

			var status = document.createElement('td');
			status.textContent = row.status;

			var details = document.createElement('td');
			details.textContent = row.message;

			tr.appendChild(title);
			tr.appendChild(status);
			tr.appendChild(details);
			body.appendChild(tr);
		});
	}

	function showScan(scan) {
		if (!scan) {
			return;
		}

		var parts = [];
		Object.keys(scan.parts).forEach(function (key) {
			if (scan.parts[key] > 0) {
				parts.push(key + ': ' + scan.parts[key]);
			}
		});

		var text = (window.sviImporterStrings ? window.sviImporterStrings.found : 'Found') + ' ' + (parts.join(', ') || '—');

		if (scan.unsupported && scan.unsupported.length) {
			text += ' · ' + (window.sviImporterStrings ? window.sviImporterStrings.notMigrated : 'Not migrated') + ': ' + scan.unsupported.length;
		}

		message(text, false);
	}

	function loadRuns() {
		var host = root.querySelector('.svi-importer__undo');

		if (!host) {
			return;
		}

		request('runs', {}).then(function (response) {
			if (!response.success || !response.data.runs.length) {
				host.innerHTML = '';
				return;
			}

			host.innerHTML = '<p><strong>' + (window.sviImporterStrings ? window.sviImporterStrings.recentRuns : 'Recent imports') + '</strong></p>';

			var list = document.createElement('ul');
			list.className = 'svi-importer__runs';

			response.data.runs.forEach(function (run) {
				var li = document.createElement('li');
				var label = document.createElement('span');
				label.textContent = run.started + ' · ' + run.source_label + ' · +' + run.created + ' / ~' + run.updated;

				li.appendChild(label);

				if (run.undone) {
					var done = document.createElement('em');
					done.textContent = ' ' + (window.sviImporterStrings ? window.sviImporterStrings.undone : 'undone');
					li.appendChild(done);
				} else if (run.finished) {
					var button = document.createElement('button');
					button.type = 'button';
					button.className = 'button-link-delete svi-importer__undo-run';
					button.textContent = window.sviImporterStrings ? window.sviImporterStrings.undo : 'Undo';
					button.addEventListener('click', function () {
						undo(run.id);
					});
					li.appendChild(button);
				} else {
					var pending = document.createElement('em');
					pending.textContent = ' ' + (window.sviImporterStrings ? window.sviImporterStrings.incomplete : 'incomplete');
					li.appendChild(pending);
				}

				list.appendChild(li);
			});

			host.appendChild(list);
		});
	}

	function undo(runId) {
		if (running) {
			return;
		}

		if (!window.confirm(window.sviImporterStrings ? window.sviImporterStrings.confirmUndo : 'Undo this import?')) {
			return;
		}

		setBusy(true);
		message('', false);

		request('undo', { run_id: runId }).then(function (response) {
			setBusy(false);

			if (!response.success) {
				message(response.data && response.data.message ? response.data.message : 'Error', true);
				return;
			}

			message((window.sviImporterStrings ? window.sviImporterStrings.restored : 'Restored') + ' ' + response.data.restored, false);
			loadRuns();
		}).catch(function () {
			setBusy(false);
			message('Error', true);
		});
	}

	function renderSummary(counts) {
		var host = root.querySelector('.svi-importer__summary');

		if (!host) {
			return;
		}

		var labels = window.sviImporterStrings || {};

		host.innerHTML = '';

		[
			['created', labels.summaryCreated || 'created'],
			['updated', labels.summaryUpdated || 'replaced'],
			['kept', labels.summaryKept || 'kept'],
			['unchanged', labels.summaryUnchanged || 'already identical'],
			['skipped', labels.summarySkipped || 'not migrated']
		].forEach(function (pair) {
			var item = document.createElement('span');
			item.className = 'svi-importer__summary-item svi-importer__summary-item--' + pair[0];
			item.textContent = (counts[pair[0]] || 0) + ' ' + pair[1];
			host.appendChild(item);
		});
	}

	function runLoop(action, bodyHost) {
		var source = selectedSource();

		if (!source) {
			message(window.sviImporterStrings ? window.sviImporterStrings.chooseSource : 'Choose a source first.', true);
			return;
		}

		var parts = selectedParts();

		if (!parts.length) {
			message(window.sviImporterStrings ? window.sviImporterStrings.choosePart : 'Choose at least one part to import.', true);
			return;
		}

		setBusy(true);
		message('', false);
		renderSummary({});

		var offset = 0;
		var runId = null;
		var body = bodyHost;
		var totals = { created: 0, updated: 0, kept: 0, unchanged: 0, skipped: 0 };

		if (action === 'import') {
			body = resultsTable();
		}

		function step() {
			request(action, {
				source: source,
				parts: parts,
				overwrite: overwrite(),
				offset: offset,
				run_id: runId
			}).then(function (response) {
				if (!response.success) {
					setBusy(false);
					message(response.data && response.data.message ? response.data.message : 'Error', true);
					return;
				}

				var data = response.data;

				if (action === 'import') {
					runId = data.run_id;

					if (data.counts) {
						totals = data.counts;
					}
				} else if (data.summary) {
					Object.keys(data.summary).forEach(function (key) {
						totals[key] = (totals[key] || 0) + data.summary[key];
					});
				}

				appendRows(body, data.rows);
				progress(data.next_offset, data.total);
				renderSummary(totals);

				if (data.done) {
					setBusy(false);

					var summary = action === 'preview'
						? (window.sviImporterStrings ? window.sviImporterStrings.previewDone : 'Preview finished. Nothing was written.')
						: (window.sviImporterStrings ? window.sviImporterStrings.done : 'Done');
					message(summary, false);

					if (action === 'import') {
						loadRuns();
					}

					return;
				}

				offset = data.next_offset;
				step();
			}).catch(function () {
				setBusy(false);
				message('Error', true);
			});
		}

		step();
	}

	root.addEventListener('change', function (event) {
		if (event.target && event.target.name === 'svi_import_source') {
			var source = event.target.value;
			message('', false);

			if (!source) {
				return;
			}

			request('scan', { source: source }).then(function (response) {
				if (response.success) {
					showScan(response.data.scan);
				}
			});
		}
	});

	root.addEventListener('click', function (event) {
		var button = event.target.closest('[data-svi-import]');

		if (!button || running) {
			return;
		}

		var action = button.getAttribute('data-svi-import');

		if (action === 'preview') {
			runLoop('preview', resultsTable());
			return;
		}

		if (action === 'run') {
			if (!window.confirm(window.sviImporterStrings ? window.sviImporterStrings.confirmImport : 'Import now?')) {
				return;
			}

			runLoop('import', null);
		}
	});

	var notice = document.querySelector('.svi-import-notice a');

	if (notice) {
		notice.addEventListener('click', function (event) {
			var link = document.querySelector('.wpsfsvi-nav__item-link[href="#tab-swatches_import"]');

			if (link) {
				event.preventDefault();
				link.click();
			}
		});
	}

	loadRuns();
})();
