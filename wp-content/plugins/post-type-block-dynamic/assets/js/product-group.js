function showNotice(noticeElem) {
    noticeElem.css('display', 'block');
}
function hideNotice(noticeElem) {
    noticeElem.css('display', 'none');
}

function setRange(fElem, tElem, options) {
    fElem.datetimepicker({
        useCurrent: true,
        format: options.format
    });
    tElem.datetimepicker({
        useCurrent: true,
        format: options.format
    });

    if (typeof options.fDefaultValue !== "undefined") {
        fElem.datetimepicker('defaultDate',  moment(options.fDefaultValue));
        tElem.datetimepicker('minDate', moment(options.fDefaultValue));
        if (options.rangeOneYear) {
            tElem.datetimepicker('maxDate', moment(options.fDefaultValue).add(1, 'years'));
        }
    }
    if (typeof options.tDefaultValue !== "undefined") {
        tElem.datetimepicker('defaultDate',  moment(options.tDefaultValue));
        fElem.datetimepicker('maxDate', moment(options.tDefaultValue));
        if (options.rangeOneYear) {
            fElem.datetimepicker('minDate', moment(options.tDefaultValue).add(-1, 'years'));
        }
    }
    fElem.on("change.datetimepicker", function (e) {
        tElem.datetimepicker('minDate', e.date);
        if (options.rangeOneYear) {
            tElem.datetimepicker('maxDate', moment(e.date).add(1, 'years'));
        }
    });
    tElem.on("change.datetimepicker", function (e) {
        fElem.datetimepicker('maxDate', e.date);
        if (options.rangeOneYear) {
            fElem.datetimepicker('minDate', moment(e.date).add(-1, 'years'));
        }
    });
}

function showLoading() {
    jQuery('.loading').css('display', 'flex');
}
function hideLoading() {
    jQuery('.loading').css('display', 'none');
}

function updateDataItemSortedWhenDragDrop() {
    // Add product to input hidden
    jQuery('#listItemSorted').bind('DOMSubtreeModified', function(){
        let items = [];
        jQuery(this).find('.item-box').each(function () {
            items.push(jQuery(this).attr('data-id'));
        });

        jQuery("input[name='listItemSorted']").val(items.join(','));
    });
}

function onChangeAvailableType() {
    jQuery('#availableType').on('change', function () {
        if (jQuery(this).val() == 'day') {
            jQuery('.block-day').show();
            jQuery('.block-dates').hide();
        } else if (jQuery(this).val() == 'dates') {
            jQuery('.block-day').hide();
            jQuery('.block-dates').show();
        }
    });
}

function onAddRangeTime(currentTotalRangeTime) {
    jQuery('.btn-add-range-time').on('click', function () {
        currentTotalRangeTime++;
        let tempHtml = '';
        tempHtml += '<div class="row block-range-time col-md-12">';
        tempHtml += '<div class="col-md-5">';
        tempHtml += '<div class="form-group">';
        tempHtml += '<div class="input-group time" id="startTime' + currentTotalRangeTime + '" data-target-input="nearest">';
        tempHtml += '<input type="text" class="form-control datetimepicker-input start-time value" name="startTime[]" data-target="#startTime' + currentTotalRangeTime + '" />';
        tempHtml += '<div class="input-group-append" data-target="#startTime' + currentTotalRangeTime + '" data-toggle="datetimepicker">';
        tempHtml += '<div class="input-group-text"><i class="fa fa-clock-o"></i></div>';
        tempHtml += '</div>';
        tempHtml += '</div>';
        tempHtml += '</div>';
        tempHtml += '</div>';
        tempHtml += '<div class="col-md-5">';
        tempHtml += '<div class="form-group">';
        tempHtml += '<div class="input-group time" id="endTime' + currentTotalRangeTime + '" data-target-input="nearest">';
        tempHtml += '<input type="text" class="form-control datetimepicker-input end-time value" name="endTime[]" data-target="#endTime' + currentTotalRangeTime + '" />';
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
        jQuery('.range-time').append(tempHtml);
        let tempStartTime = jQuery('#startTime' + currentTotalRangeTime),
          tempEndTime = jQuery('#endTime' + currentTotalRangeTime);
        setRange(tempStartTime, tempEndTime, {
            format: 'HH:mm'
        });
    });
}

function resetDataSortItem() {
    jQuery("div[data-name='type'] select").on('change', function () {
        let listItemSortedElem = jQuery('#listItemSorted'),
          listProductElem = jQuery('#listItemUnSorted'),
          provinceElem = jQuery("div[data-name='province_id'] select");

        provinceElem.empty().trigger('change');
        listProductElem.html('');
        listItemSortedElem.html('');
    });

    jQuery("div[data-name='screen'] input[type='radio']").on('change', function () {
        let listItemSortedElem = jQuery('#listItemSorted'),
          listProductElem = jQuery('#listItemUnSorted'),
          provinceElem = jQuery("div[data-name='province_id'] select"),
          merchantElem = jQuery("div[data-name='merchant'] select");

        merchantElem.empty().trigger('change');
        provinceElem.empty().trigger('change');
        listProductElem.html('');
        listItemSortedElem.html('');
    });
}

function searchItem(keyWord) {
    var selectorBlockItem = jQuery('#listItemUnSorted .block-item');
    var titleItem = jQuery('#listItemUnSorted .block-item span');

    if(parseInt(keyWord.length) >= 1){
        selectorBlockItem.hide(); // hide li province

        // do search text
        var temp = titleItem.filter(function () {
            return removeAccents(jQuery(this).text()).toLowerCase().indexOf(removeAccents(keyWord.toLowerCase())) > -1;
        });

        // display result
        temp.parent().show(); // display block product
    } else {
        selectorBlockItem.show();
    }
}

function removeAccents(str) {
    return str.normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/đ/g, 'd').replace(/Đ/g, 'D');
}

function generateHtmlUnSort(dataUnSort) {
    let listProductHtml= '';
    jQuery.each(dataUnSort, function (index, value) {
        listProductHtml += '<div class="block-item text-center white-text blue m-1 item-box col-3" data-id="' + value.id + '">';
        listProductHtml += '<img class="lazy" src="' + value.thumbnail + '"';
        listProductHtml += 'data-src="' + value.thumbnail + '">';
        listProductHtml += '<span class="title-item" title="' + value.name + '">' + value.name + '</span>';
        listProductHtml += '</div>';
    });
    
    return listProductHtml;
}

function generateHtmlSorted(dataSorted) {
    let listItemSortedHtml= '';
    jQuery.each(dataSorted, function (index, value) {
        listItemSortedHtml += '<div class="block-item text-center white-text blue m-1 item-box col-3" data-id="' + value.id + '">';
        listItemSortedHtml += '<img class="lazy" src="' + value.thumbnail + '"';
        listItemSortedHtml += 'data-src="' + value.thumbnail + '">';
        listItemSortedHtml += '<span class="title-item" title="' + value.name + '">' + value.name + '</span>';
        listItemSortedHtml += '</div>';
    });
    
    return listItemSortedHtml;
}