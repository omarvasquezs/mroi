import jQuery from 'jquery';
(function ($) {
    "use strict";

    // Ensure the DOM is fully loaded
    $(function () {
        // Add click event listener to list-group-item
        $('.custom-list-group .list-group-item').one('click', function (event) {
            const link = $(this).find('a');
            if (link.length && link.attr('href')) {
                event.preventDefault();
                link[0].click();
            }
        });
    });

})(jQuery);