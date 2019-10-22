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

    $("#favorite_news").click(e => {
        var nid = $("#favorite_news").attr("data-node-id");
        if ($("#favorite_news").is(":checked")) {
            $.ajax({
                type: "GET",
                url: "/api/add/favorite/news/" + nid,
                success: function(response) {
                },
                error: function(response) {
                }
            });
        } else {
            $.ajax({
                type: "GET",
                url: "/api/remove/favorite/news/" + nid,
                success: function(response) {
                },
                error: function(response) {
                }
            });
        }
    });

    $('.custom-file-input').on('change',function(e){
        $(this).next('.custom-file-label').html(e.target.files[0].name);
    })

    $("#schoolVideo").on("hide.bs.modal", function() {
        let _this = this,
            youtubeSrc = $(_this).find("iframe").attr("src");
        let video = youtubeSrc.replace('autoplay=1', 'autoplay=0');
        $(_this).find("iframe").attr("src", video);
    });

});

function SetCaretAtEnd(elem) {
    var elemLen = elem.value.length;
    // For IE Only
    if (document.selection) {
        // Set focus
        elem.focus();
        // Use IE Ranges
        var oSel = document.selection.createRange();
        // Reset position to 0 & then set at end
        oSel.moveStart('character', -elemLen);
        oSel.moveStart('character', elemLen);
        oSel.moveEnd('character', 0);
        oSel.select();
    }
    else if (elem.selectionStart || elem.selectionStart == '0') {
        // Firefox/Chrome
        elem.selectionStart = elemLen;
        elem.selectionEnd = elemLen;
        elem.focus();
    } // if
} // SetCaretAtEnd()

var textboxToFocus = {};



jQuery(function($) {
        var addFocusReminder = function(textbox) {
            textbox.bind('keypress keyup', function(e) {
                textboxToFocus.formid = $(this).closest('form').attr('id');
                textboxToFocus.name = $(this).attr('name');

                if(e.type == 'keypress') {
                    if(e.keyCode != 8) { // everything except return
                        textboxToFocus.value = $(this).val() + String.fromCharCode(e.charCode);
                    } else {
                        textboxToFocus.value = $(this).val().substr(0, $(this).val().length-1)
                    }
                }
                else { // keyup
                    textboxToFocus.value = $(this).val();
                }
            });
        }

        addFocusReminder($('.navbar-search-input .form-item-combine input'));
        $(document).ajaxComplete(function(event,request, settings) {
            if(typeof textboxToFocus.formid !== 'undefined') {
                var textBox = $('#' + textboxToFocus.formid + ' input:text[name="' + textboxToFocus.name + '"]');
                textBox.val(textboxToFocus.value);
                SetCaretAtEnd(textBox[0]);
                addFocusReminder(textBox);
                //textboxToFocus = {}; // if you have other auto-submitted inputs as well
            }
        });
});

$(".investor-parent-checkbox input").on("change",  function () {
    let isChecked = $(this).parent().hasClass('checked');
    let investorChildren =  $(".investor-children");
    if(isChecked){
        investorChildren.attr('hidden', false);
        $(".investment-amount").attr('min', 10000);
    }
    else {
        investorChildren.attr('hidden', true);
        $(".investment-amount").attr('min', 1);
    }
});
