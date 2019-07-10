$(document).ready(function() {
    const pass = $('#password');
    const passConfirm = $('#password-confirmation');
    const message =  $('#status-messages');
    const uid = $("#userid");


    $("#create-pass").on("click", e => {
        e.preventDefault();
        message.html('');
       if(!pass.val()){
           message.append(`<div class="alert alert-danger"> ${Drupal.t('Password is empty ')}</div>`);
       }else if(!passConfirm.val()){
           message.append(`<div class="alert alert-danger"> ${Drupal.t('Confirm password is empty')} </div>`);
       }else if (pass.val() !== passConfirm.val()){
           message.append(`<div class="alert alert-danger"> ${Drupal.t('The specified passwords do not match')}</div>`);
       }
       else{
           $.ajax({
               type: "POST",
               url: "/api/confirm/password",
               data: {
                   pass: pass.val(),
                   uid: uid.val()
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
    });
});
