<div class="block-loading">
    <span class="fa fa-3x fa-spinner fa-pulse fa-fw color-loading" aria-hidden="true"></span>
</div>

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