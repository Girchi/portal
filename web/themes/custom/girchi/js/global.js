$(document).ready(function() {
    $("#edit-field-politician-value").on("change", e => {
        if (e.target.checked) {
        $(".form-checkbox-input").addClass("checked");
    } else {
        $(".form-checkbox-input").removeClass("checked");
    }
});

    $(".search-submit").on("click", e => {
        if ($("#search-text").val()) {
        $(".navbar-search-input ")
            .fadeIn()
            .removeClass("border-white")
            .addClass("border-secondary")
            .addClass("w-lg-500");
        $(".navbar-search").submit();
    }
});

    if ($('.paragraph-yellow').length % 2 === 1) {
        $('.paragraph-yellow').last().addClass('w-100');
    }

    $("#favorite_news").click(e => {
        var nid = $("#favorite_news").attr("data-node-id");
    if ($("#favorite_news").is(":checked")) {
        $.ajax({
            type: "GET",
            url: "/api/add/favorite/news/" + nid,
            success: function(response) {
                console.log(response);
            },
            error: function(response) {
                console.log(response);
            }
        });
    } else {
        $.ajax({
            type: "GET",
            url: "/api/remove/favorite/news/" + nid,
            success: function(response) {
                console.log(response);
            },
            error: function(response) {
                console.log(response);
            }
        });
    }
});
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

    $('.custom-file-input').on('change',function(e){
        $(this).next('.custom-file-label').html(e.target.files[0].name);
    })

    $('body').on('click', '.politician-modal', (e) => {
        let userID = e.target.getAttribute('data-uid');
        if(userID == null) {
            userID = $(e.target).parent().attr('data-uid');
        }

        $.ajax({
            type: "POST",
            url: "/api/party-list/getPoliticianSupporters",
            data: {"userId": userID}
        })
            .done((data) => {
                let supporterTable = $('#supporters table tbody');
                supporterTable.empty();
                $.each(data, (key, supporter) => {
                    let tableElement = `<tr data-uid="${supporter.id}">
                  <th
                    scope="row"
                    class="pl-3 w-auto w-md-80-px text-center align-middle"
                  >
                    <span
                      class="font-size-4 font-size-xl-5 text-dark-silver font-weight-normal"
                      >${key+1}</span
                    >
                  </th>
                  <td class="align-middle">
                    <div class="d-flex w-100 align-items-center">
                      <a
                        href="#"
                        class="rounded-circle overflow-hidden d-none d-md-block"
                      >
                        <img
                          src="${supporter.img_url}"
                          class="rounded w-40-px"
                          alt="..."
                        />
                      </a>
                      <h6
                        class="w-100 w-sm-auto text-uppercase line-height-1-2 font-size-3 font-size-md-3 font-size-xl-base mb-0 mx-0 mx-md-3"
                      >
                          <span class="text-decoration-none d-inline-block">
                            <span class="font-weight-bold">${supporter.name}</span>
                          </span>
                      </h6>
                    </div>
                  </td>
                  <td
                    class="text-right text-md-center align-middle font-weight-bold"
                  >
                    <span
                      class="text-success font-size-4 font-weight-bold d-block d-md-none text-nowrap line-height-0-8"
                      >${supporter.ged_amount}<i class="icon-ged font-size-3"></i
                    ></span>
                    ${supporter.percentage}%
                  </td>
                  <td class="align-middle text-center d-none d-md-table-cell">
                    <span
                      class="text-success font-size-4 font-size-xl-4 font-weight-bold text-nowrap"
                      >${supporter.ged_amount} <i class="icon-ged font-size-3"></i
                    ></span>
                  </td>
                </tr>`;
                    supporterTable.append(tableElement);
                });
            });
    });

});
