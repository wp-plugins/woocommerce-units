jQuery(function ($) {
    $(".area_needed").on("keyup", function () {
        var area_needed = $(this).val(); // user input sq m
        var product_actual = $(this).parent().find('input:hidden').val(); // actual sq m per unit
        var waste = $(this).parents("tr").find('#waste').val(); // waste
        check = $("#waste").is(":checked");

        if (check) {
            area_waste = parseFloat((area_needed / 100) * waste);

            area_needed = area_waste + parseFloat(area_needed);

            product_quantity = Math.ceil(area_needed / product_actual);

        } else {
        var product_quantity = Math.ceil(area_needed / product_actual);
        }

        $("input.qty").val(product_quantity);

    });
});