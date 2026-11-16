'use strict';

$('#historique-div').hide();
$('#statuts-div').hide();

$('#open-historique').on('click', function () {
    $('#plate-content').hide();
    $('#buttons').hide();
    $('#historique-div').show();
    $.post("controller/getLogfile.php", {plate: $('#plate').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#close-historique').on('click', function () {
    $('#plate-content').show();
    $('#buttons').show();
    $('#historique-div').hide();
    $('#display').html("");
} );

$('#statut-open').on('click', function () {
    $.post("controller/selectStatutsPeriod.php", {plate: $('#plate').val()}, function (data) {
        $('#plate-content').hide();
        $('#buttons').hide();
        $('#statuts-period').html(data);
        $('#statuts-period').show();
        $('#statuts-div').show();
    });
} );

$(document).on("change", "#statut-from", function() {
    const from = $(this).find(":selected").val();
    const to = $('#statut-last').find(":selected").val();
    let blank = true;
    let start = '<label for="statut-to">A</label><select id="statut-to" class="custom-select lockable">';
    let next = "";
    $(this.children).each(function()
    {
        if(parseInt($(this).val()) >= parseInt(from)) {
            next += '<option value="'+$(this).val()+'"';
            if(to && (to === $(this).val())) {
                blank = false;
                next += ' selected ';
            }
            next += '>'+$(this).text()+'</option>';
        }
    });
    if(blank) {
        start += '<option disabled selected></option>';
        $('#statut-buttons').html('');
    }
    const last = start + next + '</select>';
    $('#statut-last').html(last);
} );

$(document).on("change", "#statut-to", function() {
    const buttons = '<label for="statut-mode">Mode</label><select id="statut-mode" class="custom-select lockable">' +
            '<option value="toutes" selected>Toutes</option>' +
            '<option value="en-cours">En cours</option>' +
            '<option value="en-erreur">En erreur</option>' +
            '<option value="refusees">Refusées, Refaites, Renvoyées </option>' +
            '<option value="non-payees">Non payées</option></select>' +
            '<button type="button" id="statut-generate" class="btn but-line lockable">Valider</button>';
    $('#statut-buttons').html(buttons);
} );

$(document).on("click", "#statut-generate", function() {
    const from = $('#statut-from').find(":selected").val();
    const to = $('#statut-to').find(":selected").val();
    const mode = $('#statut-mode').find(":selected").val();
    $.post("controller/getStatuts.php", {plate: $('#plate').val(), mode: mode, from: from, to: to}, function (data) {
        $('#statuts-period').hide();
        $('#display').html(data);
    });
} );

$('#statut-close').on('click', function () {
    $('#plate-content').show();
    $('#buttons').show();
    $('#statuts-div').hide();
    $('#statuts-period').hide();
    $('#display').html("");
} );

$('#download-generated').on('click', function () {
    window.location.href = "controller/download.php?type=generated";
} );

$('.open-run').on('click', function () {
    window.location.href = "run.php?plateforme="+$('#plate').val()+"&"+$(this).val();
} );

$('#destroy').on('click', function () {
    window.location.href = "controller/destroy.php?plate="+$('#plate').val();
} );

$('#init-period').on('click', function () {
    let first = '<label for="from">De</label><select id="from" class="custom-select lockable"><option disabled selected></option>';
    const choices = $(this).data('choices');
    for(let key in choices) {
        first += '<option value="'+key+'">'+choices[key][1]+' '+choices[key][0]+'</option>';
    }
    $('#first').html(first + '</select>');
} );

$(document).on("change", "#from", function () {
    const from = $(this).find(":selected").val();
    const to = $('#last').find(":selected").val();
    let blank = true;
    let start = '<label for="to">A</label><select id="to" class="custom-select lockable">';
    let next = "";
    $(this.children).each(function()
    {
        if(parseInt($(this).val()) >= parseInt(from)) {
            next += '<option value="'+$(this).val()+'"';
            if(to && (to === $(this).val())) {
                blank = false;
                next += ' selected ';
            }
            next += '>'+$(this).text()+'</option>';
        }
    });
    if(blank) {
        start += '<option disabled selected></option>';
        $('#generate').html('');
    }
    const last = start + next + '</select>';
    $('#last').html(last);
} );

$(document).on("change", "#to", function() {
    $('#reinit').html('<button type="button" class="btn but-line lockable">Lancer</button>');
} );

$(document).on("click", "#reinit", function() {
    window.location.href = "controller/loadPeriod.php?plate="+$('#plate').val()+"&from="+$('#from').val()+"&to="+$('#to').val();
} );

$(document).on("change", ".zip-file", function () {
    const id = $(this).attr('id');
    $('#type').val(id);
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#form-fact').submit();
        if(id != "ARCHIVE") {
            $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
            $(".lockable").prop('disabled', true);
        }
    }
    else {
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                                'Vous devez uploader une archive zip !'+
                                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                    '<span aria-hidden="true">&times;</span>'+
                                '</button>'+
                            '</div>');
    }
});
