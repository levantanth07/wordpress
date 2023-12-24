<?php

add_filter( 'manage_edit-product_columns', 'customProductColumn',11);
function customProductColumn($columns)
{
    //add columns
    $columns['rkCode'] = 'RK Code';
    $columns['merchant'] = 'Merchant';

    $fields = ['cb', 'thumb', 'name', 'price', 'taxonomy-ecom-category', 'taxonomy-merchant-category', 'merchant', 'date', 'rkCode'];
    $reSortedColumns = [];
    foreach($fields as $column) {
        $reSortedColumns[$column] = $columns[$column];
    }
    return $reSortedColumns;
}

add_action( 'manage_product_posts_custom_column' , 'customProductColumnContent', 10, 2 );
function customProductColumnContent( $column, $product_id )
{
    // HERE get the data from your custom field (set the correct meta key below)
    switch ( $column )
    {
        case 'rkCode' :
            $rkCode = get_field('product_rk_code', $product_id );
            echo $rkCode;
            break;
        case 'merchant' :
            $merchant = get_field('merchant_id', $product_id );
            echo $merchant->post_title ?? '';
            break;
        default:
    }
}

add_filter( 'posts_search', 'searchByCustomFieldForAdmin', 999, 2 );
/**
 * @param $search
 * @param WP_Query $wp_query
 *
 * @return mixed
 */
function searchByCustomFieldForAdmin( $search, $wp_query )
{
    $s = $_REQUEST['s'] ?? '';

    global $wpdb, $pagenow;
    $post_type = 'product';
    $custom_fields = [
        'product_sap_code',
        'product_rk_code',
    ];

    if ( 'edit.php' != $pagenow || !is_admin() || $wp_query->query['post_type'] != $post_type ) {
        return $search;
    }

    $get_post_ids = [];
    foreach ($custom_fields as $custom_field_name) {
        $args = [
            'posts_per_page'  => -1,
            'post_type' => $post_type,
            'meta_query' => [
                [
                    'key' => $custom_field_name,
                    'value' => $s,
                    'compare' => '='
                ]
            ]
        ];
        $posts = get_posts( $args );

        if(!empty($posts)){
            foreach($posts as $post){
                $get_post_ids[] = $post->ID;
            }
        }
    }

    if ($get_post_ids) {
        $inId = '';
        foreach ($get_post_ids as $one) {
            $inId .= $one.',';
        }
        $inId = rtrim($inId, ',');
        $search .= " OR wp_posts.ID IN ({$inId}) ";
    }

    return $search;
}
