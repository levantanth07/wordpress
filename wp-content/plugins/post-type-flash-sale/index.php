<?php

/*
Plugin Name: Post Type Flash Sale
Plugin URI: http://ggg.com.vn/
Description: Manage Flash Sale.
Author: hoang.dh
Version: 1.0
*/

define('FLASH_SALE_PLUGIN_URL', plugin_dir_url(__FILE__));


class FlashSalePostType {

    public function __construct()
    {
        // register province post type
        add_action( 'init', [$this, 'registerPostType'], 0 );
        add_action('add_meta_boxes', [$this, 'flashSaleMetaBox']);
        add_action('save_post', [$this, 'savePostCallback'], 10, 1);

        require_once "libs/helper/helper.php";
        require_once "libs/ajax/get-products.php";
        require_once "libs/ajax/get-promotions.php";
        require_once "api/index.php";
    }

    public function registerPostType()
    {
        $post_type = 'flash_sale';
        $args = [
            'label' => 'Flash Sale',
            'labels' => [
                'name' => 'Flash Sale',
                'singular_name' => 'Flash Sale',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Flash Sale',
            ],
            'menu_position' => 20,
            'menu_icon' => 'dashicons-superhero',
            'rewrite' => ['slug' => 'flash-sale '],
            'supports' => ['title', 'thumbnail'],
            'exclude_from_search' => false,
            'capabilities' => [
                /*'edit_post'          => 'edit_tgs-notification',
                'read_post'          => 'read_tgs-notification',
                'delete_post'        => 'delete_tgs-notification',
                'edit_posts'         => 'edit_tgs-notification',
                'edit_others_posts'  => 'edit_tgs-notification',
                'publish_posts'      => 'publish_tgs-notification',
                'read_private_posts' => 'read_private_tgs-notification',
                'create_posts'       => 'edit_tgs-notification',*/
            ],
            'show_ui' => true,
            'public' => true,
            'show_in_rest' => true,
        ];
        register_post_type( $post_type, $args );
    }

    public function flashSaleMetaBox()
    {
        add_meta_box('flash_sale_meta_box', 'Điều kiện và sản phẩm', [$this, 'metaBoxContent'], 'flash_sale', 'advanced', 'core');
    }
    public function acf_field_key($field_name, $post_id = false){

        if ( $post_id )
            return get_field_reference($field_name, $post_id);

        if( !empty($GLOBALS['acf_register_field_group']) ) {

            foreach( $GLOBALS['acf_register_field_group'] as $acf ) :

                foreach($acf['fields'] as $field) :

                    if ( $field_name === $field['name'] )
                        return $field['key'];

                endforeach;

            endforeach;
        }
        return $field_name;
    }

