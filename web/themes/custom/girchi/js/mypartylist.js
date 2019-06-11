$(document).ready(function () {
    $('.bs-searchbox :input').on('keyup', (e) => {
        let keyword = e.target.value;
        $.ajax({
            type: "GET",
            url: "/api/party-list/my-supported-members?user=" + keyword,
        })
            .done((data) => {
                console.log(data);
                $('#politician').html('');
                $.each( data, function( i, user ) {
                    $(`<option
                value="${user.id}"
                data-content='
            <div class="d-flex w-100 align-items-center p-1">
            <span
                class="rounded-circle overflow-hidden d-inline-block"
            >
                <img
                src="${user.imgUrl}"
                width="35"
                class="rounded pl-politician-avatar"
                alt="..."
                />
            </span>
            <h6
                class="text-uppercase line-height-1-2 font-size-3 font-size-xl-3 mb-0 mx-2"
            >
                <span
                class="text-decoration-none d-inline-block text-hover-success"
                >
                <span class="pl-politician-first-name">${user.firstName}</span>
                <span class="font-weight-bold pl-politician-last-name">${user.lastName}</span>
                </span>
                <span class="d-flex font-size-1 text-grey pl-politician-position">
                პოლიტიკოსი
                </span>
            </h6>
            '
            ></option>`).appendTo('#politician');
                });
                $("#politician").selectpicker("refresh");
            });
    })
});
