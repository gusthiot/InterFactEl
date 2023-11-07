
$('#historique').on('click', function () {
    $.post("controller/getLogfile.php", {plate: $('#plateNum').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#destroy').on('click', function () {
    window.location.href = "controller/destroy.php?plate="+$('#plateNum').val();
} );

$('.erase').on('click', function () {
    window.location.href = "controller/erase.php?plate="+$('#plateNum').val()+"&dir="+$(this).data('dir')+"&run="+$(this).data('run');
} );

$('#launch').on('click', function () {
    let html = '<form action="controller/uploadPrepa.php" method="post" id="factform" enctype="multipart/form-data" >';
    html += '<input type="file" name="zip_file" id="zip_file" accept=".zip">';
    html += '<input type="hidden" name="plate" id="plate" value="'+$('#plateNum').val()+'">';
    html += '<input type="hidden" name="sciper" id="sciper" value="'+$('#sciperNum').val()+'">';
    html += '<input type="hidden" name="type" id="type" value="none">';
    html += '<div><button type="button" id="facturation" class="btn btn-outline-dark">Préparer facturation</button><button type="button" id="proforma" class="btn btn-outline-dark">Générer proforma</button><button type="button" id="simu" class="btn btn-outline-dark">Simuler</button></div>';
    $('#display').html(html);
} );

let prepas = null;
$('#export').on('click', function () {
    $.get("controller/getPrepas.php?plate="+$('#plateNum').val(), function (json) {
        prepas = json;
        let html = '<div>Data OUT <div><select id="exptype"><option value="SAP">SAP</option><option value="PROFORMA">PROFORMA</option></select></div>';
        html += '<div id="date">' + displayMessages('SAP') + '</div>';
        $('#display').html(html);
    });
} );

function displayMessages(type) {
    const messages = prepas[type];
    let txt = "";
    for (let i = 0; i < messages.length; i++) {
        const element = messages[i];
        if(element.type == "result") {
            txt += '<button type="button" class="prepa btn btn-outline-dark" id="' + type + '_' + i + '">' + element.msg + '</button>';
        }
        else {
            txt += '<div>' + element.msg + '</div>';
        }
    }
    return txt;
}

$(document).on("click", ".prepa", function() {
    const tab = $(this).attr("id").split('_');
    const prepa = prepas[tab[0]][tab[1]];
    window.location.href = "controller/download.php?type=prepa&plate="+$('#plateNum').val()+"&tyfact="+tab[0]+"&prepa="+JSON.stringify(prepa);
} );

$(document).on("change", "#exptype", function() {
    const html = displayMessages($('#exptype').val());
    $('#date').html(html);
} );

$('.run').on('click', function () {
    window.location.href = "prefacturation.php?"+$(this).val();
} );

function prefa() {
    const file = $('#zip_file').val();
    if(file.indexOf('.zip') > -1) {
        $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
        $("#facturation").prop('disabled', true);
        $("#proforma").prop('disabled', true);
        $("#simu").prop('disabled', true);
        $('#factform').submit();
    }
    else {
        $('#message').text('Vous devez uploader une archive zip !');
    }
}

$(document).on("click", "#facturation", function() {
    $('#type').val('SAP');
    prefa();
} );

$(document).on("click", "#proforma", function() {
    $('#type').val('PROFORMA');
    prefa();
} );
$(document).on("click", "#simu", function() {
    $('#type').val('SIMU');
    prefa();
} );