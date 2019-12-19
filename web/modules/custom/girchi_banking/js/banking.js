$('#cardSelector').on('click', function (e) {

        const gedAmount = $('#ged-place-2').html();
        const amount = $('#edit-amount--2').val();
        const frequency = $('#edit-frequency').val();
        const date = $('#edit-date').val();
        const amountHolder = $('#amount-holder');
        const dateHolder = $('#date-holder');
        const gedHolder = $('#ged-holder');
        const mainHolder = $('#main-holder');
        const politician = $('#edit-politicians--2').find('option:selected');
        const aim = $('#edit-donation-aim--2').find('option:selected');


        gedHolder.html(`<i class="icon-ged line-height-1-1"></i> ${gedAmount}`);
        if (frequency) {
            dateHolder.html(`${frequency} თვე`);
        }
        else {
            dateHolder.html('აირჩიეთ თვე');

        }
        if (amount) {
            amountHolder.html(`${amount} ლარი`);
        }
        else {
            amountHolder.html('აირჩიეთ თანხა');
        }

        if(politician.val() || aim.val()){
            if(politician.text()){
                mainHolder.html(politician.text());
            }
            mainHolder.html(aim.text());
        }else{
            mainHolder.html('აირჩიეთ მიზანი ან პოლიტიკოსი');
        }
    }
);


$('input[type=radio][name=credit-card]').change(function() {
    $('#card_id').val(this.value);
});
