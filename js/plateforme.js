
$('#historique').on('click', function () {
    $.post("controller/getLogfile.php", {plate: $('#plateNum').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('.run').on('click', function () {
    window.location.href = "prefacturation.php?"+$(this).val();
} );

$('#destroy').on('click', function () {
    window.location.href = "controller/destroy.php?plate="+$('#plateNum').val();
} );

$('.erase').on('click', function () {
    window.location.href = "controller/erase.php?plate="+$('#plateNum').val()+"&dir="+$(this).data('dir')+"&run="+$(this).data('run');
} );

$(document).on("click", ".prepare", function() {
    const type = $(this).data('type');
    let title = "";
    switch (type) {
        case 'SIMU':
            title = "Simuler";
            break;
        case 'FIRST':
            title = "Préparer 1ère facturation";
            break;
        case 'MONTH':
        case 'REDO':
            title = "Lancer préparation";
            break;
        case 'PROFORMA':
            title = "Générer";
            break;
    }     
    let html = '<form action="controller/uploadPrepa.php" method="post" id="factform" enctype="multipart/form-data" >';
    html += '<input type="file" name="zip_file" id="zip_file" accept=".zip">';
    html += '<input type="hidden" name="plate" id="plate" value="'+$('#plateNum').val()+'">';
    html += '<input type="hidden" name="sciper" id="sciper" value="'+$('#sciperNum').val()+'">';
    html += '<input type="hidden" name="type" id="type" value="'+type+'">';
    html += '<div><button type="button" id="facturation" class="btn btn-outline-dark">'+title+'</button></div>';
    $('#display').html(html);
});

$('#redo').on('click', function () {
    $('#display').html('<div><button type="button" data-type="REDO" class="btn btn-outline-dark export">Exporter</button><button type="button" data-type="REDO" class="btn btn-outline-dark prepare">Préparer Facturation</button></div>');
});

$('#month').on('click', function () {
    $('#display').html('<div><button type="button" data-type="MONTH" class="btn btn-outline-dark export">Exporter</button><button type="button" data-type="MONTH" class="btn btn-outline-dark prepare">Préparer Facturation</button></div>');
});

$('#proforma').on('click', function () {
    $('#display').html('<div><button type="button" data-type="PROFORMA" class="btn btn-outline-dark export">Exporter</button><button type="button" data-type="PROFORMA" class="btn btn-outline-dark prepare">Générer Proforma</button></div>');
});

$(document).on("click", "#facturation", function() {
    const file = $('#zip_file').val();
    if(file.indexOf('.zip') > -1) {
        $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
        $("#facturation").prop('disabled', true);
        $('#factform').submit();
    }
    else {
        $('#message').text('Vous devez uploader une archive zip !');
    }
} );

$(document).on("click", ".export", function() {
    window.location.href = "controller/download.php?type=prepa&plate="+$('#plateNum').val()+"&tyfact="+$(this).data('type');
} );
