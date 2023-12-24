<!--Check province available-->
<script type="text/javascript">
    function checkProvinceAvailable(provinceId) {
        let result = {};
        if (jQuery.inArray(parseInt(provinceId), [43, 27, 12]) !== -1) {
            result.messageCode = "<?=\Abstraction\Object\Message::GENERAL_ERROR?>";
            result.message = "<p style='text-align: center'>G-Delivery tạm dừng dịch vụ theo chỉ thị phòng chống dịch của thủ tướng chính phủ. <br />Vui lòng liên hệ số điện thoại 02473003077 để được hỗ trợ. <br />Xin lỗi Quý khách vì sự bất tiện này.</p>";
        } else {
            result.messageCode = "<?=\Abstraction\Object\Message::SUCCESS?>";
            result.message = 'Đang hoạt động';
        }

        return result;
    }
</script>

<!--Reload content page when change province-->
<script type="text/javascript">
    function reloadContentPageWhenChangeProvince(action, provinceId, successCallback) {
        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=admin_url('admin-ajax.php')?>',
            'dataType' : 'json',
            'data' : {
                action: action,
                selectedProvinceId: provinceId
            },
            'success' : function (res) {
                if (res.messageCode == 1) {
                    successCallback(res);
                } else {
                    openModalAlert('<?=__('alert.title.error', 'g-delivery')?>', res.message, {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
                }
            },
            'error' : function (x, y, z) {
                openModalAlert('<?=__('alert.title.error', 'g-delivery')?>', '<?=__('alert.message.please_try_again_after_later', 'g-delivery')?>', {scene : 'info', btnOkText: '<?=__('alert.btn.close', 'g-delivery')?>'});
            },
            'complete': function () {
                let checkProvince = checkProvinceAvailable(provinceId);
                if (checkProvince.messageCode === "<?=\Abstraction\Object\Message::GENERAL_ERROR?>") {
                    // Show notification when province unavailable
                    jQuery(document).ready(function () {
                        openModalAlert('Thông báo', checkProvince.message, {scene : 'info', btnOkText: 'Đóng'})
                    });
                }
                hideLoadingPage();
            }
        }); // end ajax
    }
</script>