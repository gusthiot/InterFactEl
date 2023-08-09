
$('#historique').on('click', function () {
    $.get("controls/getLogfile.php?plate="+$('#plate').val(), function (data) {
        $('#display').html(data);
    });
} );

$('#launch').on('click', function () {
    var html = '<form action="controls/uploadPrepa.php" method="post" id="factform" enctype="multipart/form-data" >';
    html += '<input type="file" name="zip_file" id="zip_file" accept=".zip">';
    html += '<input type="hidden" name="plate" id="plate" value="'+$('#plate').val()+'">';
    html += '<input type="hidden" name="type" id="type" value="none">';
    html += '<div><button type="button" id="facturation" class="btn btn-outline-dark">Préparer facturation</button><button type="button" id="proforma" class="btn btn-outline-dark">Générer proforma</button><button type="button" id="simu" class="btn btn-outline-dark">Simuler</button></div>';
    $('#display').html(html);
} );

var prepas = null;
$('#export').on('click', function () {
    $.get("controls/getPrepas.php?plate="+$('#plate').val(), function (json) {
        prepas = json;
        var html = '<div>Data OUT <div><select id="exptype"><option value="sap">SAP</option><option value="proforma">PROFORMA</option></select></div>';
        html += '<div id="date">date</div>';
        html += '<button type="button" id="expdl" class="btn btn-outline-dark">Download</button>';
        $('#display').html(html);
    });
} );

$(document).on("change", "#exptype", function() {
    console.log(prepas);

} );

$(document).on("click", "#expdl", function() {
    $('#date').html(prepas.proforma.error);
} );

$('.run').on('click', function () {
    window.location.href = "prefacturation.php?"+$(this).val();
} );

function prefa() {
    var file = $('#zip_file').val();
    if(file.indexOf('.zip') > -1) {
        $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
        //$("#zip_file").prop('disabled', true);
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