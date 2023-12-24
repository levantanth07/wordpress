<?php
add_action( 'admin_menu', 'registerSettingWebsiteMenu' );
function registerSettingWebsiteMenu() {
    add_menu_page(
        'System Settings',
        'System Settings',
        'edit_posts',
        'system_setting',
        'pageSystemSettings',
        'dashicons-media-spreadsheet'
    );

    add_submenu_page(
        'system_setting',
        'Setting Websites',
        'Setting Websites',
        'edit_posts',
        'setting-website',
        'importSettingWebsitePage'
    );

    add_submenu_page(
        'system_setting',
        'Log Update Order',
        'Log Update Order',
        'edit_posts',
        'log-update-order',
        'importLogUpdateOrderPage'
    );

    add_submenu_page(
        'system_setting',
        'Clear Cache Redis',
        'Clear Cache Redis',
        'edit_posts',
        'flush_db_redis',
        'flushDbRedis'
    );

    add_submenu_page(
        'system_setting',
        'Clear CLM Voucher',
        'Clear CLM Voucher',
        'edit_posts',
        'clear-clm-voucher',
        'clearClmVoucher'
    );

}

function pageSystemSettings() {
    echo 'Cài đặt';
}


function importSettingWebsitePage()
{
    $paymentMethod = \GDelivery\Libs\Helper\Payment::$paymentMethod;
    $currentUser = wp_get_current_user();
    $user = Permission::checkCurrentUserRole($currentUser);

    if ($_POST) {
        try {
            if (!empty($_POST['enable_evoucher'])) {
                update_option('enable_evoucher', 1);
            } else {
                update_option('enable_evoucher', 0);
            }

            if (current_user_can('setting_payment_method') || $user->role == 'administrator') {
                foreach ($paymentMethod as $key => $value) {
                    if (!empty($_POST['enable_' . $key])) {
                        update_option('enable_' . $key, 1);
                    } else {
                        update_option('enable_' . $key, 0);
                    }
                }
            }

            if (!empty($_POST['enable_subiz'])) {
                update_option('enable_subiz', 1);
            } else {
                update_option('enable_subiz', 0);
            }

            if (!empty($_POST['masoffer_cookie_time_to_live'])) {
                update_option('masoffer_cookie_time_to_live', $_POST['masoffer_cookie_time_to_live']);
            } else {
                update_option('masoffer_cookie_time_to_live', '');
            }

            if (!empty($_POST['google_map_service_address'])) {
                update_option('google_map_service_address', $_POST['google_map_service_address']);
            } else {
                update_option('google_map_service_address', 'goong_address');
            }

            if (!empty($_POST['tax_shipping_fee'])) {
                update_option('tax_shipping_fee', $_POST['tax_shipping_fee']);
            }

            if (!empty($_POST['tax_shipping_fee_icook'])) {
                update_option('tax_shipping_fee_icook', $_POST['tax_shipping_fee_icook']);
            }

            /*if (!empty($_POST['netcore_is_enabled'])) {
                update_option('netcore_is_enabled', 1);
                update_option('netcore_tracking_script', esc_html(@$_POST['netcore_tracking_script']));
            } else {
                update_option('netcore_is_enabled', 0);
                update_option('netcore_tracking_script', '');
            }*/
            wp_redirect('/wp-admin/admin.php?page=setting-website');
        } catch (\Exception $e) {
            echo 'Lỗi khi xử lý file: '.$e->getMessage();
        }
    }
    ?>
    <h1>Setting Website</h1>

    <form action="" method="post" enctype="multipart/form-data">
        <table>
            <tr>
                <td>
                    Bật Evoucher
                </td>
                <td>
                    <label class="switch">
                        <input name="enable_evoucher" type="checkbox" value="1" <?php echo get_option('enable_evoucher') == '1' ? 'checked' : ''?>/>
                        <span class="slider round"></span>
                    </label>
                </td>
            </tr>
            <?php if (current_user_can('setting_payment_method') || $user->role == 'administrator'): ?>
            <?php foreach ($paymentMethod as $key => $value): ?>
            <tr>
                <td>
                    Bật thanh toán <?=$value?>
                </td>
                <td>
                    <label class="switch">
                        <input name="enable_<?=$key?>" type="checkbox" value="1" <?php echo get_option('enable_' . $key) === '1' ? 'checked' : ''?>/>
                        <span class="slider round"></span>
                    </label>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            <tr>
                <td>
                    Bật Subiz
                </td>
                <td>
                    <label class="switch">
                        <input name="enable_subiz" type="checkbox" value="1" <?php echo get_option('enable_subiz') === '1' ? 'checked' : ''?>/>
                        <span class="slider round"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    Set MasOffer Cookie
                </td>
                <td>
                    <input name="masoffer_cookie_time_to_live" id="masoffer_cookie_time_to_live" type="number" value="<?=get_option('masoffer_cookie_time_to_live')?>" onclick="myFunction()"/>
                </td>
            </tr>
            <tr>
                <td>
                    Google Map Service Address
                </td>
                <?php
                    $serviceAddress = get_option('google_map_service_address');
                ?>
                <td>
                    <select name="google_map_service_address" id="google_map_service_address">
                        <option value="goong_address" <?=$serviceAddress == 'goong_address' ? 'selected' : ''?>>goong_address</option>
                        <option value="autocomplete" <?=$serviceAddress == 'autocomplete' ? 'selected' : ''?>>autocomplete</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    Thuế phí vận chuyển chung
                </td>
                <td>
                    <input name="tax_shipping_fee" id="tax_shipping_fee" placeholder="10.0" pattern="[0-9]+\.?[0-9]+" value="<?=get_option('tax_shipping_fee')?>"/>
                </td>
            </tr>
            <tr>
                <td>
                    Thuế phí vận chuyển Icook
                </td>
                <td>
                    <input name="tax_shipping_fee_icook" id="tax_shipping_fee_icook" placeholder="10.0" pattern="[0-9]+\.?[0-9]+" value="<?=get_option('tax_shipping_fee_icook')?>"/>
                </td>
            </tr>
            <!--<tr>
                <td>
                    Bật Netcore
                </td>
                <td>
                    <label class="switch">
                        <input name="netcore_is_enabled" id="netcore_is_enabled" type="checkbox" value="1" <?php /*echo get_option('netcore_is_enabled') === '1' ? 'checked' : ''*/?> onclick="myFunction()"/>
                        <span class="slider round"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    Mã js tracking của netcore
                </td>
                <td>
                    <textarea name="netcore_tracking_script" id="netcore_tracking_script" cols="30" rows="5"><?php /*echo get_option('netcore_tracking_script') ? get_option('netcore_tracking_script') : ''*/?></textarea>
                </td>
            </tr>-->
            <tr>
                <td colspan="2">
                    <button type="submit">Cập nhật</button>
                </td>
            </tr>
        </table>
    </form>
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 36px;
            height: 19px;
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

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
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
    </style>
    <script>
        function myFunction() {
            var checkBox = document.getElementById("netcore_is_enabled");
            var text = document.getElementById("netcore_tracking_script");
            if (checkBox.checked == true){
                text.style.display = "block";
            } else {
                text.style.display = "none";
            }
        }
        myFunction();
    </script>
    <?php
}

