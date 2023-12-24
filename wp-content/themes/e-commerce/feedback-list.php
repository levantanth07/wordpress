<?php
/*
Template Name: Feedback
*/

use GDelivery\Libs\Helper\Helper;

$currentUser = wp_get_current_user();

if (!is_user_logged_in()) {
    header('Location: '.site_url('wp-login.php'));
}

$user = Permission::checkCurrentUserRole($currentUser);

if (!current_user_can('show_feedback_list')) {
    wp_die('Bạn không được phép truy cập trang này');
}

$currentPage = get_query_var('paged') > 1 ? get_query_var('paged') : 1;
$perPage = $_GET['perPage'] ?? 10;

$args = [
    'post_type' => 'feedback',
    'post_status' => 'any',
    'posts_per_page'=> $perPage,
    'paged' => $currentPage,
];

$feedbackStatus = $_GET['feedback_status'] ?? '';
if (!empty($feedbackStatus)) {
    $args['meta_query'][] = [
        'key' => 'feedback_status',
        'value' => $_GET['feedback_status']
    ];
}

if (!empty($_GET['search'])) {
    $args['meta_query'][] = [
        'key' => 'feedback_phone_number',
        'value' => $_GET['search'],
        'compare' => 'like'
    ];
}

$listStatus = Helper::getMetaValue('feedback_status', 'feedback');

$query = new WP_Query($args);
$feedback = $query->posts;
$lastPage = $query->max_num_pages > 0 ? $query->max_num_pages : 1;
$total = $query->found_posts;
$currentUrl = add_query_arg( NULL, NULL ) ;
$queryString = isset(parse_url($currentUrl)['query']) ? '?' . parse_url($currentUrl)['query'] : '';

wp_reset_query();

get_header('setting', [
    'user' => $user
]);

