
let lastYear = $('#last-year').val();
let lastMonth = $('#last-month').val();
let plateforme = $('#plate').val();
    
$('#month-picker').datepicker({
    dateFormat: "mm yy",
    changeMonth: true,
    changeYear: true, 
    showButtonPanel: true,
    minDate: new Date(lastYear, lastMonth),
    maxDate: '+5Y',
    onClose: function(e){
        var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
        var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
        $(this).datepicker("setDate",new Date(year,month));
    }
})
.datepicker("setDate",new Date(lastYear,lastMonth));

$(document).on("change", "#zip-tarifs", function () {
    const file = $('#zip-tarifs').val();
    if(file.indexOf('.zip') > -1) {
        $('#form-tarifs').submit();
        $('#message').text('');
    }
    else {
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                                'Vous devez uploader une archive zip !'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                    '<span aria-hidden="true">&times;</span>'+
                                '</button>'+
                            '</div>');
    }
} );

$(document).on("change", "#zip-correct", function () {
    const file = $('#zip-correct').val();
    if(file.indexOf('.zip') > -1) {
        $('#form-correct').submit();
        $('#message').text('');
    }
    else {
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                                'Vous devez uploader une archive zip !'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                    '<span aria-hidden="true">&times;</span>'+
                                '</button>'+
                            '</div>');
    }
} );

$(document).on("click", ".export", function() {
    const tab = $(this).attr('id').split("-");
    window.location.href = "controller/download.php?type=tarifs&plate="+plateforme+"&year="+tab[1]+"&month="+tab[2];
} );

$(document).on("click", ".all", function() {
    const tab = $(this).attr('id').split("-");
    const run = $(this).data("run");
    const version = $(this).data("version");
    window.location.href = "controller/download.php?type=alltarifs&plate="+plateforme+"&year="+tab[1]+"&month="+tab[2]+"&version="+version+"&run="+run;
} );

$(document).on("click", ".suppress", function() {
    const tab = $(this).attr('id').split("-");
    window.location.href = "controller/suppressTarifs.php?plate="+plateforme+"&year="+tab[1]+"&month="+tab[2];
} );

$(document).on("click", ".etiquette", function() {
    const tab = $(this).attr('id').split("-");
    $.post("controller/getLabel.php", {plate: plateforme, year: tab[1], month: tab[2]}, function (data) {
        $('#label-'+tab[1]+'-'+tab[2]).html(data);
    });
} );

$(document).on("click", "#save-label", function() {
    const tab = $(this).parent().parent().attr('id').split("-");
    const txt = $('#label-area').val();
    $.post("controller/saveLabel.php", {txt: txt, plate: plateforme, year: tab[1], month: tab[2]}, function () {
        window.location.href = "tarifs.php?plateforme="+plateforme;
    });
} );
