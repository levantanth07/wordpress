<?php
namespace GDelivery\Libs;

class Config {
    const ENV = 'production';
    const ENV_KEY = '7e1fd86e4bea905b2d3551b191f54d7a';
    const VERSION = '2.1.0';

    const OLD_TGS_IV_KEY = 'GgG!b3st';
    const OLD_TGS_PUBLIC_KEY = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDR0MWU0QxABmcymsMkJ4UUClnAhYA1mQfz0Gk2wb6MbajE6W4mEl3tN2LGlB5W+c8vH8HnO4mg61d9vurdW3LAoc8801Oeu8yBuGpplhSjNGvmBxPFXOQPVDjaSZ6k/RJme7bbzhc65e+GtWQh5PR58X35xBGYRG6JfPAWIZGKQIDAQAB';
    const OLD_TGS_API_URL = 'http://118.71.251.188:59003/api/';

    const TGS_API_BASE_URL = 'http://118.71.251.188:59004/api/v1/';
    const TGS_VERSION = '10.10.10';
    const TGS_SMS_BRAND_NAME = 'GoldenSpoon';
    const TGS_SMS_APP_TO_LOGIN = 'Gdelivery.vn';

    const GOOGLE_MAP_API_KEY = 'AIzaSyANxglAhcrKB-NdPF3mkA5BWwXkvQX27bo';
    const GOOGLE_MAP_API_KEY_FOR_WEB = 'AIzaSyDT4lPQm6sJ1xxuQ6BJLEK12QP-8JmLWfc';

    const BOOKING_API_BASE_URL = 'http://booking.ggg.com.vn/api/v1/';
    const BOOKING_API_KEY = '84b656ecdcac94421bc236969971ce91';

    const REDIS_HOST = '172.16.200.79';
    const REDIS_PORT = '6379';
    const REDIS_PASS = 'ggg315';
    const REDIS_DB_INDEX = 2;

    const PAYMENT_HUB_API_BASE_URL = 'http://172.16.200.77:6677/api/v1/';
    const PAYMENT_HUB_API_KEY = '7e1fd86e4bea905b2d3551b191f54d7a';

    const PAYMENT_HUB_TGS_RESTAURANT_CODE = 99002;
    const PAYMENT_HUB_TGS_RESTAURANT_POS_ID = 4;
    const PAYMENT_HUB_TGS_RESTAURANT_CODE_HCM = 99003;
    const PAYMENT_HUB_TGS_RESTAURANT_POS_ID_HCM = 4;
    const PAYMENT_HUB_TGS_SENDER = 'TGS';

    const PAYMENT_HUB_VNPAY_RK_PAYMENT_CODE = 991111;
    const PAYMENT_HUB_ZALO_RK_PAYMENT_CODE = 991112;
    const PAYMENT_HUB_VINID_RK_PAYMENT_CODE = 991115;
    const PAYMENT_HUB_MOMO_RK_PAYMENT_CODE = 991114;
    const PAYMENT_HUB_SHOPEE_PAY_RK_PAYMENT_CODE = 991117;
    const PAYMENT_HUB_VNPAY_BANK_ONLINE_PARTNER_ID = 19;
    const PAYMENT_HUB_VNPAY_BANK_ONLINE_REQUEST_URL = 'http://118.71.251.188:6677/payment-gateway/bank-online/request';

    const PAYMENT_HUB_VNPT_EPAY_BANK_ONLINE_PARTNER_ID = 22;
    const PAYMENT_HUB_VNPT_EPAY_BANK_ONLINE_IFRAME_URL = 'http://118.71.251.188:6677/payment-gateway/bank-online/request-with-iframe';

    const PAYMENT_HUB_COUPON_GPP_DISCOUNT_CODE_HN = 205;
    const PAYMENT_HUB_COUPON_GPP_DISCOUNT_CODE_HCM = 206;
    const PAYMENT_HUB_COUPON_GPP_DISCOUNT_CODE_HCM_SBU = 207;

    const PAYMENT_HUB_FARCARD_INTERFACE_CODE = 9;

    const PAYMENT_HUB_BIZ_ACCOUNT_PAYMENT_CODE = 11;
    const PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME = 'BizAccount';
    const PAYMENT_HUB_GBIZ_SOURCE = 'AppTGS';

    const POS_SHIPPING_FEE_ITEM_CODE = 9078;
    const POS_SHIPPING_FEE_ITEM_CODE_ICOOK = 20300;
    const POS_SHIPPING_FEE_ITEM_CODE_HCM = 66666;
    const POS_SHIPPING_FEE_ITEM_CODE_ICOOK_HCM = 18112;

