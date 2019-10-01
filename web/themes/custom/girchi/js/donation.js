$("document").ready( function () {
    $.each($(".amount"), function (key,input) {
        let amount = input.value;
        let value = Math.floor((amount / currency) * 100);
        $(input).closest('.d-flex').find('.ged-output-add').html(value);
    });
});
// GED Count
let currency = $("#currency_girchi").val();

$("#edit-amount").on("keyup", e => {
    let amount = e.target.value;
    let value = Math.floor((amount / currency) * 100);
    $("#ged-place1").html(value);
    $("#ged-place-2").html(value);
});

$(".amount").on("keyup", e => {
    let amount = e.target.value;
    let value = Math.floor((amount / currency) * 100);
    $(e.target).closest('.d-flex').find('.ged-output-add').html(value);
});



$("#edit-amount--2").on("keyup", e => {
    let amount = e.target.value;
    let value = Math.floor((amount / currency) * 100);
    $("#ged-place-2").html(value);
});

// Front validation
$("#edit-politicians").on("change", e => {
    if (e.target.value) {
        $("#edit-donation-aim").attr("disabled", "disabled");
    } else {
        $("#edit-donation-aim").removeAttr("disabled");
    }
});

$("#edit-donation-aim").on("change", e => {
    if (e.target.value) {
        $("#edit-politicians").attr("disabled", "disabled");
    } else {
        $("#edit-politicians").removeAttr("disabled");
    }
});

$("#edit-politicians--2").on("change", e => {
    if (e.target.value) {
        $("#edit-donation-aim--2").attr("disabled", "disabled");
    } else {
        $("#edit-donation-aim--2").removeAttr("disabled");
    }
});

$("#edit-donation-aim--2").on("change", e => {
    if (e.target.value) {
        $("#edit-politicians--2").attr("disabled", "disabled");
    } else {
        $("#edit-politicians--2").removeAttr("disabled");
    }
});

$('body').on('click', '.pauseDonation',  e => {
    var entity_id = $(e.target).attr('data-id');
    $.ajax({
        type: "POST",
        url: "/donate/update_donation_status",
        data: {"action": "pause", "id": entity_id}
    })
        .done((data) => {
            $(e.target).replaceWith(`<button
                                   data-id = "${entity_id}"
                                   class="btn btn-success mr-sm-2 text-uppercase px-3 d-block d-sm-inline-block mx-0 w-100 w-sm-auto mt-1 mt-sm-0 resumeDonation">
                                   ${Drupal.t("Resume")}
                                   </button>`);

            $(`[data-wrapper-id=${entity_id}]`).removeClass('bg-gradient-green').addClass('bg-gradient-warning');
            $(`.donation-status-${entity_id}`).text(Drupal.t('PAUSED'));

        });

});
$('body').on('click', '.resumeDonation', e => {
    var entity_id = $(e.target).attr('data-id');
    $.ajax({
        type: "POST",
        url: "/donate/update_donation_status",
        data: {"action": "resume", "id": entity_id}
    })
        .done((data) => {
            $(e.target).replaceWith(`<button
                                     data-id = "${entity_id}"
                                     class="btn btn-outline-light-silver mr-sm-2 text-grey text-uppercase px-3 d-block d-sm-inline-block mx-0 w-100 w-sm-auto mt-1 mt-sm-0 pauseDonation">
                                     ${Drupal.t('Pause')}
                                     </button>`);
            $(`[data-wrapper-id=${entity_id}]`).removeClass('bg-gradient-warning').addClass('bg-gradient-green');
            $(`.donation-status-${entity_id}`).text(Drupal.t("ACTIVE"));
        });


});
