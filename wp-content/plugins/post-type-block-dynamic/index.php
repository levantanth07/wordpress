<?php
/*
Plugin Name: Post Type Block dynamic
Plugin URI: http://ggg.com.vn/
Description: Manage Block dynamic.
Author: hoang.daohuy <hoang.daohuy@ggg.com.vn>
Version: 1.0
*/

define('BLOCK_DYNAMIC_PLUGIN_URL', plugin_dir_url(__FILE__));

use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;
use Abstraction\Object\Result;

class BlockDynamicPostType
{

    const SCREEN_BLOCK_DYNAMIC = [
        'di_cho' => 'Đi chợ',
        'goi_do_an' => 'Gọi đồ ăn',
        'merchant_detail' => 'Merchant detail'
    ];

    public function __construct()
    {
        add_action('woocommerce_loaded', [$this, 'addHooks']);
        add_action('init', [$this, 'registerPostType'], 0);
        add_action('add_meta_boxes', [$this, 'blockDynamicMetaBox']);

        require_once 'libs/ajax/block-dynamic.php';
        require_once "libs/helper/helper.php";
        require_once "api/index.php";
    }

    public function addHooks()
    {
        add_action('save_post', [$this, 'savePostBlockDynamicCallback']);
        add_action('post_updated', [$this, 'clearCacheDynamicListing']);
        add_action( 'pre_get_posts', [$this, 'addQueryGetBlockDynamic'] );
        
        add_action('restrict_manage_posts', [$this, 'restrictManagePostType']);
        add_filter( 'parse_query', [$this, "customFilterBlockDynamic"]);

        add_filter('manage_edit-block_dynamic_columns', [$this, 'customEditColumns']);
        add_filter('manage_block_dynamic_posts_custom_column', [$this, 'customBlockDynamicColumn'], 10, 3);
        add_filter('manage_edit-block_dynamic_sortable_columns', [$this, 'registerSortableColumns']);
    }

    public function registerPostType()
    {
        $post_type = 'block_dynamic';
        $args = [
            'label' => 'Block dynamic',
            'labels' => [
                'name' => 'Block dynamic',
                'singular_name' => 'Block dynamic',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Block dynamic',
            ],
            'menu_position' => 20,
            'menu_icon' => 'dashicons-screenoptions',
            'rewrite' => ['slug' => 'block_dynamic'],
            'supports' => ['title'],
            'exclude_from_search' => false,
            'capabilities' => [
                /*'edit_post'          => 'edit_tgs-notification',
                'read_post'          => 'read_tgs-notification',
                'delete_post'        => 'delete_tgs-notification',
                'edit_posts'         => 'edit_tgs-notification',
                'edit_others_posts'  => 'edit_tgs-notification',
                'publish_posts'      => 'publish_tgs-notification',
                'read_private_posts' => 'read_private_tgs-notification',
                'create_posts'       => 'edit_tgs-notification',*/],
            'show_ui' => true,
            'public' => true,
            'show_in_rest' => true,
        ];
        register_post_type($post_type, $args);
    }

    public function blockDynamicMetaBox()
    {
        add_meta_box('block_dynamic_meta_box', 'Điều kiện và items', [$this, 'metaBoxContent'], 'block_dynamic', 'advanced', 'core');
    }

