jQuery(document).ready(function ($) {
	$('.select2').select2({
		placeholder: 'Select an option',
		minimumInputLength: 0,
		ajax: {
			url: toppingMetaBoxScriptVars.ajaxUrl,
			dataType: 'json',
			delay: 250,
			data: function (params) {
				let data = {
					action: 'getToppingProductOptions',
					q: params.term,
					nonce: toppingMetaBoxScriptVars.nonce,
					postId: toppingMetaBoxScriptVars.postId
				};
				let currentProductIds = $('.topping_product_id');
				if (currentProductIds.length > 0) {
					let exludeIds = [];
					currentProductIds.each(function () {
						let inputValue = $(this).val();
						exludeIds.push(inputValue);
					});
					data['excludeIds[]'] = exludeIds;
				}
				return data;
			},
			processResults: function (data) {
				return {
					results: data
				};
			},
			cache: true
		}
	});

	var addToppingBtn = $('.btn-add-topping');
	var removeToppingBtn = $('.remove-topping-item');
	var listToppingAdded = $('.list_topping_product');
	$(addToppingBtn).click(function (e) {
		e.preventDefault();
		let selectedId = $('#topping-meta-box-value').val();
		let selectedText = $('#topping-meta-box-value :selected').text();
		let checkInput = $('input.topping_product_id[value="' + selectedId + '"]');
		if (selectedId == null || checkInput.length > 0) {
			return;
		}
		$(listToppingAdded).append(`
          	<tr>
			  	<td></td>
				<td>
					<span>${selectedText}</span>
					<span class="dashicons dashicons-trash remove-topping-item" style="cursor: pointer;"></span>
				</td>
				<td><input class='topping_product_id' type='hidden' name='_topping_product_ids[]' value='${selectedId}'></td>
          	</tr>
      	`);
	});
	$(listToppingAdded).on('click', '.remove-topping-item', function (e) {
		e.preventDefault();
		$(this).parents('tr').remove();
	});
});