    public function metaBoxContent()
    {
        global $post_ID;

        $startDate = get_field('start_date', $post_ID);
        $endDate = get_field('end_date', $post_ID);
        $availableType = get_field('available_type', $post_ID);
        $availableValue = get_field('available_value', $post_ID);
        $startTime = get_field('start_time', $post_ID) ?? [];
        $endTime = get_field('end_time', $post_ID) ?? [];

        $blockTime = [];
        foreach ($startTime as $key => $value) {
            $temp = new \stdClass();
            $temp->startTime = $value;
            $temp->endTime = $endTime[$key];
            $blockTime[] = $temp;
        }

        $dayConfigs = [
            [
                'value' => 'monday',
                'text' => 'Thứ 2',
            ],
            [
                'value' => 'tuesday',
                'text' => 'Thứ 3',
            ],
            [
                'value' => 'wednesday',
                'text' => 'Thứ 4',
            ],
            [
                'value' => 'thursday',
                'text' => 'Thứ 5',
            ],
            [
                'value' => 'friday',
                'text' => 'Thứ 6',
            ],
            [
                'value' => 'saturday',
                'text' => 'Thứ 7',
            ],
            [
                'value' => 'sunday',
                'text' => 'Chủ nhật',
            ],
        ];

        $productFlashSale = [];
        $getProducts = get_post_meta($post_ID, 'productFlashSale', true) ?: [];
        foreach ($getProducts as $data) {
            $temp = new \stdClass();
            $productId = $data->id;
            $promotionId = $data->promotionId;

            $productWp = get_post($productId);
            $currentProductId = $productWp->ID;
            $parentId = $productWp->ID;
            $productInfo = wc_get_product($currentProductId);
            if ($productInfo->is_type('variable')) {
                $variations = $productInfo->get_available_variations();
                if (isset($variations[0])) {
                    $currentProductId = $variations[0]['variation_id'];
                }
            }
            if ($productInfo->is_type('variation')) {
                $parentId = $productInfo->get_parent_id();
            }
            $merchantId = get_field('merchant_id', $parentId);
            $merchant = \GDelivery\Libs\Helper\Helper::getMerchantInfo(get_post($merchantId));

            $temp->id = $productId;
            $temp->name = $productWp->post_title;
            $temp->merchantName = $merchant->name;
            $temp->regularPrice = (float) get_field('_regular_price',$currentProductId);
            $temp->promotionId = $promotionId;
            $temp->promotionName = 'Waiting...';
            $temp->salePrice = 'Waiting...'; // todo: tính giá giảm theo chương trình giảm giá
            $temp->quantity = $data->quantity;
            $temp->soldQuantityFake = $data->soldQuantityFake;
            $temp->soldQuantity = 'Waiting...'; // todo: lưu số lượng sản phẩm đã bán trong flash sale
            $temp->inventory = 'Waiting...'; // todo: số lượng sản phẩm còn lại của flash sale

            $productFlashSale[] = $temp;
        }

        ?>
        <link href="<?= FLASH_SALE_PLUGIN_URL ?>/assets/css/font-awesome.min.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              rel="stylesheet">
        <link rel="stylesheet"
              href="<?= FLASH_SALE_PLUGIN_URL ?>assets/css/tempusdominus-bootstrap-4.min.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              crossorigin="anonymous"/>
        <link href="<?= FLASH_SALE_PLUGIN_URL ?>/assets/css/select2.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              rel="stylesheet">
        <script src="<?= FLASH_SALE_PLUGIN_URL ?>/assets/js/lazyload-img.js?ver=<?= \GDelivery\Libs\Config::VERSION ?>"
                type="text/javascript"></script>
        <link href="<?= FLASH_SALE_PLUGIN_URL ?>assets/css/bootstrap-datepicker3.min.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              rel="stylesheet">
        <link href="<?= FLASH_SALE_PLUGIN_URL ?>assets/css/custom.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              rel="stylesheet">
        <style type="text/css">
            div[data-name='province_id'] .select2.select2-container {
                margin-left: 0 !important;
            }

            .text-bold {
                font-weight: bold;
            }

            ul.typeahead.dropdown-menu {
                max-height: 340px;
                overflow-y: auto;
            }
            .dropdown-menu>.active>a,
            .dropdown-menu>.active>a:focus,
            .dropdown-menu>.active>a:hover {
                text-decoration: none;
                background-color: #007bff;
                color: #fff;
                outline: 0;
            }
        </style>

        <div class="sort-tabs">
            <div id="save-success" class="notice notice-success settings-error is-dismissible" style="display: none;">
                <p><strong>Lưu thành công</strong></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Đóng thông báo.</span>
                </button>
            </div>
            <div id="error-message" class="notice notice-error settings-error is-dismissible" style="display: none;">
                <p><strong class="content">Lưu không thành công</strong></p>
                <button type="button" class="notice-dismiss"><span class="screen-reader-text">Đóng thông báo.</span>
                </button>
            </div>
            <br><br>
            <div class="block-dynamic-form" data-tab-id="">
                <div class="form-group d-flex">
                    <label class="col-md-3 text-bold">Thời gian hiển thị</label>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-3">Ngày</div>
                            <div class="col-md-9 row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="input-group date" id="startDate" data-target-input="nearest">
                                            <input type="text"
                                                   class="form-control datetimepicker-input start-date value"
                                                   name="startDate"
                                                   data-target="#startDate" required="required"
                                                   value="<?= $startDate ?>"/>
                                            <div class="input-group-append" data-target="#startDate"
                                                 data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 is-required">
                                    <div class="form-group">
                                        <div class="input-group date" id="endDate" data-target-input="nearest">
                                            <input type="text" class="form-control datetimepicker-input end-date value"
                                                   name="endDate"
                                                   data-target="#endDate" required="required" value="<?= $endDate ?>"/>
                                            <div class="input-group-append" data-target="#endDate"
                                                 data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">Hiệu lực</div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <select class="form-control col-md-3" id="availableType" name="availableType">
                                        <option value="">--- Chọn loại hiệu lực ---</option>
                                        <option value="day" <?= $availableType == 'day' ? 'selected' : '' ?>>Thứ
                                        </option>
                                        <option value="dates" <?= $availableType == 'dates' ? 'selected' : '' ?>>Ngày
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 offset-3 <?= $availableType != 'day' ? 'hidden' : '' ?> block-day">
                                <div class="form-group">
                                    <?php foreach ($dayConfigs as $day): ?>
                                        <div class="form-check form-check-inline" style="min-width: 95px;">
                                            <input class="form-check-input value" id="day-<?= $day['value'] ?>"
                                                   type="checkbox"
                                                   name="day[]"
                                                   value="<?= $day['value'] ?>" <?= (is_array($availableValue) && in_array($day['value'], $availableValue)) ? 'checked' : '' ?>>
                                            <label class="form-check-label"
                                                   for="day-<?= $day['value'] ?>"><?= $day['text'] ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-md-6 offset-3 <?= $availableType != 'dates' ? 'hidden' : '' ?> block-dates">
                                <div class="form-group">
                                    <input class="form-control" type="text" name="dates"
                                           value="<?= is_string($availableValue) ? $availableValue : '' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">Khung giờ&nbsp</div>
                            <div style="margin-right: 4px;">
                                <a class="btn btn-primary btn-add-range-time" href="javascript:void(0);"><i
                                        class="fa fa-plus"></i></a>
                            </div>
                            <div class="col-md-9 row range-time">
                                <?php if ($blockTime): $index = 0; ?>
                                    <?php foreach ($blockTime as $key => $time): ?>
                                        <div class="row block-range-time col-md-12">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <div class="input-group time" id="startTime<?= $key ?>"
                                                         data-target-input="nearest">
                                                        <input type="text"
                                                               class="form-control datetimepicker-input start-time value"
                                                               name="startTime[]" data-target="#startTime<?= $key ?>"
                                                               value="<?= $time->startTime ?>"/>
                                                        <div class="input-group-append"
                                                             data-target="#startTime<?= $key ?>"
                                                             data-toggle="datetimepicker">
                                                            <div class="input-group-text"><i class="fa fa-clock-o"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <div class="input-group time" id="endTime<?= $key ?>"
                                                         data-target-input="nearest">
                                                        <input type="text"
                                                               class="form-control datetimepicker-input end-time value"
                                                               name="endTime[]" data-target="#endTime<?= $key ?>"
                                                               value="<?= $time->endTime ?>"/>
                                                        <div class="input-group-append"
                                                             data-target="#endTime<?= $key ?>"
                                                             data-toggle="datetimepicker">
                                                            <div class="input-group-text"><i class="fa fa-clock-o"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($index >= 1): ?>
                                                <div class="col-md-2">
                                                    <a class="btn btn-danger btn-remove-range-time"
                                                       href="javascript:void(0);"><i class="fa fa-trash"></i></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        $index++;
                                    endforeach;
                                    ?>
                                <?php else: ?>
                                    <div class="row block-range-time col-md-12">
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <div class="input-group time" id="startTime0"
                                                     data-target-input="nearest">
                                                    <input type="text"
                                                           class="form-control datetimepicker-input start-time value"
                                                           name="startTime[]" data-target="#startTime0"/>
                                                    <div class="input-group-append" data-target="#startTime0"
                                                         data-toggle="datetimepicker">
                                                        <div class="input-group-text"><i class="fa fa-clock-o"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <div class="input-group time" id="endTime0" data-target-input="nearest">
                                                    <input type="text"
                                                           class="form-control datetimepicker-input end-time value"
                                                           name="endTime[]" data-target="#endTime0"/>
                                                    <div class="input-group-append" data-target="#endTime0"
                                                         data-toggle="datetimepicker">
                                                        <div class="input-group-text"><i class="fa fa-clock-o"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group col-12 block-sort-items">
                    <div id="productSelectedHide"></div>
                    <label class="text-bold">Sản phẩm</label>
                    <div class="row col-12 box-sort" id="boxProduct" style="opacity: 1;">
                        <a type="button" id="addProduct" class="btn btn-primary disabled" href="javascript:void(0);">Add</a>
                        <table class="table table-striped" id="tableProduct">
                            <thead>
                                <tr>
                                    <th width="15%">Tên</th>
                                    <th width="10%">Giá gốc</th>
                                    <th width="15%">Chương trình KM</th>
                                    <th width="10%">Giá KM</th>
                                    <th width="10%">SL bán</th>
                                    <th width="12%">SL đã bán hiển thị</th>
                                    <th width="12%">SL đã bán thực tế</th>
                                    <th width="11%">SL còn lại thực tế</th>
                                    <th width="5%">-</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProductModalLabel">Thêm sản phẩm flash sale</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="row col-md-8">
                                <label for="productName" class="col-sm-4 col-form-label">Tên</label>
                                <div class="col-sm-8" style="display: flex; align-items: center; flex-direction: column;">
                                    <div style="display: flex; justify-content: center; flex-direction: column; width: 100%;">
                                        <input type="text" id="productName" class="typeahead form-control" name="productName"/>
                                        <div class="loading spinner-border spinner-border-sm"
                                             style="position: absolute; right: 19px; display: none;" role="status"></div>
                                    </div>
                                    <i style="align-self: flex-start;" class="note-for-product"></i>
                                </div>
                            </div>
                            <div class="row col-md-4">
                                <label class="col-sm-4 col-form-label">Giá gốc</label>
                                <div class="col-sm-8">
                                    <input class="regular-price form-control" readonly/>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="row col-md-8">
                                <label for="promotion" class="col-sm-4 col-form-label">Chương trình KM</label>
                                <div class="col-sm-8">
<!--                                    <input type="text" class="form-control" id="promotion">-->
                                    <select class="form-control" id="promotion">
                                        <option>--- Chọn chương trình ---</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row col-md-4">
                                <label class="col-sm-4 col-form-label">Giá KM</label>
                                <div class="col-sm-8">
                                    <input class="sale-price form-control" readonly/>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="row col-md-8">
                                <label for="quantity" class="col-form-label col-sm-4">SL bán</label>
                                <div class="col-sm-4">
                                    <input type="number" class="form-control" id="quantity">
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="row col-md-8">
                                <label for="soldQuantityFake" class="col-form-label col-sm-4">SL đã bán hiển thị</label>
                                <div class="col-sm-4">
                                    <input type="number" class="form-control" id="soldQuantityFake">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" id="doAddProduct">Xác nhận</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="justify-content-center loading" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <script src="<?= FLASH_SALE_PLUGIN_URL ?>/assets/js/bootstrap.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"
                type="text/javascript"></script>
        <script src="<?= FLASH_SALE_PLUGIN_URL ?>assets/js/moment.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
        <script src="<?= FLASH_SALE_PLUGIN_URL ?>assets/js/tempusdominus-bootstrap-4.min.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"
                crossorigin="anonymous"></script>

        <script src="<?= FLASH_SALE_PLUGIN_URL ?>/assets/js/select2.min.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
        <script src="<?= FLASH_SALE_PLUGIN_URL ?>assets/js/flash-sale.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
        <script src="<?= FLASH_SALE_PLUGIN_URL ?>assets/js/custom.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
        <script src="<?= FLASH_SALE_PLUGIN_URL ?>assets/js/bootstrap-datepicker.js"></script>
        <script src="<?= FLASH_SALE_PLUGIN_URL ?>assets/js/typeahead.js"></script>
        <script type="text/javascript">
            (jQuery)(function ($) {
	            var productFlashSale = <?=json_encode($productFlashSale)?>;
	            let blockProvince = $("div[data-name='province_id']").find('select');
	            let provinceId = blockProvince.val();
	            var timeoutSearchProduct;
                var tempProductAdd;
                var tempProductSearch;
                var productIdSelected = $.map(productFlashSale, function(value) {
	                return value.id;
                });

	            $('#tableProduct tbody').html(generateProductTableBody(productFlashSale));

	            $('#addProduct').on('click', function () {
                    $('#addProductModal').modal({
	                    'show' : true,
	                    'backdrop' : 'static'
                    });
	            });
	            $('#addProductModal').on('show.bs.modal', function (e) {
                    tempProductAdd = {
	                    id: null,
	                    name: null,
	                    regularPrice: null,
	                    promotionId: 1,
	                    promotionName: 'Giam 50k',
	                    salePrice: 15000 ,
	                    quantity: 0,
	                    soldQuantityFake: 0,
	                    soldQuantity: 0,
	                    inventory: 0,
                    };
                });

	            let getSuggestion = function(thisElem, params, callback) {
                    if (timeoutSearchProduct) {
                        clearTimeout(timeoutSearchProduct);
                    }

                    if (thisElem.val().length >= 3) {
	                    thisElem.closest('div').find('.loading').show();

                        let inputId = params.inputId,
                            data = params.data;

                        timeoutSearchProduct = setTimeout(function () {
                            $.ajax({
                                url: "<?=admin_url('admin-ajax.php')?>",
                                data: data,
                                dataType: "json",
                                type: "GET",
                                success: function (res) {
                                    let arrProduct = res.result;
	                                tempProductSearch = arrProduct;
                                    let html = '<ul class="typeahead dropdown-menu" role="listbox"';
                                    html += 'style="right: 15px; left: 15px;" id="' + inputId + '-suggestion' + '">';
                                    $.each(arrProduct, function (index, value) {
                                        html += '<li>';
                                        html += '<a class="dropdown-item product-suggestion" ';
                                        html += 'href="#" role="option" data-id="' + value.id + '" ';
                                        html += '>';
                                        html += value.nameFormated;
                                        html += '</a>';
                                        html += '</li>';
                                    });
                                    if (!arrProduct) {
                                        html += '<div style="padding-left: 1.5rem">Không có sản phẩm</div>';
                                    }
                                    html += '</ul>';
                                    $('#' + inputId + '-suggestion').remove();
                                    $(html).insertAfter('#' + inputId);
                                    $('#' + inputId + '-suggestion').show();

                                    callback();
                                },
                                complete: function () {
	                                thisElem.closest('div').find('.loading').hide();
                                }
                            });
                        }, 1000);
                    }
	            }
	            $(document).ready(function() {
		            $(window).keydown(function(event){
			            if(event.keyCode === 13 &&
                            $(event.target).closest('#addProductModal').length === 1) {
				            event.preventDefault();
				            return false;
			            }
		            });
	            });

	            $('#productName')
		            .on('keyup', function () {
			            if (tempProductAdd.name !== $('#productName').val()) {
				            tempProductAdd.id = null;
				            tempProductAdd.name = null;
				            tempProductAdd.regularPrice = null;
				            $('#addProductModal .regular-price').val('');
				            $('#addProductModal .note-for-product').text('');
                            resetPromotions();
			            }
			            let thisElem = $(this);
			            let params = {
				            inputId: 'productName',
				            data: {
					            action: "get_products_by_province_and_keyword",
					            provinceId: provinceId,
					            keyWord: $('#productName').val(),
                                productIdSelected: productIdSelected
				            }
			            };
			            getSuggestion(thisElem, params, function () {
				            // Select product
				            $(document).on('click', '#productName-suggestion li', function (e) {
                                e.preventDefault();
					            let productId = $(this).find('a').attr('data-id');
					            let productSelected = tempProductSearch.filter(function (obj) {
						            return obj.id == productId;
					            });
					            let productName = productSelected[0].name,
						            noteForProduct = productSelected[0].merchantName,
						            regularPrice = productSelected[0].regularPrice,
						            textRegularPrice = productSelected[0].textRegularPrice;
					            $('#productName-suggestion li').removeClass('active');
					            $(this).addClass('active');
					            $('#productName').val(productName);
					            $('.note-for-product').text(noteForProduct);
					            $('#productName-suggestion').hide();
					            $('#addProductModal .regular-price').val(textRegularPrice);

					            tempProductAdd.id = productId;
					            tempProductAdd.name = productName;
					            tempProductAdd.regularPrice = regularPrice;

                                doSearchPromotions(productId);
				            });
				            $(document).on('click', function (e) {
					            let thisElem = $(e.target);
					            if (thisElem.attr('id') !== 'productName' && !thisElem.hasClass('product-suggestion')) {
						            $('#productName-suggestion').html('').hide();
					            }
				            });
			            });
		            });

	            $('#doAddProduct').on('click', function () {
                    if (tempProductAdd.id) {
                        tempProductAdd.quantity = $('#addProductModal #quantity').val();
                        tempProductAdd.soldQuantityFake = $('#soldQuantityFake').val();
	                    addProductFlashSale(productFlashSale, tempProductAdd);
	                    $('#addProductModal').modal('hide');

	                    $('#tableProduct tbody').html(generateProductTableBody(productFlashSale));

	                    productIdSelected = $.map(productFlashSale, function(value) {
		                    return value.id;
	                    });
                    }
	            });

	            $(document).on('click', '.delete-product', function (e) {
		            let productId = $(this).attr('data-id');
                    productFlashSale = productFlashSale.filter(function (obj) {
                        return obj.id != productId;
                    });
                    productIdSelected = $.map(productFlashSale, function(value) {
                        return value.id;
                    });
                    $('#tableProduct tbody').html(generateProductTableBody(productFlashSale));
	            });

	            $('#addProductModal').on('hidden.bs.modal', function () {
		            $('#addProductModal input').val('');
                    $('.note-for-product').text('');
	            });

                let startDate = $('#startDate'),
                    endDate = $('#endDate');
                setRange(startDate, endDate, {
                    format: 'DD-MM-YYYY',
                    fDefaultValue: "<?=date_i18n('m/d/Y', strtotime($startDate))?>",
                    tDefaultValue: "<?=date_i18n('m/d/Y', strtotime($endDate))?>",
                    rangeOneYear: true
                });

                $('input[name="dates"]').datepicker({
                    format: 'dd-mm-yyyy',
                    multidate: true
                });

                let startTime0 = $('#startTime0'),
                    endTime0 = $('#endTime0');
                setRange(startTime0, endTime0, {
                    format: 'HH:mm'
                });

                if (provinceId) {
	                $('#addProduct').removeClass('disabled');
                }

                blockProvince.on('change', function () {
                    $('#addProduct').removeClass('disabled');
	                $('#tableProduct tbody').html('');
	                productFlashSale = [];
	                provinceId = $(this).val();
                });

	            onChangeAvailableType();
                let currentTotalRangeTime = <?=$blockTime ? count($blockTime) : 0?>;
                onAddRangeTime(currentTotalRangeTime);

                $(document).on('click', '.btn-remove-range-time', function () {
                    $(this).parents('.block-range-time').remove();
                });

                <?php foreach ($blockTime as $key => $value): ?>
                    let tempStartTime<?=$key?> = $('#startTime<?=$key?>'),
                        tempEndTime<?=$key?> = $('#endTime<?=$key?>');
                    setRange(tempStartTime<?=$key?>, tempEndTime<?=$key?>, {
                        format: 'HH:mm'
                    });
                <?php endforeach; ?>

	            function doSearchPromotions(pId) {
		            $.ajax({
			            url: "<?=admin_url('admin-ajax.php')?>",
			            data: {
				            action: "get_promotion_for_product",
				            productId: pId
			            },
			            dataType: "json",
			            type: "GET",
			            success: function (res) {
				            let html = "<option>--- Chọn chương trình ---</option>";
                            $.each(res.result, function (index, value) {
	                            html += "<option value='" + value.id + "'>" + value.name + "</option>";
                            });
				            jQuery('#promotion').html(html);
			            },
			            complete: function () {
				            //
			            }
		            });
	            }
            });

        </script>
        <?php
    }

