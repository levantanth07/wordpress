<?php

////////////////////////////////////////////////////////////////////////////////
///   add custom field to user  ///////////////
/// ///////////////////////////////////////////////////////////////////////////
add_action( 'show_user_profile', 'showUserCustomFields' );
add_action( 'edit_user_profile', 'showUserCustomFields' );
function showUserCustomFields( $user ) {
    $args = [
        'post_type' => 'merchant',
        'posts_per_page'=> -1,
        'post_status' => 'publish'
    ];
    $loop = new \WP_Query($args);
    $merchantIdMeta = get_user_meta($user->ID, 'user_operator_merchant_id', true);
    if ($merchantIdMeta && is_array($merchantIdMeta)) {
        $merchantIds = $merchantIdMeta;
    } else {
        $merchantIds =  [];
    }

    ?>
        <h3>Cấu hình merchant cho vận đơn</h3>
        <table class="form-table">
            <tr>
                <th><label for="store_region">Phân quyền</label></th>
                <td>
                    <?php foreach ($loop->posts as $onePost) :?>
                        <label for="merchantId-<?=$onePost->ID?>">
                            <input
                                    id="merchantId-<?=$onePost->ID?>"
                                    type="checkbox"
                                    name="user_operator_merchant_id[]"
                                    value="<?=$onePost->ID?>"
                                    class="regular-text"
                                <?php if(in_array("{$onePost->ID}", $merchantIds)) echo 'checked';?>
                            />
                            <?=$onePost->post_title?>
                        </label>
                        <br />
                    <?php
                    endforeach; //end foreach brands
                    ?>
                    <br />
                </td>
            </tr>
        </table>
    <?php

    // restaurant
    $bookingService = new \GDelivery\Libs\BookingService();
    $getRestaurants = $bookingService->getRestaurants();
    if ($getRestaurants->messageCode == \Abstraction\Object\Message::SUCCESS) {
        $currentUserRestaurant = get_user_meta($user->ID, 'user_restaurant', true);
        ?>
        <h3>Nhà hàng</h3>

        <table class="form-table">
            <tr>
                <th><label for="select-restaurant">Nhà hàng</label></th>
                <td>
                    <select name="user_restaurant" id="select-restaurant">
                        <option value="0">Chọn nhà hàng</option>
                        <?php foreach ($getRestaurants->result as $restaurant) { ?>
                            <option <?=($currentUserRestaurant == $restaurant->code ? 'selected' : '')?> value="<?=$restaurant->code?>">(<?=$restaurant->code?> - <?=$restaurant->regionName?>) <?=$restaurant->name?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
        </table>
        <script type="text/javascript">
            jQuery('#select-restaurant').select2({
                placeholder: 'Chọn nhà hàng',
                language: {
                    noResults: function (params) {
                        return "Không tìm thấy nhà hàng";
                    }
                }
            })
        </script>
        <?php
    }
}

add_action( 'personal_options_update', 'updateUserCustomFields' );
add_action( 'edit_user_profile_update', 'updateUserCustomFields' );

function updateUserCustomFields( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }

    if ( ! empty( $_POST['user_operator_category'] )) {
        $provinceBrand = $_POST['user_operator_category'];

        $arrOperatorRights = [];
        foreach ($provinceBrand as $one) {
            $temp = explode('_', $one);

            if ($temp) {
                if (array_key_exists($temp[0], $arrOperatorRights)) {
                    $arrOperatorRights[$temp[0]][] = $temp[1];
                } else {
                    $arrOperatorRights[$temp[0]] = [$temp[1]];
                }
            }
        }

        update_user_meta( $user_id, 'user_operator_category', $provinceBrand );
        update_user_meta( $user_id, 'user_operator_rights', $arrOperatorRights );
    } else {
        update_user_meta( $user_id, 'user_operator_category', [] );
        update_user_meta( $user_id, 'user_operator_rights', [] );
    }

    if ( ! empty( $_POST['user_restaurant'] )) {
        $restaurant = $_POST['user_restaurant'];

        update_user_meta( $user_id, 'user_restaurant', $restaurant );
    } else {
        update_user_meta( $user_id, 'user_restaurant', 0 );
    }

    if ( ! empty( $_POST['user_operator_merchant_id'] )) {
        $merchantIds = $_POST['user_operator_merchant_id'];

        $codes = [];
        foreach ($merchantIds as $id) {
            $codes[] = get_field('restaurant_code', $id);
        }

        update_user_meta( $user_id, 'user_operator_merchant_id', $merchantIds );
        update_user_meta( $user_id, 'user_operator_restaurant_code', $codes );
    } else {
        update_user_meta( $user_id, 'user_operator_merchant_id', [] );
        update_user_meta( $user_id, 'user_operator_restaurant_code', [] );
    }
}
/////////////////////////////////////////////////////////////////////////////////
/// end add user custom field
/// ////////////////////////////////////////////////////////////////////////////