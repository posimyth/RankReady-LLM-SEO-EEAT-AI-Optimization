/**
 * RankReady — Admin UI
 * Handles: bulk summary generation, bulk author changer, toggle fields, cache flush.
 * No build step required. Vanilla JS.
 */
( function () {
	'use strict';

	var nonce   = rrAdmin.nonce;
	var apiBase = rrAdmin.apiBase;

	function rrFetch( path, method, body ) {
		return fetch( apiBase + path, {
			method:  method || 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce':   nonce,
			},
			body: body ? JSON.stringify( body ) : undefined,
		} ).then( function ( r ) { return r.json(); } );
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * TOGGLE FIELDS (data-toggle-target)
	 * ═══════════════════════════════════════════════════════════════════════ */

	document.querySelectorAll( '[data-toggle-target]' ).forEach( function ( checkbox ) {
		checkbox.addEventListener( 'change', function () {
			var target = document.getElementById( checkbox.getAttribute( 'data-toggle-target' ) );
			if ( target ) {
				target.style.display = checkbox.checked ? '' : 'none';
			}
		} );
	} );

	/* ═══════════════════════════════════════════════════════════════════════
	 * BULK SUMMARY GENERATION
	 * ═══════════════════════════════════════════════════════════════════════ */

	var bulkStart = document.getElementById( 'rr-bulk-start' );
	var bulkStop  = document.getElementById( 'rr-bulk-stop' );
	var bulkProg  = document.getElementById( 'rr-bulk-progress' );
	var bulkBar   = document.getElementById( 'rr-bulk-bar' );
	var bulkStat  = document.getElementById( 'rr-bulk-status' );
	var bulkRunning = false;

	if ( bulkStart ) {
		bulkStart.addEventListener( 'click', function () {
			var types = [];
			document.querySelectorAll( '.rr-bulk-type:checked' ).forEach( function ( cb ) {
				types.push( cb.value );
			} );
			if ( ! types.length ) {
				alert( 'Select at least one post type.' );
				return;
			}

			bulkRunning           = true;
			bulkStart.disabled    = true;
			bulkStart.textContent = 'Running...';
			bulkStop.style.display  = 'inline-block';
			bulkProg.style.display  = 'block';
			bulkBar.style.width     = '0%';
			bulkStat.textContent    = 'Starting...';

			rrFetch( '/bulk/start', 'POST', { post_types: types } ).then( function ( data ) {
				if ( data.code ) {
					bulkStat.textContent = 'Error: ' + ( data.message || 'Unknown error' );
					bulkFinish();
					return;
				}
				bulkUpdate( data );
				if ( data.total === 0 ) {
					bulkStat.textContent = 'No published posts found.';
					bulkFinish();
				} else {
					bulkNext();
				}
			} ).catch( function () {
				bulkStat.textContent = 'Failed to start.';
				bulkFinish();
			} );
		} );

		bulkStop.addEventListener( 'click', function () {
			bulkRunning = false;
			rrFetch( '/bulk/stop', 'POST' );
			bulkStat.textContent = 'Stopped.';
			bulkFinish();
		} );
	}

	function bulkUpdate( data ) {
		var pct = data.total > 0 ? Math.round( ( data.done / data.total ) * 100 ) : 0;
		bulkBar.style.width    = pct + '%';
		bulkStat.textContent   = data.done + ' / ' + data.total + ' posts (' + pct + '%)';
	}

	function bulkNext() {
		if ( ! bulkRunning ) return;
		rrFetch( '/bulk/process', 'POST' ).then( function ( data ) {
			if ( data.code ) {
				bulkStat.textContent = 'Error: ' + ( data.message || 'Unknown' );
				bulkFinish();
				return;
			}
			bulkUpdate( data );
			if ( data.running ) {
				setTimeout( bulkNext, 300 );
			} else {
				bulkStat.textContent = 'Done! ' + data.done + ' summaries generated.';
				bulkFinish();
			}
		} ).catch( function () {
			bulkStat.textContent = 'Request failed — retrying in 5s...';
			if ( bulkRunning ) setTimeout( bulkNext, 5000 );
		} );
	}

	function bulkFinish() {
		bulkRunning            = false;
		bulkStart.disabled     = false;
		bulkStart.textContent  = 'Start Bulk Generate';
		bulkStop.style.display = 'none';
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * BULK AUTHOR CHANGER
	 * ═══════════════════════════════════════════════════════════════════════ */

	var bacPreview   = document.getElementById( 'rr-bac-preview' );
	var bacExecute   = document.getElementById( 'rr-bac-execute' );
	var bacStop      = document.getElementById( 'rr-bac-stop' );
	var bacPrevResult = document.getElementById( 'rr-bac-preview-result' );
	var bacProgress  = document.getElementById( 'rr-bac-progress' );
	var bacBar       = document.getElementById( 'rr-bac-bar' );
	var bacStatus    = document.getElementById( 'rr-bac-status' );
	var bacDone      = document.getElementById( 'rr-bac-done' );
	var bacRunning   = false;

	function bacGetParams() {
		var types = [];
		document.querySelectorAll( '.rr-bac-pt:checked' ).forEach( function ( cb ) {
			types.push( cb.value );
		} );
		return {
			post_types  : types,
			to_author   : parseInt( document.getElementById( 'rr-bac-to' ).value ) || 0,
			from_author : parseInt( document.getElementById( 'rr-bac-from' ).value ) || 0,
			date_from   : document.getElementById( 'rr-bac-date-from' ).value,
			date_to     : document.getElementById( 'rr-bac-date-to' ).value,
		};
	}

	function bacValidate( params ) {
		if ( ! params.post_types.length ) {
			alert( 'Select at least one post type.' );
			return false;
		}
		if ( ! params.to_author ) {
			alert( 'Select a target author (To).' );
			return false;
		}
		return true;
	}

	if ( bacPreview ) {
		bacPreview.addEventListener( 'click', function () {
			var params = bacGetParams();
			if ( ! bacValidate( params ) ) return;

			bacPreview.disabled    = true;
			bacPreview.textContent = 'Checking...';
			if ( bacPrevResult ) bacPrevResult.style.display = 'none';
			if ( bacDone ) bacDone.style.display = 'none';
			bacExecute.disabled = true;

			rrFetch( '/author/preview', 'POST', params )
				.then( function ( data ) {
					bacPreview.disabled    = false;
					bacPreview.textContent = 'Preview Count';

					if ( data.code ) {
						bacPrevResult.textContent  = 'Error: ' + ( data.message || 'Unknown error' );
						bacPrevResult.className    = 'rr-notice rr-notice--error';
						bacPrevResult.style.display = 'block';
						return;
					}

					bacPrevResult.textContent   = data.message;
					bacPrevResult.className     = data.count > 0 ? 'rr-notice rr-notice--info' : 'rr-notice rr-notice--warn';
					bacPrevResult.style.display = 'block';
					bacExecute.disabled         = data.count < 1;
				} )
				.catch( function () {
					bacPreview.disabled    = false;
					bacPreview.textContent = 'Preview Count';
					bacPrevResult.textContent  = 'Preview request failed.';
					bacPrevResult.className    = 'rr-notice rr-notice--error';
					bacPrevResult.style.display = 'block';
				} );
		} );

		bacExecute.addEventListener( 'click', function () {
			var params = bacGetParams();
			if ( ! bacValidate( params ) ) return;
			if ( ! confirm( 'This will permanently reassign post authors. Continue?' ) ) return;

			bacRunning              = true;
			bacExecute.disabled     = true;
			bacExecute.textContent  = 'Running...';
			bacPreview.disabled     = true;
			bacStop.style.display   = 'inline-block';
			bacProgress.style.display = 'block';
			bacBar.style.width      = '0%';
			bacStatus.textContent   = 'Starting...';
			if ( bacDone ) bacDone.style.display = 'none';

			rrFetch( '/author/execute', 'POST', params )
				.then( function ( data ) {
					if ( data.code ) {
						bacSetFinished( 'Error: ' + ( data.message || 'Unknown error' ) );
						return;
					}
					if ( data.total === 0 ) {
						bacDone.textContent   = 'No matching posts found.';
						bacDone.style.display = 'block';
						bacSetFinished();
						return;
					}
					bacUpdateProgress( data );
					bacProcessNext();
				} )
				.catch( function () {
					bacSetFinished( 'Failed to start.' );
				} );
		} );

		bacStop.addEventListener( 'click', function () {
			bacRunning = false;
			rrFetch( '/author/stop', 'POST' );
			bacStatus.textContent = 'Stopped.';
			bacSetFinished();
		} );
	}

	function bacUpdateProgress( data ) {
		var pct = data.total > 0 ? Math.round( ( data.done / data.total ) * 100 ) : 0;
		bacBar.style.width     = pct + '%';
		bacStatus.textContent  = data.done + ' / ' + data.total + ' posts updated (' + pct + '%)';
	}

	function bacProcessNext() {
		if ( ! bacRunning ) return;
		rrFetch( '/author/process', 'POST' )
			.then( function ( data ) {
				if ( data.code ) {
					bacSetFinished( 'Error: ' + ( data.message || 'Unknown' ) );
					return;
				}
				bacUpdateProgress( data );
				if ( data.running ) {
					setTimeout( bacProcessNext, 200 );
				} else {
					bacDone.textContent  = 'Done! ' + data.done + ' post' + ( data.done !== 1 ? 's' : '' ) + ' reassigned.';
					bacDone.style.display = 'block';
					bacSetFinished();
				}
			} )
			.catch( function () {
				if ( bacRunning ) {
					bacStatus.textContent = 'Request failed — retrying in 3s...';
					setTimeout( bacProcessNext, 3000 );
				}
			} );
	}

	function bacSetFinished( errorMsg ) {
		bacRunning              = false;
		bacExecute.disabled     = false;
		bacExecute.textContent  = 'Execute';
		bacStop.style.display   = 'none';
		bacPreview.disabled     = false;

		if ( errorMsg ) {
			bacPrevResult.textContent  = errorMsg;
			bacPrevResult.className    = 'rr-notice rr-notice--error';
			bacPrevResult.style.display = 'block';
		}
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * LLMS CACHE FLUSH
	 * ═══════════════════════════════════════════════════════════════════════ */

	var flushBtn    = document.getElementById( 'rr-flush-llms-cache' );
	var flushStatus = document.getElementById( 'rr-flush-status' );

	if ( flushBtn ) {
		flushBtn.addEventListener( 'click', function () {
			flushBtn.disabled    = true;
			flushBtn.textContent = 'Flushing...';

			rrFetch( '/llms/flush-cache', 'POST' )
				.then( function () {
					flushBtn.disabled    = false;
					flushBtn.textContent = 'Flush LLMs.txt Cache';
					if ( flushStatus ) {
						flushStatus.style.display = 'inline';
						setTimeout( function () {
							flushStatus.style.display = 'none';
						}, 3000 );
					}
				} )
				.catch( function () {
					flushBtn.disabled    = false;
					flushBtn.textContent = 'Flush LLMs.txt Cache';
				} );
		} );
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * BULK FAQ GENERATION
	 * ═══════════════════════════════════════════════════════════════════════ */

	var faqStart   = document.getElementById( 'rr-faq-bulk-start' );
	var faqStop    = document.getElementById( 'rr-faq-bulk-stop' );
	var faqProg    = document.getElementById( 'rr-faq-bulk-progress' );
	var faqBar     = document.getElementById( 'rr-faq-bulk-bar' );
	var faqStat    = document.getElementById( 'rr-faq-bulk-status' );
	var faqRunning = false;

	if ( faqStart ) {
		faqStart.addEventListener( 'click', function () {
			var types = [];
			document.querySelectorAll( '.rr-faq-bulk-type:checked' ).forEach( function ( cb ) {
				types.push( cb.value );
			} );
			if ( ! types.length ) {
				alert( 'Select at least one post type.' );
				return;
			}

			faqRunning           = true;
			faqStart.disabled    = true;
			faqStart.textContent = 'Running...';
			faqStop.style.display  = 'inline-block';
			faqProg.style.display  = 'block';
			faqBar.style.width     = '0%';
			faqStat.textContent    = 'Starting...';

			rrFetch( '/faq-bulk/start', 'POST', { post_types: types } ).then( function ( data ) {
				if ( data.code ) {
					faqStat.textContent = 'Error: ' + ( data.message || 'Unknown error' );
					faqFinish();
					return;
				}
				faqUpdate( data );
				if ( data.total === 0 ) {
					faqStat.textContent = 'No published posts found.';
					faqFinish();
				} else {
					faqNext();
				}
			} ).catch( function () {
				faqStat.textContent = 'Failed to start.';
				faqFinish();
			} );
		} );

		faqStop.addEventListener( 'click', function () {
			faqRunning = false;
			rrFetch( '/faq-bulk/stop', 'POST' );
			faqStat.textContent = 'Stopped.';
			faqFinish();
		} );
	}

	function faqUpdate( data ) {
		var pct = data.total > 0 ? Math.round( ( data.done / data.total ) * 100 ) : 0;
		faqBar.style.width    = pct + '%';
		faqStat.textContent   = data.done + ' / ' + data.total + ' posts (' + pct + '%)';
	}

	function faqNext() {
		if ( ! faqRunning ) return;
		rrFetch( '/faq-bulk/process', 'POST' ).then( function ( data ) {
			if ( data.code ) {
				faqStat.textContent = 'Error: ' + ( data.message || 'Unknown' );
				faqFinish();
				return;
			}
			faqUpdate( data );
			if ( data.running ) {
				setTimeout( faqNext, 500 );
			} else {
				faqStat.textContent = 'Done! ' + data.done + ' FAQs generated.';
				faqFinish();
			}
		} ).catch( function () {
			faqStat.textContent = 'Request failed — retrying in 5s...';
			if ( faqRunning ) setTimeout( faqNext, 5000 );
		} );
	}

	function faqFinish() {
		faqRunning            = false;
		faqStart.disabled     = false;
		faqStart.textContent  = 'Start Bulk FAQ Generate';
		faqStop.style.display = 'none';
	}

	/* ═══════════════════════════════════════════════════════════════════════
	 * CRAWLER SELECT ALL
	 * ═══════════════════════════════════════════════════════════════════════ */

	var selectAll = document.getElementById( 'rr-crawlers-select-all' );
	if ( selectAll ) {
		var crawlerBoxes = document.querySelectorAll( '.rr-crawler-checkbox' );

		selectAll.addEventListener( 'change', function () {
			crawlerBoxes.forEach( function ( cb ) {
				cb.checked = selectAll.checked;
			} );
		} );

		// Update select-all state when individual boxes change.
		crawlerBoxes.forEach( function ( cb ) {
			cb.addEventListener( 'change', function () {
				var allChecked = Array.from( crawlerBoxes ).every( function ( c ) { return c.checked; } );
				selectAll.checked = allChecked;
			} );
		} );
	}

} )();