    const MESSAGE_SYSTEM_API_BASE_URL = 'http://10.28.1.140/api/v1/';
    const MESSAGE_SYSTEM_API_KEY = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMC4yOC4xLjE0MFwvYXBpXC92MVwvdXNlcl94eHhcL2xvZ2luIiwiaWF0IjoxNTk5MDcyNTk5LCJleHAiOjE5MTQ0MzI1OTksIm5iZiI6MTU5OTA3MjU5OSwianRpIjoiSkp2dUd4MW9JVjh1dkZDRCIsInN1YiI6MSwicHJ2IjoiYjkxMjc5OTc4ZjExYWE3YmM1NjcwNDg3ZmZmMDFlMjI4MjUzZmU0OCIsInVzZXJuYW1lIjoidG9hbm5kIn0.cEaiLdtBP0wl1cZeidC655pVBPVTbjD_mLHEVdZOTYQ';
    const MESSAGE_SYSTEM_SMS_VENDOR = 'vmg';
    const MESSAGE_SYSTEM_SMS_BRAND_NAME = 'GoldenSpoon';

    const BRAND_NAME = 'TGS';
    const BRAND_HOTLINE = '1900 6622';

    const REPORT_API_BASE_URL = 'http://report.gdelivery.vn/api/';
    const REPORT_API_KEY = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9yZXBvcnQuZ2RlbGl2ZXJ5LnZuXC9hcGlcL2xvZ2luIiwiaWF0IjoxNjA2ODc5MTY2LCJleHAiOjg4MDA2ODc5MTY2LCJuYmYiOjE2MDY4NzkxNjYsImp0aSI6Ik9sT1hTM1pmeVhLV2xTQmsiLCJzdWIiOjEsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.L3GJ_bhrkxk5cqLZ1T2BARdYV0N8Ry0gNeu08YQhsK4';

    const GRAB_ENV = 'staging';
    const GRAB_CLIENT_ID = '6a1241b07d524c17a99539107d7beb38';
    const GRAB_CLIENT_SECRET = 'MA8pz_P2fjqshlpa';

    const MESSAGE_CALLING_NEW_ORDER = 5;

    const EVOUCHER_API_BASE_URL = 'http://10.28.1.115/api/v1/';
    const EVOUCHER_API_KEY='yJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMDMuMzkuOTQuNzdcL2FwaVwvdjFcL2xvZ2luIiwiaWF0IjoxNTg0NDM3NzY5LCJleHAiOjE1ODQ1MjQxNjksIm5iZiI6MTU4NDQzNzc2OSwianRpIjoiR1ZYRUJaRDRMZ0dPbTlyMiIsInN1YiI6MSwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.ii6W77s_S2Mi2jdYET0LAjpM3QMkumF6iTIT5ikStWY';

    const GOONG_API_KEY = 'gqaMbiArcfz7AF7V2dYftolN3uo16VSbuFb0HKtP';
    const LIMIT_SEARCH_GOONG_ADDRESS = 30;

    const MAS_OFFER_API_BASE_URL = 'http://s2s.dev.masoffer.tech/v1/';
    const MAS_OFFER_ID = 'goldengate';
    const MAS_OFFER_SIGNATURE = '27Lt2mYZbOIXKovr';

    const INTERNAL_AFFILIATE_API_BASE_URL = 'http://uat.affiliate.ggg.com.vn/api/';
    const INTERNAL_AFFILIATE_API_KEY = 'psmZeAF9JrOJnvu8LywV0pF9O1X2JAnWYm7YJNkQmwGl9qKAzzM93kHplVtJvpbj';

    // Tax
    const SHIPPING_TAX = 0.1;
    const TAX_CLASS_STANDARD = '';

    // Brand
    const BRAND_IDS = [
        'icook' => 17,
    ];

    const DOWNLOAD_CC_EMAIL = 'buivannghi1991@gmail.com';

    const ECOMMERCE_BE_BASE_URL = "http://be.e-commerce.local.com/api/v1/";
    const ECOMMERCE_BE_KEY = "RwOlwvXC8xMC4yOC4xLjE0MFwvYXBpXC92MVwvd";

    const ECOMMERCE_BE_IP_SERVERS = "172.16.200.77,";

    const POS_GUEST_TYPE = 10;
    const POS_GUEST_TYPE_HCM = 10;

    const TGS_API_V2_BASE_URL = "https://be-uat.tgss.vn/api/";
    const TGS_API_V2_KEY = "eyJhbGciOiJIUzI1NiJ9.eyJSb2xlIjoiQWRtaW4iLCJJc3N1ZXIiOiJJc3N1ZXIiLCJVc2VybmFtZSI6IkdHRyIsImV4cCI6MTY1MzM3ODI5NiwiaWF0IjoxNjUzMzc4Mjk2fQ.A1Zgque7dIbUDBP7MkIEXdh4lNDkl5czLMm-Xg4UAhU";

    const CO_RECEIVING_EMAIL_ORDER = 'nghi.buivan@ggg.com.vn';
}