    public function metaBoxContent()
    {
        global $post_ID;

        $startDate = get_field('start_date', $post_ID);
        $endDate = get_field('end_date', $post_ID);
        $blockType = get_field('type', $post_ID);
        $provinceId = get_field('province_id', $post_ID);
        $availableType = get_field('available_type', $post_ID);
        $availableValue = get_field('available_value', $post_ID);
        $startTime = get_field('start_time', $post_ID) ?? [];
        $endTime = get_field('end_time', $post_ID) ?? [];
        $listItemSorted = get_field('list_item_sorted', $post_ID);
        $screen = get_field('screen', $post_ID);
        $merchant = get_field('merchant', $post_ID);
        $merchantId = $merchant->ID ?? null;

        $getDataItem = [];
        if ($screen == 'merchant_detail') {
            $getDataItem = Helper::getProductSortUnSortByMerchant($merchantId, $post_ID)->result;
        } else {
            $options = [
                'screen' => $screen
            ];
            if ($blockType == 'merchant') {
                $getDataItem = Helper::getMerchantByProvince($provinceId, $post_ID, $options)->result;
            } elseif ($blockType == 'product') {
                $getDataItem = Helper::getProductSortUnSortByProvince($provinceId, $post_ID, $options)->result;
            }
        }

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

        ?>
        <link href="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>/assets/css/font-awesome.min.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              rel="stylesheet">
        <link rel="stylesheet"
              href="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>assets/css/tempusdominus-bootstrap-4.min.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              crossorigin="anonymous"/>
        <link href="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>/assets/css/select2.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              rel="stylesheet">
        <script src="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>/assets/js/lazyload-img.js?ver=<?= \GDelivery\Libs\Config::VERSION ?>"
                type="text/javascript"></script>
        <link href="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>assets/css/bootstrap-datepicker3.min.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              rel="stylesheet">
        <style type="text/css">
            div[data-name='province_id'] .select2.select2-container {
                margin-left: 0 !important;
            }

            .text-bold {
                font-weight: bold;
            }
        </style>

        <!--        <script src="--><?//=BLOCK_DYNAMIC_PLUGIN_URL
        ?><!--/assets/js/jquery-3.5.1-min.js" type="text/javascript"></script>-->

        <link href="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>assets/css/custom-group-dynamic.css?v=<?= \GDelivery\Libs\Config::VERSION ?>"
              rel="stylesheet">

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
                    <label class="text-bold">Sắp xếp sản phẩm</label>
                    <div class="row col-12 box-sort" id="boxProduct" style="opacity: 1;">
                        <div class="form-group col-6 pdl-0">
                            <label for="group">Danh sách sản phẩm</label>
                            <div class="search col-12 pd-0 input-group">
                                <input class="form-control" type="text" name="search-product"
                                       placeholder="Hãy nhập 3 kí tự trở lên">
                                <div class="input-group-append">
                                    <button class="btn btn-primary search-product" type="button">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="product-loading" style="display: none; justify-content: center;">
                                <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"
                                      style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1; font-size: 30px;"></span>
                            </div>
                            <div class="list-item-group" id="listItemUnSorted"
                                 style="max-height: calc(100vh - 200px); overflow-y: auto; cursor: all-scroll;">
                                <?php if (isset($getDataItem['unSort'])): ?>
                                    <?php foreach ($getDataItem['unSort'] as $item): ?>
                                        <div class="block-item text-center white-text blue m-1 item-box col-3"
                                             data-id="<?= $item->id ?>">
                                            <img class="lazy" src="<?= $item->thumbnail ?>"
                                                 data-src="<?= $item->thumbnail ?>">
                                            <span class="title-item"
                                                  title="<?= $item->name ?>"><?= $item->name ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group col-6">
                            <div class="row">
                                <label class="col-md-5" for="group">Danh sách sắp xếp</label>
                                <div class="col-md-7">
                                    <select class="form-select" name="sortType" style="float: right;">
                                        <option value="nearest">Sắp xếp theo khoảng cách gần nhất</option>
                                    </select>
                                </div>
                            </div>
                            <div class="list-item-group" id="listItemSorted"
                                 style="max-height: calc(100vh - 162px); min-height: 338px; overflow-y: auto; cursor: all-scroll;">
                                <?php if (isset($getDataItem['sorted'])): ?>
                                    <?php foreach ($getDataItem['sorted'] as $item): ?>
                                        <div class="block-item text-center white-text blue m-1 item-box col-3"
                                             data-id="<?= $item->id ?>">
                                            <img class="lazy" src="<?= $item->thumbnail ?>"
                                                 data-src="<?= $item->thumbnail ?>">
                                            <span class="title-item"
                                                  title="<?= $item->name ?>"><?= $item->name ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="listItemSorted" value="<?= $listItemSorted ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmModalLabel">Bạn có muốn xóa tab "<span
                                    class="tab-name"></span>" không?</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Không</button>
                        <button type="button" class="btn btn-primary confirm-delete" data-tab-id=""
                                data-dismiss="modal">Có
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="justify-content-center loading" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <script src="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>/assets/js/bootstrap.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"
                type="text/javascript"></script>
        <script src="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>assets/js/moment.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
        <script src="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>assets/js/tempusdominus-bootstrap-4.min.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"
                crossorigin="anonymous"></script>

        <script src="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>/assets/js/sortable.min.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
        <script src="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>/assets/js/select2.min.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
        <script src="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>assets/js/product-group.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
        <script src="<?= BLOCK_DYNAMIC_PLUGIN_URL ?>assets/js/bootstrap-datepicker.js"></script>
        <script type="text/javascript">
					(jQuery)(function ($) {
						Sortable.create(listItemUnSorted, {
							animation: 100,
							group: 'list-item-group',
							draggable: '.block-item',
							handle: '.block-item',
							sort: true,
							filter: '.sortable-disabled',
							chosenClass: 'active'
						});
						Sortable.create(listItemSorted, {
							animation: 100,
							group: 'list-item-group',
							draggable: '.block-item',
							handle: '.block-item',
							sort: true,
							filter: '.sortable-disabled',
							chosenClass: 'active'
						});

						var dataSort = {
							listItems: []
						};

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

						$('input[name="search-product"]').on('keyup', function () {
							searchItem($(this).val());
						});

						let blockProvince = $("div[data-name='province_id']");
						blockProvince.find('select').on('change', function () {
							let listItemSortedElem = $('#listItemSorted'),
								listProductElem = $('#listItemUnSorted'),
								listProductHtml = '',
								listItemSortedHtml = '';

							let blockType = $("div[data-name='type'] select").val();
							let screen = $("div[data-name='screen'] input[type='radio']:checked").val();
							if (this.value !== '') {
								let provinceId = this.value;
								if (typeof dataSort.listItems[screen + '_' + blockType + '_' + provinceId] === "undefined") {
									$.ajax({
										'type': 'get',
										'url': '<?=admin_url('admin-ajax.php')?>',
										'dataType': 'json',
										'data': {
											action: 'get_item_by_province',
											provinceId: provinceId,
											blockType: blockType,
											screen: screen
										},
										'success': function (res) {
											if (res.messageCode == '1') {
												dataSort.listItems[screen + '_' + blockType + '_' + provinceId] = res.result;
												listProductHtml += generateHtmlUnSort(res.result.unSort);
												listItemSortedHtml += generateHtmlSorted(res.result.sorted);
											}
											listProductElem.html(listProductHtml);
											Sortable.create(listItemUnSorted, {
												animation: 100,
												group: 'list-item-group',
												draggable: '.block-item',
												handle: '.block-item',
												sort: true,
												filter: '.sortable-disabled',
												chosenClass: 'active'
											});
											listItemSortedElem.html(listItemSortedHtml);
											Sortable.create(listItemSorted, {
												animation: 100,
												group: 'list-item-group',
												draggable: '.block-item',
												handle: '.block-item',
												sort: true,
												filter: '.sortable-disabled',
												chosenClass: 'active'
											});
										},
										'error': function (x, y, z) {
											console.log('Có lỗi xảy ra! Hãy tải lại trang!');
										},
										'complete': function () {
											$('.group-loading').css('display', 'none');
										}
									});
								} else {
									let data = dataSort.listItems[screen + '_' + blockType + '_' + provinceId];
									listProductHtml += generateHtmlUnSort(data.unSort);
									listItemSortedHtml += generateHtmlSorted(data.sorted);
									listProductElem.html(listProductHtml);
									Sortable.create(listItemUnSorted, {
										animation: 100,
										group: 'list-item-group',
										draggable: '.block-item',
										handle: '.block-item',
										sort: true,
										filter: '.sortable-disabled',
										chosenClass: 'active'
									});
									listItemSortedElem.html(listItemSortedHtml);
									Sortable.create(listItemSorted, {
										animation: 100,
										group: 'list-item-group',
										draggable: '.block-item',
										handle: '.block-item',
										sort: true,
										filter: '.sortable-disabled',
										chosenClass: 'active'
									});
								}
							} else {
								listProductElem.html('');
								listItemSortedElem.html('');
							}
						});

						let blockMerchant = $("div[data-name='merchant']");
						blockMerchant.find('select').on('change', function () {
							let listItemSortedElem = $('#listItemSorted'),
								listProductElem = $('#listItemUnSorted'),
								listProductHtml = '',
								listItemSortedHtml = '';

							if (this.value !== '') {
								let merchantId = this.value;
								if (typeof dataSort.listItems[merchantId] === "undefined") {
									$.ajax({
										'type': 'get',
										'url': '<?=admin_url('admin-ajax.php')?>',
										'dataType': 'json',
										'data': {
											action: 'get_item_by_merchant',
											merchantId: merchantId,
										},
										'success': function (res) {
											if (res.messageCode == '1') {
												dataSort.listItems[merchantId] = res.result;
												listProductHtml += generateHtmlUnSort(res.result.unSort);
												listItemSortedHtml += generateHtmlSorted(res.result.sorted);
											}
											listProductElem.html(listProductHtml);
											Sortable.create(listItemUnSorted, {
												animation: 100,
												group: 'list-item-group',
												draggable: '.block-item',
												handle: '.block-item',
												sort: true,
												filter: '.sortable-disabled',
												chosenClass: 'active'
											});
											listItemSortedElem.html(listItemSortedHtml);
											Sortable.create(listItemSorted, {
												animation: 100,
												group: 'list-item-group',
												draggable: '.block-item',
												handle: '.block-item',
												sort: true,
												filter: '.sortable-disabled',
												chosenClass: 'active'
											});
										},
										'error': function (x, y, z) {
											console.log('Có lỗi xảy ra! Hãy tải lại trang!');
										},
										'complete': function () {
											$('.group-loading').css('display', 'none');
										}
									});
								} else {
									let data = dataSort.listItems[merchantId];
									listProductHtml += generateHtmlUnSort(data.unSort);
									listItemSortedHtml += generateHtmlSorted(data.sorted);
									listProductElem.html(listProductHtml);
									Sortable.create(listItemUnSorted, {
										animation: 100,
										group: 'list-item-group',
										draggable: '.block-item',
										handle: '.block-item',
										sort: true,
										filter: '.sortable-disabled',
										chosenClass: 'active'
									});
									listItemSortedElem.html(listItemSortedHtml);
									Sortable.create(listItemSorted, {
										animation: 100,
										group: 'list-item-group',
										draggable: '.block-item',
										handle: '.block-item',
										sort: true,
										filter: '.sortable-disabled',
										chosenClass: 'active'
									});
								}
							} else {
								listProductElem.html('');
								listItemSortedElem.html('');
							}
						});

						resetDataSortItem();
						updateDataItemSortedWhenDragDrop();
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
					});
        </script>
        <?php
    }

