<?php
/*
    Template Name: Chi tiết khiếu nại
*/

$types = [
    'class' => [
        [
            'id' => 1,
            'name' => 'Đồ ăn',
            'issue' => [
                [
                    'id' => '1.1',
                    'name' => 'Chất lượng',
                    'errors' => [
                        [
                            'id' => '1.1.1',
                            'name' => 'Định lượng thực phẩm không đủ',
                        ],
                        [
                            'id' => '1.1.2',
                            'name' => 'Thực phẩm không ngon và không đúng vị',
                        ],
                        [
                            'id' => '1.1.3',
                            'name' => 'Thực phẩm bị thay đổi trạng thái, cấu trúc, kết cấu',
                        ],
                        [
                            'id' => '1.1.4',
                            'name' => 'Hình ảnh, màu sắc sản phẩm mùi không đúng',
                        ],
                    ]
                ],
                [
                    'id' => '1.2',
                    'name' => 'ATTP',
                    'errors' => [
                        [
                            'id' => '1.2.1',
                            'name' => 'Đồ ăn có dị vật (tóc, gián...)',
                        ],
                        [
                            'id' => '1.2.2',
                            'name' => 'Thực phẩm có dấu hiệu hư hỏng, ôi thiu',
                        ],
                        [
                            'id' => '1.2.3',
                            'name' => 'Đưa nhầm đồ ăn đã qua sử dụng cho KH',
                        ],
                        [
                            'id' => '1.2.4',
                            'name' => 'Hành vi, diện mạo NVNH không đảm bảo VS',
                        ],
                        [
                            'id' => '1.2.5',
                            'name' => 'KH phản hồi đau bụng và được QA xác nhận là nguyên nhân do NH',
                        ],
                        [
                            'id' => '1.2.6',
                            'name' => 'KH phản hồi đau bụng và QA xác nhận không phải lỗi của NH',
                        ],
                        [
                            'id' => '1.2.7',
                            'name' => 'Delivery đóng gói không vệ sinh',
                        ],
                    ]
                ]
            ]
        ],
        [
            'id' => 2,
            'name' => 'Dịch vụ',
            'issue' => [
                [
                    'id' => '2.1',
                    'name' => 'Phục vụ (tốc độ. kỹ năng)',
                    'errors' => [
                        [
                            'id' => '2.1.1',
                            'name' => 'Lên đồ chậm',
                        ],
                        [
                            'id' => '2.1.2',
                            'name' => 'Lên đồ sai',
                        ],
                        [
                            'id' => '2.1.3',
                            'name' => 'Lên đồ thiếu món',
                        ],
                        [
                            'id' => '2.1.4',
                            'name' => 'Thiếu món nhưng không thông tin cho KH từ trước khi khách vào Nh hoặc đặt chỗ trên web',
                        ],
                        [
                            'id' => '2.1.5',
                            'name' => 'Tư vấn sai',
                        ],
                        [
                            'id' => '2.1.6',
                            'name' => 'Tư vấn không đầy đủ',
                        ],
                        [
                            'id' => '2.1.7',
                            'name' => 'Nhân viên không nhanh nhẹn, kỹ năng hỗ trợ KH chưa tốt',
                        ],
                        [
                            'id' => '2.1.8',
                            'name' => 'Nhân viên không dọn đồ dơ trong quá trình KH dùng bữa',
                        ],
                        [
                            'id' => '2.1.9',
                            'name' => 'Nhân viên không hỗ trợ các yêu cầu của KH',
                        ],
                        [
                            'id' => '2.1.10',
                            'name' => 'BQL/nhân viên không bao quát NH',
                        ],
                        [
                            'id' => '2.1.11',
                            'name' => 'Thanh toán chậm',
                        ],
                        [
                            'id' => '2.1.12',
                            'name' => 'Thu nhân tính sai tiền của KH',
                        ],
                        [
                            'id' => '2.1.13',
                            'name' => 'NV tích sai điểm cho KH',
                        ],
                        [
                            'id' => '2.1.14',
                            'name' => 'NV không hỗ trợ khi KH để xe và lấy xe',
                        ],
                    ]
                ],
                [
                    'id' => '2.2',
                    'name' => 'Thái độ nhân viên (nhân viên.bảo vệ.QLNH)',
                    'errors' => [
                        [
                            'id' => '2.2.1',
                            'name' => 'Không niềm nở, không nhiệt tình',
                        ],
                        [
                            'id' => '2.2.2',
                            'name' => 'Không lắng nghe, có ngôn từ giao tiếp với KH chưa đúng chuẩn mực',
                        ],
                        [
                            'id' => '2.2.3',
                            'name' => 'Có hành động không đúng chuẩn mực khi phục vụ KH',
                        ],
                        [
                            'id' => '2.2.4',
                            'name' => 'Có hành động gian lận',
                        ],
                    ]
                ],
                [
                    'id' => '2.3',
                    'name' => 'GDelivery của NH',
                    'errors' => [
                        [
                            'id' => '2.3.1',
                            'name' => 'Giao thiếu đồ',
                        ],
                        [
                            'id' => '2.3.2',
                            'name' => 'Giao sai đồ',
                        ],
                        [
                            'id' => '2.3.3',
                            'name' => 'Giao đồ chậm (do NH )',
                        ],
                        [
                            'id' => '2.3.4',
                            'name' => 'Đóng gói không đúng tiêu chuẩn',
                        ],
                        [
                            'id' => '2.3.5',
                            'name' => 'Đóng gói thiếu nhãn mác, hướng dẫn',
                        ],
                    ]
                ]
            ]
        ],
        [
            'id' => 3,
            'name' => 'Cơ sở vật chất',
            'issue' => [
                [
                    'id' => '3.1',
                    'name' => 'Tình trạng vệ sinh',
                    'errors' => [
                        [
                            'id' => '3.1.1',
                            'name' => 'Công cụ dụng cụ không sạch sẽ',
                        ],
                        [
                            'id' => '3.1.2',
                            'name' => 'Công cụ dụng cụ hỏng hóc',
                        ],
                        [
                            'id' => '3.1.3',
                            'name' => 'Công cụ dụng cụ thiếu',
                        ],
                        [
                            'id' => '3.1.4',
                            'name' => 'Tình trạng vệ sinh chung của NH không tốt',
                        ],
                    ]
                ],
                [
                    'id' => '3.2',
                    'name' => 'Hình ảnh không gian (bài trí.trang trí.nhiệt độ.ánh sáng.nhà vệ sinh...)',
                    'errors' => [
                        [
                            'id' => '3.2.1',
                            'name' => 'Không gian không thỏa mái',
                        ],
                        [
                            'id' => '3.2.2',
                            'name' => 'Nhà vệ sinh không sạch sẽ, thiếu công cụ dụng cụ',
                        ],
                        [
                            'id' => '3.2.3',
                            'name' => 'Cơ sở vật chất hỏng hóc',
                        ],
                    ]
                ],
                [
                    'id' => '3.3',
                    'name' => 'An toàn cho KH',
                    'errors' => [
                        [
                            'id' => '3.3.1',
                            'name' => 'An toàn cho KH',
                        ],
                    ]
                ]
            ]
        ],
        [
            'id' => 4,
            'name' => 'Vận hành chung',
            'issue' => [
                [
                    'id' => '4.1',
                    'name' => 'Website GDelivery'
                ],
                [
                    'id' => '4.2',
                    'name' => 'App TGS'
                ],
                [
                    'id' => '4.3',
                    'name' => 'GBooking'
                ],
                [
                    'id' => '4.4',
                    'name' => 'Thiết bị thanh toán(máy POS, máy quẹt thẻ,..)'
                ],
                [
                    'id' => '4.5',
                    'name' => 'Website Brand'
                ],
                [
                    'id' => '4.6',
                    'name' => 'Chính sách/CTKM'
                ],
                [
                    'id' => '4.7',
                    'name' => 'Voucher'
                ]
            ]
        ],
        [
            'id' => 5,
            'name' => 'Giao hàng',
            'issue' => [
                [
                    'id' => '5.1',
                    'name' => 'Thái độ nhân viên giao hàng'
                ],
                [
                    'id' => '5.2',
                    'name' => 'Phí ship'
                ],
                [
                    'id' => '5.3',
                    'name' => 'Tốc độ giao hàng'
                ]
            ]
        ],
        [
            'id' => 6,
            'name' => 'Không vấn đề'
        ],
        [
            'id' => 7,
            'name' => 'Khách hàng đánh giá nhầm'
        ],
    ],
    'errors' => [
        'Lỗi ít nghiêm trọng và có thể bị ảnh hưởng bởi yếu tố khách quan',
        'Lỗi ít nghiêm trọng và có thể bị ảnh hưởng bởi yếu tố khách quan',
        'Lỗi nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi nghiêm trọng/Lỗi đặc biệt nghiêm trọng',
        'Lỗi nghiêm trọng/Lỗi đặc biệt nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi đặc biệt nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng và có thể bị ảnh hưởng bởi yếu tố khách quan',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi nghiêm trọng',
        'Lỗi ít nghiêm trọng và có thể bị ảnh hưởng bởi yếu tố khách quan',
        'Lỗi ít nghiêm trọng và có thể bị ảnh hưởng bởi yếu tố khách quan',
        'Lỗi ít nghiêm trọng và có thể bị ảnh hưởng bởi yếu tố khách quan',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng và có thể bị ảnh hưởng bởi yếu tố khách quan',
        'Lỗi ít nghiêm trọng',
        'Lỗi ít nghiêm trọng và có thể bị ảnh hưởng bởi yếu tố khách quan',
        'Lỗi đặc biệt nghiêm trọng',
    ]
];