    public function savePostCallback()
    {
        global $post_type, $post_ID;

        if ($_POST && $post_type == 'flash_sale') {
            // Save product flash sale
            $productFlashSale = [];
            if (isset($_POST['productId'])) {
                $productIds = $_POST['productId'];
                $promotionIds = $_POST['promotionId'];
                $quantities = $_POST['quantity'];
                $soldQuantityFakes = $_POST['soldQuantityFake'];

                foreach ($productIds as $key => $productId) {
                    $temp = new \stdClass();
                    $temp->id = $productId;
                    $temp->promotionId = $promotionIds[$key];
                    $temp->quantity = $quantities[$key];
                    $temp->soldQuantityFake = $soldQuantityFakes[$key];

                    $productFlashSale[] = $temp;
                }
            }
            update_post_meta($post_ID, 'productFlashSale', $productFlashSale);

            // Save time show
            $startDate = $_POST['startDate'];
            $endDate = $_POST['endDate'];
            $availableType = $_POST['availableType'];
            $startTime = $_POST['startTime'];
            $endTime = $_POST['endTime'];

            $timeBefore = 0;
            foreach ($_POST['acf'] as $key => $val) {
                $customField = acf_get_field($key);
                if ($customField['name'] == 'time_display_before') {
                    $timeBefore = $val;
                }
            }

            $availableValue = [];
            if ($availableType == 'day') {
                $availableValue = $_POST['day'] ?? [];
            } elseif ($availableType == 'dates') {
                $availableValue = $_POST['dates'] ?? [];
            }

            $arrTimeShow = [];
            $arrTimeActive = [];
            $arrDateAvailable = HelperFlashSale::getDateAvailable($startDate, $endDate, [
                'availableType' => $availableType,
                'availableValue' => $availableValue,
            ]);
            update_post_meta($post_ID, 'available_date', $arrDateAvailable);
            // Format and save time available
            $arrTimeAvailable = [];
            if (!empty($startTime)) {
                foreach ($startTime as $k => $val) {
                    $begin = new DateTime(date_i18n('H:i', strtotime($val)));
                    $end = new DateTime(date_i18n('H:i', strtotime($endTime[$k])));

                    $interval = DateInterval::createFromDateString('1 minutes');
                    $period = new DatePeriod($begin, $interval, $end);
                    foreach ($period as $dt) {
                        $arrTimeAvailable[] = $dt->format("H:i");
                    }
                }
            } else {
                $begin = new DateTime('00:00');
                $end = new DateTime('23:59');

                $interval = DateInterval::createFromDateString('1 minutes');
                $period = new DatePeriod($begin, $interval, $end);
                foreach ($period as $dt) {
                    $arrTimeAvailable[] = $dt->format("H:i");
                }
            }
            update_post_meta($post_ID, 'available_time', array_values(array_unique($arrTimeAvailable)));
            if (empty($availableType) && empty($startTime) && empty($endTime)) {
                $temp = new \stdClass();
                $temp->start = (new DateTime(date_i18n('Y-m-d 00:00:00', strtotime($startDate))))
                    ->modify("-{$timeBefore} minute")
                    ->format('Y-m-d H:i:s');
                $temp->end = (new DateTime(date_i18n('Y-m-d 23:59:59', strtotime($endDate))))
                    ->format('Y-m-d H:i:s');
                $arrTimeShow[] = $temp;

                $tempActive = new \stdClass();
                $tempActive->start = (new DateTime(date_i18n('Y-m-d 00:00:00', strtotime($startDate))))
                    ->format('Y-m-d H:i:s');
                $tempActive->end = (new DateTime(date_i18n('Y-m-d 23:59:59', strtotime($endDate))))
                    ->format('Y-m-d H:i:s');
                $arrTimeActive[] = $tempActive;
            } elseif ($availableType && empty($startTime) && empty($endTime)) {
                foreach ($arrDateAvailable as $date) {
                    $temp = new \stdClass();
                    $temp->start = (new DateTime(date_i18n('Y-m-d 00:00:00', strtotime($date))))
                        ->modify("-{$timeBefore} minute")
                        ->format('Y-m-d H:i:s');
                    $temp->end = (new DateTime(date_i18n('Y-m-d 23:59:59', strtotime($date))))
                        ->format('Y-m-d H:i:s');
                    $arrTimeShow[] = $temp;

                    $tempActive = new \stdClass();
                    $tempActive->start = (new DateTime(date_i18n('Y-m-d 00:00:00', strtotime($date))))
                        ->modify("-{$timeBefore} minute")
                        ->format('Y-m-d H:i:s');
                    $tempActive->end = (new DateTime(date_i18n('Y-m-d 23:59:59', strtotime($date))))
                        ->format('Y-m-d H:i:s');
                    $arrTimeActive[] = $tempActive;
                }
            } elseif (empty($availableType) && !empty($startTime) && !empty($endTime)) {
                foreach ($arrDateAvailable as $date) {
                    foreach ($startTime as $k => $val) {
                        $temp = new \stdClass();
                        $tempBegin = $val;
                        $tempEnd = $endTime[$k];
                        $temp->start = (new DateTime(date_i18n('Y-m-d H:i:s', strtotime($date . ' ' . $tempBegin))))
                            ->modify("-{$timeBefore} minute")
                            ->format('Y-m-d H:i:s');
                        $temp->end = (new DateTime(date_i18n('Y-m-d H:i:s', strtotime($date . ' ' . $tempEnd))))
                            ->format('Y-m-d H:i:s');
                        $arrTimeShow[] = $temp;

                        $tempActive = new \stdClass();
                        $tempActive->start = (new DateTime(date_i18n('Y-m-d H:i:s', strtotime($date . ' ' . $tempBegin))))
                            ->format('Y-m-d H:i:s');
                        $tempActive->end = (new DateTime(date_i18n('Y-m-d H:i:s', strtotime($date . ' ' . $tempEnd))))
                            ->format('Y-m-d H:i:s');
                        $arrTimeActive[] = $tempActive;
                    }
                }
            } elseif (!empty($availableType) && !empty($startTime) && !empty($endTime)) {
                foreach ($arrDateAvailable as $date) {
                    foreach ($startTime as $k => $val) {
                        $temp = new \stdClass();
                        $tempBegin = $val;
                        $tempEnd = $endTime[$k];
                        $temp->start = (new DateTime(date_i18n('Y-m-d H:i:s', strtotime($date . ' ' . $tempBegin))))
                            ->modify("-{$timeBefore} minute")
                            ->format('Y-m-d H:i:s');
                        $temp->end = (new DateTime(date_i18n('Y-m-d H:i:s', strtotime($date . ' ' . $tempEnd))))
                            ->format('Y-m-d H:i:s');
                        $arrTimeShow[] = $temp;

                        $tempActive = new \stdClass();
                        $tempActive->start = (new DateTime(date_i18n('Y-m-d H:i:s', strtotime($date . ' ' . $tempBegin))))
                            ->format('Y-m-d H:i:s');
                        $tempActive->end = (new DateTime(date_i18n('Y-m-d H:i:s', strtotime($date . ' ' . $tempEnd))))
                            ->format('Y-m-d H:i:s');
                        $arrTimeActive[] = $tempActive;
                    }
                }
            }
            update_post_meta($post_ID, 'rangeTimeShowFlashSale', $arrTimeShow);
            update_post_meta($post_ID, 'rangeTimeActiveFlashSale', $arrTimeActive);
        }
    }
    
}

// init
$flashSalePostType = new FlashSalePostType();