    public function savePostBlockDynamicCallback()
    {
        global $post_ID;

        if ($_POST) {
            $startDate = $_POST['startDate'];
            $endDate = $_POST['endDate'];
            $availableType = $_POST['availableType'];
            $startTime = $_POST['startTime'];
            $endTime = $_POST['endTime'];
            $listItemSorted = $_POST['listItemSorted'];
            $sortType = $_POST['sortType'];

            $availableValue = [];
            if ($availableType == 'day') {
                $availableValue = $_POST['day'] ?? [];
            } elseif ($availableType == 'dates') {
                $availableValue = $_POST['dates'] ?? [];
            }
            if ($availableType == 'day' || $availableType == 'dates') {
                update_post_meta($post_ID, 'available_value', $availableValue);
            }

            foreach ($startTime as $key => $val) {
                if (empty($val) || empty($endTime[$key])) {
                    unset($startTime[$key], $endTime[$key]);
                }
            }

            update_post_meta($post_ID, 'start_date', $startDate);
            update_post_meta($post_ID, 'end_date', $endDate);
            update_post_meta($post_ID, 'available_type', $availableType);
            update_post_meta($post_ID, 'start_time', $startTime);
            update_post_meta($post_ID, 'end_time', $endTime);
            update_post_meta($post_ID, 'list_item_sorted', $listItemSorted);
            update_post_meta($post_ID, 'sort_type', $sortType);

            // Format and save date available
            $begin = new DateTime(date_i18n('Y-m-d', strtotime($startDate)));
            $end = new DateTime(date_i18n('Y-m-d', strtotime('+1 day', strtotime($endDate))));
            $interval = DateInterval::createFromDateString('1 day');
            $period = new DatePeriod($begin, $interval, $end);
            $arrDateAvailable = [];
            if ($availableType == 'day') {
                $arrAvailableValue = $availableValue;
                foreach ($period as $dt) {
                    $day = strtolower($dt->format("l"));
                    $date = $dt->format("d-m-Y");

                    if (in_array($day, $arrAvailableValue)) {
                        $arrDateAvailable[] = $date;
                    }
                }
            } elseif ($availableType == 'dates') {
                $arrAvailableValue = explode(',', $availableValue);
                foreach ($period as $dt) {
                    $date = $dt->format("d-m-Y");

                    if (in_array($date, $arrAvailableValue)) {
                        $arrDateAvailable[] = $date;
                    }
                }
            } else {
                foreach ($period as $dt) {
                    $arrDateAvailable[] = $dt->format("d-m-Y");
                }
            }
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

            $this->clearCacheDynamicListing();
        }
    }

