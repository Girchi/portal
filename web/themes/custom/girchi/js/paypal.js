$("document").ready(function () {
    paypal.Buttons({
        style: {
            layout: 'vertical',
            color: 'gold',
            shape: 'pill',
            label: 'paypal',
        },
        onInit: function (data, actions) {

            // Disable the buttons
            actions.disable();

            // Listen for changes to the input
            document.querySelector('#paypal-donation form')
                .addEventListener('change', function (event) {
                    // Enable or disable the button when it is checked or unchecked
                    if ($('#edit-amount--3').val() != '') {
                        if ($("#edit-politicians--3 option:selected").val() != ''
                            || $("#edit-donation-aim--3 option:selected").val() != '') {
                            actions.enable();
                        }
                        else {
                            actions.disable();
                        }
                    }
                    else {
                        actions.disable();
                    }
                });
        },
        onClick: function() {
            if ($('#edit-amount--3').val() != '') {
                if ($("#edit-politicians--3 option:selected").val() != ''
                    || $("#edit-donation-aim--3 option:selected").val() != '') {
                    $("#message-container").html('');
                }
                else {
                    $("#message-container").html(`<div class="alert alert-danger">${Drupal.t("Please select politician or aim")} </div>`);
                }
            }
            else {
                $("#message-container").html(`<div class="alert alert-danger">${Drupal.t("Please enter amount")} </div>`);

            }
        },
        createOrder: function (data, actions) {
            var amount = $("#edit-amount--3").val();
            // This function sets up the details of the transaction
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: amount,
                    }
                }]
            });
        },
        onApprove: function (data, actions) {
            var aim = $("#edit-donation-aim--3 option:selected").val();
            var politician = $("#edit-politicians--3 option:selected").val();
            var currency = $("#edit-currencies option:selected").val();

            return actions.order.capture().then(function (details) {
                // Call server to save the transaction
                return fetch('/donate/finish/paypal', {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: data.orderID,
                        currency: currency,
                        aim: aim,
                        politician: politician
                    })
                }).then(function() {
                    var orderId = data.orderID;
                    $('#donation_id').val(orderId);
                    $('#paypal-donation form').submit();
                });
            });
        }
    }).render('#paypal-button-container');
});
