// GED Count
$("#edit-amount").on("focus", async e => {
    let currency = $('#currency_girchi').val();
    $(this).on("keyup", e => {
        let amount = e.target.value;
        let value = Math.ceil(amount / currency * 100);
        $("#ged-place1").html(value);
    });
});

$("#edit-amount--2").on("focus", async e => {
    let currency = $('#currency_girchi').val();
    $(this).on("keyup", e => {
        let amount = e.target.value;
        let value = Math.ceil(amount / currency * 100);
        $("#ged-place-2").html(value);
    });
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

