<script type="text/javascript">
    jQuery('#select-province').on('change', function () {
        var thisElement = jQuery(this);
        var provinceId = thisElement.val();

        if (provinceId != 0) {
            jQuery.ajax({
                url : '<?=admin_url('admin-ajax.php')?>',
                type : 'post',
                dataType : 'json',
                data : {
                    action: 'province_info',
                    id : provinceId,
                    beHonest : '<?=wp_create_nonce('province_info')?>',
                },
                success : function (res) {
                    if (res.messageCode == 1) {
                        var firstDistrict = [];
                        var i = 1;
                        // district
                        jQuery('#select-district').html('<option value="0">Chọn quận/huyện</option>');
                        res.result.districts.forEach(function (one) {
                            if (i == 1) {
                                firstDistrict = one;
                            }

                            jQuery('#select-district').append('<option value="' + one.id + '">' + one.name + '</option>');

                            i++;
                        });

                        // ward
                        jQuery('#select-ward').html('<option value="0">Chọn phường/xã</option>');
                        firstDistrict.wards.forEach(function (one) {
                            jQuery('#select-ward').append('<option value="' + one.id + '">' + one.name + '</option>');
                        });
                    } else {
                        openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                    }
                },
                error : function (x, y, z) {
                    openModalAlert('Lỗi', 'Có lỗi xảy ra khi lấy thông tin tỉnh thành, vui lòng thử lại', {scene : 'info', btnOkText: 'Đóng'});
                }
            });
        } else {
            jQuery('#select-district').html('<option value="0">Chọn quận/huyện</option>');
            jQuery('#select-ward').html('<option value="0">Chọn phường/xã</option>');
        }
    });

    jQuery('#select-district').on('change', function () {
        var thisElement = jQuery(this);
        var districtId = thisElement.val();

        if (districtId != 0) {
            jQuery.ajax({
                url : '<?=admin_url('admin-ajax.php')?>',
                type : 'post',
                dataType : 'json',
                data : {
                    action: 'district_info',
                    id : districtId,
                    beHonest : '<?=wp_create_nonce('district_info')?>',
                },
                success : function (res) {
                    if (res.messageCode == 1) {
                        // ward
                        jQuery('#select-ward').html('<option value="0">Chọn phường/xã</option>');
                        res.result.wards.forEach(function (one) {
                            jQuery('#select-ward').append('<option value="' + one.id + '">' + one.name + '</option>');
                        });
                    } else {
                        openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                    }
                },
                error : function (x, y, z) {
                    openModalAlert('Lỗi', 'Có lỗi xảy ra khi lấy thông tin tỉnh thành, vui lòng thử lại', {scene : 'info', btnOkText: 'Đóng'});
                }
            });
        }
    });

    jQuery('#select-ward').on('change', function () {
        var thisElement = jQuery(this);
        var wardId = thisElement.val();

        if (wardId != 0) {
            jQuery.ajax({
                url : '<?=admin_url('admin-ajax.php')?>',
                type : 'post',
                dataType : 'json',
                data : {
                    action: 'nearest_restaurant',
                    wardId : wardId,
                    districtId : jQuery('#select-district').val(),
                    provinceId : jQuery('#select-province').val(),
                    categoryId : <?=\GDelivery\Libs\Helper\Helper::getCurrentCategory()->term_id?>,
                    beHonest : '<?=wp_create_nonce('nearest_restaurant')?>',
                },
                success : function (res) {
                    if (res.messageCode == 1) {
                        jQuery('p.selected-restaurant').html(res.result.restaurant.name + ' - ' +res.result.restaurant.address);

                        if (res.result.allowCutleryTool == 1) {
                            jQuery('#cutlery-tool').show();
                            allowCutleryTool = 1;
                        } else {
                            jQuery('#cutlery-tool').hide();
                        }

                        reloadCartAndCalculateShippingFee();

                        // fire netcore
                        if (isEnabledNetCore == 1) {
                            ncBeginOrder(
                                {
                                    brandName: '<?=str_replace("'", ' ', \GDelivery\Libs\Helper\Helper::getCurrentCategory()->name)?>',
                                    restaurantName: res.result.restaurant.name
                                }
                            );
                        }
                    } else {
                        openModalAlert('Thông báo', res.message, {scene : 'info', btnOkText: 'Đóng'});
                    }
                },
                error : function (x, y, z) {
                    openModalAlert('Lỗi', 'Có lỗi xảy ra khi lấy thông tin tỉnh thành, vui lòng thử lại', {scene : 'info', btnOkText: 'Đóng'});
                }
            });
        }
    });
</script>