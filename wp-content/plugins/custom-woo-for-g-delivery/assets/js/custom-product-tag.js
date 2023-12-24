jQuery(document).ready(function ($) {
    let applyBtn = $('#posts-filter #doaction');
    applyBtn.click(function(e) {
        let selectedTags = $('.tags input[type=checkbox]:checked');
        if (selectedTags.length == 0) {
            e.preventDefault();
            return alert('Vui lòng chọn tag cần thực hiện');
        }
        let bulkAction = $('#bulk-action-selector-top');
        if (bulkAction.val() == -1) {
            e.preventDefault();
            return alert('Vui lòng chọn hành động.');
        }
    });
});