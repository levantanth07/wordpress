<!-- Facebook business - requested by an.nguyensong@ggg.com.vn - 31.05.2021 -->
<meta name="facebook-domain-verification" content="vgqt3q5jw3uqfqf6rm8zo0gcuek91r" />

<!-- Google verification - requested by chinh.pham@ggg.com.vn - 12.01.2022 -->
<meta name="google-site-verification" content="AqL_ikdji_xYzbyLtsvZxX4_IF-ZesNXikkvrJpRBCY" />

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-177104301-1"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'UA-177104301-1');
</script>

<!-- Google Tag Manager - dat.nguyen@ggg.com.vn MKT request 04/02/2021 -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-PVLV6LB');</script>
<!-- End Google Tag Manager -->

<?php
$enableSubiz = get_option('enable_subiz');
if ($enableSubiz) {
    ?>
    <!-- Subiz -->
    <script>!function(s,u,b,i,z){var o,t,r,y;s[i]||(s._sbzaccid=z,s[i]=function(){s[i].q.push(arguments)},s[i].q=[],s[i]("setAccount",z),r=["widget.subiz.net","storage.googleapis"+(t=".com"),"app.sbz.workers.dev",i+"a"+(o=function(k,t){var n=t<=6?5:o(k,t-1)+o(k,t-3);return k!==t?n:n.toString(32)})(20,20)+t,i+"b"+o(30,30)+t,i+"c"+o(40,40)+t],(y=function(k){var t,n;s._subiz_init_2094850928430||r[k]&&(t=u.createElement(b),n=u.getElementsByTagName(b)[0],t.async=1,t.src="https://"+r[k]+"/sbz/app.js?accid="+z,n.parentNode.insertBefore(t,n),setTimeout(y,2e3,k+1))})(0))}(window,document,"script","subiz","acqxtaoomammutltgoqk")</script>
    <!-- End Subiz -->
<?php
}
?>

<?php
// netcore
$enabledNetCore = get_option('netcore_is_enabled');
$scriptNetcore = get_option('netcore_tracking_script');
if ($enabledNetCore == 1 && $scriptNetcore) {
    echo $scriptNetcore;
}
?>
<script type="text/javascript">
    var isEnabledNetCore = <?=($enabledNetCore ?? 0)?>;

    /**
     * Number.prototype.format(n, x)
     *
     * @param integer n: length of decimal
     * @param integer x: length of sections
     */
    Number.prototype.format = function(n, x) {
        var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
        return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
    };
</script>
<?php get_template_part('content/js', 'massoffer'); ?>

<link rel="canonical" href="https://gdelivery.vn/">
<script type="application/ld+json">
    {
        "@context" : "http://schema.org",
        "@type" : "Gdelivery Restaurant",
        "name" : "Giao hàng của hệ thống nhà hàng Golden Gate",
        "url" : "https://gdelivery.vn/",
        "image" : "https://datban.ggg.com.vn/assets/img/gbooking_thumb.png",
        "priceRange" : "Đặt bàn trực tuyến nhận nhiều ưu đãi, tích lũy tới 15%",
        "servesCuisine": [
            "Việt Nam"
        ],
        "telephone": "1900 6622",
        "aggregateRating" : {
            "@type" : "AggregateRating",
            "ratingValue" : "5",
            "reviewCount" : "1000"
        },
        "address" : {
            "@type" : "PostalAddress",
            "streetAddress" : "Số 60 Giang Văn Minh, Phường Đội Cấn, Quận Ba Đình, Thành phố Hà Nội, Việt Nam",
            "addressLocality" : "Hà Nội",
            "addressRegion" : "​​Q. Ba Đình",
            "postalCode" : "100000",
            "addressCountry" : {
                "@type" : "Country",
                "name" : "Việt Nam"
            }
        }
    }
</script>
