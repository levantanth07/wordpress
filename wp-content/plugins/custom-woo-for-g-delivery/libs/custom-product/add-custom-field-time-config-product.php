<?php

use GDelivery\Libs\Helper\Product;

class AddCustomFieldTimeConfigProduct
{
    public static $startDateKey = 'time_config_start_date';
    public static $endDateKey = 'time_config_end_date';
    public static $configTypeKey = 'time_config_type';
    public static $configTypeDayKey = 'day';
    public static $configTypeDateKey = 'date';
    public static $listOfDaysKey = 'time_config_list_of_days';
    public static $listOfWeekDaysKey = 'time_config_week_days';
    public static $timeFrameKey = 'time_frames';
    public static $hasConfigTimeFrameKey = 'has_config_time_frame';
    const DEFAULT_START_DATE = 946659600;
    const DEFAULT_END_DATE = 4102419600;

    public function __construct()
    {
        add_action('add_meta_boxes_product', [$this, 'addToppingProductMetaBox']);
        add_action('admin_enqueue_scripts', [$this, 'addTimeConfigProductScripts'], 10, 1);
        add_action('admin_enqueue_scripts', [$this, 'localizeTimeConfigMetaBoxScript']);
        add_action('save_post_product', [$this, 'saveProductTimeConfig']);
    }

    function addToppingProductMetaBox()
    {
        add_meta_box('time-config-meta-box', __('Thời gian hiển thị', 'text-domain'), [$this, 'toppingProductMetaBoxCallback'], 'product', 'normal', 'high');
    }

    function getTimeFrame($postId)
    {
        $hasConfigTimeFrame = get_post_meta($postId, self::$hasConfigTimeFrameKey, true) ?? 0;
        if (!intval($hasConfigTimeFrame)) {
            return [];
        }
        $returnData = [];
        $timeFrameRaw = get_post_meta($postId, self::$timeFrameKey, true) ?? '';
        if (empty($timeFrameRaw)) {
            return [];
        }
        $listTimeFrames = explode('_', $timeFrameRaw);
        foreach ($listTimeFrames as $key => $timeFrameItem) {
            $timeObject = explode('|', $timeFrameItem);
            $timeRange = explode(',', $timeObject[0]);
            $returnData[] = $timeRange;
        }
        return $returnData;
    }

