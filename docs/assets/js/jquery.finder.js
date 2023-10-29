/**
 * jQuery Finder Plugin
 * @author Danny McGee
 * @version 1.0
 * github.com/dannymcgee
 * 
 * Copyright 2018 Danny McGee
 * Released under the MIT License
 * https://opensource.org/licenses/MIT
 */

$(document).ready(function () {
	finder.activate();

	$(document).mousedown(function (event) {
		if (event.which === 1) {
			switch ($(event.target).attr('id') || $(event.target).parents().attr('id')) {
				case 'finderClose':
					finder.closeFinder();
					break;
				
				case 'finderPrev':
					finder.prevResult();
					break;

				case 'finderNext':
					finder.nextResult();
					break;
				
				default:
					return true;
			}
		}
	});
	
});

const finder = {
	activator: '[data-finder-activator]',

	content: '[data-finder-content]',

	wrapper: '[data-finder-wrapper]',

	scrollOffset: () => $(finder.wrapper).data('finderScrollOffset'),

	activate: () => {
		if (!$('#finder').length) {
			finder.createFinder();
		}
		setTimeout(function () {
		
			$('#finder').addClass('active');
			$('#finderInput').focus();
			if ($('#finderInput').val()) {
				if($('#finderInput').val() > 3) {  finder.findTerm($('#finderInput').val());  }  else { $(finder.content).unhighlight(); $('#finder').removeClass('active'); finder.currentResult = 0; $('#finderInput').addClass('not-found'); finder.resultsCount = 0; finder.updateCurrent();}
			}
			$('#finderInput').on('input', function () {
				if($('#finderInput').val().length > 2) {  finder.findTerm($(this).val()); }   else { $(finder.content).unhighlight(); $('#finder').removeClass('active'); finder.currentResult = 0; $('#finderInput').addClass('not-found'); finder.resultsCount = 0; finder.updateCurrent(); }
			});
		}, 50);
	},

	createFinder: () => {
		const finderElem = $('<div />')
			.attr({
				'id': 'finder',
				'class': 'finder'
			})
			.prependTo(finder.wrapper);

		const input = $('<input />')
			.attr({
				'id': 'finderInput',
				'type': 'text',
				'class': 'finder-input',
				'placeholder': 'Search String',
			})
			.appendTo(finderElem);

		const prev = $('<button />')
			.attr({
				'id': 'finderPrev',
				'class': 'btn btn-finder btn-finder-prev',
			})
			.appendTo(finderElem);

		const prevIcon = $('<i />')
			.attr({
				'class': 'fas fa-angle-up',
			})
			.appendTo(prev);

		const next = $('<button />')
			.attr({
				'id': 'finderNext',
				'class': 'btn btn-finder btn-finder-next',
			})
			.appendTo(finderElem);

		const nextIcon = $('<i />')
			.attr({
				'class': 'fas fa-angle-down',
			})
			.appendTo(next);

		/*const close = $('<button />')
			.attr({
				'id': 'finderClose',
				'class': 'btn btn-finder btn-finder-close',
			})
			.appendTo(finderElem); */

		/*const closeIcon = $('<i />')
			.attr({
				'class': 'fas fa-times',
			})
			.appendTo(close);*/
	},

	closeFinder: () => {
		$('#finder').removeClass('active');
		$(finder.content).unhighlight();
	},

	resultsCount: 0,

	currentResult: 0,

	findTerm: (term) => {
		// highlight results
		$(finder.content).unhighlight();
		$(finder.content).highlight(term);

		// count results
		finder.resultsCount = $('.highlight').length;

		if (finder.resultsCount) {
			// there are results, scroll to first one
			finder.currentResult = 1;
			finder.scrollToCurrent();
		} else {
			// no results
			finder.currentResult = 0;
		}

		// term not found
		if (!finder.resultsCount && term) {
			$('#finderInput').addClass('not-found');
		} else {
			$('#finderInput').removeClass('not-found');
		}

		finder.updateCurrent();
	},

	scrollToCurrent: () => {
		let scrollingElement;

		let i = finder.currentResult - 1;
		$('.highlight').removeClass('active');
		$(`.highlight:eq(${i})`).addClass('active');

		let offsetTop = -150;
		if (finder.scrollOffset() !== null) {
			offsetTop = finder.scrollOffset() * -1;
			offsetTop =  -120;
		}

		$(finder.wrapper).scrollTo('.highlight.active', {
			offset: {
				left: 0,
				top: offsetTop,
			},
		});
	},

	prevResult: () => {
		if (finder.resultsCount) {
			if (finder.currentResult > 1) {
				finder.currentResult--;
			} else {
				finder.currentResult = finder.resultsCount;
			}
			finder.scrollToCurrent();
		}

		finder.updateCurrent();
	},

	nextResult: () => {
		if (finder.resultsCount) {
			if (finder.currentResult < finder.resultsCount) {
				finder.currentResult++;
			} else {
				finder.currentResult = 1;
			}
			finder.scrollToCurrent();
		}

		finder.updateCurrent();
	},

	updateCurrent: () => {
		if ($('#finderInput').val()) {
			if (!$('#finderCount').length) {
				const countElem = $('<span />')
					.attr({
						'id': 'finderCount',
						'class': 'finder-count',
					})
					.insertAfter('#finderInput');
			}
			setTimeout(function () {
				$('#finderCount').text(finder.currentResult + ' / ' + finder.resultsCount);
			}, 50);
		} else {
			$('#finderCount').remove();
		}
	},
}