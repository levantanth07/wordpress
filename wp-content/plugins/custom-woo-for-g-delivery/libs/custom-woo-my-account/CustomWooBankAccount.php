<?php
class CustomWooBankAccount {

    // ------------------
    // 1. Register new endpoint to use for My Account page
    // Note: Resave Permalinks or it will give 404 error
    public function bankAccountAddEndpoint() {
        add_rewrite_endpoint( 'bank-account', EP_ROOT | EP_PAGES );
    }
    
    // ------------------
    // 3. Insert the new endpoint into the My Account menu
    public function bankAccountLinkMyAccount( $items ) {
        $items['bank-account'] = 'TK Ngân Hàng';
        return $items;
    }

    // ------------------
    // 4. Add content to the new endpoint
    public function bankAccountContentPage()
    {
        $user = wp_get_current_user();
        $bankAccount = get_user_meta($user->ID, 'bank-account', true);

        $arrBanks = [
           '970454' => 'Ngân hàng TMCP Bản Việt',
           '970452' => 'Ngân hàng TMCP Kiên Long',
           '970400' => 'Ngân hàng TMCP Sài Gòn Công Thương',
           '970430' => 'Ngân hàng TMCP Xăng Dầu Petrolimex',
           '970455' => 'Ngân hàng Công nghiệp Hàn Quốc',
           '970421' => 'Ngân hàng Liên Doanh Việt Nga',
           '970405' => 'Ngân hàng Nông Nghiệp và Phát triển Nông Thôn Việt Nam',
           '970408' => 'Ngân hàng TM TNHH MTV Dầu Khí Toàn Cầu',
           '970416' => 'Ngân hàng TMCP Á Châu',
           '970425' => 'Ngân hàng TMCP An Bình',
           '970409' => 'Ngân hàng TMCP Bắc Á',
           '970438' => 'Ngân hàng TMCP Bảo Việt',
           '970449' => 'Ngân hàng TMCP Bưu Điện Liên Việt',
           '970415' => 'Ngân hàng TMCP Công Thương Việt Nam',
           '970412' => 'Ngân hàng TMCP Đại Chúng Việt Nam',
           '970414' => 'Ngân hàng TMCP Đại Dương',
           '970418' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam',
           '970406' => 'Ngân hàng TMCP Đông Á',
           '970440' => 'Ngân hàng TMCP Đông Nam Á',
           '970426' => 'Ngân hàng TMCP Hàng Hải Việt Nam',
           '970407' => 'Ngân hàng TMCP Kỹ thương Việt Nam',
           '970428' => 'Ngân hàng TMCP Nam Á',
           '970437' => 'Ngân hàng TMCP Phát Triển Thành Phố Hồ Chí Minh',
           '970448' => 'Ngân hàng TMCP Phương Đông',
           '970422' => 'Ngân hàng TMCP Quân Đội',
           '970419' => 'Ngân hàng TMCP Quốc Dân',
           '970441' => 'Ngân hàng TMCP Quốc Tế',
           '970429' => 'Ngân hàng TMCP Sài Gòn',
           '970443' => 'Ngân hàng TMCP Sài Gòn - Hà Nội',
           '970403' => 'Ngân hàng TMCP Sài Gòn Thương Tín',
           '970423' => 'Ngân hàng TMCP Tiên Phong',
           '970427' => 'Ngân hàng TMCP Việt Á',
           '970432' => 'Ngân hàng TMCP Việt Nam Thịnh Vương',
           '970433' => 'Ngân hàng TMCP Việt Nam Thương Tín',
           '970431' => 'Ngân hàng TMCP Xuất nhập khẩu Việt Nam',
           '970434' => 'Ngân hàng TNHH Indovina',
           '422589' => 'Ngân hàng TNHH MTV CIMB Việt Nam',
           '970442' => 'Ngân hàng TNHH MTV Hongleong Việt Nam',
           '970439' => 'Ngân hàng TNHH MTV Public Việt Nam',
           '970424' => 'Ngân hàng TNHH MTV Shinhan Việt Nam',
           '970458' => 'Ngân hàng TNHH MTV United Overseas Bank',
           '970457' => 'Ngân hàng Wooribank',
        ];

        if (isset($_SESSION['save_account_details'])) {
            $message = $_SESSION['save_account_details'];
        } else {
            $message = '';
        }

        if ($bankAccount) {
            $bankId = $bankAccount['bankId'];
            $accountName = $bankAccount['accountName'];
            $accountNumber = $bankAccount['accountNumber'];
            $accountBranch = $bankAccount['accountBranch'];
        } else {
            $bankId = 0;
            $accountName = '';
            $accountNumber = '';
            $accountBranch = '';
        }

        ?>
        <div class="woocommerce-MyAccount-content">
            <div class="woocommerce-notices-wrapper"><span style="color: green;"><em><?=$message?></em></span></div>

            <form class="woocommerce-EditAccountForm edit-account" action="" method="post">
                <h3>Tài khoản ngân hàng</h3>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="MaNganHangHuong">Ngân hàng</label>
                    <select name="bank_id" id="MaNganHangHuong">
                        <option value="0">Chọn tên ngân hàng</option>
                        <?php
                            foreach ($arrBanks as $id => $name) {
                                if ($id == $bankId) {
                                    echo '<option value="'.$id.'" selected>'.$name.'</option>';
                                } else {
                                    echo '<option value="'.$id.'">'.$name.'</option>';
                                }
                            }
                        ?>
                    </select>
                </p>
                <div class="clear"></div>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="account_name">Họ tên<span class="required">*</span></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_name" id="account_name" value="<?=$accountName?>" placeholder="">
                    <span><em>Tên chủ tài khoản</em></span>
                </p>
                <div class="clear"></div>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="account_number">Số tài khoản</label>
                    <input type="text" class="woocommerce-Input input-text" name="account_number" id="account_number" value="<?=$accountNumber?>">
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="account_email">Chi nhánh</label>
                    <input type="text" class="woocommerce-Input input-text" name="account_branch" id="account_branch"  value="<?=$accountBranch?>">
                </p>

                <p>
                    <input type="hidden" name="action" value="bank-account-form" />
                    <button type="submit" class="woocommerce-Button button" name="save_account_details" value="Lưu thay đổi">&nbsp;&nbsp; Lưu &nbsp;&nbsp;</button>
                </p>

            </form>
        </div>
<?php
    }