    function toppingProductMetaBoxCallback($post)
    {
        $configType = get_post_meta($post->ID, self::$configTypeKey, true) ?? '';
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
        $listOfDays = get_post_meta($post->ID, self::$listOfDaysKey, true) ?? '';
        $listOfDaysArr = get_post_meta($post->ID, self::$listOfWeekDaysKey, true);
        $listOfDaysArr = !empty($listOfDaysArr) ? explode(',', $listOfDaysArr) : [];
        if ($configType == '') {
            $listOfDays = '';
            $listOfDaysArr = [];
        }
        $timeFrames = $this->getTimeFrame($post->ID);
        $pluginDirUrl = plugins_url('', dirname(dirname(__FILE__)) );
    ?>
        <link rel="stylesheet" href="<?=$pluginDirUrl?>/assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?=$pluginDirUrl?>/assets/css/bootstrap-datetimepicker.min.css"/>
        <script type="text/javascript" src="<?=$pluginDirUrl?>/assets/js/moment-with-locales.min.js"></script>
        <script src="<?=$pluginDirUrl?>/assets/js/bootstrap-datetimepicker.min.js"></script>
        <script src="<?=$pluginDirUrl?>/assets/js/bootstrap-datepicker.min.js"></script>
        <div class="time-config-form mt-4">
            <div class="form-group d-flex">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-3">Ngày</div>
                        <div class="col-md-9 row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="input-group date" >
                                        <input autocomplete="off" id="startDate" type="text" class="form-control" name="<?=self::$startDateKey?>" />
                                        <div class="input-group-append" data-toggle="datetimepicker">
                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="input-group date" >
                                        <input autocomplete="off" id="endDate" type="text" class="form-control" name="<?=self::$endDateKey?>" />
                                        <div class="input-group-append" data-toggle="datetimepicker">
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
                                <select class="form-control col-md-3" id="availableType" name="<?=self::$configTypeKey?>">
                                    <option value="">--- Chọn loại hiệu lực ---</option>
                                    <option value="<?=self::$configTypeDayKey?>" <?= $configType == self::$configTypeDayKey ? 'selected' : ''?>>Thứ</option>
                                    <option value="<?=self::$configTypeDateKey?>" <?= $configType == self::$configTypeDateKey ? 'selected' : ''?>>Ngày</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 offset-3 <?=$configType != self::$configTypeDayKey ? 'hidden' : ''?> block-day">
                            <div class="form-group">
                                <?php foreach ($dayConfigs as $day): ?>
                                <div class="form-check form-check-inline" style="min-width: 95px;">
                                    <input class="form-check-input value" id="day-<?=$day['value']?>" type="checkbox"
                                            name="<?=self::$configTypeDayKey?>[]" value="<?=$day['value']?>" <?=!empty($listOfDaysArr) && in_array($day['value'], $listOfDaysArr) ? 'checked' : '';?>>
                                    <label class="form-check-label" for="day-<?=$day['value']?>"><?=$day['text']?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-8 offset-3 <?=$configType != self::$configTypeDateKey ? 'hidden' : ''?> block-dates">
                            <div class="form-group">
                                <div class="input-group date" >
                                    <input class="form-control list-of-date" type="text" name="<?=self::$configTypeDateKey?>" value="<?=$configType === self::$configTypeDateKey ? $listOfDays : '';?>">
                                    <div class="input-group-append" data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="row">
                                <div class="col-md-6">Khung giờ</div>
                                <div class="col-md-6">
                                    <a class="btn btn-primary btn-add-range-time" href="javascript:void(0);"><i class="fa fa-plus"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9 row range-time range-time-container" data-number-of-items="<?=count($timeFrames);?>">
                            <?php if ($timeFrames): $index = 0;?>
                                <?php foreach ($timeFrames as $key => $timeFrame): ?>
                                <div class="row block-range-time col-md-12">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <div class="input-group time" data-target-input="nearest">
                                                <input type="text" id="startTime<?=$key?>" class="form-control datetimepicker-input start-time value"
                                                        name="startTime[]" data-target="#startTime<?=$key?>" value="<?=$this->intToHourTime($timeFrame[0]);?>"/>
                                                <div class="input-group-append" data-target="#startTime<?=$key?>" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-clock-o"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <div class="input-group time" data-target-input="nearest">
                                                <input type="text" id="endTime<?=$key?>" class="form-control datetimepicker-input end-time value"
                                                        name="endTime[]" data-target="#endTime<?=$key?>" value="<?=$this->intToHourTime($timeFrame[1]);?>" />
                                                <div class="input-group-append" data-target="#endTime<?=$key?>" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-clock-o"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($index >= 1): ?>
                                    <div class="col-md-2">
                                        <a class="btn btn-danger btn-remove-range-time" href="javascript:void(0);"><i class="fa fa-trash"></i></a>
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
                                        <div class="input-group time" data-target-input="nearest">
                                            <input type="text" id="startTime0" class="form-control datetimepicker-input start-time value"
                                                    name="startTime[]" data-target="#startTime0" />
                                            <div class="input-group-append" data-target="#startTime0" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-clock-o"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <div class="input-group time" data-target-input="nearest">
                                            <input type="text" id="endTime0" class="form-control datetimepicker-input end-time value"
                                                    name="endTime[]" data-target="#endTime0" />
                                            <div class="input-group-append" data-target="#endTime0" data-toggle="datetimepicker">
                                                <div class="input-group-text"><i class="fa fa-clock-o"></i></div>
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
        </div>
    <?php
    }