    public function clearCacheDynamicListing()
    {
        $gBackendService = new \GDelivery\Libs\GBackendService();
        $tags = 'dynamic-listing';
        $gBackendService->clearRedisCache($tags);
    }

    public function customEditColumns($columns)
    {
        $newColumns = array();

        if (isset($columns['cb'])) {
            $newColumns['cb'] = $columns['cb'];
            unset($columns['cb']);
        }
        $newColumns['title'] = $columns['title'];
        $newColumns['order'] = __('Thứ tự');
        $newColumns['screen'] = __('Trang hiển thị');

        return array_merge($newColumns, $columns);
    }

    public function customBlockDynamicColumn($column, $postId)
    {
        switch ($column) {
            case 'order':
                echo get_field('order', $postId, true);
                break;
            case 'screen':
                $screen = get_field('screen', $postId, true);
                echo self::SCREEN_BLOCK_DYNAMIC[$screen] ?: '';
                break;
        }
    }

    public function registerSortableColumns($columns)
    {
        $columns['order'] = 'order_meta';

        return $columns;
    }

    public function addQueryGetBlockDynamic($query) {
        global $post_type;
        if (
            $post_type == 'block_dynamic'
        ) {
            if (
                isset($_GET['orderby'])
                && $_GET['orderby'] == 'order_meta'
            ) {
                $query->set( 'orderby', 'meta_value_num' );
                $query->set( 'meta_key', 'order' );
            }
        }
    }

    public function restrictManagePostType()
    {
        $postType = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
        if ($postType == 'block_dynamic') {
            $current = isset($_GET['screen']) ? $_GET['screen'] : '';
            ?>
            <select name="screen">
                <option value=""><?php _e('Filter By Screen'); ?></option>
                <?php
                foreach (self::SCREEN_BLOCK_DYNAMIC as $key => $screen) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        $key,
                        $key == $current ? ' selected="selected"' : '',
                        $screen
                    );
                }
                ?>
            </select>
            <?php
        }
    }

    public function customFilterBlockDynamic( $query ) {
        global $pagenow;
        $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
        if (
            is_admin()
            && $pagenow=='edit.php'
            && $post_type == 'block_dynamic') {
            if (! empty( $_GET['screen'] )) {
//                $query->query_vars['meta_key'] = 'screen';
//                $query->query_vars['meta_value'] = $_GET['screen'];
                $query->meta_query[] = [
                    'key' => 'screen',
                    'value' => $_GET['screen'],
                    'compare' => '=',
                ];
            }
        }
//        echo \json_encode($query);
    }
} // end class

// init
$blockDynamicPostType = new BlockDynamicPostType();
