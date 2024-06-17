
$(function() {
    let lastYear = $('#lastYear').val();
    let lastMonth = $('#lastMonth').val();
    let plateforme = $('#plate').val();
    let active = "";
    let keep = "";

    $(document).on("click", ".param", function () {
        const id = $(this).attr('id');
        const moment = $(this).data("moment");
        const run = $(this).data("run");
        const version = $(this).data("version");
        let more = "";
        if(run > 0) {
            more += '<button type="button" id="all-'+id+'" data-run="'+run+'" data-version="'+version+'" class="btn but-line all">Exporter tout</button>';
        }
        if(moment == 1) {
            more += '<label class="btn but-line">'+
                        '<form action="controller/uploadTarifs.php" method="post" id="corform" enctype="multipart/form-data" >'+
                            '<input type="hidden" name="plate" id="plate" value="'+plateforme+'" />'+
                            '<input type="hidden" name="type" value="correct" />'+
                            '<input type="file" id="zip-correct" name="zip_file" class="zip_file" accept=".zip">'+
                        '</form>'+
                    'Corriger</label>';
        }
        if(moment == 2) {
            more += '<button type="button" id="suppress-'+id+'" class="btn but-line suppress">Supprimer</button>';
        }
        if(active != "") {
            $('#cell-'+active).html(keep);
        }
        keep = $('#cell-'+id).html();
        $('#cell-'+id).html('<button type="button" id="etiquette-'+id+'" class="btn but-line etiquette">Etiquette</button>'+
                            '<button type="button" id="export-'+id+'" class="btn but-line export">Exporter</button>'+ more +
                            '<div id="label-'+id+'"></div>');
        active = id;
    });
        
    $('#month-picker').datepicker({
        dateFormat: "mm yy",
        changeMonth: true,
        changeYear: true, 
        showButtonPanel: true,
        minDate: new Date(lastYear, lastMonth, 1),
        maxDate: '+5Y',
        onClose: function(e){
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker("setDate",new Date(year,month,1));
        }
    })
    .datepicker("setDate",new Date(lastYear,lastMonth,1));

    $(document).on("change", "#zip-tarifs", function () {
        const file = $('#zip-tarifs').val();
        if(file.indexOf('.zip') > -1) {
            $('#upform').submit();
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
            $('#corform').submit();
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

    $(document).on("click", "#saveLabel", function() {
        const tab = $(this).parent().parent().attr('id').split("-");
        const txt = $('#labelArea').val();
        $.post("controller/saveLabel.php", {txt: txt, plate: plateforme, year: tab[1], month: tab[2]}, function () {
            window.location.href = "tarifs.php?plateforme="+plateforme;
        });
    } );

});
