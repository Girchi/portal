$(document).ready(function () {
    $('#edit-field-politician-value').on('change', (e) => {
        if (e.target.checked) {
            $('.form-checkbox-input').addClass('checked');
        } else {
            $('.form-checkbox-input').removeClass('checked');
        }

    });


    $('.search-submit').on('click', e => {
        if ($('#search-text').val()) {
            $('.navbar-search-input ')
                .fadeIn()
                .removeClass('border-white')
                .addClass('border-secondary')
                .addClass('w-lg-500');
            $('.navbar-search').submit();
        }
    });

    if ($('.paragraph-yellow').length % 2 === 1) {
        $('.paragraph-yellow').last().addClass('w-100');
    }
    $('#supporter-search').keyup(function (e) {
        let searchKeyword = e.target.value.toLowerCase();
        let rank = 1;

      let supporters = $('#supporters tbody tr');
        $.each(supporters, (key,value) => {
          let searchText = $(value).find('td h6 .font-weight-bold').text();
        });
       $.each(supporters,(key, supporter) => {
          let searchText = $(supporter).find('td h6 .font-weight-bold').text().toLowerCase();
          if (searchText.includes(searchKeyword)) {
            $(supporter).find('th span').text(rank);
              rank++;
            $(supporter).show();
          } else {
            $(supporter).hide();
          }
        });
        

    });
    $('body').on('click', '.politician-modal', (e) => {
        let userID = e.target.getAttribute('data-uid');
        if(typeof userID === "undefined" || userID === null) {
            userID = $(e.target).parents('a:first').attr('data-uid');
        }
        $.ajax({
            type: "POST",
            url: "/api/party-list/getPoliticianSupporters",
            data: {"userId": userID}
        })
            .done((data) => {
                let supporterTable = $('#supporters table tbody');
                supporterTable.html(data);
            });
    });

});