$urlJson = get_template_directory_uri().'/assets/json/complainType.json';
$str = file_get_contents($urlJson);
$arrErrors = \json_decode($str);
//var_dump(\json_decode($str, true)); die();

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

$id = $_REQUEST['id'] ?? null;
if ($id) {
    $post = get_post($id);
    $complain = (new GDelivery\Libs\Helper\Complain)->getComplainInfo($post);
    //print_r($complain); die();
    if ($complain) {
        $order = wc_get_order($complain->orderId);

        $getRestaurant = \GDelivery\Libs\Helper\Helper::getMerchant($order->get_meta('merchant_id'));
        if ($getRestaurant->messageCode == \Abstraction\Object\Message::SUCCESS) {
            $jsonRestaurant = $getRestaurant->result;
        }
    }
}
get_header('restaurant', [
    'user' => $user
]);
?>

<style>
    .icon-method-contact svg{
        width: 15px;
        margin-right: 5px;
    }
    .discussion {
        /*max-width: 600px;
        margin: 0 auto;*/

        display: flex;
        flex-flow: column wrap;
    }

    .discussion > .bubble {
        border-radius: 1em;
        padding: 0.25em 0.75em;
        margin: 0.0625em;
        max-width: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.13);
        margin-bottom: 10px;
    }

    .discussion > .bubble.sender {
        align-self: flex-start;
        background-color: cornflowerblue;
        color: #fff;
    }
    .discussion > .bubble.recipient {
        align-self: flex-end;
        background-color: #efefef;
    }

    .discussion > .bubble.sender.first { border-bottom-left-radius: 0.1em; }
    .discussion > .bubble.sender.last { border-top-left-radius: 0.1em; }
    .discussion > .bubble.sender.middle {
        border-bottom-left-radius: 0.1em;
        border-top-left-radius: 0.1em;
    }

    .discussion > .bubble.recipient.first { border-bottom-right-radius: 0.1em; }
    .discussion > .bubble.recipient.last { border-top-right-radius: 0.1em; }
    .discussion > .bubble.recipient.middle {
        border-bottom-right-radius: 0.1em;
        border-top-right-radius: 0.1em;
    }

    .btn-group-action{
        display: flex;
        justify-content: center;
        align-content: center;
        align-items: center;
        width: 100%;
    }
    .btn-group-action .btn{
        margin: 0px 20px;
        padding-left: 35px;
        padding-right: 35px;
    }
