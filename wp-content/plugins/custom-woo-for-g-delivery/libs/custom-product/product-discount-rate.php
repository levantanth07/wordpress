<?php

    $startTime = microtime(true);
    WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Bắt đầu xử lý đồng bộ \n");

    $page = 1;
    $isNext = true;

    while ($isNext) {
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'paged' => $page,
            'orderby' => 'ID',
            'order' => 'DESC'
        ];
        WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - WP query page {$page} \n");
        $query = new \WP_Query($args);
        foreach ($query->posts as $post) {

            WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Update Post ID {$post->ID} \n");

            $product = wc_get_product($post);
            $regular = $product->get_regular_price() ? (float) $product->get_regular_price() : 0;
            $sale = $product->get_sale_price() ? (float) $product->get_sale_price() : 0;
            $discount = $rate = 0;
            if ($sale > 0 && $regular > 0) {
                $discount = $regular - $sale;
                $rate = ceil(($discount / $regular) * 100);
            }
            update_post_meta( $post->ID, '_discount_amount', $discount);
            update_post_meta( $post->ID, '_discount_rate', $rate);
        }

        if (count($query->posts) > 0) {
            WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Total ".count($query->posts)." \n");
            $page++;
        } else {
            WP_CLI::log(date_i18n('Y-m-d H:i:s') . " - Kết thúc {$page} \n");
            $isNext = false;
        }
        wp_reset_query();
    }

