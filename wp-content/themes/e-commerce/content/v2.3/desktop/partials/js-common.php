<script type="text/javascript">
    var doAjaxParamsDefault = {
        url: '<?=admin_url('admin-ajax.php')?>',
        requestType: "GET",
        dataType: 'json',
        data: {},
        beforeSendCallbackFunction: null,
        successCallbackFunction: null,
        completeCallbackFunction: null,
        errorCallBackFunction: null,
    };


    function doAjax(params) {

        let url = params.url,
            requestType = params.requestType,
            dataType = params.dataType,
            data = params.data,
            beforeSendCallbackFunction = params.beforeSendCallbackFunction,
            successCallbackFunction = params.successCallbackFunction,
            completeCallbackFunction = params.completeCallbackFunction,
            errorCallBackFunction = params.errorCallBackFunction;

        jQuery.ajax({
            'type' : requestType,
            'url' : url,
            'dataType' : dataType,
            'data' : data,
            beforeSend: function(jqXHR, settings) {
                if (typeof beforeSendCallbackFunction === "function") {
                    beforeSendCallbackFunction();
                }
            },
            success: function(res, textStatus, jqXHR) {
                if (typeof successCallbackFunction === "function") {
                    successCallbackFunction(res);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (typeof errorCallBackFunction === "function") {
                    errorCallBackFunction(errorThrown);
                }

            },
            complete: function(jqXHR, textStatus) {
                if (typeof completeCallbackFunction === "function") {
                    completeCallbackFunction();
                }
            }
        });
    }
</script>