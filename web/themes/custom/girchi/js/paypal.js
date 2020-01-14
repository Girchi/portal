$("document").ready(function () {
    paypal.Buttons({
        style: {
            layout: 'vertical',
            color: 'gold',
            shape: 'rect',
            label: 'paypal',
        },
        onInit: function (data, actions) {

            // Disable the buttons
            actions.disable();

            // Listen for changes to the input
            document.querySelector('#single-donation form')
                .addEventListener('change', function (event) {
                    // Enable or disable the button when it is checked or unchecked
                    if ($('#edit-amount').val() != '') {
                        if ($("#edit-politicians option:selected").val() != ''
                            || $("#edit-donation-aim option:selected").val() != '') {
                            actions.enable();
                        } else {
                            actions.disable();
                        }
                    } else {
                        actions.disable();
                    }
                });
        },

        createOrder: function (data, actions) {
            var amount = $("#edit-amount").val();
            // This function sets up the details of the transaction
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: amount
                    }
                }]
            });
        },
        onApprove: function (data, actions) {
            var aim = $("#edit-donation-aim option:selected").val();
            var politician = $("#edit-politicians option:selected").val();

            return actions.order.capture().then(function (details) {
                console.log(data);
                // Call server to save the transaction
                return fetch('/donate/finish/paypal', {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: data.orderID,
                        aim: aim,
                        politician: politician
                    })
                });
            });
        }
    }).render('#paypal-button-container');
});
