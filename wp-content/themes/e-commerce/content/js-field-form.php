<script type="text/javascript">
    jQuery('input[type=tel]').on('blur', blurInput);
    function blurInput(e) {
        this.value = this.value.substring(0,10).replace(/[^0-9]/g, '');
    }

    /**
     * Validate phone number
     * @param phoneNumber
     * @param cellphoneRegex
     * @returns {boolean}
     */
    function validatePhoneNumber(phoneNumber, cellphoneRegex = /^(09|03|07|08|05)+([0-9]{8})$/g) {
        return cellphoneRegex.test(phoneNumber);
    }
</script>