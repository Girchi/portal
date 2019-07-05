$(document).ready(function() {
    const pass = $('#password');
    const passConfirm = $('#password-confirmation');
    const message =  $('#status-messages');
    // const pattern = /^(?=.*[A-Za-z])[A-Za-z\d]$/;

    $("#create-pass").on("click", e => {
        e.preventDefault();
        message.html('');
       if(!pass.val()){
           message.append('<div class="alert alert-danger"> Password is empty </div>');
       }else if(!passConfirm.val()){
           message.append('<div class="alert alert-danger"> Confirm password is empty </div>');
       }else if (pass.val() !== passConfirm.val()){
           message.append('<div class="alert alert-danger"> Passwords dont match</div>');
       }
       // else if (!pattern.test(pass)){
       //     message.append('<div class="alert alert-danger"> Invalid haracters </div>');
       // }
       else{
           $.ajax({
               type: "POST",
               url: "/api/confirm/password",
               data: {
                   'pass':pass.val()
               }
           })
           .done(data => {
               if(data === 'success'){
                   window.location.href = "/user";
               }else {
                   message.append('<div class="alert alert-danger"> Error </div>');
               }
           });
       }
    });
});
