
$(function() {
    let lastYear = $('#lastYear').val();
    let lastMonth = $('#lastMonth').val();
    let active = "";

    $('.param').on('click', function () {
        const id = $(this).attr('id');
        if(active != "") {
            $('#more-'+active).html("");
        }
        $('#more-'+id).html('<button type="button" id="etiquette-'+id+'" class="btn but-line etiquette">Etiquette</button>'+
                            '<button type="button" id="export-'+id+'" class="btn but-line export">Export</button>'+
                            '<div id="label-'+id+'"></div>');
        active = id;
    });

    $('#import').on('click', function () {
        $('#more').html('<input name="month-picker" id="month-picker" class="date-picker"/>'+
                        '<label class="up-but">'+
                        '<input type="file" id="zip-tarifs" name="zip_file" class="zip_file" accept=".zip">'+
                        'Importer'+
                        '</label>');
        
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
    });

    $(document).on("change", "#zip-tarifs", function () {
        const file = $('#zip-tarifs').val();
        if(file.indexOf('.zip') > -1) {
            $('#upform').submit();
            $('#message').text('');
        }
        else {
            $('#message').text('Vous devez uploader une archive zip !');
        }
    } );

    $(document).on("click", ".export", function() {
        const tab = $(this).attr('id').split("-");
        window.location.href = "controller/download.php?type=tarifs&plate="+$('#plate').val()+"&year="+tab[1]+"&month="+tab[2];
    } );

    $(document).on("click", ".etiquette", function() {
        const tab = $(this).attr('id').split("-");
        const dir = $('#plate').val()+"/"+tab[1]+"/"+tab[2];
        $.post("controller/getLabel.php", {dir: dir}, function (data) {
            $('#label-'+tab[1]+'-'+tab[2]).html(data);
        });
    } );

    $(document).on("click", "#saveLabel", function() {
        const tab = $(this).parent().parent().attr('id').split("-");
        const txt = $('#labelArea').val();
        const dir = $('#plate').val()+"/"+tab[1]+"/"+tab[2];
        $.post("controller/saveLabel.php", {txt: txt, dir: dir}, function (message) {
            window.location.href = "tarifs.php?plateforme="+$('#plate').val();
        });
    } );

});