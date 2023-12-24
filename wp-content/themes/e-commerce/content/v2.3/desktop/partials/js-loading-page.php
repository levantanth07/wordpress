<!--Loading all page-->
<script type="text/javascript">
    function showLoadingPage() {
        jQuery('.block-loading').addClass('loading');
        jQuery('.container.content-page').addClass('loading');
    }

    function hideLoadingPage() {
        jQuery('.block-loading').removeClass('loading');
        jQuery('.container.content-page').removeClass('loading');
    }
</script>