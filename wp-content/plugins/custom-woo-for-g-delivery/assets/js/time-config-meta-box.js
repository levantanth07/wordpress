jQuery(document).ready(function ($) {
	let startDate = $('#startDate'), endDate = $('#endDate');
	let options = {
		format: 'DD-MM-YYYY'
	};
	let datePickerOptions = {
		format: 'dd-mm-yyyy',
		multidate: true
	};
	if (scriptVars.startDate && scriptVars.endDate) {
		options['fDefaultValue'] = scriptVars.startDate;
		options['tDefaultValue'] = scriptVars.endDate;

		datePickerOptions['startDate'] = new Date(scriptVars.startDate);
		datePickerOptions['endDate'] = new Date(scriptVars.endDate);
	}
	
	setDateTimeRange(startDate, endDate, options);

	$('.list-of-date').datepicker(datePickerOptions);

	$('#availableType').on('change', function () {
		if ($(this).val() == 'day') {
			$('.block-day').show();
			$('.block-dates').hide();
		} else if ($(this).val() == 'date') {
			$('.block-day').hide();
			$('.block-dates').show();
		} else if ($(this).val() == '') {
			$('.block-day').hide();
			$('.block-dates').hide();
		}
	});

	let startTime0 = $('#startTime0'),endTime0 = $('#endTime0');
	setTimeRange(startTime0, endTime0, {
		format: 'HH:mm'
	});

	setTimeFrameTimePicker();

	$('.btn-add-range-time').on('click', function () {
		let currentTotalRangeTime = $('.range-time').find('.block-range-time').length;
		let tempHtml = '';
		tempHtml += '<div class="row block-range-time col-md-12">';
		tempHtml += '<div class="col-md-5">';
		tempHtml += '<div class="form-group">';
		tempHtml += '<div class="input-group time" data-target-input="nearest">';
		tempHtml += '<input type="text" id="startTime' + currentTotalRangeTime + '" class="form-control datetimepicker-input start-time value" name="startTime[]" data-target="#startTime' + currentTotalRangeTime + '" />';
		tempHtml += '<div class="input-group-append" data-target="#startTime' + currentTotalRangeTime + '" data-toggle="datetimepicker">';
		tempHtml += '<div class="input-group-text"><i class="fa fa-clock-o"></i></div>';
		tempHtml += '</div>';
		tempHtml += '</div>';
		tempHtml += '</div>';
		tempHtml += '</div>';
		tempHtml += '<div class="col-md-5">';
		tempHtml += '<div class="form-group">';
		tempHtml += '<div class="input-group time" data-target-input="nearest">';
		tempHtml += '<input type="text" id="endTime' + currentTotalRangeTime + '" class="form-control datetimepicker-input end-time value" name="endTime[]" data-target="#endTime' + currentTotalRangeTime + '" />';
		tempHtml += '<div class="input-group-append" data-target="#endTime' + currentTotalRangeTime + '" data-toggle="datetimepicker">';
		tempHtml += '<div class="input-group-text"><i class="fa fa-clock-o"></i></div>';
		tempHtml += '</div>';
		tempHtml += '</div>';
		tempHtml += '</div>';
		tempHtml += '</div>';
		tempHtml += '<div class="col-md-2">';
		tempHtml += '<a class="btn btn-danger btn-remove-range-time" href="javascript:void(0);">';
		tempHtml += '<i class="fa fa-trash"></i>';
		tempHtml += '</a>';
		tempHtml += '</div>';
		tempHtml += '</div>';
		$('.range-time').append(tempHtml);
		let tempStartTime = $('#startTime' + currentTotalRangeTime),tempEndTime = $('#endTime' + currentTotalRangeTime);
		setTimeRange(tempStartTime, tempEndTime, {
			format: 'HH:mm'
		});
	});
	$(document).on('click', '.btn-remove-range-time', function () {
		$(this).parents('.block-range-time').remove();
	});

	function setTimeFrameTimePicker() {
		let numberOfItems = $('.range-time-container').data('number-of-items');
		for(let i = 1; i < numberOfItems; i++) {
			let startTime = $('#startTime' + i);
			let endTime = $('#endTime' + i);
			setTimeRange(startTime, endTime, {
				format: 'HH:mm'
			});
		}
	}

	function updateDatePicker() {
		let startDate = jQuery('#startDate').val();
		let endDate = jQuery('#endDate').val();
		if (startDate) {
			startDate = startDate.split('-').reverse().join('-');
			$('.list-of-date').datepicker('setStartDate', new Date(startDate));
		}
		if (endDate) {
			endDate = endDate.split('-').reverse().join('-');
			$('.list-of-date').datepicker('setEndDate', new Date(endDate));
		}
	}

	function setDateTimeRange(fElem, tElem, options) {
		let startOptions = {
			format: options.format,
			useCurrent: false,
		};
		let endOptions = {
			format: options.format,
			useCurrent: false
		};
		let currentDate = new Date(scriptVars.currentDate);
		if (typeof options.fDefaultValue !== "undefined" && options.fDefaultValue) {
			let defaultDate1 = new Date(options.fDefaultValue);
			startOptions['defaultDate'] = defaultDate1;
			if (defaultDate1 <= currentDate) {
				startOptions['minDate'] = defaultDate1;
			} else {
				startOptions['minDate'] = currentDate;
			}
			endOptions['minDate'] = defaultDate1;
		} else {
			startOptions['minDate'] = currentDate;
		}

		if (typeof options.tDefaultValue !== "undefined" && options.tDefaultValue) {
			let defaultDate2 = new Date(options.tDefaultValue);
			endOptions['defaultDate'] = defaultDate2;
			if (defaultDate2 <= currentDate) {
				endOptions['minDate'] = defaultDate2;
			} else {
				endOptions['minDate'] = currentDate;
			}
			startOptions['maxDate'] = defaultDate2;
		} else {
			endOptions['minDate'] = currentDate;
		}
		
		fElem.datetimepicker(startOptions);
		tElem.datetimepicker(endOptions);
		fElem.on("dp.change", function (e) {
			tElem.data("DateTimePicker").minDate(e.date);
			updateDatePicker();
		});
		tElem.on("dp.change", function (e) {
			fElem.data("DateTimePicker").maxDate(e.date);
			updateDatePicker();
		});
	}

	function setTimeRange(fElem, tElem, options) {
		let startOptions = {
			format: options.format
		};
		let endOptions = {
			format: options.format
		};
		fElem.datetimepicker(startOptions);
		tElem.datetimepicker(endOptions);
		fElem.on("dp.change", function (e) {
			tElem.data("DateTimePicker").minDate(e.date);
		});
		tElem.on("dp.change", function (e) {
			fElem.data("DateTimePicker").maxDate(e.date);
		});
	}

});