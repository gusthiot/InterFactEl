
$('#historique').on('click', function () {
    $('#arbo').hide();
    $('#buttons').hide();
    $('#histo').show();
    $.post("controller/getLogfile.php", {plate: $('#plate').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#close-histo').on('click', function () {
    $('#arbo').show();
    $('#buttons').show();
    $('#histo').hide();
    $('#display').html("");
} );

$('.run').on('click', function () {
    window.location.href = "prefacturation.php?"+$(this).val();
} );

$('#destroy').on('click', function () {
    window.location.href = "controller/destroy.php?plate="+$('#plate').val();
} );

$('.erase').on('click', function () {
    window.location.href = "controller/erase.php?plate="+$('#plate').val()+"&dir="+$(this).data('dir')+"&run="+$(this).data('run');
} );

$('#redo').on('click', function () {
    $('#display').html(uploader("REDO", "Préparer Facturation"));
});

$('#month').on('click', function () {
    $('#display').html(uploader("MONTH", "Préparer Facturation"));
});

$('#proforma').on('click', function () {
    $('#display').html(uploader("PROFORMA", "Générer Proforma"));
});

function uploader(type, title) {
    let html = '<div><button type="button" data-type="'+type+'" class="btn but-line export lockable">Exporter</button>';
    html += '<input type="file" id="'+type+'" name="zip_file" class="zip_file lockable" accept=".zip">';
    html += '<label class="up-but" for="'+type+'">';
    html += title;
    html += '</label></div>';
    return html;
}

$(document).on("change", ".zip_file", function () {
    const id = $(this).attr('id');
    $('#type').val(id);
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#factform').submit();
        $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
        $(".lockable").prop('disabled', true);
    }
    else {
        $('#message').text('Vous devez uploader une archive zip !');
    }
});


$(document).on("click", ".export", function() {
    window.location.href = "controller/download.php?type=prepa&plate="+$('#plate').val()+"&tyfact="+$(this).data('type');
} );

$('#tarifs').on('click', function () {
    window.location.href = "tarifs.php?plateforme="+$('#plate').val();
} );
