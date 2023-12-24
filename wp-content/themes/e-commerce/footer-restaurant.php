<?php
    $currentUser = wp_get_current_user();
    $user = Permission::checkCurrentUserRole($currentUser);
?>
<footer class="footer">
    <div class="container">
        <?php if (
                $user->role == 'operator'
                || $user->role == 'administrator'
                || $user->role == 'am'
                || $user->role == 'acc'
        ) :?>
            <a href="<?=site_url('operator-list-orders')?>">Quản lý vận đơn |</a>
            <a href="<?=site_url('restaurant-order-report')?>">Báo cáo bán hàng</a>
        <?php endif; ?>
        <?php if ($user->role == 'restaurant') : ?>
            <a href="<?=site_url('restaurant-list-orders')?>">Quản lý đơn hàng |</a>
        <?php endif; ?>
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
    function updateOrderStatus(id, status, note, restaurant, extraData = {})
    {
        jQuery.ajax({
            'type' : 'post',
            'url' : '<?=site_url('ajax-update-order-status')?>',
            'dataType' : 'json',
            'data' : {
                'id' : id,
                'status' : status,
                'note' : note,
                'restaurant' : restaurant,
                'extraData' : extraData
            },
            'success' : function (res) {
                if (res.messageCode == 1) {
                    //alert(res.message);
                    window.location.reload();
                } else {
                    alert(res.message);
                    if (res.messageCode == 0) {
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