    function addTimeConfigProductScripts($hook)
    {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ('product' === $post->post_type) {
                $pluginDirUrl = plugins_url('', dirname(dirname(__FILE__)) );
                wp_enqueue_style( 'fontAwesomeCss', $pluginDirUrl . '/assets/css/font-awesome.min.css');
                wp_enqueue_style( 'customProductTimeConfig', $pluginDirUrl . '/assets/css/custom-product-time-config.css');
                wp_enqueue_script('timeConfigMetaBoxScript', $pluginDirUrl . '/assets/js/time-config-meta-box.js');
            }
        }
    }

    function localizeTimeConfigMetaBoxScript()
    {
        $postId = get_the_ID();
        $timeConfigStartDate = get_post_meta($postId, self::$startDateKey, true) ?? '';
        $timeConfigEndDate = get_post_meta($postId, self::$endDateKey, true) ?? '';
        if (intval($timeConfigStartDate) == self::DEFAULT_START_DATE && intval($timeConfigEndDate) == self::DEFAULT_END_DATE) {
            $timeConfigStartDate = $timeConfigEndDate = '';
        }
        $data = array(
            'startDate' => $timeConfigStartDate ? date('Y-m-d', $timeConfigStartDate) : '',
            'endDate' => $timeConfigEndDate ? date('Y-m-d', $timeConfigEndDate) : '',
            'currentDate' => date_i18n('Y-m-d'),
        );
        wp_localize_script('timeConfigMetaBoxScript', 'scriptVars', $data);
    }

    function saveProductTimeConfig($postId)
    {
        if (empty($_POST)) {
            return;
        }
        $notUseTimeConfig = isset($_POST[self::$startDateKey], $_POST[self::$endDateKey]) && $_POST[self::$startDateKey] == '' && $_POST[self::$endDateKey] == '';
        $notConfigAvailableType = isset($_POST[self::$configTypeKey]) && $_POST[self::$configTypeKey] == '';
        $notConfigTimeFrame = empty(array_filter($_POST['startTime'] ?? [], fn($value) => !is_null($value) && $value !== ''));
        if ($notUseTimeConfig && $notConfigAvailableType && $notConfigTimeFrame) {
            return $this->saveDefaultTimeConfigData($postId);
        }
        
        $this->saveStartEndDate($postId, $_POST);

        $this->saveConfigType($postId, $_POST);

        $this->saveTimeFrame($postId, $_POST);
    }

    function saveStartEndDate($postId, $requestData)
    {
        $startDate = isset($requestData[self::$startDateKey]) && $requestData[self::$startDateKey] ? strtotime($requestData[self::$startDateKey]) : self::DEFAULT_START_DATE;
        $endDate = isset($requestData[self::$endDateKey]) && $requestData[self::$endDateKey] ? strtotime($requestData[self::$endDateKey]) : self::DEFAULT_END_DATE;
        update_post_meta($postId, self::$startDateKey, $startDate);
        update_post_meta($postId, self::$endDateKey, $endDate);
    }

    function saveConfigType($postId, $requestData)
    {
        $configType = $requestData[self::$configTypeKey] ?? '';
        $startDate = $requestData[self::$startDateKey] ?? '';
        $endDate = $requestData[self::$endDateKey] ?? '';

        if ($configType == self::$configTypeDateKey) {
            update_post_meta($postId, self::$listOfDaysKey, $requestData[self::$configTypeDateKey] ?? '');
        } else {
            $weekDays = $configType == '' ? $this->getAllWeekDays() : ($requestData[self::$configTypeDayKey] ?: []);
            update_post_meta($postId, self::$listOfWeekDaysKey, implode(',', $weekDays));
            update_post_meta(
                $postId, 
                self::$listOfDaysKey, 
                implode(',', $this->getDatesOfWeekDaysInRangeTime($weekDays, $startDate, $endDate))
            );
        }
        update_post_meta($postId, self::$configTypeKey, $configType);
    }

    function saveTimeFrame($postId, $requestData)
    {
        $hasConfigTimeFrame = 0;
        $checkHasTimeConfig = array_filter($requestData['startTime'] ?? [], fn($value) => !is_null($value) && $value !== '');
        if (!empty($checkHasTimeConfig)) {
            $timeRanges = [];
            $timeFrames = [];
            foreach($requestData['startTime'] as $key => $startTimeItem) {
                $endTimeItem = $requestData['endTime'][$key] ?? '';
                if (!$startTimeItem || !$endTimeItem) {
                    continue;
                }
                $hasConfigTimeFrame = 1;
                list($timeRange, $itemTimeFrames) = $this->makeTimeFrameData($startTimeItem, $endTimeItem);
                $timeRanges[] = $timeRange;
                $timeFrames = array_merge($timeFrames, $itemTimeFrames);
            }
            if (!empty($timeFrames) && !empty($timeRanges)) {
                update_post_meta($postId, self::$timeFrameKey, implode('_', $timeRanges) . '|' . implode(',', array_unique($timeFrames)));
            }
        }
        if ($hasConfigTimeFrame == 0) {
            $this->saveDefaultTimeFrame($postId);
        }
        update_post_meta($postId, self::$hasConfigTimeFrameKey, $hasConfigTimeFrame);
    }

    function saveDefaultTimeConfigData($postId)
    {
        update_post_meta($postId, self::$startDateKey, self::DEFAULT_START_DATE);
        update_post_meta($postId, self::$endDateKey, self::DEFAULT_END_DATE);

        update_post_meta($postId, self::$configTypeKey, '');
        $weekDays = $this->getAllWeekDays();
        update_post_meta($postId, self::$listOfWeekDaysKey, implode(',', $weekDays));
        update_post_meta($postId, self::$listOfDaysKey, implode(',', $this->getDatesOfWeekDaysInRangeTime($weekDays)));

        $this->saveDefaultTimeFrame($postId);
        update_post_meta($postId, self::$hasConfigTimeFrameKey, 0);
    }

    function getAllWeekDays()
    {
        $weekDays = array();
        for ($i = 0; $i < 7; $i++) {
            $weekDays[] = strtolower(date('l', strtotime("Monday +{$i} days")));
        }
        return $weekDays;
    }

    function saveDefaultTimeFrame($postId)
    {
        list($timeRange, $timeFrames) = $this->makeTimeFrameData('00:00', '23:59');
        update_post_meta($postId, self::$timeFrameKey, $timeRange . '|' . implode(',', $timeFrames));
    }

    function makeTimeFrameData($startTime, $endTime)
    {
        $timeRange = $this->hourTimeToInt($startTime) . ',' . $this->hourTimeToInt($endTime);
        $listTimeFrames = Product::getListTimeFrameFromRangeTime($startTime, $endTime);
        return [$timeRange, $listTimeFrames];
    }

    function getDatesOfWeekDaysInRangeTime($weekDays, $startDate = '', $endDate = '')
    {
        $startDate = new DateTime($startDate ?: date_i18n('d-m-Y'));
        $endDate = $endDate ? new DateTime($endDate) : (clone $startDate)->add(new DateInterval('P1Y'));
        if ($startDate > $endDate) {
            return [];
        }
        $dates = array();
        while ($startDate <= $endDate) {
            $weekDay = strtolower($startDate->format('l'));
            if (in_array($weekDay, $weekDays)) {
                $dates[] = $startDate->format('d-m-Y');
            }
            $startDate->modify('+1 day');
        }
        return $dates;
    }

    function hourTimeToInt($time)
    {
        $time = explode(':', $time);
        $hourInt = intval($time[0])*60*60;
        $minutesInt = intval($time[1])*60;
        return $hourInt + $minutesInt;
    }

    function intToHourTime($intTime)
    {
        $intTime = intval($intTime);
        $hour = floor($intTime/60/60);
        $hour = $hour < 10 ? '0' . $hour : $hour;
        $minutes = ($intTime - $hour*60*60)/60;
        $minutes = $minutes < 10 ? '0' . $minutes : $minutes;
        return $hour . ':' .  $minutes;
    }
}

$initCustomFieldTimeConfigProduct = new AddCustomFieldTimeConfigProduct();