?>
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 36px;
        height: 19px;
        z-index: 1;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 15px;
        width: 15px;
        left: 2px;
        bottom: 2px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider.active {
        background-color: #2196F3;
        box-shadow: 0 0 1px #2196F3;
    }

    .slider.active:before {
        -webkit-transform: translateX(15px);
        -ms-transform: translateX(15px);
        transform: translateX(17px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

    .pagination a {
        margin: 0 4px;
    }

    .loader {
        width: 30px;
        height: 30px;
        border: 5px solid #ddd;
        border-bottom-color: #2196F3;
        border-radius: 50%;
        display: inline-block;
        box-sizing: border-box;
        animation: rotation 1s linear infinite;
    }

    @keyframes rotation {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .spinner {
        display: none;
        margin-top: -10px;
        height: 100%;
        width: 100%;
        position: absolute;
    }
    .spinner.show {
        z-index: 2;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }
    .per-page {
        position: absolute;
        right: 0;
    }
    .dropbtn {
        background-color: #848687;
        color: white;
        padding: 0.5rem 0.75rem;
        font-size: 14px;
        border: none;
        cursor: pointer;
        width: 45px;
    }

    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1;
        max-height: 120px;
        overflow: auto;
    }

    .dropdown-content a {
        color: black;
        padding: 12px 12px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a.active {
        background-color: #848687;
    }

    .dropdown-content a:hover {background-color: #f1f1f1}

    .dropdown:hover .dropdown-content {
        display: block;
    }

    .dropdown:hover .dropbtn {
        background-color: #2196F3;
    }

    tr td {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        max-height: 78px;
        line-height: 21px;
    }
</style>

<main class="content">
    <div class="container">
        <div class="row feature">
            <div class="col-xl-12 col-lg-12">
                <form class="row" action="" method="get">
                    <input class="col-md-4" value="<?=$_GET['search'] ?? ''?>" name="search" placeholder="Tìm kiếm theo số điện thoại" autocomplete="off"/>
                    <?php if (!empty($listStatus)): ?>
                    <select name="feedback_status">
                        <option value="">--- Chọn trạng thái ---</option>
                        <option value="complete" <?='complete' == $feedbackStatus ? 'selected' : ''?>>Đã xử lý</option>
                        <option value="pending" <?='pending' == $feedbackStatus ? 'selected' : ''?>>Chưa xử lý</option>
                    </select>
                    <?php endif; ?>
                    <input class="btn btn-submit" value="Tìm" type="submit" />
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12"><hr/></div>
            <div class="col-xl-8 col-lg-12">
                <nav class="wrap-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?=site_url()?>" title="<?=bloginfo('name')?>">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Danh sách khiếu nại</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- end block info -->
        <div class="row">
            <div class="col-xl-12 col-lg-12">
                <div class="wrap-tbl">
                    <table class="table table-hover ">
                        <thead>
                            <tr class="row">
                                <th class="" width="6%" scope="col">Mã</th>
                                <th class="" width="14%" scope="col">Ngày</th>
                                <th class="" width="10%" scope="col">Đơn hàng</th>
                                <th class="" width="20%" scope="col">Người khiếu nại</th>
                                <th class="" width="10%" scope="col">Số điện thoại</th>
                                <th class="text-center" width="40%" scope="col">Nội dung</th>
<!--                                <th class="" width="10%" scope="col">Trạng thái</th>-->
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($feedback as $fback) :
                            $orderId = get_field('feedback_for_object_id', $fback->ID);
                            ?>
                            <tr class="row" data-id="">
                                <td class="" width="6%"><?=$fback->ID?></td>
                                <td class="" width="14%"><?=$fback->post_date?></td>
                                <td class="" width="10%">
                                    <a href="<?=site_url('restaurant-order-detail')?>?id=<?=$orderId?>"><?=$orderId?></a>
                                </td>
                                <td class="" width="20%"><?=get_field('feedback_author', $fback->ID)?></td>
                                <td class="" width="10%"><?=get_field('feedback_phone_number', $fback->ID)?></td>
                                <td class="" width="40%" title="<?=$fback->post_title?>"><?=$fback->post_title?></td>
<!--                                <td class="" width="10%">-->
<!--                                    <div class="switch">-->
<!--                                        <span class="status slider round--><?//=get_field('feedback_status', $fback->ID) == 'complete' ? ' active' : ''?><!--"-->
<!--                                            title="--><?//=get_field('feedback_status', $fback->ID) == 'complete' ? 'Đã xử lý' : 'Chưa xử lý' ?><!--">-->
<!--                                        </span>-->
<!--                                    </div>-->
<!--                                </td>-->
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end table -->
            <div class="col-xl-12" style="margin-bottom: 125px;">
                <nav aria-label="..." style="display: flex;justify-content: center;">
                    <ul class="pagination">
                        <li class="page-item <?=($currentPage == 1 ? 'disabled' : '')?>">
                            <a class="page-link" href="<?=site_url('feedback-list')?>/page/<?=$currentPage-1?><?=$queryString?>" tabindex="-1" aria-disabled="true"><span aria-hidden="true">&laquo;</span></a>
                        </li>
                        <?php for ($i = 1; $i <= $lastPage; $i ++):?>
                            <li class="page-item <?=($currentPage == $i ? 'active' : '')?>">
                                <a class="page-link" href="<?=$currentPage == $i ? 'javascript:void(0);' : site_url('feedback-list') . '/page/' . $i . $queryString?>" tabindex="-1" aria-disabled="true">
                                    <span aria-hidden="true"><?=$i?></span>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?=($currentPage == $lastPage ? 'disabled' : '')?>">
                            <a class="page-link" href="<?=site_url('feedback-list')?>/page/<?=$currentPage+1?><?=$queryString?>"> <span aria-hidden="true">&raquo;</span></a>
                        </li>
                    </ul>
                    <div class="per-page">
                        <div class="dropdown">
                            <button class="dropbtn"><?=$perPage?></button>
                            <div class="dropdown-content">
                                <?php
                                    $initQuery = '';
                                    if (isset($_GET['search'])) {
                                        $initQuery .= "&search={$_GET['search']}";
                                    }
                                    if (isset($_GET['feedback_status'])) {
                                        $initQuery .= "&feedback_status={$_GET['feedback_status']}";
                                    }
                                ?>
                                <a class="<?=$perPage == 10 ? 'active' : ''?>" href="<?=$perPage == 10 ? 'javascript:void(0);' : site_url('feedback-list') . '/page/1?perPage=10' . $initQuery?>">
                                    10
                                </a>
                                <a class="<?=$perPage == 20 ? 'active' : ''?>" href="<?=$perPage == 20 ? 'javascript:void(0);' : site_url('feedback-list') . '/page/1?perPage=20' . $initQuery?>">
                                    20
                                </a>
                                <a class="<?=$perPage == 50 ? 'active' : ''?>" href="<?=$perPage == 50 ? 'javascript:void(0);' : site_url('feedback-list') . '/page/1?perPage=50' . $initQuery?>">
                                    50
                                </a>
                                <a class="<?=$perPage == 100 ? 'active' : ''?>" href="<?=$perPage == 100 ? 'javascript:void(0);' : site_url('feedback-list') . '/page/1?perPage=100' . $initQuery?>">
                                    100
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</main>

<script type="text/javascript">
    (function ($) {
        $('.switch .status.slider').on('click', function () {

            let productId = $(this).data('id'),
                newStatus = $(this).hasClass('active') ? 0 : 1,
                thisElem = $(this),
                spinnerElem = $(thisElem.parents('td')).find('.spinner');

            // Show loading
            spinnerElem.addClass('show');

            $.ajax({
                'type' : 'post',
                'url' : '<?=site_url('/ajax-on-off-product')?>',
                'dataType' : 'json',
                'data' : {
                    'id' : productId,
                    'status' : newStatus
                },
                'success' : function (res) {
                    if (res.messageCode == 1) {
                        if (thisElem.hasClass('active')) {
                            thisElem.removeClass('active');
                        } else {
                            thisElem.addClass('active');
                        }
                    }
                },
                'error' : function (x, y, z) {
                    alert('Lỗi khi gọi ajax');
                },
                'complete': function () {
                    // Hide loading
                    spinnerElem.removeClass('show');
                }
            }); // end ajax
        });
    })(jQuery);
</script>

<?php
get_footer('setting');
?>

