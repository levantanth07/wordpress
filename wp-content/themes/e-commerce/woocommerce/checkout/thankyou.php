<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;
?>


<div id="payment" class="ui container">
    <div style="margin: 15px 0; margin-bottom: 30px;">
        <form id="frmThanhtoan" class="ui form" autocomplete="off">
            <div class="ui inverted dimmer main">
                <div class="ui loader"></div>
            </div>
            <div class="ui stackable grid main-content">
                <div class="ten wide column">
                    <div id="sidebar-left">
                        <div class="menu-category">
                            <h3>Thông tin đặt hàng<br/>Mã đơn hàng: #KK160147</h3>
                            <div class="content">
<div class="woocommerce-order">

	<?php if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

				<li class="woocommerce-order-overview__order order">
					<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>

			</ul>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		

	<?php else : ?>

		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

	<?php endif; ?>

</div>
                            </div>
                        </div>
                    
					
					</div>
                </div>
                <div id="cart-column" class="six wide column">
                    <div id="sidebar-right">
                        <div class="cart-right">
<div class="cart-list">

	<?php


$order = wc_get_order( $order->get_id() );
$items = $order->get_items();
foreach ( $items as $cart_item ) {

	?>

          
    <div class="item" style="cursor: default; background: #fff !important;">
        <div class="ui inverted dimmer">
            <div class="ui loader"></div>
        </div>
        <div class="ui grid">
            <div class="two wide column left aligned"><span class="qty"><?php echo $cart_item['quantity'];?></span></div>
            <div class="eight wide column left aligned">
                <div><?php echo get_the_title($cart_item['product_id']); ?>
                    <div><?php the_field('unit',$cart_item['product_id']); ?></div>
                </div>
            </div>
            <div class="six wide column left aligned" style="text-align: right; padding: 5px 1em;">
                <div><?php echo ptype($cart_item['quantity']*get_field('_price',$cart_item['product_id'])); ?>₫</div>
                <div>
		   </div>
        </div>
    </div>
	<div class="ui divider"></div>
    <?php } ?>
	<input type="hidden" id="payment_brand" name="brand" value="5">
	
</div>



                            <div class="submit-button">
                                <a id="cancelOrder" href="javascript:;" rel="nofollow" class="ui fluid button large red" data-id="<?php echo $order->get_id();?>" >HUỶ ĐƠN HÀNG</a>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="regionFrm" class="ui tiny longer modal">
    <div class="ui inverted dimmer">
        <div class="ui text loader">&nbsp;</div>
    </div>
    <form id="frmRegion" class="ui form" autocomplete="off">
        <div style="text-align: right;"><i class="close icon"></i></div>
        <h3 style="font-size: 16px; height: 48px;">Khu vực giao hàng</h3>
        <div class="content" style="padding: 0;">
            <div class="region-header">
                <span id="seleted-city" class="region-header-text active">Tỉnh/Thành</span>
                <span id="seleted-district" class="region-header-text">Quận/Huyện</span>
                <span id="seleted-ward" class="region-header-text">Phường/Xã</span>
            </div>
            <div class="region-body-search">
                <div class="ui huge fluid icon input">
                    <i class="search left icon"></i>
                    <input id="searchRegion" placeholder="Tìm kiếm" type="text" autocomplete="off" class="uk-input">
                </div>
            </div>
            <div class="region-body-item-list">
                <div id="list-city">
                                    </div>
                <div id="list-district"></div>
                <div id="list-ward"></div>
            </div>
        </div>
    </form>
</div> 


