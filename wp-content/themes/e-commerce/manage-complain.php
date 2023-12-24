<?php
/*
Template Name: Quản lý khiếu nại
*/

$currentUser = wp_get_current_user();

if (!is_user_logged_in()) {
    header('Location: '.site_url('wp-login.php'));
}
$user = Permission::checkCurrentUserRole($currentUser);
if (
    $user->role != 'operator'
    && $user->role != 'administrator'
    && $user->role != 'am'
) {
    header('Location: '.site_url('wp-login.php'));
    //wp_die('Bạn không được phép truy cập trang này');
}

$page = get_query_var('paged') > 1 ? get_query_var('paged') : 1;
$perPage = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
$status = $_GET['status'] ?? 'pending';

$fromDate = $_GET['fromDate'] ?? date_i18n('Y-m-01');
$toDate = $_GET['toDate'] ?? date_i18n('Y-m-d');

$search = $_GET['search'] ?? '';

$time = current_time('mysql');

$args = [
    'post_type' => 'complain',
    'post_status' => 'publish',
    'posts_per_page' => $perPage,
    'paged' => $page,
];

if ($fromDate && $toDate) {
    $args['date_query'] = [
        'column' => 'post_date',
        'after'     => $fromDate,
        'before'    => $toDate,
        'inclusive' => true,
    ];
}
$metaQuery = [];
if ($search) {
    $metaQuery[] = [
        'key'     => 'customerPhone',
        'value'   => $search,
        'compare' => '='
    ];
}
if ($status != 'all') {
    if ($status) {
        $metaQuery[] = [
            'key' => 'status',
            'value' => $status,
            'compare' => '='
        ];
    }
}
$args['meta_query'] = $metaQuery;
$query = new \WP_Query($args);

$complains = [];
foreach ($query->posts as $post) {
    $complains[] = (new GDelivery\Libs\Helper\Complain)->getComplainInfo($post);
}
$queryUri = \GDelivery\Libs\Helper\Helper::parseQueryUri();

get_header('restaurant', [
    'user' => $user
]);
?>
<style>
.pagination a.page-numbers, .pagination span {
    display: inline-block;
    float: none;
    height: 36px;
    line-height: 34px;
    margin: 0 0 0 5px;
    padding: 0;
    text-align: center;
    width: 36px;
    color: #212529;
    background: #e1e1e1;
    border-radius: 4px;
    font-weight: 600;
}

.pagination a.page-numbers:focus,
.pagination a.page-numbers:hover,
.pagination span.current,
.pagination span.current:focus,
.pagination span.current:hover {
    background: #007bff;
    color: #fff;
}

