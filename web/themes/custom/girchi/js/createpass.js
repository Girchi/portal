$(document).ready(function() {
    const pass = $('#password');
    const passConfirm = $('#password-confirmation');
    const message =  $('#status-messages');
    const uid = $("#userid");
    const name = $("#name");
    const lastName = $('#last-name');
    const idNumber = $('#id-number');
    const phoneNumber = $('#phone-number');
    const phoneNumberParent = $('.int-phone-parent');
    const fbUrl = $('#facebook-url');

    $("#create-pass").on("click", e => {
        const country = $('#country').selectpicker('val');
        e.preventDefault();
        message.html('');
        let isValid = true;
        // let country = $('ul li.selected');
        $('.form-group input[type=text]').each(function () {
            if($(this).val() == '') {
                message.append(`<div class="alert alert-danger"> ${Drupal.t('რეგისტრაციის დადასრულებლად საჭიროა ყველა ველის შევსება')}</div>`);
                return false;
            }
        });
        if(!phoneNumberParent.hasClass('is-valid')) {
            message.append(`<div class="alert alert-danger"> ${Drupal.t('ტელეფონის ნომერი არასწორია')}</div>`);
            isValid = false;
        }
        if(idNumber.val().length != 11) {
            message.append(`<div class="alert alert-danger"> ${Drupal.t('პირადი ნომერი უნდა შედგებოდეს 11 ციფრისგან.')}</div>`);
            isValid = false;
        }
       if(!pass.val()){
           message.append(`<div class="alert alert-danger"> ${Drupal.t('Password is empty ')}</div>`);
           isValid = false;
       }else if(!passConfirm.val()){
           message.append(`<div class="alert alert-danger"> ${Drupal.t('Confirm password is empty')} </div>`);
           isValid = false;
       }else if (pass.val() !== passConfirm.val()){
           message.append(`<div class="alert alert-danger"> ${Drupal.t('The specified passwords do not match')}</div>`);
           isValid = false;
       }
       console.log(phoneNumber.val())
       if(country.length == 0) {
           isValid = false;
       }
       if (isValid){
           $.ajax({
               type: "POST",
               url: "/api/confirm/password",
               data: {
                   pass: pass.val(),
                   uid: uid.val(),
                   country: country,
                   name: name.val(),
                   lastName: lastName.val(),
                   idNumber: idNumber.val(),
                   fbUrl: fbUrl.val(),
                   phoneNumber: phoneNumber.val()
               }
           }).done(data => {
               if (data === "success") {
                   window.location.href = "/user";
               } else {
                   message.append(
                       '<div class="alert alert-danger"> Error </div>'
                   );
               }
           }).catch(error =>{
                 message.append(
                     '<div class="alert alert-danger"> Error </div>'
                 );
           });
       }
        console.log(isValid)
    });
});
