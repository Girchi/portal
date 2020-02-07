(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s);
    js.id = id;
    js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.11&appId=303239883689419&autoLogAppEvents=1';
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

$(document).ready(function() {

    var fbModal = $('#facebook-invite-modal');
    document.getElementById('joinFbGroup').style.display ='block';
    var width = document.getElementById('facebook-invite-modal').clientWidth;
    document.getElementById('joinFbGroup').style.display ='none';

    var fbContent = `<div class="fb-group"
                          data-href="https://www.facebook.com/groups/198593947689340/"
                          data-width="${width}"
                          data-show-social-context="true"
                          data-show-metadata="false"
                          id="fb-widget-group">
                    </div>`;
    fbModal.html(fbContent);
});