function importLogUpdateOrderPage()
{
    if ($_POST) {
        try {
            $source = ABSPATH.'/logs/order/update-order-info-' . $_POST['date'] . '.log';
            if (file_exists($source)) {
                $file = fopen($source, "r");
                $log = fread($file,filesize($source));
                $logs = explode(' [] []', $log);

                fclose($file);
            }
        } catch (\Exception $e) {
            echo 'Lỗi khi xử lý file: '.$e->getMessage();
        }
    }
    ?>
    <h1>Log Update Order</h1>

    <form action="" method="post">
        <input name="date" type="date" value="<?php echo !empty($_POST['date']) ? $_POST['date'] : ''?>"/>
        <button type="submit">Tìm</button>
    </form>

    <table cellspacing="1" cellpadding="1">
        <tr>
            <!--            <th>Ngày</th>-->
            <th>User Change</th>
            <th>Data</th>
        </tr>
        <?php
        if (!empty($logs)) {
            foreach ($logs as $k => $item) {
                $position = strpos($item, 'Data: ');
                if (is_numeric($position)) {
                    $data = json_decode(substr($item, $position + 6));
                    ?>
                    <tr>
                        <!--                        <td>--><?php //echo $_POST['date']?><!--</td>-->
                        <td><?php echo $data->user_id_change?></td>
                        <td>
                            <p>ID: <?php echo $data->order_old->id ?? ''?> -> <?php echo $data->order_new->id ?? ''?></p>
                            <p>Đối tác thanh toán: <?php echo $data->order_old->payment_method ?? ''?> -> <?php echo $data->order_new->payment_method ?? ''?></p>
                            <p>Trạng thái đã thanh toán online: <?php echo $data->order_old->is_paid == 1 ? 'Đã thanh toán' : 'Chưa thanh toán'?> -> <?php echo $data->order_new->is_paid == 1 ? 'Đã thanh toán' : 'Chưa thanh toán'?></p>
                            <p>Phương thức vận chuyển: <?php echo $data->order_old->vendor_transport ?? ''?> -> <?php echo $data->order_new->vendor_transport ?? ''?></p>
                            <p>Trạng thái: <?php $arrStatus = \GDelivery\Libs\Helper\Order::$arrayStatus;
                                foreach ($arrStatus as $key => $item_status) :
                                    if ($key == $data->order_old->status) :
                                        echo $item_status;
                                    endif;
                                endforeach;?>
                                ->
                                <?php $arrStatus = \GDelivery\Libs\Helper\Order::$arrayStatus;
                                foreach ($arrStatus as $key => $item_status) :
                                    if ($key == $data->order_new->status) :
                                        echo $item_status;
                                    endif;
                                endforeach;
                                ?>
                            </p>
                            <p>Mã giao dịch ở hệ thống đối tác: <?php echo $data->order_old->payment_partner_transaction_id ?? ''?> -> <?php echo $data->order_new->payment_partner_transaction_id ?? ''?></p>
                        </td>
                    </tr>
                    <?php
                }
            }
        }
        ?>
    </table>
    <style>
        table tr {
            border-top: 1px solid;
        }
    </style>
    <?php
}

