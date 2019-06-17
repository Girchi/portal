$(document).ready(function () {
    $('#edit-field-politician-value').on('change', (e) => {
         if (e.target.checked) {
            $('.form-checkbox-input').addClass('checked');
        } else {
            $('.form-checkbox-input').removeClass('checked');
        }

    });


    $('.search-submit').on('click',e => {
        if($('#search-text').val()){
            $('.navbar-search-input ')
                .fadeIn()
                .removeClass('border-white')
                .addClass('border-secondary')
                .addClass('w-lg-500');
            $('.navbar-search').submit();
        }
    });
});
