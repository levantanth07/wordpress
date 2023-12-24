<?php

if (!is_user_logged_in()) {
    header('Location: ' . site_url('wp-login.php'));
}

// Get process request
$startDate = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : date('Y-m-d');
$endDate = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : date('Y-m-d');
$typeReport = isset($_REQUEST['typeReport']) ? $_REQUEST['typeReport'] : "";
$dataRestaurant = isset($_REQUEST['dataRestaurant']) ? $_REQUEST['dataRestaurant'] : "";
$status = isset($_REQUEST['status']) ? $_REQUEST['status'] : "";
$dataUtm = isset($_REQUEST['utm']) ? $_REQUEST['utm'] : "";

// process param
$params = [
    'startDate' => $startDate,
    'endDate' => $endDate,
    'utm' => $dataUtm,
];

if ($dataRestaurant) {
    $restaurantCodes = explode(",", $dataRestaurant);
    if ($restaurantCodes) {
        foreach ($restaurantCodes as $key => $value) {
            $params['restaurantCodes'][] = $value;
        }
    }
}

if ($status) {
    $statusOrder = explode(",", $status);
    if ($statusOrder) {
        foreach ($statusOrder as $key => $value) {
            $params['orderStatuses'][] = $value;
        }
    }
}

$report = new \GDelivery\Libs\Helper\Report();
$getReport = $report->export($typeReport, $params);
if ($getReport->messageCode == 1) {
    header("Content-Type:   application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$getReport->nameFileCsv");  //File name extension was wrong
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false);
    echo $getReport->result;
} else {
    echo $getReport->message;
}