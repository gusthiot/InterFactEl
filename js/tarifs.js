
$(function() {


    $('#month').datepicker({
            dateFormat: "mm yy"
    })
    .datepicker('setDate', new Date())
    .datepicker("option", "changeMonth", true)
    .datepicker("option", "changeYear", true)
    .datepicker("option", "showButtonPanel", true)
    .datepicker("option", "onClose", function(e){
        var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
        var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
        $(this).datepicker("setDate",new Date(year,month,1));
    });

});