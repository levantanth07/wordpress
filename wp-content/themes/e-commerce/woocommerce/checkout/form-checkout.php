<div id="payment" class="ui container">
    <div style="margin: 15px 0; margin-bottom: 30px;">
        <form id="frmThanhtoan" class="ui form" autocomplete="off">
            <input type="hidden" id="payment_utm_source" name="utm_source" value="">
            <input type="hidden" id="payment_utm_medium" name="utm_medium" value="">
            <div class="ui inverted dimmer main">
                <div class="ui loader"></div>
            </div>
                <div class="ten wide column">
                    <div id="sidebar-left">
                        <div class="menu-category">
                            <h3>Xác nhận thông tin đặt hàng</h3>
                            <div class="content">
                                <div class="field">
                                    <div id="map">
                                        <div class="ui inverted dimmer main">
                                            <div class="ui loader"></div>
                                        </div>
                                        <div id="map_canvas" style="height: 100%; width: 100%;">&nbsp;</div>
                                    </div>
                                </div>
                                                                    <div class="field">
                                        <label style="display: block; margin-bottom: 3px;">Khu vực giao hàng:</label>
                                        <div class="three fields">
                                            <div class="field">
                                                <div class="ui compact fluid menu region-city">
                                                    <div class="ui item" style="width: 100%;">
                                                        <span class="text" style="width: 100%;">Tỉnh/Thành</span>
                                                        <i class="dropdown icon"></i>
                                                    </div>
                                                    <input type="hidden" id="payment_city" name="city" autocomplete="new-city" value="">
                                                    <div class="errordiv payment_city" style="top: 44px;"><div class="arrow"></div>Vui lòng nhập tỉnh/thành phố</div>
                                                </div>
                                            </div>
                                            <div class="field">
                                                <div class="ui compact fluid menu region-district">
                                                    <div class="ui item" style="width: 100%;">
                                                        <span class="text" style="width: 100%;">Quận/Huyện</span>
                                                        <i class="dropdown icon"></i>
                                                    </div>
                                                    <input type="hidden" id="payment_district" name="district" autocomplete="new-district" value="">
                                                    <div class="errordiv payment_district" style="top: 44px;"><div class="arrow"></div>Vui lòng nhập quận/huyện</div>
                                                </div>
                                            </div>
                                            <div class="field" style="margin-bottom: 0 !important;">
                                                <div class="ui compact fluid menu region-ward">
                                                    <div class="ui item" style="width: 100%;">
                                                        <span class="text" style="width: 100%;">Phường/Xã</span>
                                                        <i class="dropdown icon"></i>
                                                    </div>
                                                    <input type="hidden" id="payment_ward" name="ward" autocomplete="new-ward" placeholder="" value="">
                                                    <div class="errordiv payment_ward" style="top: 44px;"><div class="arrow"></div>Vui lòng nhập phường/xã</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                                                    <div class="field">
                                    <div class="ui stackable grid">
                                        <div class="row">
                                            <div class="ten wide column">
                                                <div class="field">
                                                    <label style="display: block; margin-bottom: 3px;">Địa chỉ giao hàng:</label>
                                                    <input type="hidden" name="address" id="payment_address" value=""><input type="hidden" name="latitude" id="payment_latitude" value="0"><input type="hidden" name="longitude" id="payment_longitude" value="0">                                                        <div class="ui icon input">
                                                            <input type="text" id="payment_street" name="street" autocomplete="new-street" placeholder="Số nhà, tên đường">
                                                            <i class="map icon"></i>
                                                        </div>
                                                        <div class="errordiv payment_street" style="top: 61px;"><div class="arrow"></div>Vui lòng nhập địa chỉ giao hàng</div>
                                                                                                        </div>
                                            </div>
                                            <div class="six wide column">
                                                <div class="field">
                                                    <label style="display: block; margin-bottom: 3px;">Ngày giao hàng:</label>
                                                                                                            <div class="ui grid">
                                                            <div class="row">
                                                                <div class="nine wide column">
                                                                    <div class="ui calendar" id="date_shipping">
                                                                        <div class="ui input icon">
                                                                            <input type="text" name="date_shipping" value="04/27/2020" onkeydown="return false" />
                                                                            <i class="calendar icon"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="seven wide column">
                                                                    <div class="ui calendar" id="time_shipping">
                                                                        <div class="ui input icon">
                                                                            <input type="text" name="time_shipping" value="16:56" onkeydown="return false" />
                                                                            <i class="time icon"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                                                                        </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="field">
                                    <label style="display: block; margin-bottom: 3px;">Người nhận:</label>
                                                                            <div class="ui icon input">
                                            <input type="text" id="payment_fullname" name="fullname" autocomplete="new-fullname" placeholder="" value="">
                                            <i class="user icon"></i>
                                        </div>
                                        <div class="errordiv payment_fullname" style="top: 61px;"><div class="arrow"></div>Vui lòng nhập người nhận hàng</div>
                                                                        </div>
                                <div class="field">
                                    <div class="ui stackable grid">
                                        <div class="row">
                                            <div class="six wide column" style="padding-bottom: 0 !important;">
                                                <div class="field">
                                                    <label style="display: block; margin-bottom: 3px;">Điện thoại:</label>
                                                                                                            <div class="ui icon input">
                                                            <input type="tel" id="payment_phone" name="phone" autocomplete="new-phone" placeholder="" value="">
                                                            <i class="phone icon"></i>
                                                        </div>
                                                        <div class="errordiv payment_phone" style="top: 61px;"><div class="arrow"></div>Vui lòng nhập số điện thoại</div>
                                                                                                        </div>
                                            </div>
                                            <div class="ten wide column">
                                                <div class="field">
                                                <label style="display: block; margin-bottom: 3px;">Email:</label>
                                                                                                            <div class="ui icon input">
                                                            <input type="email" id="payment_email" name="email" autocomplete="new-email" placeholder="" value="">
                                                            <i class="mail icon"></i>
                                                        </div>
                                                                                                        </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="field">
                                    <label style="display: block; margin-bottom: 3px;">Ghi chú:</label>
                                                                            <div class="ui icon input">
                                            <input type="text" id="payment_note" name="note" autocomplete="new-note" placeholder="">
                                            <i class="pencil icon"></i>
                                        </div>
                                                                        </div>
                                <div class="fields">
                                    <div class="six wide field">
                                        <h3>Hình thức thanh toán</h3>
                                        <div>
                                            <div class="grouped fields">
                                                                                                    <div class="field">
                                                        <div class="ui radio checkbox">
                                                            <input type="radio" name="payment_type" value="0" checked="" tabindex="0" class="hidden">
                                                            <label>Thanh toán khi nhận hàng</label>
                                                        </div>
                                                    </div>
                                                    <div class="field" style="display: none;">
                                                        <div class="ui radio checkbox">
                                                            <input type="radio" name="payment_type" value="1" tabindex="0" class="hidden">
                                                            <label>Quét QR Code MoMo</label>
                                                        </div>
                                                    </div>
                                                                                                </div>
                                        </div>
                                                                            </div>
                                    <div class="ten wide field">
                                                                                    <div id="branch" style="display: none;">
                                                <h3>Nhà hàng giao</h3>
                                                <div id="branch-info">
                                                    <div class="grouped fields">
                                                        <div class="field">
                                                            <input type="hidden" id="payment_branch" name="branch" value="0">
                                                            <input type="hidden" id="delivery_distance" name="distance" value="0">
                                                            <div class="name"></div>
                                                            <div class="address"></div>
                                                            <div class="phone"></div>
                                                            <div>Khoảng cách nhà hàng: <span class="distance" style="font-size: 18px; font-weight: bold; margin-top: 5px;"></span></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                                                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			<div id="cart-column" class="six wide column">
                <div id="sidebar-right">
                    <div class="cart-right sidebar__inner">
                        <div class="ui inverted dimmer main">
                            <div class="ui loader"></div>
                        </div>
                        <form class="ui form" autocomplete="off">
                            <div id="cart-container" data-brand="kichi" data-mode="order"></div>
                        </form>
						<?php //do_action( 'woocommerce_before_checkout_form', $checkout );?>  

					</div>
                </div>
            </div>
		</form>
        </div>
	</div>
</div>