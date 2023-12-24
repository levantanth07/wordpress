<script type="text/javascript">

    jQuery(document).ready(function () {
        showBtnScroll('#list-code-promotion', '.code');
    });

    // Scroll to right when click left button.
    function scrollBtnLeft(elem, speed) {
        let leftPos = jQuery(elem).scrollLeft();
        let width = jQuery(elem).width() * 2 / 3;
        jQuery(elem).animate({scrollLeft: leftPos - width}, speed);
    }

    // Scroll to left when click right button.
    function scrollBtnRight(elem, speed) {
        let leftPos = jQuery(elem).scrollLeft();
        let width = jQuery(elem).width() * 2 / 3;
        jQuery(elem).animate({scrollLeft: leftPos + width}, speed);
    }

    function showBtnScroll(parentElem, childElem) {
        let isOverflow = isScrollElem(parentElem, childElem);

        if (isOverflow) {
            jQuery(parentElem).find('.left-btn').addClass('show');
            jQuery(parentElem).find('.right-btn').addClass('show');
        }
    }

    // Check scroll of element.
    function isScrollElem(parentElem, childElem) {
        let parentWidth = jQuery(parentElem).width(),
            child = jQuery(parentElem).find(childElem),
            childWidth = 0,
            isOverflow = false;

        for (var i = 0; i < child.length; i++) {
            childWidth += child[i].offsetWidth;
        }

        if (parentWidth < childWidth) {
            isOverflow = true;
        }

        return isOverflow;
    }
</script>