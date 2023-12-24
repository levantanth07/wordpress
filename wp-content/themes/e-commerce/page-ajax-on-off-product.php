<?php
/*
Template Name: Ajax Update Product Status
*/

use Abstraction\Object\Result;
use GDelivery\Libs\Config;
use GDelivery\Libs\GBackendService;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;

$res = new Result();
$currentUser = wp_get_current_user();
$user = Permission::checkCurrentUserRole($currentUser);

if (current_user_can('setting_on_off_product')) {
    if (isset($_POST['id']) && isset($_POST['status'])) {
        $productId = $_POST['id'];
        $gBackendService = new GBackendService();
        $redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host'   => Config::REDIS_HOST,
            'port'   => Config::REDIS_PORT,
            'password' => Config::REDIS_PASS
        ]);
        $tags = 'product,product-tag,product-hotdeal-home,product-suggestion-home,product-in-group,product-group,product-tag,product-list,product-detail,product-upsells';
        $doClearCache = $gBackendService->clearRedisCache($tags);
        if ($doClearCache->messageCode != Message::SUCCESS) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "Xóa cache redis thất bại: " . $doClearCache->message;

            Response::returnJson($res);
            die;
        }

        $doUpdate = wp_update_post(array(
            'ID' => $productId,
            'post_status' => $_POST['status'] == 1 ? 'publish' : 'draft'
        ));

        $doSyncProduct = $gBackendService->syncElasticSearchProduct($productId);
        if ($doSyncProduct->messageCode != Message::SUCCESS) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "Đồng bộ elasticsearch thất bại: " . $doSyncProduct->message;

            $doUpdate = wp_update_post(array(
                'ID' => $productId,
                'post_status' => $_POST['status'] == 1 ? 'draft' : 'publish'
            ));

            Response::returnJson($res);
            die;
        }

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $doUpdate;
    } else {
        $res->messageCode = Message::GENERAL_ERROR;
        $res->message = 'Cần truyền id và trạng thái của sản phẩm';
    }
} else {
    $res->messageCode = Message::GENERAL_ERROR;
    $res->message = 'Bạn ko có quyền truy cập';
}

Response::returnJson($res);