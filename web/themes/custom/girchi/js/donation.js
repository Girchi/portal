$("document").ready(function () {
    $.each($(".amount"), function (key, input) {
        let amount = input.value;
        let value = Math.floor((amount / currency) * 100);
        $(input).closest('.d-flex').find('.ged-output-add').html(value);
    });

    const selects =$('[id^=selected-option]');
    $.each(selects,function(key,value){
        const sourceAttr = $(value).attr('source-type');
        const selectEl = $(value);

        //Add delete button for selected option (Aim or Politician)
        let delete_button = `
        <span id='del-sel-option-${sourceAttr}' class="font-size-4 p-0 shadow-none text-dark-silver text-hover-danger float-right ml-auto d-none" >
        <i class="icon-delete"></i>
        </span>`;
        $(`[data-id="selected-option-${sourceAttr}"]`).append(delete_button);

        //Delete selected option (Aim or Politician)
        $(`#del-sel-option-${sourceAttr}`).on('click', e =>{
            $(selectEl).selectpicker('val', '');
            $(`.${sourceAttr}-hidden-politician`).val('');
            $(`.${sourceAttr}-hidden-aim`).val('');
            $(selectEl).selectpicker('refresh');
            $(`#del-sel-option-${sourceAttr}`).addClass('d-none');
        })

        //Load politician from query string
        let politician_id = getParameterByName('politician');
        if (politician_id) {
            const selectedOption = $(selectEl).find(`option[value="2:${politician_id}"]`).get(0);
            $(selectedOption).attr('selected','selected');
            $(selectEl).selectpicker('refresh');
            $(`#del-sel-option-${sourceAttr}`).removeClass('d-none');
	        $(`.${sourceAttr}-hidden-politician`).val(politician_id);
        }
        let amount = getParameterByName('amount');
        if(amount) {
            $('#edit-amount').val(amount);
            let value = Math.floor((amount / currency) * 100);
            $("#ged-place1").html(value);
            $("#ged-place-2").html(value);
        }

        selectEl.on('changed.bs.select',function(e){
            $(`.${sourceAttr}-hidden-politician`).val('');
            $(`.${sourceAttr}-hidden-aim`).val('');
            let selectedElValue = e.currentTarget.value;
            let splitValue = selectedElValue.split(':');
            let value = splitValue[0];
            let id = splitValue[1];
            if(value === '1') {
                $(`#del-sel-option-${sourceAttr}`).removeClass('d-none');
                $(`.${sourceAttr}-hidden-politician`).val('');
                $(`.${sourceAttr}-hidden-aim`).val(id);
            }
            else if(value === '2') {
                $(`#del-sel-option-${sourceAttr}`).removeClass('d-none');
                $(`.${sourceAttr}-hidden-aim`).val('');
                $(`.${sourceAttr}-hidden-politician`).val(id);
            }
        })
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

$("#edit-amount--3").on('keyup', e => {
    let amount = e.target.value;
    let currency = $('#edit-currencies option:selected').val();
    let ged = calculateGed(amount, currency);
    if ( currency != '' && amount != '') {
        let ged = calculateGed(amount, currency);
        $("#ged-place-3").html(ged);
    }
    if (amount == '') {
        $("#ged-place-3").html(0);
    }
});

$("#edit-currencies").on('change', e => {
    let currency = e.target.value;
    let amount = $('#edit-amount--3').val();
    if ( currency != '' && amount != '') {
        let ged = calculateGed(amount, currency);
        $("#ged-place-3").html(ged);
    }
})

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

$("#edit-politicians--3").on("change", e => {
    if (e.target.value) {
        $("#edit-donation-aim--3").attr("disabled", "disabled");
    } else {
        $("#edit-donation-aim--3").removeAttr("disabled");
    }
});

$("#edit-donation-aim--3").on("change", e => {
    if (e.target.value) {
        $("#edit-politicians--3").attr("disabled", "disabled");
    } else {
        $("#edit-politicians--3").removeAttr("disabled");
    }
});

$('body').on('click', '.pauseDonation', e => {
    var entity_id = $(e.target).attr('data-id');
    var user_id = $(e.target).attr('user-id');
    $.ajax({
        type: "POST",
        url: "/donate/update_donation_status",
        data: {"action": "pause", "id": entity_id, "user_id": user_id}
    })
        .done((data) => {
            if (data.statusCode == 200) {
                $(e.target).replaceWith(`<button
                                   data-id = "${entity_id}"
                                   user-id = "${user_id}"
                                   class="btn btn-success mr-sm-1 text-uppercase px-2 d-block d-sm-inline-block mx-0 w-100 w-sm-auto mt-1 mt-sm-0 resumeDonation">
                                   ${Drupal.t("Resume")}
                                   </button>`);

                $(`[data-wrapper-id=${entity_id}]`).removeClass('bg-gradient-green').addClass('bg-gradient-warning');
                $(`.donation-status-${entity_id}`).text(Drupal.t('PAUSED'));
            }
        });

});
$('body').on('click', '.resumeDonation', e => {
    var entity_id = $(e.target).attr('data-id');
    var user_id = $(e.target).attr('user-id');
    $.ajax({
        type: "POST",
        url: "/donate/update_donation_status",
        data: {"action": "resume", "id": entity_id, "user_id": user_id}
    })
        .done((data) => {
            if (data.statusCode == 200) {
                $(e.target).replaceWith(`<button
                                     data-id = "${entity_id}"
                                     user-id = "${user_id}"
                                     class="btn btn-outline-light-silver mr-sm-1 text-grey text-uppercase px-2 d-block d-sm-inline-block mx-0 w-100 w-sm-auto mt-1 mt-sm-0 pauseDonation">
                                     ${Drupal.t('Pause')}
                                     </button>`);
                $(`[data-wrapper-id=${entity_id}]`).removeClass('bg-gradient-warning').addClass('bg-gradient-green');
                $(`.donation-status-${entity_id}`).text(Drupal.t("ACTIVE"));
            }
        });


});


function calculateGed(amount, currency) {
    let eur = $('#currency_girchi_eur').val();
    let usd = $('#currency_girchi_usd').val();
    let value;
    if (currency === 'eur') {
        value = Math.floor(((amount * eur) / usd) * 100);
    }
    else if (currency === 'usd') {
        value = Math.floor(amount * 100);
    }
    else {
        value = Math.floor((amount / currency) * 100);
    }
    return value;
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}