function flushDbRedis()
{
    $action = '';
    if ($_POST) {
        $action = $_POST['action'];
        switch ($action) {
            case 'backend':
                $ipsBe = explode(',', \GDelivery\Libs\Config::ECOMMERCE_BE_IP_SERVERS);

                try {
                    if ($ipsBe) {
                        foreach ($ipsBe as $ip) {
                            if ($ip) {
                                file_get_contents("http://{$ip}/api/v1/services/clear-cache");
                            }
                        }

                    }

                    $message = 'Thành công.';
                } catch (\Exception $e) {
                    echo 'Lỗi khi xử lý file: ' . $e->getMessage();
                    $message = 'Clear cache không thành công!';
                }
                break;
            case 'cms':
                $redis = new \Predis\Client(
                    [
                        'scheme' => 'tcp',
                        'host'   => \GDelivery\Libs\Config::REDIS_HOST,
                        'port'   => \GDelivery\Libs\Config::REDIS_PORT,
                        'password' => \GDelivery\Libs\Config::REDIS_PASS
                    ]
                );
                $redis->flushdb();
                $message = 'Thành công.';
                break;
            default:
                $message = 'Vui lòng chọn hệ thống.';
        }

        add_settings_error('flush_db_redis', 'flush_db_error', $message);
        settings_errors('flush_db_redis');
    }
    ?>
    <h1>Clear cache Redis</h1>

    <form action="" method="post" enctype="multipart/form-data">
        CMS <input type="radio" name="action" value="cms" <?=($action == 'cms' ? 'checked' : '')?> /> - Backend <input type="radio" name="action" value="backend" <?=($action == 'backend' ? 'checked' : '')?> />
        <button type="submit">Clear</button>
    </form>
    <style>
        .notice {
            margin-left: 0;
        }
    </style>
    <?php
}

function clearClmVoucher()
{
    if ($_POST) {
        if (isset($_POST['clm-voucher']) && $_POST['clm-voucher']) {
            $voucherCode = $_POST['clm-voucher'];

            // get option
            $listVouchers = get_option('processing_clm_vouchers', []);
            $temp = [];
            $count = 0;
            if ($listVouchers) {
                foreach ($listVouchers as $one) {
                    if ($voucherCode != $one) {
                        $temp[] = $one;
                    } else {
                        $count++;
                    }
                }
            }

            // save option
            update_option('processing_clm_vouchers', $temp);

            $message = "Đã xử lý {$count} voucher";
        } else {
            $message = 'Vui lòng nhập mã voucher';
            $voucherCode = '';
        }
    } else {
        $voucherCode = '';
        $message = '';
    }
    ?>
    <h1>Clear CLM Voucher</h1>

    <form action="" method="post">
        <input type="text" name="clm-voucher" value="<?=$voucherCode?>" placeholder="Mã voucher TGS của khách">
        <button type="submit">Clear</button>
    </form>

    <?php
    echo $message;
}

function importSlideHomePage()
{
    echo '1';
}