$(document).ready(function () {
    $('#edit-field-politician-value').on('change', (e) => {
         if (e.target.checked) {
            $('.form-checkbox-input').addClass('checked');
        } else {
            $('.form-checkbox-input').removeClass('checked');
        }

    });

    if ($(window).width() < 1200) {
        $('.nav-item.is-dropdown .nav-link i').on('click', (e) => {
          e.preventDefault()
          const item = $(e.target).parents('.nav-item')
          const dropdown = $(e.target).parents('.nav-item').find('.nav-dropdown')
          if (dropdown.is(':visible')) {
            dropdown.hide()
            item.removeClass('collapsed')
          } else {
            dropdown.show()
            item.addClass('collapsed')
          }
        })
      } else {
        $('.nav-item.is-dropdown').on('mouseenter', function (e) {
          $(this).addClass('collapsed')
        }).on('mouseleave', function (e) {
          $(this).removeClass('collapsed')
        })
      }

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
