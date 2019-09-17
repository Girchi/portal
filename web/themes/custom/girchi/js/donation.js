// GED Count
let currency = $("#currency_girchi").val();

$("#edit-amount").on("keyup", e => {
    let amount = e.target.value;
    let value = Math.floor((amount / currency) * 100);
    $("#ged-place1").html(value);
});

$("#edit-amount--2").on("keyup", e => {
    let amount = e.target.value;
    let value = Math.floor((amount / currency) * 100);
    $("#ged-place-2").html(value);
});

// Front validation
$("#politicians-donation").on("change", e => {
    if (e.target.value) {
        $("#edit-donation-aim").attr("disabled", "disabled");
    } else {
        $("#edit-donation-aim").removeAttr("disabled");
    }
});

$("#edit-donation-aim").on("change", e => {
    if (e.target.value) {
        $("#politicians-donation").attr("disabled", "disabled");
    } else {
        $("#politicians-donation").removeAttr("disabled");
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

//get 10 top politicians on focus.
$("#politicians-donation").on("focus", e => {
    $(".politiciansList").show();
    let politiciansList = $("ul.politiciansList");
    let elementsCounter = 0;
    $.ajax({
        type: "GET",
        url: "/api/donations/top-politicians"
    }).done(data => {
        politiciansList.html("");
        $.each(data, function(i, user) {
            let dataLength = data.length;
            elementsCounter++;
            let newElement = $(`<li style="border-top: 1px solid #ecf1f5" class="last bg-dark-white politiciansListItem">
                    <a class="dropdown-item" role="option" style="border-radius: 0" id="${user.id}">
                    <span class="text">
                    <div class="d-flex w-100 align-items-center p-1">
                      <span class="rounded-circle overflow-hidden d-inline-block">
                        <img
                                src="${user.imgUrl}"
                                width="35"
                                class="rounded pl-politician-avatar"
                                alt="..."
                        />
                      </span>
                        <h6 class="text-uppercase line-height-1-2 font-size-3 font-size-xl-3 mb-0 mx-2">
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
                    </div>
                    </span>
                    </a>
                </li>`);
             if (elementsCounter === 1) {
                newElement.css("border-top-left-radius", "20px");
                newElement.css("border-top-right-radius", "20px");
                let a = newElement.find("a").first();
                a.css("border-top-left-radius", "20px");
                a.css("border-top-right-radius", "20px");
            }

            if (elementsCounter === dataLength) {
                let lastElement = newElement.last();
                lastElement.css("border-bottom-left-radius", "20px");
                lastElement.css("border-bottom-right-radius", "20px");
                let a = lastElement.find("a").first();
                a.css("border-bottom-left-radius", "20px");
                a.css("border-bottom-right-radius", "20px");
            }

            politiciansList.append(newElement);
        });
        $(".politiciansList").selectpicker("refresh");
    });
});

//get politicians on key up.
$("#politicians-donation").on("keyup", e => {
    $(".politiciansList").show();
    let keyword = e.target.value;
    let politiciansList = $("ul.politiciansList");
    let elementsCounter = 0;
    $.ajax({
        type: "GET",
        url: "/api/party-list/my-supported-members?user=" + keyword
    }).done(data => {
        politiciansList.html("");
        $.each(data, function(i, user) {
            let dataLength = data.length;
            elementsCounter++;
            let newElement = $(`<li style="border-top: 1px solid #ecf1f5" class="last bg-dark-white politiciansListItem">
                    <a class="dropdown-item" role="option" style="border-radius: 0" id="${user.id}">
                    <span class="text">
                    <div class="d-flex w-100 align-items-center p-1">
                      <span class="rounded-circle overflow-hidden d-inline-block">
                        <img
                                src="${user.imgUrl}"
                                width="35"
                                class="rounded pl-politician-avatar"
                                alt="..."
                        />
                      </span>
                        <h6 class="text-uppercase line-height-1-2 font-size-3 font-size-xl-3 mb-0 mx-2">
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
                    </div>
                    </span>
                    </a>
                </li>`);

            if (elementsCounter === 1) {
                newElement.css("border-top-left-radius", "20px");
                newElement.css("border-top-right-radius", "20px");
                let a = newElement.find("a").first();
                a.css("border-top-left-radius", "20px");
                a.css("border-top-right-radius", "20px");
            }

            if (elementsCounter === dataLength) {
                let lastElement = newElement.last();
                lastElement.css("border-bottom-left-radius", "20px");
                lastElement.css("border-bottom-right-radius", "20px");
                let a = lastElement.find("a").first();
                a.css("border-bottom-left-radius", "20px");
                a.css("border-bottom-right-radius", "20px");
            }

            politiciansList.append(newElement);
        });

        $(".politiciansList").selectpicker("refresh");
    });
});

//Choose the politician.
$(".politiciansList").on("click", "a", e => {
    $("#politician-autocomplete").hide();
    $("#autocomplete-result").show();
    let item = $(e.target).closest("a");
    let politicianId = item.attr("id");
    let img = item.find("img").attr("src");
    let firstName = item.find("span.pl-politician-first-name")[0].innerText;
    let lastName = item.find("span.pl-politician-last-name")[0].innerText;
    $("#autocomplete-result").html(`
    <button type="button" class="btn btn-white border btn-block bg-hover-white rounded-oval"title="${firstName}
    ${lastName}">
        <div class="d-flex w-100 align-items-center p-1">
            <span class="rounded-circle overflow-hidden d-inline-block">
                <img src="${img}" width="35" class="rounded" alt="...">
            </span>
            <h6 class="text-uppercase line-height-1-2 font-size-3 font-size-xl-3 mb-0 mx-2">
            <span class="text-decoration-none d-inline-block text-hover-success">
            ${firstName}
            </span>
            <span class="font-weight-bold">${lastName}</span>
            </span>
            <span class="d-flex font-size-1 text-grey justify-content-center justify-content-sm-start">
                პოლიტიკოსი
            </span>
            </h6>
            <span class="delete-politician font-size-4 p-0 shadow-none text-dark-silver text-hover-danger float-right ml-auto" >
                    <i class="icon-delete"></i>
            </span>
        </div>
    </button>
    `);

    $("#politician_id").val(politicianId);
});

//Delete choosen politician.
$(document).on("click", ".delete-politician", e => {
    $("#autocomplete-result").hide();
    $("#politician-autocomplete").show();
    $("#politicians-donation").val("");
    $("#edit-donation-aim").removeAttr("disabled");
});

//hide list.
$("body").on("click", e => {
    if(!$(e.target).is('#politicians-donation')){
        $(".politiciansList").hide();
    }
});

// Get current politician from URL
$(document).ready(function() {
    let id = location.search.substring(location.search.lastIndexOf("=") + 1);
    if (id) {
        $("#politician-autocomplete").hide();
        $("#autocomplete-result").show();
        let firstName = document.getElementById('hidden-first-name').value;
        let lastName = document.getElementById('hidden-last-name').value;
        let image = document.getElementById('hidden-image').src;
        $("#autocomplete-result").html(`
        <button type="button" class="btn btn-white border btn-block bg-hover-white rounded-oval"title="${firstName}
        ${lastName}">
            <div class="d-flex w-100 align-items-center p-1">
                <span class="rounded-circle overflow-hidden d-inline-block">
                    
                    <img src='${image}' width="35" class="rounded" alt="...">
                </span>
                <h6 class="text-uppercase line-height-1-2 font-size-3 font-size-xl-3 mb-0 mx-2">
                <span class="text-decoration-none d-inline-block text-hover-success">
                ${firstName}
                </span>
                <span class="font-weight-bold">${lastName}</span>
                </span>
                <span class="d-flex font-size-1 text-grey justify-content-center justify-content-sm-start">
                    პოლიტიკოსი
                </span>
                </h6>
                <span class="delete-politician font-size-4 p-0 shadow-none text-dark-silver text-hover-danger float-right ml-auto" >
                        <i class="icon-delete"></i>
                </span>
            </div>
        </button>
        `);
    }
    $("#politician_id").val(id);
});

$("#politicians-donation").on("focusout", e => {
    document.getElementById('politicians-donation').value = "";
});

