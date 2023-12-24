<?php
class ExportReport extends \Abstraction\Core\AAjaxHook {

    /**
     * @var \Predis\Client
     */
    private $redis;

    public function __construct()
    {
        parent::__construct();
        // list search address
        add_action("wp_ajax_export_report_order_detail", [$this, "exportReportOrderDetail"]);
        add_action("wp_ajax_nopriv_export_report_order_detail", [$this, "mustLogin"]);

        $this->redis = new \Predis\Client(
            [
                'scheme' => 'tcp',
                'host'   => \GDelivery\Libs\Config::REDIS_HOST,
                'port'   => \GDelivery\Libs\Config::REDIS_PORT,
                'password' => \GDelivery\Libs\Config::REDIS_PASS
            ]
        );
    }

    public function exportReportOrderDetail()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['beHonest'])) {
            if (wp_verify_nonce($_REQUEST['beHonest'], "export_report_order_detail")) {
                $currentUser = wp_get_current_user();
                $user = Permission::checkCurrentUserRole($currentUser);
                if ($user->role == 'restaurant') {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Bạn không được phép truy cập trang này';
                } else {
                    $keyCache = "icook:request:export:report";
                    //$this->redis->del($keyCache); die();
                    $getCache = $this->redis->get($keyCache);
                    $items = [
                        'id' => $currentUser->id,
                        'email' => $currentUser->user_email,
                        'restaurants' => $_REQUEST['restaurant'] ?? null,
                        'fromDate' => $_REQUEST['fromDate'] ?? null,
                        'toDate' => $_REQUEST['toDate'] ?? null,
                        'fromDateDelivery' => $_REQUEST['fromDateDelivery'] ?? null,
                        'toDateDelivery' => $_REQUEST['toDateDelivery'] ?? null,
                        'status' => $_REQUEST['status'] ?? null,
                        'isProgress' => false,
                        'time' => time()
                    ];
                    $isMatch = true;

                    if ($getCache) {
                        $requestList = \json_decode($getCache, true);
                         foreach ($requestList as $list) {
                             if (
                                 $list['id'] == $items['id']
                                 && $list['restaurants'] == $items['restaurants']
                                 && $list['fromDate'] == $items['fromDate']
                                 && $list['toDate'] == $items['toDate']
                                 && $list['status'] == $items['status']
                                 && (($items['time'] - $list['time']) < 1800)
                             ) {
                                 $isMatch = false;
                             }
                         }
                         if ($isMatch) {
                             $requestList[] = $items;
                         }
                    } else {
                        $requestList = [
                            $items
                        ];
                    }
                    if ($isMatch) {
                        $this->redis->set($keyCache, \json_encode($requestList));
                        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                        $res->message = 'Request download đang được xử lý, kết quả sẽ được gửi vào mail khi kết thúc!';
                    } else {
                        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                        $res->message = 'Mỗi lần download cách nhau 30 phút, vui lòng đợi!';
                    }
                }
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Need to pass params';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }
}
// init class
$exportAjax = new ExportReport();