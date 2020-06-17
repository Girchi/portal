Drupal.behaviors.userPage = {
    attach: function (context, settings) {
        var submit_button = $("#views-exposed-form-users-page-1 :submit");
        //Refresh selected options in bootstrap-select


        function onHeaderClick() {
            console.log("HEADER")
            const optGroupIndex = $('.dropdown-header')
                .toArray()
                .indexOf(this)
            const originalSelect = $(this)
                .parents('.bootstrap-select')
                .find('select')
            const currentOptgroup = originalSelect.find('optgroup').eq(optGroupIndex)
            const currentValue = originalSelect.selectpicker('val')

            if ($(this).hasClass('selected')) {
                $.each(currentOptgroup.find('option'), function (index, option) {
                    delete currentValue[currentValue.indexOf($(option).val())]
                })
            }
            else {
                $.each(currentOptgroup.find('option'), function (index, option) {
                    currentValue.push($(option).val())
                })
            }

            originalSelect.selectpicker('val', currentValue)
            originalSelect.selectpicker('render')
        }
        function  refreshSelects() {
            var country_values = $('[data-drupal-selector="edit-field-region-target-id"]').val();
            $('#country').selectpicker('val', country_values);
            var status_values = $('[data-drupal-selector="edit-field-approved-badges-target-id"]').val();
            $('#status').selectpicker('val', status_values);
            $('#user-search').val($('[data-drupal-selector="edit-combine"]').val());
            $('#user-search').focus();
            $('[data-toggle="tooltip"]').tooltip('dispose');
            $('[data-toggle="tooltip"]').tooltip();
        }

// Remove single filter

        $('body').delegate('.filter-chip', 'click', function () {
            const parent = $($(this).attr('data-parent'))
            const value = $(this).attr('data-value')
            const selectedFilters = $(parent).selectpicker('val')

            $(parent).selectpicker('val', selectedFilters.filter(f => f !== value))
        })

// Clear Filter
        $('.js-clear-filter').on('click', function () {
            $.each($('select.select'), function (index, select) {
                $(select).selectpicker('deselectAll')
            })

            $('[data-drupal-selector="edit-field-approved-badges-target-id"] option:selected').prop("selected", false);
            $('[data-drupal-selector="edit-field-region-target-id"] option:selected').prop("selected", false);
            submit_button.click();
        })

// Insert filters into filters bar
        function syncFiltersAndFiltersBar() {
            const filtersContainer = $('.filter-selected-chips-list')
            filtersContainer.html('')

            $.each($('select.select'), function (index, select) {
                const values = $(select).selectpicker('val')

                $.each(values, function (i, value) {
                    const opt = $(select).find(`option[value="${value}"]`)[0]
                    const label = opt.label
                    const val = opt.value

                    filtersContainer.append(
                        `<button class="filter-chip" data-value="${val}" data-parent="#${$(
                            select
                        ).attr('id')}">${label}</button>`
                    )
                })
            })
        }

        $('.select')
            .selectpicker({
                showContent: true,
                dropupAuto: false,
                size: 5
            })
            .on('changed.bs.select', function (e) {
                const originalSelect = $(e.currentTarget)[0]

                if ($(originalSelect).hasClass('tree-select')) {
                    // Tree
                    $(e.currentTarget)
                        .parent()
                        .find('.dropdown-menu .dropdown-header')
                        .removeClass('selected')

                    $.each($(originalSelect).find('optgroup'), function (ogIndex, optgroup) {
                        let totalOptions = 0
                        let totalSelected = 0

                        $.each($(optgroup).find('option'), function (opIndex, option) {
                            totalOptions++
                            if ($(option).is(':selected')) {
                                totalSelected++
                            }
                        })

                        if (totalOptions === totalSelected) {
                            $(e.currentTarget)
                                .parent()
                                .find(`.dropdown-menu .dropdown-header`)
                                .eq(ogIndex)
                                .addClass('selected')
                        }
                    })
                }

                // Filter bar
                syncFiltersAndFiltersBar()
            })
            .on('shown.bs.select', function () {
                $('.tree-select ~ .dropdown-menu .dropdown-header').on(
                    'click',
                    onHeaderClick
                )
            })
            .on('hidden.bs.select', function () {
                $('.tree-select ~ .dropdown-menu .dropdown-header').unbind()
            })

        $('.form-control select').on('change', (e) => {
            if (e.target.value.toString().length) {
                $(e.target).addClass('selected')
            }
            else {
                $(e.target).removeClass('selected')
            }
        })

        $(".region-container").on("hidden.bs.dropdown",
            function(e) {
                var region_select = $('[data-drupal-selector="edit-field-region-target-id"]');
                $('[data-drupal-selector="edit-field-region-target-id"] option:selected').prop("selected", false);
                $(".filter-chip").each(function (index,value) {
                    if($(value).attr('data-value') && $(value).attr('data-parent') == '#country') {
                        region_select.find("option[value='"+ $(value).attr('data-value')+"']").attr("selected",true)
                    }
                })
                submit_button.click();
            });

        $(".status-container").on("hidden.bs.dropdown",
            function(e) {
                var badges_select = $('[data-drupal-selector="edit-field-approved-badges-target-id"]');
                $('[data-drupal-selector="edit-field-approved-badges-target-id"] option:selected').prop("selected", false);
                $(".filter-chip").each(function (index,value) {
                    if($(value).attr('data-value') && $(value).attr('data-parent') == '#status') {
                        badges_select.find("option[value='"+ $(value).attr('data-value')+"']").attr("selected",true)
                    }
                })
                submit_button.click();
            });

        $('body').on('click', '.filter-chip', function (e) {
            var data_value = $(e.target).attr('data-value');
            var data_parent = $(e.target).attr('data-parent');
            var badges_select = $('[data-drupal-selector="edit-field-approved-badges-target-id"]');
            var region_select = $('[data-drupal-selector="edit-field-region-target-id"]');
            if(data_parent == "#country") {
                $(`[data-drupal-selector="edit-field-region-target-id"] option[value=${data_value}]`).attr("selected", false)
            } else if(data_parent == "#status") {
                $(`[data-drupal-selector="edit-field-approved-badges-target-id"] option[value=${data_value}]`).attr("selected", false)
            }
            submit_button.click();
        });

        $('body').on("click", ".table-sort-elem",  function (e) {
            e.preventDefault();
            var elem = $(e.target);
            var data_target = elem.attr("data-target");
            var submit_button = $("#views-exposed-form-users-page-1 :submit")

            $('[data-drupal-selector="edit-sort-by"]>option[value="' + data_target + '"]').prop('selected', true);

            if($('[data-drupal-selector="edit-sort-order"]>option[value="ASC"]').prop('selected') == true){
                $('[data-drupal-selector="edit-sort-order"]>option[value="DESC"]').prop('selected', true);
            } else {
                // elem.find(".tablesort--desc").remove();
                // elem.append("<span class=\"tablesort tablesort--asc\"></span>");
                $('[data-drupal-selector="edit-sort-order"]>option[value="ASC"]').prop('selected', true);
            }
            submit_button.click();
        });
        $('body').on("keyup", "#user-search", debounce(function(e) {
            var text = e.target.value;
            $('[data-drupal-selector="edit-combine"]').val(text);
            submit_button.click();
        },700));

        //Refresh selections after drupal re-render
        refreshSelects()

    }
};


function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};