    public function saveBankAccountInfo()
    {
        if (is_user_logged_in() && isset($_POST['action']) && $_POST['action'] == 'bank-account-form') {
            $bankAccount = [];
            $bankAccount['bankId'] = $_POST['bank_id'];
            $bankAccount['accountName'] = $_POST['account_name'];
            $bankAccount['accountNumber'] = $_POST['account_number'];
            $bankAccount['accountBranch'] = $_POST['account_branch'];

            $user = wp_get_current_user();
            $update = update_user_meta($user->ID, 'bank-account', $bankAccount);
            
            if ($update) {
                $_SESSION['save_account_details'] = 'Đã lưu thông tin tài khoản ngân hàng';
            } else {
                $_SESSION['save_account_details'] = 'Đã lưu thông tin tài khoản ngân hàng'; // @todo mịa nó
            }
        }
    }
    
    public function __construct()
    {
        // define and add endpoint for bank account page
        add_action( 'init', [$this, 'bankAccountAddEndpoint']);

        add_filter( 'woocommerce_account_menu_items', [$this, 'bankAccountLinkMyAccount'] );

        // add end point
        add_action( 'woocommerce_account_bank-account_endpoint', [$this, 'bankAccountContentPage']);
        // Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format

        // save bank account info
        add_action('wp', [$this, 'saveBankAccountInfo']);

    }
}