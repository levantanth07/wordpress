<?php
    $currentUser = wp_get_current_user();
    $user = Permission::checkCurrentUserRole($currentUser);
?>
<footer class="footer">
    <div class="container">
    </div>
</footer>
<script src="<?=bloginfo('template_url')?>/assets/js/bootstrap-datepicker.min.js" type="text/javascript"></script>
<script src="<?=bloginfo('template_url')?>/assets/js/bootstrap-datepicker.vi.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(function () {
        $('.tool-tip').popover()
    });

    jQuery('.datetime-picker').datepicker({
        format: "yyyy-mm-dd",
        weekStart: 1,
        maxViewMode: 2,
        todayBtn: true,
        language: "vi"
    });

</script>

<script type="text/javascript">
    function updateOrderStatus(id, status, note, restaurant)
    {
        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=site_url('ajax-update-order-status')?>',
            'dataType' : 'json',
            'data' : {
                'id' : id,
                'status' : status,
                'note' : note,
                'restaurant' : restaurant
            },
            'success' : function (res) {
                if (res.messageCode == 1) {
                    //alert(res.message);
                    window.location.reload();
                } else {
                    alert(res.message);
                    if (res.messageCode == 2) {
                        window.location.reload();
                    }
                }
            },
            'error' : function (x, y, z) {

            }
        }); // end ajax

        return false;
    }
</script>
</body>
</html>
