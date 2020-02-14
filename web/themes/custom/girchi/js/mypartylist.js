$(document).ready(function () {
    const listGroupEl = $('.pl-group-item');
    const selectEl = $('.select[id="politician"]');
    let sum = 0;
    $(selectEl).selectpicker('refresh');
    $.each(listGroupEl,(i,item)=>{
        sum += parseInt($(item).find('input[type="number"]').val());
        const id = $(item).attr('data-id');
        const selectedOption = $(selectEl).find(`option[value="${id}"]`).get(0);
        $(selectEl).selectpicker('refresh');
        $(selectedOption).attr('disabled', 'disabled');
        $(selectEl).selectpicker('val', '');
        $(selectEl).selectpicker('refresh');
        if(sum === 100){
            selectEl.prop('disabled', true);
            $('input[id="percent"]').prop('disabled', true);
            $(selectEl).selectpicker('refresh');
        }
    });
});
