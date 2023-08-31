(function ($) {
    $(document).ready(
        function () {
            // Remove the autocomplete attribute from the form field.
            $('input#edit-country-code').removeAttr('autocomplete');
            var some_id = $('#some_id');
            some_id.prop('type', 'text');
            some_id.removeAttr('autocomplete');
        }
    );
})(jQuery);
