<footer>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h3>Về GDelivery</h3>
                <ul>
                    <li><a href="https://gdelivery.vn/thong-tin-xuat-xu-san-pham" title="Thông tin xuất xứ sản phẩm">Thông tin xuất xứ sản phẩm</a></li>
                    <li><a href="https://gdelivery.vn/phuong-thuc-dat-hang-va-giao-hang" title="Phương thức đặt hàng và giao hàng">Phương thức đặt hàng và giao hàng</a></li>
                    <li><a href="https://gdelivery.vn/chinh-sach-doi-tra-san-pham" title="Chính sách đổi trả sản phẩm">Chính sách đổi trả sản phẩm</a></li>
                    <li><a href="https://gdelivery.vn/quy-trinh-khieu-nai" title="Quy trình khiếu nại">Quy trình khiếu nại</a></li>
                    <li><a href="https://gdelivery.vn/chinh-sach-bao-mat-thong-tin" title="Quy trình khiếu nại">Chính sách bảo mật thông tin</a></li>
                </ul>
            </div>
            <div class="col-12">
                <h3>Phương thức thanh toán</h3>
                <ul class="wrap-list-payment">
                    <li><a href="#"><img src="https://gdelivery.vn/wp-content/themes/gdelivery-v2/assets/images/zalopay.svg"></a></li>
                    <li><a href="#"><img src="https://gdelivery.vn/wp-content/themes/gdelivery-v2/assets/images/vnpay.svg"></a></li>
                    <li><a href="#"><img src="https://gdelivery.vn/wp-content/themes/gdelivery-v2/assets/images/cash.svg"></a></li>
                    <li><a href="#"><img src="https://gdelivery.vn/wp-content/themes/gdelivery-v2/assets/images/momo.svg"></a></li>
                    <li><a href="#"><img src="https://gdelivery.vn/wp-content/themes/gdelivery-v2/assets/images/atm.svg"></a></li>
                    <li><a href="#"><img src="https://gdelivery.vn/wp-content/themes/gdelivery-v2/assets/images/visa.svg"></a></li>
                    <li><a href="#"><img src="https://gdelivery.vn/wp-content/themes/gdelivery-v2/assets/images/shopeepay.svg"></a></li>
                </ul>
            </div>
            <div class="col-12">
                <h3>Truyền thông xã hội</h3>
                <ul class="list-socail-network">
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/v2.3/mobile/images/facebook-logo.png"/>Facebook</a></li>
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/v2.3/mobile/images/youtube.png"/>Youtube</a></li>
                    <li><a href="#"><img src="<?=bloginfo('template_url')?>/assets/v2.3/mobile/images/tgs.png"/>The golden spoon</a></li>
                </ul>
            </div>
            <div class="col-12">
                <h3>Hỗ trợ khách hàng</h3>
                <ul>
                    <li class="d-flex">
                        <b>Hot line:</b>
                        <table class="ml-1">
                            <tbody><tr>
                                <td><a href="tel:0962471230" title="Gọi mua hàng">0962-471-230 (Miền Bắc)</a></td>
                            </tr>
                            <tr>
                                <td><a href="tel:0903932493" title="Gọi mua hàng">0903-932-493 (Miền Nam)</a></td>
                            </tr>
                            </tbody></table>
                    </li>
                    <li>
                        <b>Mua hàng:</b>
                        <a href="tel:19006622" title="Gọi mua hàng">0247-3003-007</a> <br>
                    </li>
                    <li><b>Khiếu nại:</b> <a href="tel:02473003077" title="Khiếu nại">0247 300 3077</a></li>
                    <li><b>Email:</b> cskh.gdelivery@ggg.com.vn</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="sub-footer">
        <div class="container">
            <div class="row">
                <div class="col-12 address">
                    <h5>Công ty Cổ phần Thương mại Dịch vụ Cổng Vàng</h5>
                    <p>Trụ sở chính: Số 60 Giang Văn Minh, phường Đội Cấn, Quận Ba Đình, Thành phố Hà Nội, Việt Nam</p>
                    <p>GPĐK: Số 0103.023.679 cấp ngày 09/04/2008 cấp ngày 09/04/2008</p>
                    <p>ĐT: 0247.300.3077 Email: cskh.gdelivery@ggg.com.vn</p>
                </div>
                <div class="col-12">
                    <div class="wrap-register">
                        <a href="http://online.gov.vn/Home/WebDetails/65427" title="Đã đăng ký bộ công thương">
                            <img src="https://gdelivery.vn/wp-content/themes/gdelivery-v2/assets/images/register.png" alt="Đã đăng ký bộ công thương">
                        </a>
                        <p>©2011 Golden Gate ., JSC. All rights reserved</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="<?=bloginfo('template_url')?>/assets/js/bootstrap-datepicker.min.js" type="text/javascript"></script>
<script src="<?=bloginfo('template_url')?>/assets/js/bootstrap-datepicker.vi.min.js" type="text/javascript"></script>


<?php get_template_part('content/content', 'modal-login'); ?>

<?php get_template_part('content/content', 'modal-alert'); ?>

<?php get_template_part('content/popup', 'banner'); ?>

<?php get_template_part('content/v2.3/mobile/partials/loading', 'page');?>

<!-- Start common helper js -->
<?php get_template_part('content/js', 'common-helper'); ?>
<!-- End common helper js -->

<?php wp_footer();?>

</body>
</html>