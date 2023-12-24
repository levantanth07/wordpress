<?php

/*
Template Name: Restaurant Export Order Detail
*/

// Get from Args của WP_CLI
/*$params = [];
if (isset($args)) {
    $arrArgs = explode( ',', $args[0]);
    foreach ($arrArgs as $arg) {
        $tmp = explode('=', $arg);
        if (isset($tmp[1])) {
            $params[$tmp[0]] = $tmp[1];
        }
    }
}*/

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


$startTime = microtime(true);
WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Bắt đầu xử lý báo cáo\n");

while (true) {

    $redis = new \Predis\Client(
        [
            'scheme' => 'tcp',
            'host' => \GDelivery\Libs\Config::REDIS_HOST,
            'port' => \GDelivery\Libs\Config::REDIS_PORT,
            'password' => \GDelivery\Libs\Config::REDIS_PASS
        ]
    );

    $logger = new Logger('export-report');
    $logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
    $logger->pushHandler(new StreamHandler(ABSPATH . '/logs/export/export-report-' . date_i18n('Y-m-d') . '.log', Logger::DEBUG));

    $cacheKey = $keyCache = "g-delivery:request:export:report";
    $getCache = $redis->get($keyCache);
    if ($getCache) {
        /*$dir = 'download';
        print(date_i18n('Y-m-d H:i:s')." - Xử lý thư mục ghi file\n");
        if (file_exists($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                $today = date('Y-m-d');
                if ($object != "." && $object != ".." && $object != $today) {
                    if (filetype($dir."/".$object) == "dir") {

                        $childObjects = scandir($dir."/".$object);
                        foreach ($childObjects as $child) {
                            if ($child != "." && $child != "..") {
                                unlink($dir . "/" . $object . "/" . $child);
                            }
                        }
                        rmdir($dir."/".$object);

                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            reset($objects);
        }*/

        if (!file_exists('download/' . date_i18n('Y-m-d'))) {
            mkdir('download/' . date_i18n('Y-m-d'), 0777, true);
        }
        WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Thành công - Xử lý thư mục ghi file\n");
        $requestList = \json_decode($getCache, true);

        if (!empty($requestList)) {

            $logger->info("Process export request: " . \json_encode($requestList));


            foreach ($requestList as $index => $params) {
                if (!$params['isProgress']) {

                    $requestList[$index]['isProgress'] = true;
                    $redis->set($keyCache, \json_encode($requestList));
                    WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Process file với params: " . \json_encode($params) . "\n");

                    $restaurants = isset($params['restaurants']) && $params['restaurants'] ? explode(',', trim($params['restaurants'], ',')) : '';
                    $fromDate = $params['fromDate'] ?? date_i18n('Y-m-d');
                    $toDate = $params['toDate'] ?? date_i18n('Y-m-d');
                    $fromDateDelivery = $params['fromDateDelivery'] ?? null;
                    $toDateDelivery = $params['toDateDelivery'] ?? null;
                    $selectedStatus = $params['status'] ?? 'all';
                    $numberPerPage = $params['numberPerPage'] ?? -1;

                    $wpArgs = [];

                    if ($restaurants) {
                        $wpArgs['meta_key'] = 'restaurant_code';
                        $wpArgs['meta_value'] = $restaurants;
                        $wpArgs['meta_compare'] = 'IN';
                    }

                    if ($fromDate && $toDate) {
                        $wpArgs['date_created'] = "{$fromDate}...{$toDate}";
                    }

                    if ($fromDateDelivery && $toDateDelivery) {
                        $wpArgs['meta_key'] = 'delivery_date';
                        $wpArgs['meta_value'] = [
                            date("d/m/Y", strtotime($fromDateDelivery)),
                            date("d/m/Y", strtotime($toDateDelivery))
                        ];
                        $wpArgs['meta_compare'] = 'BETWEEN';
                    }

                    if ($selectedStatus && $selectedStatus != 'all') {
                        $wpArgs['status'] = $selectedStatus;
                    }

                    $wpArgs['posts_per_page'] = $numberPerPage;

                    $oldMemory = ini_get('memory_limit');
                    ini_set("memory_limit", -1);

                    $orders = wc_get_orders($wpArgs);
                    wp_reset_query();
                    ini_set("memory_limit", $oldMemory);

                    $subject = "Kết quả yêu cầu download report order từ: {$fromDate} - {$toDate}";
                    if (\GDelivery\Libs\Config::ENV != 'production') {
                        $subject .= ' (UAT)';
                    }
                    if ($orders) {
                        $spreadsheet = new Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();

                        $spreadsheet->getProperties()->setCreator('miraimedia.co.th')
                            ->setLastModifiedBy('Cholcool')
                            ->setTitle('how to export data to excel use phpspreadsheet in codeigniter')
                            ->setSubject('Generate Excel use PhpSpreadsheet in CodeIgniter')
                            ->setDescription('Export data to Excel Work for me!');

                        // add style to the header
                        $styleArray = array(
                            'font' => array(
                                'bold' => true,
                            ),
                            'alignment' => array(
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            ),
                            'borders' => array(
                                'bottom' => array(
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    'color' => array('rgb' => '333333'),
                                ),
                            ),
                            'fill' => array(
                                'type' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                                'rotation' => 90,
                                'startcolor' => array('rgb' => '0d0d0d'),
                                'endColor' => array('rgb' => 'f2f2f2'),
                            ),
                        );
                        $spreadsheet->getActiveSheet()->getStyle('A1:R1')->applyFromArray($styleArray);
                        // auto fit column to content
                        foreach (range('A', 'R') as $columnID) {
                            $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
                        }
                        // set the names of header cells
                        $sheet->setCellValue('A1', 'STT');
                        $sheet->setCellValue('B1', 'Khách hàng');
                        $sheet->setCellValue('C1', 'SĐT');
                        $sheet->setCellValue('D1', 'Mã nhà hàng (SAP CODE)');
                        $sheet->setCellValue('E1', 'Chuỗi');
                        $sheet->setCellValue('F1', 'Tên nhà hàng');
                        $sheet->setCellValue('G1', 'Mã nhà hàng chuyển đơn');
                        $sheet->setCellValue('H1', 'Tên nhà hàng chuyển đơn');
                        $sheet->setCellValue('I1', 'Mã đơn hàng');
                        $sheet->setCellValue('J1', 'Trạng thái');
                        $sheet->setCellValue('K1', 'Số bill');
                        $sheet->setCellValue('L1', 'Danh mục');
                        $sheet->setCellValue('M1', 'Mã sản phẩm');
                        $sheet->setCellValue('N1', 'Tên sản phẩm');
                        $sheet->setCellValue('O1', 'Số lượng');
                        $sheet->setCellValue('P1', 'Đơn giá');
                        $sheet->setCellValue('Q1', 'Số tiền giảm giá');
                        $sheet->setCellValue('R1', 'Thành tiền');

                        $styleArray = array(
                            'borders' => array(
                                'allBorders' => array(
                                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                    'color' => array('argb' => '343a40'),
                                ),
                            ),
                        );

                        $x = 2;
                        $stt = 1;

                        foreach ($orders as $key => $order) {
                            $jsonRestaurant = $order->get_meta('restaurant_object');
                            $rkOrder = $order->get_meta('rkOrder');
                            if ($rkOrder) {
                                if (is_array($rkOrder) && isset($rkOrder['billNumber'], $rkOrder['checkNumber'])) {
                                    $billNumber = $rkOrder['billNumber'];
                                    $checkNumber = $rkOrder['checkNumber'];
                                } elseif (is_object($rkOrder) && isset($rkOrder->billNumber, $rkOrder->checkNumber)) {
                                    $billNumber = $rkOrder->billNumber;
                                    $checkNumber = $rkOrder->checkNumber;
                                } else {
                                    $billNumber = '';
                                    $checkNumber = '';
                                }
                            } else {
                                $billNumber = '';
                                $checkNumber = '';
                            }

                            $index = 1;
                            if ($order->get_items()) {
                                foreach ($order->get_items() as $item) {
                                    if ($item->get_product_id()) {
                                        $productId = $item->get_data()['variation_id'] ? $item->get_data()['variation_id'] : $item->get_data()['product_id'];
                                        $product = wc_get_product($productId);
                                        $terms = get_the_terms($item->get_product_id(), 'product_group');

                                        if ($index == 1) {
                                            $sheet->setCellValue('A' . $x, $stt);
                                            $sheet->setCellValue('B' . $x, $order->get_shipping_first_name());
                                            $sheet->setCellValue('C' . $x, $order->get_shipping_phone());
                                            $sheet->setCellValue('D' . $x, $jsonRestaurant->code);
                                            $sheet->setCellValue('E' . $x, $jsonRestaurant->brand->name);
                                            $sheet->setCellValue('F' . $x, $jsonRestaurant->name);

                                            $restaurantHistories = $order->get_meta('restaurant_histories');
                                            if ($restaurantHistories && count($restaurantHistories) > 1) {
                                                $firstRestaurant = $restaurantHistories[0];
                                                $sheet->setCellValue('G' . $x, $firstRestaurant['restaurant']->restaurant->code);
                                                $sheet->setCellValue('H' . $x, $firstRestaurant['restaurant']->restaurant->name);
                                            } else {
                                                $sheet->setCellValue('G' . $x, '');
                                                $sheet->setCellValue('H' . $x, '');
                                            }

                                            $sheet->setCellValue('I' . $x, $order->get_id());
                                            $sheet->setCellValue('J' . $x, \GDelivery\Libs\Helper\Order::orderStatusName($order->get_status()));
                                            $sheet->setCellValue('K' . $x, $billNumber);
                                            $stt++;
                                        } else {
                                            $sheet->setCellValue('B' . $x, '');
                                            $sheet->setCellValue('C' . $x, '');
                                            $sheet->setCellValue('D' . $x, '');
                                            $sheet->setCellValue('E' . $x, '');
                                            $sheet->setCellValue('F' . $x, '');
                                            $sheet->setCellValue('G' . $x, '');
                                            $sheet->setCellValue('H' . $x, '');
                                            $sheet->setCellValue('I' . $x, '');
                                            $sheet->setCellValue('J' . $x, '');
                                            $sheet->setCellValue('K' . $x, '');
                                        }

                                        $productGroup = $terms;
                                        $sheet->setCellValue('L' . $x, isset($terms[0]) ? $terms[0]->name : '');
                                        $sheet->setCellValue('M' . $x, get_field('product_rk_code', $item->get_product_id()));
                                        $sheet->setCellValue('N' . $x, $item->get_name());
                                        $sheet->setCellValue('O' . $x, $item->get_quantity());

                                        $price = $product->get_sale_price() ? (float)$product->get_sale_price() : (float)$product->get_regular_price();
                                        $salePrice = $price ?: $product->get_price();
                                        $sheet->setCellValue('P' . $x, $salePrice);

                                        /*$discount = 0;
                                        if (!empty($productDiscount)) {
                                            $discount = $productDiscount[$productId]['discountItem'] + $productDiscount[$productId]['discount'];
                                        }*/
                                        $sheet->setCellValue('Q' . $x, '');
                                        $sheet->setCellValue('R' . $x, $item->get_total());
                                        $sheet->getStyle('A2:R' . $x)->applyFromArray($styleArray);
                                        $x++;
                                        $index++;
                                    }
                                }
                            } else {
                                $stt++;
                            }
                        }

                        $writer = new Xlsx($spreadsheet);

                        $fromDateR = new DateTime($fromDate);
                        $toDateR = new DateTime($toDate);
                        $fileName = 'restaurant_order_report_from_' . $fromDateR->format('d_m_Y') . '_to_' . $toDateR->format('d_m_Y') . '.xlsx';

                        /*header('Content-Type: application/vnd.ms-excel');
                        header('Content-Disposition: attachment; filename="'.$fileName.'"');*/
                        $writer->save('download/' . date_i18n('Y-m-d') . '/' . $fileName);
                        // Todo send mail for customer

                        $downloadLink = site_url('download/' . date_i18n('Y-m-d') . '/' . $fileName);
                        $sendMail = GDelivery\Libs\Helper\Mail::sendMailDownload($params['email'], $subject, $downloadLink);

                        WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Thành công - Tạo file báo cáo: {$downloadLink}\n");
                        WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Gửi mail: {$sendMail}\n");
                    } else {
                        // Todo send mail empty
                        GDelivery\Libs\Helper\Mail::sendMailDownload($params['email'], $subject);
                        WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Không có order\n");
                    }
                } else {
                    WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Đang xử lý \n");
                }
            }

            $getNewCache = $redis->get($keyCache);
            $newRequestList = \json_decode($getNewCache, true);
            foreach ($newRequestList as $key => $item) {
                foreach ($requestList as $oldItem) {
                    if (
                        $item['id'] == $oldItem['id']
                        && $item['restaurants'] == $oldItem['restaurants']
                        && $item['fromDate'] == $oldItem['fromDate']
                        && $item['toDate'] == $oldItem['toDate']
                        && $item['status'] == $oldItem['status']
                    ) {
                        unset($newRequestList[$key]);
                    }
                }
            }
            $redis->set($keyCache, \json_encode($newRequestList));
        } else {
            WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Không có queue transaction; Ngủ 15 phút\n");
            sleep(15);
        }

    } else {
        WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Không có request trong redis\n");
    }
    WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Kết thúc xử lý report; Duration: " . (microtime(true) - $startTime) . "\n");
}