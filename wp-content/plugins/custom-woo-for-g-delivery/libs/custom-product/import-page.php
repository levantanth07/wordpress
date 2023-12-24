<?php
add_action( 'admin_menu', 'registerImportProductMenu' );
function registerImportProductMenu() {
    add_options_page(
        'Import Sản phẩm',
        'Import Sản phẩm',
        'manage_options',
        'import-product',
        'importProductPage'
    );
}

function importProductPage()
{
    $listCanteen = get_terms('product_cat');
    if ($_POST) {
        $file = $_FILES['file'];

        $fileName = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if ($fileName == 'csv') {
            // read file, and import into database queue
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');
            $reader->setSheetIndex(0);

            try {
                // load csv file
                $spreadsheet = $reader->load($file['tmp_name']);

                // parse to array
                $arr = $spreadsheet->getActiveSheet()->toArray();

                ?>
                <h3>Kết quả</h3>

                <table class="result">
                    <tr>
                        <th>#</th>
                        <th>Canteen</th>
                        <th>Nhóm món</th>
                        <th>Mã SAP</th>
                        <th>Mã RK</th>
                        <th>Tên món</th>
                        <th>Giá (chưa VAT)</th>
                        <th>Trạng thái</th>
                    </tr>
                    <?php
                    echo '<pre>';
                    foreach ($arr as $key => $oneRow) {
                        if ($key == 0 ) {
                            continue; // step out header
                        }
                        $productCat = $_POST['canteen'];
                        $productGroup = $oneRow[0];
                        $slugProductGroup = sanitize_title($productGroup);
                        $sapCode = $oneRow[1];
                        $rkCode = $oneRow[2];
                        $productName = $oneRow[3];
                        $productPrice = (float) $oneRow[4];

                        // check product first
                        if (is_int($sapCode) && is_int($rkCode)) {
                            $argsCheck = [
                                'meta_query' => [
                                    [
                                        'key'     => 'product_sap_code',
                                        'value'   => $sapCode,
                                        'compare' => '='
                                    ],
                                    [
                                        'key'     => 'product_rk_code',
                                        'value'   => $rkCode,
                                        'compare' => '='
                                    ],
                                ],
                                'post_type' => 'product',
                                'tax_query' => [
                                    [
                                        'taxonomy' => 'product_cat',
                                        'field'    => 'slug',
                                        'terms'    => $productCat,
                                    ],
                                ]

                            ];

                            $checkProduct = new WP_Query($argsCheck);

                            if (!$checkProduct->have_posts()) {
                                $post = array(
                                    'post_author' => get_current_user_id(),
                                    'post_content' => '',
                                    'post_status' => "draft",
                                    'post_title' => $productName,
                                    'post_type' => "product",
                                );

                                //Create product
                                $post_id = wp_insert_post( $post );
                                if($post_id){

                                    wp_set_object_terms( $post_id, $productCat, 'product_cat' );
                                    wp_set_object_terms( $post_id, $slugProductGroup, 'product_group');

                                    update_post_meta( $post_id, '_price', $productPrice );
                                    update_post_meta( $post_id, '_regular_price', $productPrice );

                                    update_post_meta( $post_id, 'product_sap_code', $sapCode );
                                    update_post_meta( $post_id, 'product_rk_code', $rkCode );

                                    $status = 'Import thành công';
                                } else {
                                    $status = 'Lỗi khi tạo sản phẩm mới';
                                }
                            } else {
                                // update
                                $postId = $checkProduct->posts[0]->ID;

                                wp_set_object_terms( $postId, $productCat, 'product_cat' );
                                wp_set_object_terms( $postId, $slugProductGroup, 'product_group');

                                wp_update_post(array(
                                    'ID' => $postId,
                                    'post_title' => $productName,
                                ));
                                update_post_meta( $postId, '_price', $productPrice );
                                update_post_meta( $postId, '_regular_price', $productPrice );

                                $status = 'Đã update sản phẩm';
                            }
                            wp_reset_postdata();
                        } else {
                            $status = 'Mã RK7 và SAP ko hợp lệ';
                        }

                        // result
                        ?>
                        <tr>
                            <td><?=$key?></td>
                            <td><?=$productCat?></td>
                            <td><?=$productGroup?></td>
                            <td><?=$sapCode?></td>
                            <td><?=$rkCode?></td>
                            <td><?=$productName?></td>
                            <td><?=$productPrice?></td>
                            <td><?=$status?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <a href="<?= admin_url('options-general.php?page=import-product') ?>">
                    <button>Tiếp tục import</button>
                </a>
                <?php
            } catch (\Exception $e) {
                echo 'Lỗi khi xử lý file: '.$e->getMessage();
            }
        } else {
            echo 'Cần update file CSV';
        }
    } else {
        ?>
        <h1>Import Product</h1>

        <form action="" method="post" enctype="multipart/form-data">
            <table>
                <tr>
                    <td>
                        Canteen
                    </td>
                    <td>
                        <select name="canteen">
                            <?php foreach ($listCanteen as $value): ?>
                            <option value="<?=$value->slug?>"><?=$value->name?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td>
                        File món ăn
                    </td>
                    <td>
                        <input type="file" name="file" />
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <button type="submit">Import món</button>
                    </td>
                </tr>
            </table>
        </form>

        <style type="text/css">
            .result {
                margin-bottom: 6px;
            }
            .result th, .result td {
                border: 1px #ddd solid;
            }
        </style>

        <?php
    }
}