</style>
<main class="content">
    <div class="container">
        <div class="row feature">
            <div class="col-xl-12 col-lg-12">
                <h4>Khiếu nại #<?=$complain->id?></h4>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12"><hr/></div>
            <div class="col-xl-12 col-lg-12">
                <nav class="wrap-breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item "><a href="<?=site_url('manage-complain')?>">Quản lý khiếu nại</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Chi tiết khiếu nại</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="status-procces">
                    <div class="container">
                        <div class="row no-gutters">
                            <div class="col-xl-2 col-md-2 step-process active">
                                <span><i class="icon-clock"></i></span>
                            </div>
                            <div class="col-xl-6 col-md-6 step-process">
                                <span><i class="icon-check"></i></span>
                            </div>
                            <div class="col-xl-2 col-md-2 step-process">
                                <span><i class="icon-list-check"></i></span>
                            </div>
                        </div>
                        <div class="row no-gutters wrap-status-process">
                            <div class="col-xl-12">
                                <div class="bar-color">
                                    <div class="fill" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row no-gutters">
                            <div class="col-xl-2 col-md-2 step-process active">
                                <p>Chờ xử lý</p>
                            </div>
                            <div class="col-xl-6 col-md-6 step-process">
                                <p>Đã xác nhận</p>
                            </div>
                            <div class="col-xl-2 col-md-2 step-process">
                                <p>Đã hoàn thành</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <h5>Nội dung khiếu nại</h5>
            </div>
        </div>
        <div class="row" style="margin-top: 20px;">
            <div class="col-xl-6 col-lg-6">
                <div class="row">
                    <div class="col-xl-6 col-lg-6">
                        <p><strong>Ngày khiếu nại</strong>: </p>
                    </div>
                    <div class="col-xl-6 col-lg-6">
                        <p><?=$complain->createdAt?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="row">
                    <div class="col-xl-6 col-lg-6">
                        <p><strong>Loại sự cố</strong>: </p>
                    </div>
                    <div class="col-xl-6 col-lg-6">
                        <p><?=$complain->errorType?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <strong>Ghi chú:</strong>
                <textarea style="margin-top: 10px;" readonly class="form-control" cols="30" rows="2"><?=$complain->note?></textarea>
            </div>
        </div>
        <div class="row wrap-info" style="margin-top: 20px">
            <div class="col-xl-6">
                <div class="block block-left add-height-block" style="height: auto !important;">
                    <h4>Thông tin khách hàng</h4>
                    <ul>
                        <li><strong>Khách hàng:</strong> <?=$complain->customerName?></li>
                        <li><strong>SĐT:</strong> <?=$complain->customerPhone?></li>
                        <?php if (isset($jsonRestaurant)) { ?>
                        <li><strong>Nhà hàng:</strong> <?=$jsonRestaurant->name?></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="block block-right add-height-block" style="height: auto !important;">
                    <h4>Thông tin đơn hàng</h4>
                    <ul>
                        <li><strong>Đơn hàng:</strong> <a href="<?=site_url('restaurant-order-detail')?>?id=<?=$complain->orderId?>"><?=$complain->orderId?></a></li>
                        <li><strong>Trạng thái:</strong> <?=\GDelivery\Libs\Helper\Order::orderStatusName($order->get_status())?></li>
                        <li><strong>Ngày mua hàng:</strong> <?=$order->get_date_created()?></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="row wrap-info" style="margin-top: 20px;">
            <div class="col-xl-12">
                <h5>Thông tin xử lý khiếu nại</h5>
            </div>
            <div class="col-xl-12 col-lg-12 mt-3">
                <div class="row wrap-status-order" style="margin-bottom: 0px !important;">
                    <div class="col-xl-2 col-lg-2">
                        <p><strong>Trạng thái:</strong> </p>
                    </div>
                    <div class="col-xl-10 col-lg-10">
                        <?php if (!$complain->status || $complain->status == 'pending') { ?>
                            <div class="alert alert-info" style="margin-left: 0px;" role="alert">Chưa xử lý</div>
                        <?php } elseif ($complain->status == 'processing') { ?>
                            <div class="alert alert-warning" style="margin-left: 0px;" role="alert">Đang xử lý</div>
                        <?php } else { ?>
                            <div class="alert alert-success" style="margin-left: 0px;" role="alert">Đã xử lý</div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row wrap-info">
            <div class="col-xl-6 col-lg-6">
                <div class="row">
                    <div class="col-xl-4 col-lg-4">
                        <p><strong>Loại phản ánh:</strong> </p>
                    </div>
                    <div class="col-xl-8 col-lg-8">
                        <p><?=$complain->classify->issueType?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="row">
                    <div class="col-xl-4 col-lg-4">
                        <p><strong>Vấn đề:</strong> </p>
                    </div>
                    <div class="col-xl-8 col-lg-8">
                        <p><?=$complain->classify->issue?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="row">
                    <div class="col-xl-4 col-lg-4">
                        <p><strong>Chi tiết vấn đề:</strong> </p>
                    </div>
                    <div class="col-xl-8 col-lg-8">
                        <p><?=$complain->classify->issueDetail?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="row">
                    <div class="col-xl-4 col-lg-4">
                        <p><strong>Loại lỗi:</strong> </p>
                    </div>
                    <div class="col-xl-8 col-lg-8">
                        <p><?=$complain->classify->level?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-12 col-lg-12">
                <div class="row">
                    <div class="col-xl-2 col-lg-2">
                        <p><strong>Nội dung phản ánh:</strong> </p>
                    </div>
                    <div class="col-xl-10 col-lg-10">
                        <p><?=$complain->classify->comment?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-12 mt-3">
                <h5>Kết quả</h5>
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="row">
                    <div class="col-xl-4 col-lg-4">
                        <p><strong>Kết quả xử lý:</strong> </p>
                    </div>
                    <div class="col-xl-8 col-lg-8">
                        <p><?=$complain->response->comment?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6">
                <div class="row">
                    <div class="col-xl-4 col-lg-4">
                        <p><strong>Điểm đánh giá:</strong> </p>
                    </div>
                    <div class="col-xl-8 col-lg-8">
                        <p><?=$complain->response->point?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row wrap-info" style="margin-top: 20px; margin-bottom: 20px;">
            <div class="col-xl-12">
                <h5>Lịch sử khiếu nại - Xử lý khiếu nại</h5>
            </div>
            <?php if ($complain->comments) { ?>
            <div class="col-xl-12" style="margin-top: 30px;">
                <section class="discussion">
                    <?php foreach (array_reverse($complain->comments) as $comment) { ?>
                    <div class="bubble <?=$comment->author=='customer' ? 'sender' : 'recipient' ?>">
                        <?php if (isset($comment->contentMeta['result'][0])) { ?>
                            <strong><?=$comment->contentMeta['result'][0]?></strong>
                        <?php } elseif (isset($comment->contentMeta['title'][0])) { ?>
                            <strong><?=$comment->contentMeta['title'][0]?></strong>
                        <?php } ?>
                        <div class="content-comment"><?=$comment->content?></div>
                        <div class="text-right">
                            <small class="icon-method-contact">
                                <?php if ($comment->commentType == 'call') { ?>
                                <svg xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 501.01 511.5"><path fill-rule="nonzero" d="M145.23 241.93c31.31 55.96 68.11 95.64 125.54 125.55 4.98-4.48 7.64-8.17 11.76-13.51 16.29-21.45 36.58-47.2 69.81-31.68 21.68 10.97 64.29 34.03 82.31 47.39 6.86 4.82 11.82 10.9 15.16 17.81 7.09 14.71 6.17 31.58 1.67 46.85-2.32 7.86-5.7 15.65-9.71 22.71-15.05 26.44-40.04 40.23-68.87 47.72-35.05 9.07-66.72 9.32-101.56-1.42-26.29-8.13-50.72-21.27-74.05-35.67l-3.01-1.86c-11.32-7.05-23.48-14.63-35.93-23.89l-.09-.08c-23.99-18.06-48.14-40.28-70.04-65.14-20.28-23.02-38.76-48.46-53.51-75.13-13.52-24.45-23.88-50.13-29.65-76.13-8.21-37.03-7.6-79.66 10.62-113.82 21.33-39.98 60.04-56.32 103.38-52.5l1.07.13c7.49.94 14.3 5.51 17.99 12.11l49.31 83.35c4.82 6.52 7.62 13.23 8.56 20.12 3.59 26.47-18.11 42.75-36.68 56.36-6.33 4.6-13.4 9.73-14.08 10.73zm92.87-52.2c-7.4-1.91-11.85-9.48-9.94-16.88 1.91-7.41 9.48-11.86 16.88-9.94 44.1 11.5 84.18 47.81 97.88 91.55 2.27 7.31-1.8 15.1-9.12 17.37-7.31 2.28-15.1-1.8-17.37-9.12-10.82-34.54-43.55-63.93-78.33-72.98zm1.84-84.95c-7.55-1.28-12.63-8.46-11.35-16.01 1.28-7.55 8.46-12.64 16.01-11.35C329.95 92.2 405.39 164 426.66 247.63c1.89 7.43-2.61 15-10.04 16.89-7.44 1.89-15.01-2.61-16.89-10.05-18.63-73.26-84.98-136.72-159.79-149.69zm8.92-77.11c-7.61-.87-13.08-7.76-12.21-15.37.87-7.6 7.75-13.08 15.36-12.21 118.69 13.89 222.73 108.9 248.67 225.33 1.65 7.47-3.07 14.86-10.53 16.51-7.46 1.65-14.86-3.07-16.5-10.53-23.4-104.98-117.76-191.21-224.79-203.73zM119.56 255c-.47-.77-.88-1.58-1.22-2.44-8.35-21.18 8.78-33.62 24.07-44.7 7.35-5.3 20.15-13.96 23.95-22.75 2.23-5.11 1.15-8.92-2.08-13.27l-1.3-1.85-48.67-82.28c-31.03-2.17-57.86 8.63-73.25 37.45-14.84 27.81-14.58 64.08-7.93 94.08 5.1 23.01 14.5 46.13 26.85 68.47 27.53 49.8 70.27 96.95 115.63 131.16 11.04 8.23 22.87 15.62 33.89 22.48l3.07 1.92c39.75 24.7 82.66 45.09 131.23 37.92 7.42-1.09 14.86-2.47 21.87-4.28 27.72-7.21 49.33-20.63 58.18-50.69 3.15-10.7 4.66-25.46-5.59-32.87-22.42-14.64-54.45-32.46-78.35-45.11-12.33-5.49-24.51 9.95-34.45 23.04-7.16 9.36-14.86 20.05-26.28 24.33-5.6 2.1-11.4 2.04-16.92-.23l-1.86-.79C196.02 361.76 154.37 317.52 119.56 255z"/></svg>
                                <?php } elseif ($comment->commentType == 'chat') { ?>
                                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 122.88 108.25"><defs><style>.cls-1{fill-rule:evenodd;}</style></defs><title>chat-bubble</title><path class="cls-1" d="M51.16,93.74c13,12.49,31.27,16.27,49.59,8.46l15.37,6L111,96.13c17.08-13.68,14-32.48,1.44-45.3a44.38,44.38,0,0,1-4.88,13.92A51.45,51.45,0,0,1,93.45,80.84a62.51,62.51,0,0,1-19.73,10,71.07,71.07,0,0,1-22.56,2.92ZM74.74,36.13a6.68,6.68,0,1,1-6.68,6.68,6.68,6.68,0,0,1,6.68-6.68Zm-44.15,0a6.68,6.68,0,1,1-6.68,6.68,6.68,6.68,0,0,1,6.68-6.68Zm22.08,0A6.68,6.68,0,1,1,46,42.81a6.68,6.68,0,0,1,6.68-6.68ZM54,0H54c14.42.44,27.35,5.56,36.6,13.49,9.41,8.07,15,19,14.7,31v0c-.36,12-6.66,22.61-16.55,30.11C79,82.05,65.8,86.4,51.38,86a64.68,64.68,0,0,1-11.67-1.4,61,61,0,0,1-10-3.07L7.15,90.37l7.54-17.92A43.85,43.85,0,0,1,4,59,36.2,36.2,0,0,1,0,41.46c.36-12,6.66-22.61,16.55-30.12C26.3,4,39.53-.4,54,0ZM53.86,5.2h0C40.59,4.82,28.52,8.77,19.69,15.46,11,22,5.5,31.28,5.19,41.6A31.2,31.2,0,0,0,8.61,56.67a39.31,39.31,0,0,0,10.81,13L21,70.87,16.68,81.05l13.08-5.14,1,.42a55.59,55.59,0,0,0,10.05,3.18A59,59,0,0,0,51.52,80.8c13.22.39,25.29-3.56,34.12-10.26C94.31,64,99.83,54.73,100.15,44.4v0c.3-10.32-4.65-19.85-12.9-26.92C78.85,10.26,67.06,5.6,53.87,5.2Z"/></svg>
                                <?php } elseif ($comment->commentType == 'email') { ?>
                                    <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 122.88 95.02" style="enable-background:new 0 0 122.88 95.02" xml:space="preserve"><g><path d="M6.09,0h93.94c1.67,0,3.2,0.69,4.3,1.79c1.1,1.1,1.79,2.63,1.79,4.3v64.56c0,1.26-0.39,2.44-1.05,3.41 c-0.12,0.31-0.31,0.61-0.57,0.86c-0.17,0.16-0.36,0.3-0.57,0.4c-1.06,0.88-2.42,1.42-3.89,1.42H6.09c-1.67,0-3.2-0.68-4.3-1.79 C0.69,73.84,0,72.32,0,70.64V6.09c0-1.68,0.68-3.2,1.79-4.3C2.89,0.68,4.41,0,6.09,0L6.09,0L6.09,0z M116.79,95.02H18.43 c-1.67,0-3.2-0.69-4.3-1.79c-1.1-1.1-1.79-2.63-1.79-4.3v-6.12h4.62v7.54h101.36V18.54h-6.16v-4.67h4.62c1.67,0,3.2,0.68,4.3,1.79 c1.1,1.1,1.79,2.62,1.79,4.3v68.98c0,1.68-0.68,3.2-1.79,4.3C119.99,94.34,118.47,95.02,116.79,95.02L116.79,95.02L116.79,95.02z M4.67,68.08l32.92-33L4.67,8.24V68.08L4.67,68.08L4.67,68.08z M41.22,38.03L7.27,72.06h91.28L66.12,38.04l-10.69,9.11l0,0 c-0.84,0.72-2.09,0.76-2.98,0.04L41.22,38.03L41.22,38.03L41.22,38.03z M69.67,35.02l31.78,33.33V7.94L69.67,35.02L69.67,35.02 L69.67,35.02z M7.66,4.67l46.22,37.68L98.11,4.67H7.66L7.66,4.67L7.66,4.67z"/></g></svg>
                                <?php } elseif ($comment->commentType == 'talk') { ?>
                                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 114.7 122.88"><defs><style>.cls-1{fill-rule:evenodd;}</style></defs><title>discussion</title><path class="cls-1" d="M98.66,109.33c-1.58-3.17-2.12-4.4-4.45-6.93,4.39,1.7,8.13,1.92,11.46,4,2.11,1.33,5.25,2.33,6.1,3.93,2.11,4,1.8,8.35,2.93,12.55H80.51a63.78,63.78,0,0,0-.38-8l3.17,2.53,10.78-8.21,4.58.12ZM59,49c6.76,6.52,16.33,8.49,25.9,4.42l8,3.15-2.66-6.32c8.92-7.15,7.32-17,.75-23.66a23.23,23.23,0,0,1-2.55,7.27A27,27,0,0,1,81,42.22a32.63,32.63,0,0,1-10.31,5.21A37,37,0,0,1,59,49Zm.79-30.09a3.49,3.49,0,1,1-3.49,3.49,3.49,3.49,0,0,1,3.49-3.49Zm11.53,0a3.49,3.49,0,1,1-3.49,3.49,3.49,3.49,0,0,1,3.49-3.49Zm-23.06,0a3.49,3.49,0,1,1-3.49,3.49,3.49,3.49,0,0,1,3.49-3.49ZM60.41,0h0a30.82,30.82,0,0,1,19.12,7c4.91,4.22,7.86,9.94,7.68,16.19v0C87,29.54,83.75,35.08,78.59,39A30.81,30.81,0,0,1,59.06,44.9,33.48,33.48,0,0,1,53,44.17a31.75,31.75,0,0,1-5.23-1.6L36,47.2l3.93-9.36a23.06,23.06,0,0,1-5.59-7,19,19,0,0,1-2.07-9.17c.19-6.25,3.48-11.81,8.64-15.72A30.91,30.91,0,0,1,60.41,0Zm0,2.71h0A28.13,28.13,0,0,0,42.52,8.08c-4.53,3.43-7.41,8.26-7.58,13.65a16.33,16.33,0,0,0,1.79,7.87,20.65,20.65,0,0,0,5.65,6.78l.8.64L41,42.33l6.83-2.68.51.21a27.49,27.49,0,0,0,5.25,1.66,30.2,30.2,0,0,0,5.6.68A28.2,28.2,0,0,0,77,36.84c4.53-3.43,7.41-8.26,7.57-13.65v0c.15-5.38-2.43-10.34-6.73-14a28.13,28.13,0,0,0-17.43-6.4ZM27.05,99.29a7,7,0,0,1,.74-2.57,10.51,10.51,0,0,1-4.13-7.65h-.22a3.06,3.06,0,0,1-1.5-.4,4,4,0,0,1-1.64-2c-.76-1.74-1.36-6.32.55-7.64l-.36-.23,0-.51c-.07-.92-.09-2-.11-3.21-.07-4.31-.16-7.25-3.62-8.29l-1.49-.45,1-1.21a56.29,56.29,0,0,1,8.68-8.81,22,22,0,0,1,10-4.88,12.07,12.07,0,0,1,9.77,2.73,18.61,18.61,0,0,1,2.62,2.63c7.48.73,11.4,6.46,11,13.26a14,14,0,0,1-4.06,9.44,2.85,2.85,0,0,1,1.27.33c1.44.77,1.49,2.45,1.11,3.86-.38,1.17-.85,2.54-1.3,3.68-.55,1.55-1.35,1.84-2.89,1.67-.08,3.83-1.85,5.71-4.23,8l.54,1.89c-1.5,7.28-17.45,8.07-21.76.4ZM0,122.88c1.51-19.54,5.19-12.22,21.86-22.66,4.6,12,26.7,12.75,31.54,0,14.39,9.2,21.58,2.25,21.48,22.66ZM76.08,98v2.76l-1.36,2.9a11.25,11.25,0,0,1,4.1,5.46l4.73,3.67,9.62-7.53-3-4.76V97.89A54.82,54.82,0,0,0,98.74,97a11.8,11.8,0,0,0,5.43-2.61,7.87,7.87,0,0,1-4.06-4.81c-1.72-5-.09-9.84-.86-15.31-1-7.34-5.83-9.8-10.59-9.29-6.07-5-17-2.08-19.92,6.45-1.48,4.31-.58,7.59-.74,12-.21,5.87-1.74,9.22-5.06,10.47a10.56,10.56,0,0,0,5,3.13,36.72,36.72,0,0,0,8.18,1Zm-3.34,4.49.16-.06-.09.1-.07,0Z"/></svg>
                                <?php } ?>
                            </small>
                            <small><strong><?=$comment->user->displayName?></strong></small>
                            <small>-</small>
                            <small><?=$comment->date?></small></div>
                    </div>
                    <?php } ?>
                </section>
            </div>
            <?php } ?>
        </div>
        <?php if ($complain->status != \GDelivery\Libs\Helper\Complain::STATUS_CLOSE) { ?>
        <div class="row wrap-info" style="margin-top: 20px; margin-bottom: 20px;">
            <div class="btn-group-action">
                <button class="btn btn-warning" data-toggle="modal" data-target="#modal-resolve">Xử lý</button>
                <button class="btn btn-warning" data-toggle="modal" data-target="#modal-classify">Phân loại</button>
                <button class="btn btn-warning" data-toggle="modal" data-target="#modal-result">Kết thúc</button>
            </div>
        </div>
        <?php } ?>
    </div>
</main>


<!-- Modal complete order -->
<div class="modal-classify modal fade" id="modal-classify" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Thông tin phân loại</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="form-group">
                        <label for="billNumber">Loại phản ánh</label>
                        <select class="form-select form-control issueType" name="issueType">
                            <option value="">Loại phản ánh</option>
                            <?php foreach ($arrErrors->class as $err) { ?>
                                <option value="<?=$err->name?>"><?=$err->name?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="checkNumber">Vấn đề</label>
                        <select class="form-select form-control issue" name="issue">
                            <option value="">Vấn đề</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="checkNumber">Chi tiết vấn đề</label>
                        <select class="form-select form-control issueDetail" name="issueDetail">
                            <option value="">Chi tiết</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="checkNumber">Phân loại lỗi</label>
                        <select class="form-select form-control levelError" name="levelError">
                            <option value="">Phân loại</option>
                            <?php foreach ($arrErrors->errors as $err) { ?>
                                <option value="<?=$err?>"><?=$err?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="checkNumber">Nội dung phản ánh</label>
                        <textarea class="form-control issueComment" rows="3"></textarea>
                    </div>
                </div>
                <div class="text-right">
                    <button class="btn-submit btn btn-warning btn-update-complain">Hoàn tất</button>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal-resolve modal fade" id="modal-resolve" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Thông tin xử lý</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="form-group">
                        <label for="billNumber">Phương thức liên lạc</label>
                        <select class="form-select form-control contactMethod" name="contactMethod">
                            <option value="">Phương thức liên lạc</option>
                            <option value="call">Gọi điện</option>
                            <option value="chat">Chat</option>
                            <option value="email">Email</option>
                            <option value="talk">Gặp trực tiếp</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="checkNumber">Kết quả</label>
                        <select class="form-select form-control result" name="result">
                            <option value="">Kết quả</option>
                            <option value="Liên hệ thành công">Liên hệ thành công</option>
                            <option value="Không liên hệ được">Không liên hệ được</option>
                            <option value="Hẹn liên hệ sau">Hẹn liên hệ sau</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="checkNumber">Chi tiết xử lý</label>
                        <textarea class="form-control detailResolve" rows="3"></textarea>
                    </div>
                </div>
                <div class="text-right">
                    <button class="btn-submit btn btn-warning btn-resolve-complain">Lưu</button>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal-result modal fade" id="modal-result" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Kết thúc</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="checkNumber">Kết quả xử lý</label>
                    <textarea class="form-control resultComment" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="checkNumber">Điểm đánh giá</label>
                    <select class="form-select form-control commentPoint" name="commentPoint">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>
                <div class="text-right">
                    <button class="btn-submit btn btn-warning btn-finish-complain">Lưu</button>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
    get_footer('restaurant');
?>

<script type="text/javascript">
    $(document).ready(function() {
        let issueList = <?=$str?>;
        $('.issueType').on('change', function() {
            let issueTypeValue = $(this).val();
            let issues = '<option value="">Vấn đề</option>';
            $.each( issueList.class, function( index, issueType ){
                if (issueTypeValue === issueType.name) {
                    console.log(issueType);
                    $.each( issueType.issue, function( key, issue ){
                        issues += '<option value="'+issue.name+'">'+issue.name+'</option>';
                    });
                }
            });
            $('.issue').html(issues);
            $(".issueDetail").html('<option value="">Phân loại</option>');
        });
        $('.issue').on('change', function() {
            let issueTypeValue = $('.issueType').val();
            let issueValue = $(this).val();
            if (issueTypeValue) {
                let issueDetail = '<option value="">Vấn đề</option>';
                $.each( issueList.class, function( index, typeClass ){
                    if (issueTypeValue === typeClass.name) {
                        $.each( typeClass.issue, function( key, issue ){
                            if (issue.name == issueValue) {
                                $.each( issue.errors, function( keyErr, error ){
                                    issueDetail += '<option value="'+error.name+'">'+error.name+'</option>';
                                });
                            }
                        });
                    }
                });
                $('.issueDetail').html(issueDetail);
            }
        });
        $('.btn-update-complain').on('click', function() {
            let issueType = $('.issueType').val();
            if (issueType) {
                $.ajax({
                    'type': 'PUT',
                    'url': '/wp-json/api/v1/update/' + <?=$complain->id?> + '/complain',
                    'dataType': 'json',
                    'data': {
                        'issueType': $('.issueType').val(),
                        'issue': $('.issue').val(),
                        'issueDetail': $('.issueDetail').val(),
                        'issueComment': $('.issueComment').val(),
                        'levelError': $('.levelError').val()
                    },
                    'success': function (res) {
                        if (res.messageCode === 1) {
                            alert('Cập nhật thành công');
                            $('.modal-classify').modal('hide');
                            location.reload();
                        } else {
                            alert(res.message);
                        }
                    },
                    'error': function (x, y, z) {

                    }
                }); // end ajax
            } else {
                alert('Vui lòng nhập đầy đủ thông tin!');
            }
        });
        $('.btn-resolve-complain').on('click', function() {
            let commentType = $('.contactMethod').val();
            let result = $('.result').val();
            let comment = $('.detailResolve').val();
            if (commentType && result && comment) {
                $.ajax({
                    'type': 'PUT',
                    'url': '/wp-json/api/v1/complain/' + <?=$complain->id?> + '/resolve',
                    'dataType': 'json',
                    'data': {
                        'commentType': commentType,
                        'result': result,
                        'comment': comment,
                        'orderId': <?=$complain->orderId?>,
                        'userId': <?=$currentUser->ID?>
                    },
                    'success': function (res) {
                        if (res.messageCode === 1) {
                            alert('Cập nhật thành công');
                            $('.modal-resolve').modal('hide');
                            location.reload();
                        } else {
                            alert(res.message);
                        }
                    },
                    'error': function (x, y, z) {

                    }
                }); // end ajax
            } else {
                alert('Vui lòng nhập đầy đủ thông tin!');
            }
        });

        $('.btn-finish-complain').on('click', function() {
            let resultComment = $('.resultComment').val();
            let commentPoint = $('.commentPoint').val();
            if (resultComment && commentPoint) {
                $.ajax({
                    'type': 'PUT',
                    'url': '/wp-json/api/v1/complain/' + <?=$complain->id?> + '/complete',
                    'dataType': 'json',
                    'data': {
                        'resultComment': resultComment,
                        'commentPoint': commentPoint
                    },
                    'success': function (res) {
                        if (res.messageCode === 1) {
                            alert('Cập nhật thành công');
                            $('.modal-result').modal('hide');
                            location.reload();
                        } else {
                            alert(res.message);
                        }
                    },
                    'error': function (x, y, z) {

                    }
                }); // end ajax
            } else {
                alert('Vui lòng nhập đầy đủ thông tin!');
            }
        });
    });
</script>