</style>
<main class="content">
    <div class="container">
        <div class="row feature wrap-tbl">
            <div class="col-xl-12 col-lg-12">
                <div class="filter-block" style="margin-bottom: 20px;">
                    <form id="complainFilter" action="" method="get">
                        <input type="hidden" value="<?=$status?>" name="status" />
                        <div class="row">
                            <div class="col-xl-2 col-lg-2">
                                <lable>Ngày khiếu nại</lable>
                            </div>
                            <div class="col-xl-2 col-lg-2">
                                <input style="width: 100%;" class="datetime-picker" type="text"  name="fromDate" placeholder="Từ ngày" required value="<?=$fromDate?>" />
                            </div>
                            <div class="col-xl-2 col-lg-2">
                                <input style="width: 100%;" class="datetime-picker" type="text" name="toDate" placeholder="Đến ngày" required value="<?=$toDate?>" />
                            </div>
                            <div class="col-xl-2 col-lg-2">
                                <input style="width: 100%;" type="input" name="search" placeholder="Nhập SĐT..." value="<?=(isset($_GET['search']) ? $_GET['search'] : '')?>" />
                            </div>
                            <div class="col-xl-2 col-lg-2">
                                <input class="btn btn-submit" value="Tìm kiếm" type="submit" />
                            </div>
                            <div class="col-xl-2 col-lg-2">
                                <button class="btn btn-export" type="button" style="width: 100%">
                                    <svg width="15" height="15" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs><linearGradient id="a" x1="4.494" y1="-2092.086" x2="13.832" y2="-2075.914" gradientTransform="translate(0 2100)" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#18884f"/><stop offset="0.5" stop-color="#117e43"/><stop offset="1" stop-color="#0b6631"/></linearGradient></defs><title>file_type_excel</title><path d="M19.581,15.35,8.512,13.4V27.809A1.192,1.192,0,0,0,9.705,29h19.1A1.192,1.192,0,0,0,30,27.809h0V22.5Z" style="fill:#185c37"/><path d="M19.581,3H9.705A1.192,1.192,0,0,0,8.512,4.191h0V9.5L19.581,16l5.861,1.95L30,16V9.5Z" style="fill:#21a366"/><path d="M8.512,9.5H19.581V16H8.512Z" style="fill:#107c41"/><path d="M16.434,8.2H8.512V24.45h7.922a1.2,1.2,0,0,0,1.194-1.191V9.391A1.2,1.2,0,0,0,16.434,8.2Z" style="opacity:0.10000000149011612;isolation:isolate"/><path d="M15.783,8.85H8.512V25.1h7.271a1.2,1.2,0,0,0,1.194-1.191V10.041A1.2,1.2,0,0,0,15.783,8.85Z" style="opacity:0.20000000298023224;isolation:isolate"/><path d="M15.783,8.85H8.512V23.8h7.271a1.2,1.2,0,0,0,1.194-1.191V10.041A1.2,1.2,0,0,0,15.783,8.85Z" style="opacity:0.20000000298023224;isolation:isolate"/><path d="M15.132,8.85H8.512V23.8h6.62a1.2,1.2,0,0,0,1.194-1.191V10.041A1.2,1.2,0,0,0,15.132,8.85Z" style="opacity:0.20000000298023224;isolation:isolate"/><path d="M3.194,8.85H15.132a1.193,1.193,0,0,1,1.194,1.191V21.959a1.193,1.193,0,0,1-1.194,1.191H3.194A1.192,1.192,0,0,1,2,21.959V10.041A1.192,1.192,0,0,1,3.194,8.85Z" style="fill:url(#a)"/><path d="M5.7,19.873l2.511-3.884-2.3-3.862H7.758L9.013,14.6c.116.234.2.408.238.524h.017c.082-.188.169-.369.26-.546l1.342-2.447h1.7l-2.359,3.84,2.419,3.905H10.821l-1.45-2.711A2.355,2.355,0,0,1,9.2,16.8H9.176a1.688,1.688,0,0,1-.168.351L7.515,19.873Z" style="fill:#fff"/><path d="M28.806,3H19.581V9.5H30V4.191A1.192,1.192,0,0,0,28.806,3Z" style="fill:#33c481"/><path d="M19.581,16H30v6.5H19.581Z" style="fill:#107c41"/></svg>
                                    Xuất excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-xl-12"><hr/></div>
            <div class="col-xl-12 col-lg-12">
                <nav class="wrap-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?=site_url()?>" title="<?=bloginfo('name')?>">Trang chủ</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Quản lý khiếu nại</li>
                    </ol>
                </nav>
            </div>
            <div class="col-xl-12">
                <ul class="tab-status">
                    <?php
                    if ($queryUri) {
                        $temp = clone $queryUri;
                        if (isset($temp->params['tab'])) {
                            unset($temp->params['tab']);
                        }
                        $statusUri = '?'.http_build_query($temp->params).'&';
                    } else {
                        $statusUri = '?';
                    }
                    ?>
                    <li><a class="<?=($status == 'pending' ? 'active' : '')?>" href="<?=site_url('manage-complain')?>/<?=$page?>/<?=$statusUri?>status=pending">Chưa xử lý</a></li>
                    <li><a class="<?=($status == 'processing' ? 'active' : '')?>" href="<?=site_url('manage-complain')?>/<?=$page?>/<?=$statusUri?>status=processing">Đang xử lý</a></li>
                    <li><a class="<?=($status == 'close' ? 'active' : '')?>" href="<?=site_url('manage-complain')?>/<?=$page?>/<?=$statusUri?>status=close">Đã xử lý</a></li>
                    <li><a class="<?=($status == 'all' ? 'active' : '')?>" href="<?=site_url('manage-complain')?>/<?=$page?>/<?=$statusUri?>status=all">Tất cả</a></li>
                </ul>
            </div>
            <div class="col-xl-12 col-lg-12" style="margin-top: 20px;">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th scope="col">Mã khiếu nại</th>
                        <!--<th scope="col">Order ID</th>-->
                        <th scope="col">Ngày khiếu nại</th>
                        <th scope="col">Khách hàng</th>
                        <th scope="col">SĐT</th>
                        <th scope="col" class="text-center">Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($complains) { ?>
                    <?php foreach ($complains as $item) { ?>
                        <tr>
                            <td><a href="<?=site_url('complain-detail')?>?id=<?=$item->id?>"><?=$item->id?></a></td>
                            <!--<td><a href="<?php /*=site_url('restaurant-order-detail')*/?>?id=<?php /*=$item->orderId*/?>"><?php /*=$item->orderId*/?></a></td>-->
                            <td><?=$item->createdAt?></td>
                            <td><?=$item->customerName?></td>
                            <td><?=$item->customerPhone?></td>
                            <td>
                                <?php if (!$item->status || $item->status == 'pending') { ?>
                                    <div class="alert alert-info" role="alert">Chưa xử lý</div>
                                <?php } elseif ($item->status == 'processing') { ?>
                                    <div class="alert alert-warning" role="alert">Đang xử lý</div>
                                <?php } else { ?>
                                    <div class="alert alert-success" role="alert">Đã xử lý</div>
                                <?php } ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-normal" id="dropdownMenuLink1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-dot"></i></button>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink1">
                                    <a class="dropdown-item" href="<?=site_url('complain-detail')?>?id=<?=$item->id?>">Chi tiết</a>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="7" class="text-center">Không tìm thấy khiếu nại nào!</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div class="pagination">
                    <select class="form-select changeOffset" style="width: auto; float: none; display: inline-block;">
                        <option value="20" <?= ($perPage == 20) ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100</option>
                        <option value="100" <?= ($perPage == 1000) ? 'selected' : '' ?>>1000</option>
                    </select>
                    <?php
                    echo paginate_links( array(
                        'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                        'total'        => $query->max_num_pages,
                        'current'      => max( 1, get_query_var( 'paged' ) ),
                        'format'       => '?paged=%#%',
                        'show_all'     => false,
                        'type'         => 'plain',
                        'end_size'     => 2,
                        'mid_size'     => 1,
                        'prev_next'    => true,
                        'prev_text'    => sprintf( '%1$s', __( '<', 'text-domain' ) ),
                        'next_text'    => sprintf( '%1$s', __( '>', 'text-domain' ) ),
                        'add_args'     => false,
                        'add_fragment' => '',
                    ) );
                    ?>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    $( document ).ready(function() {
        $( ".btn-export" ).click(function() {
            let formData = $('form#complainFilter').serialize();
            let href = '/export-complain?' + formData;
            window.open(href);
        });

        $('.changeOffset').change(function(){
            let limit = $(this).val();
            addOrUpdateUrlParam('limit', limit);
        });

        function addOrUpdateUrlParam(name, value)
        {
            let href = window.location.href;
            let regex = new RegExp("[&\\?]" + name + "=");
            if(regex.test(href))
            {
                regex = new RegExp("([&\\?])" + name + "=\\d+");
                window.location.href = href.replace(regex, "$1" + name + "=" + value);
            }
            else
            {
                if(href.indexOf("?") > -1)
                    window.location.href = href + "&" + name + "=" + value;
                else
                    window.location.href = href + "?" + name + "=" + value;
            }
        }

    });
</script>
<?php
    get_footer('restaurant');
?>
