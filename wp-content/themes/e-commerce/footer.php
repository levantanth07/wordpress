<footer>
    <div class="container">
        <div class="row">

            <div class="col-lg-3 col-sm-6">
                <h3>Về GDelivery</h3>
                <ul>
                    <li><a href="<?=site_url('thong-tin-xuat-xu-san-pham')?>" title="Thông tin xuất xứ sản phẩm">Thông tin xuất xứ sản phẩm</a></li>
                    <li><a href="<?=site_url('phuong-thuc-dat-hang-va-giao-hang')?>" title="Phương thức đặt hàng và giao hàng">Phương thức đặt hàng và giao hàng</a></li>
                    <li><a href="<?=site_url('chinh-sach-doi-tra-san-pham')?>" title="Chính sách đổi trả sản phẩm">Chính sách đổi trả sản phẩm</a></li>
                    <li><a href="<?=site_url('quy-trinh-khieu-nai')?>" title="Quy trình khiếu nại">Quy trình khiếu nại</a></li>
                    <li><a href="<?=site_url('chinh-sach-bao-mat-thong-tin')?>" title="Quy trình khiếu nại">Chính sách bảo mật thông tin</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-sm-6">
                <h3>Phương thức thanh toán</h3>
                <ul class="wrap-list-payment">
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/images/zalopay.svg" /></a></li>
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/images/vnpay.svg" /></a></li>
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/images/cash.svg" /></a></li>
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/images/momo.svg" /></a></li>
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/images/atm.svg" /></a></li>
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/images/visa.svg" /></a></li>
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/images/shopeepay.svg" /></a></li>
                </ul>

            </div>
            <div class="col-lg-3 col-sm-6">
                <h3>Truyền thông xã hội</h3>
                <ul class="list-socail-network">
                    <li><a href="#"><i class="icon-facebook"></i>Facebook</a></li>
                    <li><a href="#"><i class="icon-youtube"></i>Youtube</a></li>
                    <li><a href="http://taiapp.thegoldenspoon.com.vn"><img src="<?=bloginfo('template_url')?>/assets/images/tgs.svg"></i>The golden spoon</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-sm-6">
                <h3>Hỗ trợ khách hàng</h3>
                <ul>
                    <li>
                        Hot line:
                        <a href="tel:19006622" title="Gọi mua hàng">1900 6622</a> <br />
                    </li>
                    <li class="d-flex">
                        Mua hàng:
                        <table class="ml-1">
                            <tr>
                                <td><a href="tel:0962471230" title="Gọi mua hàng">0962 471 230 (MB)</a></td>
                            </tr>
                            <tr>
                                <td><a href="tel:0903932493" title="Gọi mua hàng">0903 932 493 (MN)</a></td>
                            </tr>
                        </table>
                    </li>
                    <li>Khiếu nại: <a href="tel:02473003077" title="Khiếu nại">0247 300 3077</a></li>
                    <li>Email: cskh.gdelivery@ggg.com.vn</li>
                </ul>

            </div>
        </div>
    </div>
    <div class="sub-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-12 address">
                    <h5>Công ty Cổ phần Thương mại Dịch vụ Cổng Vàng</h5>
                    <p>Trụ sở chính: Số 60 Giang Văn Minh, phường Đội Cấn, Quận Ba Đình, Thành phố Hà Nội, Việt Nam</p>
                    <p>GPĐK: Số 0103.023.679 cấp ngày 09/04/2008 cấp ngày 09/04/2008</p>
                    <p>ĐT: 0247.300.3077 Email: cskh.gdelivery@ggg.com.vn</p>
                    <!-- <a href="#"><i class="icon-arrow-up"></i></a> -->
                </div>
                <div class="col-md-4 col-12">
                    <div class="wrap-register">
                        <a href="http://online.gov.vn/Home/WebDetails/65427" title="Đã đăng ký bộ công thương">
                            <img src="<?=bloginfo('template_url')?>/assets/images/register.png" alt="Đã đăng ký bộ công thương">
                        </a>
                        <p>&copy;2011 Golden Gate ., JSC. All rights reserved</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>


<!-- hotline -->
<!--<div class="wrap-hotline">
    <span>Hotline</span><p>1900 6622</p>
    <div class="wrap-ico"><i class="icon-phone-call"></i></div>
</div>-->


<!-- SMOOTH SCROLL -->
<script>
    var scrollY = 0;
    var distance = 13;
    var speed = 1;

    function autoScrollTo(el) {
        var currentY = window.pageYOffset;
        var targetY = document.getElementById(el).offsetTop;
        var bodyHeight = document.body.offsetHeight;
        var yPos = currentY + window.innerHeight;
        var animator = setTimeout('autoScrollTo(\'' + el + '\')', speed);
        if (yPos > bodyHeight) {
            clearTimeout(animator);
        } else {
            if (currentY < targetY - distance) {
                scrollY = currentY + distance;
                window.scroll(0, scrollY);
            } else {
                clearTimeout(animator);
            }
        }
    }

    function resetScroller(el) {
        var currentY = window.pageYOffset;
        var targetY = document.getElementById(el).offsetTop;
        var animator = setTimeout('resetScroller(\'' + el + '\')', speed);
        if (currentY > targetY) {
            scrollY = currentY - distance;
            window.scroll(0, scrollY);
        } else {
            clearTimeout(animator);
        }
    }
</script>
<!-- End of SMOOTH SCROLL -->

<script src="<?=bloginfo('template_url')?>/assets/js/bootstrap-datepicker.min.js" type="text/javascript"></script>
<script src="<?=bloginfo('template_url')?>/assets/js/bootstrap-datepicker.vi.min.js" type="text/javascript"></script>


<?php get_template_part('content/content', 'modal-login'); ?>

<?php get_template_part('content/content', 'modal-alert'); ?>

<?php get_template_part('content/popup', 'banner'); ?>

<?php wp_footer();?>

</body>
</html>

