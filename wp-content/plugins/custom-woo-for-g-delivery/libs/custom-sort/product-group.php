<?php
add_action('admin_menu', 'registerSortProductGroup');
function registerSortProductGroup()
{
    add_menu_page(
        'Custom Sort',
        'Custom Sort',
        'edit_posts',
        'custom_sort',
        'customSort',
        'dashicons-media-spreadsheet',
    );
}

function customSort()
{
    $bookingService = new \GDelivery\Libs\BookingService();
    $listProvinces = $bookingService->getProvinces();
    if ($listProvinces->messageCode == \Abstraction\Object\Message::SUCCESS) {
        $provinces = $listProvinces->result;
    } else {
        $provinces = [];
    }
    ?>
    <link href="<?=bloginfo('template_url')?>/assets/css/font-awesome.min.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <link href="<?=bloginfo('template_url')?>/assets/css/select2.css?v=<?=\GDelivery\Libs\Config::VERSION?>" rel="stylesheet">
    <script src="<?= bloginfo('template_url') ?>/assets/js/lazyload-img.js?ver=<?= \GDelivery\Libs\Config::VERSION ?>"
            type="text/javascript"></script>
    <style type="text/css">
        .product-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0;
            height: 140px;
            width: 90px;
            max-width: 23%;
            line-height: 140px;
            border: #ddd 1px solid;
        }
        .product-box span {
            height: 42px !important;
            overflow: hidden;
            line-height: 20px;
        }
        .product-box img {
            height: 90px;
            width: 90px;
        }
        .block-content-suggestion {
            max-height: calc(100vh/2 + 100px);
            overflow-y: auto;
        }
        .notice-success {
            margin-left: 0;
        }
        .pd-0 {
            padding: 0;
        }
        .pdl-0 {
            padding-left: 0;
        }
        .pdl-4 {
            padding-left: 4px;
        }
        #tabContent label {
            width: 100%;
        }
    </style>
    <h1>Custom sort</h1>

    <div class="sort-tabs">
        <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <a class="nav-item nav-link active" id="group-product-tab" data-toggle="tab"
                   href="#group-product" role="tab" aria-controls="group-product" aria-selected="true">
                    Nhóm sản phẩm</a>
                <a class="nav-item nav-link" id="hotdeal-tab" data-toggle="tab" href="#hotdeal" role="tab"
                   aria-controls="hotdeal" aria-selected="false">Home - Sản phẩm hotdeal</a>
                <a class="nav-item nav-link" id="suggestion-tab" data-toggle="tab" href="#suggestion" role="tab"
                   aria-controls="suggestion" aria-selected="false">Home - Sản phẩm gợi ý</a>
                <a class="nav-item nav-link" id="product-on-group-tab" data-toggle="tab"
                   href="#product-on-group" role="tab" aria-controls="product-on-group" aria-selected="false">
                    Sản phẩm trong nhóm</a>
            </div>
        </nav>
        <div class="tab-content" id="tabContent">
            <br>
            <div class="tab-pane fade show active" id="group-product" role="tabpanel"
                 aria-labelledby="group-product-tab">
                <div id="setting-sort-group-success" class="notice notice-success settings-error is-dismissible" style="display: none;">
                    <p><strong>Cập nhật thành công</strong></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>
                <div class="col-6 pdl-0">
                    <div class="form-group">
                        <label for="t1-province">Tỉnh thành</label>
                        <select id="t1-province" class="form-control">
                            <option value="">----- Chọn tỉnh thành -----</option>
                            <?php
                            foreach ($provinces as $one) :?>
                            <option value="<?= $one->id ?>"><?= $one->name ?></option>
                            <?php
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="t1-brand">Thương hiệu</label>
                        <div class="brand-loading" style="display: none; justify-content: center; max-width: 25rem;">
                            <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"
                                  style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1;"></span>
                        </div>
                        <select id="t1-brand" class="form-control" placeholder="--- Chọn thương hiệu ---">
                            <option value="">----- Chọn thương hiệu -----</option>
                        </select>
                    </div>
                </div>
                <div class="row col-12 box-sort" style="display: none;">
                    <div class="form-group col-6 pdl-0">
                        <label for="group">Danh sách nhóm</label>
                        <div class="search col-12 pd-0">
                            <input class="form-control" type="text" name="t1-search-product">
                        </div>
                        <div class="group-loading" style="display: none; justify-content: center;">
                            <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true" style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1; font-size: 30px;"></span>
                        </div>
                        <ul class="list-group" id="listGroupProduct" style="max-height: 600px; overflow-y: auto; cursor: all-scroll;">
                        </ul>
                    </div>
                    <div class="form-group col-6">
                        <label for="group">Danh sách sắp xếp</label>
                        <ul class="list-group" id="listGroupProductSorted" style="max-height: 600px; min-height: 300px; overflow-y: auto; cursor: all-scroll;">
                        </ul>
                    </div>
                </div>
                <button class="button-primary btn-save" data-tab="group-product">Save</button>
            </div>
            <div class="tab-pane fade" id="hotdeal" role="tabpanel" aria-labelledby="hotdeal-tab">
                <div class="notice notice-success settings-error is-dismissible" style="display: none;">
                    <p><strong>Cập nhật thành công</strong></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>
                <div class="product-loading" style="display: none; justify-content: center;">
                    <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true" style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1; font-size: 30px;"></span>
                </div>
                <div class="col-6 pdl-0">
                    <div class="form-group">
                        <label for="t2-province">Tỉnh thành</label>
                        <select id="t2-province" class="form-control">
                            <option value="">----- Chọn tỉnh thành -----</option>
                            <?php
                            foreach ($provinces as $one) :?>
                                <option value="<?= $one->id ?>"><?= $one->name ?></option>
                            <?php
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>
                <div id="listProductHotDeal" class="d-flex flex-center flex-wrap" style="cursor: all-scroll">
                </div>
                <br>
                <button class="button-primary btn-save" data-tab="hotdeal">Save</button>
            </div>
            <div class="tab-pane fade" id="suggestion" role="tabpanel" aria-labelledby="suggestion-tab">
                <div class="notice notice-success settings-error is-dismissible" style="display: none;">
                    <p><strong>Cập nhật thành công</strong></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>
                <div class="suggestion-loading" style="display: none; justify-content: center;">
                    <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true" style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1; font-size: 30px;"></span>
                </div>
                <div class="form-group">
                    <label for="t3-province">Tỉnh thành</label>
                    <select id="t3-province" class="form-control">
                        <option value="">----- Chọn tỉnh thành -----</option>
                        <?php
                        foreach ($provinces as $one) :?>
                            <option value="<?= $one->id ?>"><?= $one->name ?></option>
                        <?php
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="t3-tag">Tag</label>
                    <div class="tag-loading" style="display: none; justify-content: center; max-width: 25rem;">
                            <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"
                                  style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1;"></span>
                    </div>
                    <select id="t3-tag" class="form-control">
                        <option value="">----- Chọn tag -----</option>
                    </select>
                </div>
                <div class="row col-12 pdl-0" id="listAllProductSuggestion" style="display: none;">
                    <div class="form-group col-6">
                        <label>Danh sách sản phẩm</label>
                        <div class="product-loading" style="display: none; justify-content: center;">
                            <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true" style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1; font-size: 30px;"></span>
                        </div>
                        <div class="search col-12 pdl-4">
                            <input class="form-control" type="text" name="t3-search-product">
                        </div>
                        <div class="list-product un-sort row" id="t3ProductUnSort" style="max-height: 600px; overflow-y: auto; cursor: all-scroll; margin-left: 0;">
                        </div>
                    </div>
                    <div class="form-group col-6">
                        <label>Danh sách sản phẩm sắp xếp</label>
                        <div class="list-product sorted row" id="t3ProductSorted" style="max-height: 600px; min-height: 300px; overflow-y: auto; cursor: all-scroll;">
                        </div>
                    </div>
                </div>
                <div id="listProductSuggestion" class="flex-center flex-wrap block-content-suggestion" style="cursor: all-scroll; display: flex;"></div>
                <br>
                <button class="button-primary btn-save" data-tab="suggestion">Save</button>
            </div>
            <div class="tab-pane fade" id="product-on-group" role="tabpanel" aria-labelledby="product-on-group-tab">
                <div id="message-product-on-group-success" class="notice notice-success settings-error is-dismissible" style="display: none;">
                    <p><strong>Cập nhật thành công</strong></p>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
                </div>
                <div class="col-6 pdl-0">
                    <div class="form-group">
                        <label for="t4-province">Tỉnh thành</label>
                        <select id="t4-province" class="form-control">
                            <option value="">----- Chọn tỉnh thành -----</option>
                            <?php
                            foreach ($provinces as $one) :?>
                                <option value="<?= $one->id ?>"><?= $one->name ?></option>
                            <?php
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="t4-brand">Thương hiệu</label>
                        <div class="t4-brand-loading" style="display: none; justify-content: center; max-width: 25rem;">
                            <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"
                                  style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1;"></span>
                        </div>
                        <select id="t4-brand" class="form-control" placeholder="--- Chọn thương hiệu ---">
                            <option value="">----- Chọn thương hiệu -----</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="t4-group">Nhóm món</label>
                        <div class="t4-group-loading" style="display: none; justify-content: center; max-width: 25rem;">
                            <span class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"
                                  style="display: flex; position: absolute; justify-content: center; margin-top: 7px; z-index: 1;"></span>
                        </div>
                        <select id="t4-group" class="form-control" placeholder="--- Chọn nhóm món ---">
                            <option value="">----- Chọn nhóm món -----</option>
                        </select>
                    </div>
                </div>
                <div id="listProductOnGroupId" class="d-flex flex-center flex-wrap" style="cursor: all-scroll">
                </div>
                <br>
                <button class="button-primary btn-save" data-tab="product-on-group">Save</button>
            </div>
        </div>
    </div>
    <script src="<?= bloginfo('template_url') ?>/assets/js/bootstrap.js?v=<?= \GDelivery\Libs\Config::VERSION ?>" type="text/javascript"></script>
    <script src="<?= bloginfo('template_url') ?>/assets/js/sortable.min.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
    <script src="<?= bloginfo('template_url') ?>/assets/js/select2.min.js?v=<?= \GDelivery\Libs\Config::VERSION ?>"></script>
    <!-- Js sort group product -->
    <script type="text/javascript">
        (function sortGroupProduct($) {
            var dataSort = {
                listBrand: [],
                listGroup: [],
                t4ListGroup: [],
                t4ListProduct: [],
                listProductOnGroup: [],
                arrProductHotDeal: [],
                arrProductSuggestion: []
            };

            // <editor-fold defaultstate="collapsed" desc="Notice success">
            $('.notice-dismiss').on('click', function () {
                $(this).parent().hide();
            });
            // </editor-fold>

            // <editor-fold defaultstate="collapsed" desc="Tab 1: Sort group product">
            $('#t1-province').on('change', function () {
                $('#group-product .box-sort').hide();
                $('#group-product .search input').val('');

                let listGroupElem = $('#listGroupProduct'),
                    listGroupSortedElem = $('#listGroupProductSorted');
                if (this.value !== '') {
                    let provinceId = this.value;
                    $('.brand-loading').css('display', 'flex');
                    $('#t1-brand').css('opacity', 0.7);
                    if (typeof dataSort.listBrand[provinceId] === "undefined") {
                        $.ajax({
                            'type' : 'get',
                            'url' : '<?=admin_url('admin-ajax.php')?>',
                            'dataType' : 'json',
                            'data' : {
                                action: 'setting_get_brands_in_province',
                                provinceId: provinceId
                            },
                            'success' : function (res) {
                                dataSort.listBrand[provinceId] = res.result;
                                let optionBrand = generateOptionBrands(res.result);
                                $('#t1-brand').html(optionBrand).css('opacity', 1);
                                $('.brand-loading').hide();
                            },
                            'error' : function (x, y, z) {
                                console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                            }
                        });
                    } else {
                        let optionBrand = generateOptionBrands(dataSort.listBrand[provinceId]);
                        $('#t1-brand').html(optionBrand).css('opacity', 1);
                        $('.brand-loading').hide();
                    }
                } else {
                    $('#t1-brand').html('<option value="">----- Chọn thương hiệu -----</option>');
                    listGroupElem.html('<i>Hãy chọn tỉnh thành và thương hiệu</i>');
                }
                listGroupSortedElem.html('');
            });
            $('#t1-brand').on('change', function () {
                $('#group-product .search input').val('');

                let listGroupElem = $('#listGroupProduct'),
                    listGroupSortedElem = $('#listGroupProductSorted'),
                    provinceId = $('#t1-province').val();
                if (this.value !== '') {
                    $('#group-product .box-sort').show();
                    $('.group-loading').css('display', 'flex');
                    listGroupElem.css('opacity', 0.7);
                    let brandId = this.value;
                    let listGroupHtml = '',
                        listGroupSortedHtml = '';

                    if (typeof dataSort.listGroup[provinceId+'_'+brandId] === "undefined") {
                        $.ajax({
                            'type' : 'get',
                            'url' : '<?=admin_url('admin-ajax.php')?>',
                            'dataType' : 'json',
                            'data' : {
                                action: 'get_list_group_sorted_by_province_brand',
                                provinceId: provinceId,
                                brandId: brandId
                            },
                            'success' : function (res) {
                                if (res.messageCode == '1') {
                                    dataSort.listGroup[provinceId+'_'+brandId] = res.result;
                                    $.each(res.result.unSort, function (index, value) {
                                        listGroupHtml += '<li class="list-group-item" data-id="' + value.id + '">' + value.name + '</li>';
                                    });
                                    $.each(res.result.sorted, function (index, value) {
                                        listGroupSortedHtml += '<li class="list-group-item" data-id="' + value.id + '">' + value.name + '</li>';
                                    });
                                    listGroupElem.html(listGroupHtml);
                                    listGroupSortedElem.html(listGroupSortedHtml);
                                    Sortable.create(listGroupProduct, {
                                        animation: 100,
                                        group: 'list-1',
                                        draggable: '.list-group-item',
                                        handle: '.list-group-item',
                                        sort: true,
                                        filter: '.sortable-disabled',
                                        chosenClass: 'active'
                                    });
                                    Sortable.create(listGroupProductSorted, {
                                        animation: 100,
                                        group: 'list-1',
                                        draggable: '.list-group-item',
                                        handle: '.list-group-item',
                                        sort: true,
                                        filter: '.sortable-disabled',
                                        chosenClass: 'active'
                                    });
                                }
                            },
                            'error' : function (x, y, z) {
                                console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                            },
                            'complete' : function () {
                                $('.group-loading').css('display', 'none');
                                listGroupElem.css('opacity', 1);
                            }
                        });
                    } else {
                        $.each(dataSort.listGroup[provinceId+'_'+brandId].unSort, function (index, value) {
                            listGroupHtml += '<li class="list-group-item" data-id="' + value.id + '">' + value.name + '</li>';
                        });
                        $.each(dataSort.listGroup[provinceId+'_'+brandId].sorted, function (index, value) {
                            listGroupSortedHtml += '<li class="list-group-item" data-id="' + value.id + '">' + value.name + '</li>';
                        });
                        listGroupElem.html(listGroupHtml);
                        listGroupSortedElem.html(listGroupSortedHtml);
                        Sortable.create(listGroupProduct, {
                            animation: 100,
                            group: 'list-1',
                            draggable: '.list-group-item',
                            handle: '.list-group-item',
                            sort: true,
                            filter: '.sortable-disabled',
                            chosenClass: 'active'
                        });
                        Sortable.create(listGroupProductSorted, {
                            animation: 100,
                            group: 'list-1',
                            draggable: '.list-group-item',
                            handle: '.list-group-item',
                            sort: true,
                            filter: '.sortable-disabled',
                            chosenClass: 'active'
                        });
                        $('.group-loading').css('display', 'none');
                        listGroupElem.css('opacity', 1);
                    }
                } else {
                    $('#group-product .box-sort').hide();
                    listGroupElem.html('<i>Hãy chọn tỉnh thành và thương hiệu</i>');
                }
            });
            $('#group-product .btn-save').on('click', function () {
                let provinceId = $('#t1-province').val(),
                    brandId = $('#t1-brand').val();
                if (provinceId && brandId) {
                    let groupId = [],
                        thisElement = $(this),
                        oldHtml = thisElement.html();

                    addLoadingToElem(thisElement);
                    $('#listGroupProductSorted li').map(function(){
                        groupId.push($(this).attr('data-id'));
                    });

                    $.ajax({
                        'type' : 'get',
                        'url' : '<?=admin_url('admin-ajax.php')?>',
                        'dataType' : 'json',
                        'data' : {
                            action: 'update_sort_group',
                            provinceId: provinceId,
                            brandId: brandId,
                            groupId: groupId,
                        },
                        'success' : function (res) {
                            if (res.messageCode == '1') {
                                $('#setting-sort-group-success').show();
                                $('html, body').animate({
                                    scrollTop: 0
                                }, 500);
                                dataSort.listGroup[provinceId+'_'+brandId] = res.result;
                            }
                        },
                        'error' : function (x, y, z) {
                            console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                        },
                        'complete' : function () {
                            removeLoadingOnElem(thisElement, oldHtml);
                        }
                    });
                }
            });
            $(document).on('change keyup', '#group-product .search input', function () {
                let titleProduct = $("#listGroupProduct li.list-group-item"),
                    selectorBlockProduct = $("#listGroupProduct li.list-group-item"),
                    _val = $(this).val();
                if(parseInt(_val.length) >= 2){
                    selectorBlockProduct.hide(); // hide block product
                    // do search text
                    let temp = titleProduct.filter(function () {
                        return removeAccents($(this).text()).toLowerCase().indexOf(removeAccents(_val.toLowerCase())) > -1;
                    });

                    // display result
                    temp.show(); // display block product

                } else {
                    selectorBlockProduct.show();
                }
            });
            // </editor-fold>

            // <editor-fold defaultstate="collapsed" desc="Tab 2: Sort hotdeal product">
            $('#t2-province').on('change', function () {
                let provinceId = this.value;
                if (typeof dataSort.arrProductHotDeal[provinceId] === "undefined") {
                    $('#hotdeal .product-loading').css('display', 'flex');
                    let keySortOption = 'icook:province:' + provinceId + ':home-hotdeal:sort-product',
                        blockElem = $('#listProductHotDeal');
                    let data = {
                            action: 'load_product_by_group',
                            provinceId: provinceId,
                            keySortOption: keySortOption,
                            group: 'hot-deal',
                            page: 1,
                            numberPerPage: -1
                        },
                        blockProducts = '';
                    $.ajax({
                        url : '<?=admin_url('admin-ajax.php')?>',
                        type : 'post',
                        dataType : 'json',
                        data : data,
                        success : function (res) {
                            if (res.messageCode == 1) {
                                let listProducts = res.result;
                                dataSort.arrProductHotDeal[provinceId] = res.result;
                                $.each(listProducts, function (index, product) {
                                    blockProducts += '<div class="text-center white-text blue m-1 product-box" data-id="' + product.id + '">';
                                    blockProducts += '<img class="lazy" src="' + product.thumbnail + '"';
                                    blockProducts += 'data-src="' + product.thumbnail + '">';
                                    blockProducts += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                                    blockProducts += '</div>';
                                });
                                blockElem.html(blockProducts);
                                Sortable.create(listProductHotDeal, {
                                    animation: 100,
                                    chosenClass: 'active'
                                });
                            } else {
                                //
                            }
                        },
                        error : function (x, y, z) {
                            //
                        },
                        complete : function () {
                            blockElem.parent().find('.product-loading').hide();
                        }
                    });
                } else {
                    let blockProducts = '';
                    $.each(dataSort.arrProductHotDeal[provinceId], function (index, product) {
                        blockProducts += '<div class="text-center white-text blue m-1 product-box" data-id="' + product.id + '">';
                        blockProducts += '<img class="lazy" src="' + product.thumbnail + '"';
                        blockProducts += 'data-src="' + product.thumbnail + '">';
                        blockProducts += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                        blockProducts += '</div>';
                    });
                    $('#listProductHotDeal').html(blockProducts);
                    Sortable.create(listProductHotDeal, {
                        animation: 100,
                        chosenClass: 'active'
                    });
                }
            });
            $('#hotdeal .btn-save').on('click', function () {
                let productId = [],
                    provinceId = $('#t2-province').val();

                if (provinceId) {
                    let thisElement = $(this),
                        oldHtml = thisElement.html();

                    addLoadingToElem(thisElement);
                    $('#listProductHotDeal div.product-box').map(function(){
                        productId.push($(this).attr('data-id'));
                    });

                    $.ajax({
                        'type' : 'get',
                        'url' : '<?=admin_url('admin-ajax.php')?>',
                        'dataType' : 'json',
                        'data' : {
                            action: 'save_sort_product_hotdeal',
                            productId: productId,
                            provinceId: provinceId,
                            keySortOption: 'icook:province:' + provinceId + ':home-hotdeal:sort-product',
                            groupSlug: 'hot-deal'
                        },
                        'success' : function (res) {
                            if (res.messageCode == 1) {
                                $('#hotdeal .notice-success').show();
                                $('html, body').animate({
                                    scrollTop: 0
                                }, 500);
                                dataSort.arrProductHotDeal[provinceId] = res.result
                            }
                        },
                        'error' : function (x, y, z) {
                            console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                        },
                        'complete' : function () {
                            removeLoadingOnElem(thisElement, oldHtml);
                        }
                    });
                }
            });
            // </editor-fold>

            // <editor-fold defaultstate="collapsed" desc="Tab 3: Sort suggestion product on Home page">
            $('#t3-province').on('change', function () {
                $('#suggestion .tag-loading').css('display', 'flex');
                $('#listProductSuggestion').html('');
                $('#listAllProductSuggestion').hide();
                let provinceId = this.value;
                if (provinceId) {
                    $.ajax({
                        'type' : 'post',
                        'url' : '<?=admin_url('admin-ajax.php')?>',
                        'dataType' : 'json',
                        'data' : {
                            action: 'get_list_tag_by_province',
                            provinceId: provinceId
                        },
                        'success' : function (res) {
                            if (res.messageCode == 1) {
                                let html = '<option value="">----- Chọn tag -----</option>',
                                    listTag = res.result;
                                html += '<option value="tat-ca">Tất cả</option>';
                                $.each(listTag, function (key, val) {
                                    html += '<option value="' + val.slug + '">' + val.name + '</option>';
                                });
                                $('#t3-tag').html(html);
                            }
                        },
                        'error' : function (x, y, z) {
                            console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                        },
                        'complete' : function () {
                            $('#suggestion .tag-loading').hide();
                        }
                    });
                } else {
                    $('#t3-tag').html('<option value="">----- Chọn tag -----</option>');
                    $('#suggestion .tag-loading').hide();
                }
            });
            $('#t3-tag').on('change', function () {
                let provinceId = $('#t3-province').val();
                let tag = this.value;
                if (tag === 'tat-ca') {
                    $('#listAllProductSuggestion').show();
                    $('#listProductSuggestion').css('display', 'none');
                } else {
                    $('#listAllProductSuggestion').hide();
                    $('#listProductSuggestion').css('display', 'flex');
                }
                if (provinceId && tag) {
                    if (typeof dataSort.arrProductSuggestion[provinceId] === "undefined") {
                        dataSort.arrProductSuggestion[provinceId] = [];
                    }
                    if (typeof dataSort.arrProductSuggestion[provinceId][tag] === "undefined") {
                        let options = {
                            tag: tag,
                            page: 1,
                            perPage: -1,
                            provinceId: provinceId
                        };
                        let blockElem = $('#listProductSuggestion');
                        blockElem.parent().find('.suggestion-loading').css('display', 'flex');
                        $('#suggestion').css('opacity', 0.6);
                        let data = {
                                action: 'get_list_suggestion_sorted',
                                keySortOption: 'icook:province:' + provinceId + ':tag:' + tag,
                                tag: options.tag === 'tat-ca' ? '' : options.tag,
                                page: options.page,
                                numberPerPage: options.perPage,
                                provinceId: options.provinceId
                            },
                            blockProducts = '';
                        $.ajax({
                            url : '<?=admin_url('admin-ajax.php')?>',
                            type : 'post',
                            dataType : 'json',
                            data : data,
                            success : function (res) {
                                if (res.messageCode == 1) {
                                    let listProducts = res.result,
                                        tag = options.tag ?? 'tat-ca';

                                    dataSort.arrProductSuggestion[options.provinceId][tag] = res.result;
                                    if (tag === 'tat-ca') {
                                        let listProductElem = $('#t3ProductUnSort'),
                                            listProductSortedElem = $('#t3ProductSorted'),
                                            listProductHtml = '',
                                            listProductSortedHtml = '';

                                        $.each(listProducts.unSort, function (index, product) {
                                            listProductHtml += '<div class="t3-product-item text-center white-text blue m-1 product-box col-3" data-id="' + product.id + '">';
                                            listProductHtml += '<img class="lazy" src="' + product.thumbnail + '"';
                                            listProductHtml += 'data-src="' + product.thumbnail + '">';
                                            listProductHtml += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                                            listProductHtml += '</div>';
                                        });
                                        $.each(listProducts.sorted, function (index, product) {
                                            listProductSortedHtml += '<div class="t3-product-item text-center white-text blue m-1 product-box col-3" data-id="' + product.id + '">';
                                            listProductSortedHtml += '<img class="lazy" src="' + product.thumbnail + '"';
                                            listProductSortedHtml += 'data-src="' + product.thumbnail + '">';
                                            listProductSortedHtml += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                                            listProductSortedHtml += '</div>';
                                        });
                                        listProductElem.html(listProductHtml);
                                        listProductSortedElem.html(listProductSortedHtml);
                                        Sortable.create(t3ProductUnSort, {
                                            animation: 100,
                                            group: 'list-t3',
                                            draggable: '.t3-product-item',
                                            handle: '.t3-product-item',
                                            sort: true,
                                            filter: '.sortable-disabled',
                                            chosenClass: 'active'
                                        });
                                        Sortable.create(t3ProductSorted, {
                                            animation: 100,
                                            group: 'list-t3',
                                            draggable: '.t3-product-item',
                                            handle: '.t3-product-item',
                                            sort: true,
                                            filter: '.sortable-disabled',
                                            chosenClass: 'active'
                                        });
                                    } else {
                                        $.each(listProducts.sorted, function (index, product) {
                                            blockProducts += '<div class="text-center white-text blue m-1 product-box" data-id="' + product.id + '">';
                                            blockProducts += '<img class="lazy" src="' + product.thumbnail + '"';
                                            blockProducts += 'data-src="' + product.thumbnail + '">';
                                            blockProducts += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                                            blockProducts += '</div>';
                                        });
                                        blockElem.html(blockProducts);
                                        Sortable.create(listProductSuggestion, {
                                            animation: 100,
                                            chosenClass: 'active'
                                        });
                                    }
                                } else {
                                    //
                                }
                            },
                            error : function (x, y, z) {
                                //
                            },
                            complete : function () {
                                blockElem.parent().find('.suggestion-loading').hide();
                                $('#suggestion').css('opacity', 1);
                            }
                        });
                    } else {
                        if (tag === 'tat-ca') {
                            let listProductElem = $('#t3ProductUnSort'),
                                listProductSortedElem = $('#t3ProductSorted'),
                                listProductHtml = '',
                                listProductSortedHtml = '',
                                data = dataSort.arrProductSuggestion[provinceId][tag];

                            $.each(data.unSort, function (index, product) {
                                listProductHtml += '<div class="t3-product-item text-center white-text blue m-1 product-box col-3" data-id="' + product.id + '">';
                                listProductHtml += '<img class="lazy" src="' + product.thumbnail + '"';
                                listProductHtml += 'data-src="' + product.thumbnail + '">';
                                listProductHtml += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                                listProductHtml += '</div>';
                            });
                            $.each(data.sorted, function (index, product) {
                                listProductSortedHtml += '<div class="t3-product-item text-center white-text blue m-1 product-box col-3" data-id="' + product.id + '">';
                                listProductSortedHtml += '<img class="lazy" src="' + product.thumbnail + '"';
                                listProductSortedHtml += 'data-src="' + product.thumbnail + '">';
                                listProductSortedHtml += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                                listProductSortedHtml += '</div>';
                            });
                            listProductElem.html(listProductHtml);
                            listProductSortedElem.html(listProductSortedHtml);
                            Sortable.create(t3ProductUnSort, {
                                animation: 100,
                                group: 'list-t3',
                                draggable: '.t3-product-item',
                                handle: '.t3-product-item',
                                sort: true,
                                filter: '.sortable-disabled',
                                chosenClass: 'active'
                            });
                            Sortable.create(t3ProductSorted, {
                                animation: 100,
                                group: 'list-t3',
                                draggable: '.t3-product-item',
                                handle: '.t3-product-item',
                                sort: true,
                                filter: '.sortable-disabled',
                                chosenClass: 'active'
                            });
                        } else {
                            let blockProducts = '';
                            $.each(dataSort.arrProductSuggestion[provinceId][tag], function (index, product) {
                                blockProducts += '<div class="text-center white-text blue m-1 product-box" data-id="' + product.id + '">';
                                blockProducts += '<img class="lazy" src="' + product.thumbnail + '"';
                                blockProducts += 'data-src="' + product.thumbnail + '">';
                                blockProducts += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                                blockProducts += '</div>';
                            });
                            $('#listProductSuggestion').html(blockProducts);
                            Sortable.create(listProductSuggestion, {
                                animation: 100,
                                chosenClass: 'active'
                            });
                        }
                    }
                } else {
                    $('#listProductSuggestion').html('');
                }
            });
            $(document).on('change keyup', '#suggestion .search input', function () {
                let titleProduct = $("#suggestion span.title-product"),
                    selectorBlockProduct = $("#t3ProductUnSort .t3-product-item"),
                    _val = $(this).val();
                if(parseInt(_val.length) >= 2){
                    selectorBlockProduct.hide(); // hide block product
                    // do search text
                    let temp = titleProduct.filter(function () {
                        return removeAccents($(this).text()).toLowerCase().indexOf(removeAccents(_val.toLowerCase())) > -1;
                    });

                    // display result
                    temp.parent().show(); // display block product

                } else {
                    selectorBlockProduct.show();
                }
            });
            $('#suggestion .btn-save').on('click', function () {
                let productId = [],
                    provinceId = $('#t3-province').val(),
                    tag = $('#t3-tag').val();
                if (provinceId && tag) {
                    let thisElement = $(this),
                        oldHtml = thisElement.html();

                    addLoadingToElem(thisElement);
                    if (tag === 'tat-ca') {
                        $('#t3ProductSorted div.product-box').map(function(){
                            productId.push($(this).attr('data-id'));
                        });
                    } else {
                        $('#listProductSuggestion div.product-box').map(function(){
                            productId.push($(this).attr('data-id'));
                        });
                    }
                    $.ajax({
                        'type' : 'post',
                        'url' : '<?=admin_url('admin-ajax.php')?>',
                        'dataType' : 'json',
                        'data' : {
                            action: 'save_sort_product_suggestion',
                            productId: productId,
                            provinceId: provinceId,
                            tag: tag,
                            keySortOption: 'icook:province:' + provinceId + ':tag:' + tag
                        },
                        'success' : function (res) {
                            if (res.messageCode == 1) {
                                $('#suggestion .notice-success').show();
                                $('html, body').animate({
                                    scrollTop: 0
                                }, 500);
                                dataSort.arrProductSuggestion[provinceId][tag] = res.result;
                            }
                        },
                        'error' : function (x, y, z) {
                            console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                        },
                        'complete' : function () {
                            removeLoadingOnElem(thisElement, oldHtml);
                        }
                    });
                }
            });
            // </editor-fold>

            // <editor-fold defaultstate="collapsed" desc="Tab 4: Sort product on group">
            $('#t4-province').on('change', function () {
                $('#t4-brand').html('<option value="">----- Chọn thương hiệu -----</option>');
                $('#t4-group').html('<option value="">----- Chọn nhóm món -----</option>');
                $('#listProductOnGroupId').html('');
                if (this.value !== '') {
                    let provinceId = this.value;
                    $('.t4-brand-loading').css('display', 'flex');
                    $('#t4-brand').css('opacity', 0.7);
                    if (typeof dataSort.listProductOnGroup[provinceId] === "undefined") {
                        $.ajax({
                            'type' : 'get',
                            'url' : '<?=admin_url('admin-ajax.php')?>',
                            'dataType' : 'json',
                            'data' : {
                                action: 'setting_get_brands_in_province',
                                provinceId: provinceId
                            },
                            'success' : function (res) {
                                dataSort.listProductOnGroup[provinceId] = res.result;
                                let optionBrand = generateOptionBrands(res.result);
                                $('#t4-brand').html(optionBrand).css('opacity', 1);
                                $('.t4-brand-loading').hide();
                            },
                            'error' : function (x, y, z) {
                                console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                            }
                        });
                    } else {
                        let optionBrand = generateOptionBrands(dataSort.listProductOnGroup[provinceId]);
                        $('#t4-brand').html(optionBrand).css('opacity', 1);
                        $('.t4-brand-loading').hide();
                    }
                }
            });
            $('#t4-brand').on('change', function () {
                $('#t4-group').html('<option value="">----- Chọn nhóm món -----</option>');
                $('#listProductOnGroupId').html('');
                if (this.value !== '') {
                    $('.t4-group-loading').css('display', 'flex');
                    $('#t4-group').css('opacity', 0.7);
                    let provinceId = $('#t4-province').val(),
                        brandId = this.value;

                    if (typeof dataSort.t4ListGroup[provinceId+'_'+brandId] === "undefined") {
                        $.ajax({
                            'type' : 'get',
                            'url' : '<?=admin_url('admin-ajax.php')?>',
                            'dataType' : 'json',
                            'data' : {
                                action: 'get_list_group_sorted_and_product_by_province_brand',
                                provinceId: provinceId,
                                brandId: brandId
                            },
                            'success' : function (res) {
                                if (res.messageCode == 1) {
                                    dataSort.t4ListGroup[provinceId+'_'+brandId] = res.result.arrGroupSort;
                                    dataSort.t4ListProduct[provinceId+'_'+brandId] = res.result.listProduct;
                                    let html = '<option value="">----- Chọn nhóm món -----</option>';
                                    $.each(dataSort.t4ListGroup[provinceId+'_'+brandId], function (key, val) {
                                        html += '<option value="' + val.id + '">' + val.name + '</option>';
                                    });
                                    $('#t4-group').html(html).css('opacity', 1);
                                    $('.t4-group-loading').hide();
                                }
                            },
                            'error' : function (x, y, z) {
                                console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                            },
                            'complete' : function () {
                                $('.t4-group-loading').css('display', 'none');
                                $('#t4-group').css('opacity', 1);
                            }
                        });
                    } else {
                        let html = '<option value="">----- Chọn nhóm món -----</option>';
                        $.each(dataSort.t4ListGroup[provinceId+'_'+brandId], function (key, val) {
                            html += '<option value="' + val.id + '">' + val.name + '</option>';
                        });
                        $('#t4-group').html(html).css('opacity', 1);
                        $('.t4-group-loading').hide();
                    }
                }
            });
            $('#t4-group').on('change', function () {
                if (this.value !== '') {
                    let blockProducts = '',
                        provinceId = $('#t4-province').val(),
                        brandId = $('#t4-brand').val(),
                        groupId = $('#t4-group').val(),
                        listProducts = dataSort.t4ListProduct[provinceId+'_'+brandId][groupId].products;

                    $.each(listProducts, function (index, product) {
                        blockProducts += '<div class="text-center white-text blue m-1 product-box" data-id="' + (product.parentId == null ? product.id : product.parentId) + '">';
                        blockProducts += '<img class="lazy" src="' + product.thumbnail + '"';
                        blockProducts += 'data-src="' + product.thumbnail + '">';
                        blockProducts += '<span class="title-product" title="' + product.name + '">' + product.name + '</span>';
                        blockProducts += '</div>';
                    });
                    $('#listProductOnGroupId').html(blockProducts);
                    Sortable.create(listProductOnGroupId, {
                        animation: 100,
                        chosenClass: 'active'
                    });
                }
                $('#listProductOnGroup').html('');
            });
            $('#product-on-group .btn-save').on('click', function () {
                let groupId = $('#t4-group').val(),
                    provinceId = $('#t4-province').val(),
                    brandId = $('#t4-brand').val();
                if (provinceId && brandId && groupId) {
                    let productId = [],
                        thisElement = $(this),
                        oldHtml = thisElement.html();

                    addLoadingToElem(thisElement);
                    $('#listProductOnGroupId div.product-box').map(function(){
                        productId.push($(this).attr('data-id'));
                    });

                    $.ajax({
                        'type' : 'get',
                        'url' : '<?=admin_url('admin-ajax.php')?>',
                        'dataType' : 'json',
                        'data' : {
                            action: 'save_sort_product_on_group',
                            productId: productId,
                            provinceId: provinceId,
                            brandId: brandId,
                            groupId: groupId,
                            keySortOption: 'icook:province:' + provinceId + ':brand:' + brandId + ':group:' + groupId,
                            groupSlug: dataSort.t4ListProduct[provinceId+'_'+brandId][groupId].group.slug,
                        },
                        'success' : function (res) {
                            if (res.messageCode == 1) {
                                $('#product-on-group .notice-success').show();
                                $('html, body').animate({
                                    scrollTop: 0
                                }, 500);
                                dataSort.t4ListProduct[provinceId+'_'+brandId][groupId].products = res.result;
                            }
                        },
                        'error' : function (x, y, z) {
                            console.log('Có lỗi xảy ra! Hãy tải lại trang!');
                        },
                        'complete' : function () {
                            removeLoadingOnElem(thisElement, oldHtml);
                        }
                    });
                }

            });
            // </editor-fold>

            // <editor-fold defaultstate="collapsed" desc="Helper function">
            function generateOptionBrands(brands) {
                let html = '<option value="">----- Chọn thương hiệu -----</option>';
                $.each(brands, function (key, val) {
                    html += '<option value="' + val.brandId + '">' + val.name + '</option>';
                });

                return html;
            }

            function removeAccents(str) {
                return str.normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/đ/g, 'd').replace(/Đ/g, 'D');
            }
            // </editor-fold>

            // <editor-fold defaultstate="collapsed" desc="Select2">
            $('#t1-province').select2({
                placeholder: '----- Chọn tỉnh thành -----',
                language: {
                    noResults: function (params) {
                        return "Không tìm thấy tỉnh thành";
                    }
                }
            })
            $('#t1-brand').select2({
                placeholder: '----- Chọn thương hiệu -----',
                language: {
                    noResults: function (params) {
                        return "Không có thương hiệu";
                    }
                }
            })

            $('#t2-province').select2({
                placeholder: '----- Chọn tỉnh thành -----',
                width: '400px',
                language: {
                    noResults: function (params) {
                        return "Không tìm thấy tỉnh thành";
                    }
                }
            })

            $('#t3-province').select2({
                placeholder: '----- Chọn tỉnh thành -----',
                width: '400px',
                language: {
                    noResults: function (params) {
                        return "Không tìm thấy tỉnh thành";
                    }
                }
            })
            $('#t3-tag').select2({
                placeholder: '----- Chọn tag -----',
                width: '400px',
                minimumResultsForSearch: Infinity,
                language: {
                    noResults: function (params) {
                        return "Không có tag";
                    }
                }
            })

            $('#t4-province').select2({
                placeholder: '----- Chọn tỉnh thành -----',
                width: '400px',
                language: {
                    noResults: function (params) {
                        return "Không tìm thấy tỉnh thành";
                    }
                }
            })
            $('#t4-brand').select2({
                placeholder: '----- Chọn thương hiệu -----',
                width: '400px',
                language: {
                    noResults: function (params) {
                        return "Không có thương hiệu";
                    }
                }
            })
            $('#t4-group').select2({
                placeholder: '----- Chọn nhóm món -----',
                width: '400px',
                language: {
                    noResults: function (params) {
                        return "Không có nhóm món";
                    }
                }
            })
            // </editor-fold>

            // <editor-fold defaultstate="collapsed" desc="Loading on element">
            function addLoadingToElem(thisElement) {
                thisElement.attr('disabled', 'disabled').append('<strong class="fa fa-spinner fa-pulse fa-fw" aria-hidden="true"></strong>');
            }
            function removeLoadingOnElem(thisElement, oldElement) {
                thisElement.removeAttr('disabled').html(oldElement);
            }
            // </editor-fold>
        })(jQuery);
    </script>
    <?php
}