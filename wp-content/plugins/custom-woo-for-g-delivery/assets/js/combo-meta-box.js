jQuery(document).ready(function ($) {

    groupMinMaxValidation();
    addShowPriceTypeEvent();
    useSelect2($('.combo-product-item'));
    
    $(document).on('click', '.btn-add-more-product', function (e) {
        let itemClass = 'item-' + randomNumber();
        let groupUniqueKey = $(this).parents('.group-combo-item').attr('data-group-unique-key');
        let listProduct = $(this).parents('.children-product-container').find('.children-product-list');
        let productIndex = listProduct.find('.product-item').length;
        let comboProductItemHtml = makeComboProductItemHtml(comboScriptVars.comboProductItemHtml, groupUniqueKey, productIndex, itemClass);
        listProduct.append(comboProductItemHtml);
        useSelect2($(`.${itemClass} .combo-product-item`));
    });
    
    $(document).on('click', '.btn-remove-product-item', function () {
		$(this).parents('.product-item').remove();
	});

    $('#btn-add-more-group-combo').on('click', function (e) {
        let groupUniqueKey = randomNumber();
        let groupClass = `group-${groupUniqueKey}`;
        let comboHtml = makeComboHtml(comboScriptVars.comboHtml, groupUniqueKey, groupClass);
        $('.list-group-combo').append(comboHtml);
        groupMinMaxValidation();
        addShowPriceTypeEvent();
        useSelect2($(`.${groupClass}`).find('.combo-product-item'));
    });

    $(document).on('click', '.btn-remove-group', function(e) {
        $(this).parents('.group-combo-item').remove();
    });

    $('select#product-type').change(function() {
		let productType = $(this).val();
		if (productType == comboScriptVars.comboProductType) {
			$('#combo-meta-box').show();
            setTimeout(() => {
                $('#general_product_data ._tax_status_field').parent().show();
            }, 0);
		} else {
			$('#combo-meta-box').hide();
		}
	});

    $('#childProductType').change(function() {
        checkShowPriceType();
		let childProductType = $(this).val();
        let comboContainer = $('.combo-container');
        $.each(comboContainer.attr('class').split(/\s+/), function(index, className) {
            if (className.match(/^child-product-type-[1-2]$/)) {
              comboContainer.removeClass(className);
            }
        });
        comboContainer.addClass(`child-product-type-${childProductType}`);
	});

    $('#priceTaxType').change(function() {
        checkShowPriceType();
        let priceTaxType = $(this).val();
        let comboContainer = $('.combo-container');
        $.each(comboContainer.attr('class').split(/\s+/), function(index, className) {
            if (className.match(/^price-tax-type-[1-2]$/)) {
              comboContainer.removeClass(className);
            }
        });
        comboContainer.addClass(`price-tax-type-${priceTaxType}`);
    });

    function randomNumber(min = 10000, max = 99999) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function makeComboProductItemHtml(comboProductItemHtml, groupUniqueKey, productIndex, itemClass) {
        let comboProductItemName = comboScriptVars.comboProductItem;
        let comboProductItemQuantityName = comboScriptVars.comboProductItemQuantity;
        let comboProductItemIsFixedName = comboScriptVars.comboProductItemIsFixed;
        let comboProductItemBasePriceName = comboScriptVars.comboProductItemBasePrice;

        comboProductItemHtml = comboProductItemHtml.replace('product-item', `product-item ${itemClass}`);
        comboProductItemHtml = comboProductItemHtml.replace(`${comboProductItemName}[0][0]`, `${comboProductItemName}[${groupUniqueKey}][${productIndex}]`);
        comboProductItemHtml = comboProductItemHtml.replace(`${comboProductItemQuantityName}[0][0]`, `${comboProductItemQuantityName}[${groupUniqueKey}][${productIndex}]`);
        comboProductItemHtml = comboProductItemHtml.replace(`${comboProductItemIsFixedName}[0][0]`, `${comboProductItemIsFixedName}[${groupUniqueKey}][${productIndex}]`);
        comboProductItemHtml = comboProductItemHtml.replace(`${comboProductItemBasePriceName}[0]`, `${comboProductItemBasePriceName}[${groupUniqueKey}]`);
        comboProductItemHtml = comboProductItemHtml.replace(`data-base-price-trace value="0"`, `value="${productIndex}"`);
        
        return comboProductItemHtml;
    }

    function makeComboHtml(comboHtml, groupUniqueKey, groupClass) {
        let comboGroupName = comboScriptVars.comboGroupName;
        let comboGroupMaxItemName = comboScriptVars.comboGroupMaxItem;
        let comboGroupMinItemName = comboScriptVars.comboGroupMinItem;
        let itemClass = 'item-' + randomNumber();
        
        comboHtml = comboHtml.replace('group-combo-item', `group-combo-item ${groupClass}`);
        comboHtml = comboHtml.replace("data-group-unique-key='0'", `data-group-unique-key='${groupUniqueKey}'`);
        comboHtml = comboHtml.replace(`${comboGroupName}[0]`, `${comboGroupName}[${groupUniqueKey}]`);
        comboHtml = comboHtml.replace(`${comboGroupMaxItemName}[0]`, `${comboGroupMaxItemName}[${groupUniqueKey}]`);
        comboHtml = comboHtml.replace(`${comboGroupMinItemName}[0]`, `${comboGroupMinItemName}[${groupUniqueKey}]`);
        comboHtml = makeComboProductItemHtml(comboHtml, groupUniqueKey, 0, itemClass);

        return comboHtml;
    }

    function addShowPriceTypeEvent(){
        $('.select-show-price-type').change(function() {
            let showPriceType = $(this).val();
            let comboGroupItem = $(this).parents('.group-combo-item');
            $.each(comboGroupItem.attr('class').split(/\s+/), function(index, className) {
                if (className.match(/^show-price-type-[0-2]$/)) {
                    comboGroupItem.removeClass(className);
                }
            });
            comboGroupItem.addClass(`show-price-type-${showPriceType}`);
        });
    }

    function groupMinMaxValidation() {
        let currentMin = '';
        $(".combo-group-min-item select").on("mousedown", function() {
            currentMin = $(this).val();
        });

        let currentMax = '';
        $(".combo-group-max-item select").on("mousedown", function() {
            currentMax = $(this).val();
        });

        $('.combo-group-min-item select').on('change', function(e) {
            let maxNumber = parseInt($(this).parents('.group-combo-item').find('.combo-group-max-item select').val());
            let selectedValue = parseInt($(this).val());
            if (selectedValue > maxNumber) {
                alert('Số lượng tối thiểu phải nhỏ hơn hoặc bằng số lượng tối đa!');
                $(this).val(currentMin);
                return;
            }
        });

        $('.combo-group-max-item select').on('change', function(e) {
            let minNumber = parseInt($(this).parents('.group-combo-item').find('.combo-group-min-item select').val());
            let selectedValue = parseInt($(this).val());
            if (selectedValue < minNumber) {
                alert('Số lượng tối đa phải lớn hơn hoặc bằng số lượng tối thiểu!');
                $(this).val(currentMax);
                return;
            }
            checkShowPriceType();
        });
    }

    function checkShowPriceType() {
        $($('.combo-group-max-item select')).each(function(index) {
            let thisValue = parseInt($(this).val());
            let showPriceTypeObj = $(this).parents('.group-combo-item');
            if (thisValue == 1) {
                showPriceTypeObj.addClass('show-price-type');
            } else {
                showPriceTypeObj.removeClass('show-price-type');
            }
        });
    }

    function useSelect2(inputObject) {
        inputObject.select2({
            placeholder: 'Chọn sản phẩm',
            minimumInputLength: 0,
            ajax: {
                url: '/wp-admin/admin-ajax.php?action=get_combo_child_product',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    let merchantId = $('#merchant_id select').val();
                    let data = {
                        q: params.term,
                        merchantId: merchantId
                    };
                    return data;
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true,
            }
        });
    }
});