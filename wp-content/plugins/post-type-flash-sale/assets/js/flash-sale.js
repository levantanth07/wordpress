var productFlashSale = {
	id: '',
	name: '',
	regularPrice: 0,
	promotionId: '',
	promotionName: '',
	salePrice: 0,
	quantity: 0,
	soldQuantityFake: 0,
	soldQuantity: 0,
	inventory: 0,
};

function addProductFlashSale(products, productInfo) {
	products.push(productInfo);

	return products;
}

function removeProductFlashSale(products, productId) {
	let tempProducts = [];
	jQuery.each(products, function (index, value) {
		if (value.id != productId) {
			tempProducts.push(value);
		}
	})

	return tempProducts;
}

function generateProductTableBody(products) {
	let html = '';
	let listProduct = '';
	jQuery.each(products, function (index, value) {
		html += '<tr>';
		html += '<td>' + value.name + '</td>';
		html += '<td>' + value.regularPrice + '</td>';
		html += '<td>' + value.promotionName + '</td>';
		html += '<td>' + value.salePrice + '</td>';
		html += '<td>' + value.quantity + '</td>';
		html += '<td>' + value.soldQuantityFake + '</td>';
		html += '<td>' + value.soldQuantity + '</td>';
		html += '<td>' + value.inventory + '</td>';
		html += '<td><a class="btn btn-danger delete-product" data-id="' + value.id + '" href="javascript:void(0);"><i class="fa fa-trash"></i></a></td>';
		html += '</tr>';

		listProduct += '<input name="productId[]" type="hidden" value="' + value.id + '" />';
		listProduct += '<input name="promotionId[]" type="hidden" value="' + value.promotionId + '" />';
		listProduct += '<input name="quantity[]" type="hidden" value="' + value.quantity + '" />';
		listProduct += '<input name="soldQuantityFake[]" type="hidden" value="' + value.soldQuantityFake + '" />';
	})

	jQuery('#productSelectedHide').html(listProduct);

	return html;
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

function resetPromotions() {
	jQuery('#promotion').html("<option>--- Chọn chương trình ---</option>");
	jQuery('.sale-price').val('');
}
