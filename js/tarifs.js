
//let lastYear = $('#last-year').val();
//let lastMonth = $('#last-month').val();
let plateforme = $('#plate').val();
/*    
$('#month-picker').datepicker({
    dateFormat: "mm yy",
    changeMonth: true,
    changeYear: true, 
    showButtonPanel: true,
    minDate: new Date(lastYear, lastMonth - 1),
    maxDate: '+5Y',
    onClose: function(e){
        var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
        var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
        $(this).datepicker("setDate",new Date(year,month));
    }
})
.datepicker("setDate",new Date(lastYear,lastMonth - 1));
*/
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
/*
$(document).on("click", ".export", function() {
    const tab = $(this).attr('id').split("-");
    window.location.href = "controller/download.php?type=tarifs&plate="+plateforme+"&year="+tab[1]+"&month="+tab[2];
} );
*/
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
    $.post("controller/getLabel.php", {plate: plateforme, right: "tarifs", year: tab[1], month: tab[2]}, function (data) {
        $('#label-'+tab[1]+'-'+tab[2]).html(data);
    });
} );

$(document).on("click", "#save-label", function() {
    const tab = $(this).parent().parent().attr('id').split("-");
    const txt = $('#label-area').val();
    $.post("controller/saveLabel.php", {txt: txt, right: "tarifs", plate: plateforme, year: tab[1], month: tab[2]}, function () {
        window.location.href = "tarifs.php?plateforme="+plateforme;
    });
} );

$("#tarifs-read").on("click", function() {
    $.post("controller/getReadableDates.php", {plate: plateforme}, function (data) {
        $('#tarifs-select').html(data);
    });
});

$("#tarifs-load").on("click", function() {

});
$("#tarifs-check").on("click", function() {

});
$("#tarifs-correct").on("click", function() {

});

$("#tarifs-import").on("change", function(e) {
    const zip = new JSZip();
    zip.loadAsync( this.files[0])
        .then(function(zip) {
            console.log(zip);
            const files = zip.files;
            $('#tarifs-select').html("");
            let filesList = "";
            Object.keys(files).forEach(function(key) {
                filesList += key + "<br />";
            });
            $('#tarifs-files').html(filesList);
        }, function() {
            alert("Not a valid zip file")
        }); 
});


$(document).on("change", "#tarifs-month", function() {
    $('#tarifs-open').html('<button type="button" class="btn but-line lockable">Ouvrir</button>');
});

$(document).on("click", "#tarifs-open", function() {
    $.post("controller/openParameters.php", {plate: plateforme, date: $('#tarifs-dates').val()}, function (data) {
        const files = JSON.parse(data);
        $('#tarifs-select').html("");
        let filesList = "";
        Object.keys(files).forEach(function(key) {
            filesList += key + "<br />";
        });
        $('#tarifs-files').html(filesList);
    });
});
