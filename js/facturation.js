
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

$('#download-generated').on('click', function () {
    window.location.href = "controller/download.php?type=generated";
} );

$('.open-run').on('click', function () {
    window.location.href = "run.php?plateforme="+$('#plate').val()+"&"+$(this).val();
} );

$('#destroy').on('click', function () {
    window.location.href = "controller/destroy.php?plate="+$('#plate').val();
} );

$('#period').on('click', function () {
    let first = '<label for="from">De</label><select id="from" class="custom-select lockable"><option disabled selected></option>';
    const choices = $(this).data('choices');
    Object.keys(choices).forEach(function(key) {
        first += '<option value="'+key+'">'+choices[key][1]+' '+choices[key][0]+'</option>';
    });
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
            if(to) console.log(to, $(this).val());
            if(to && (to == $(this).val())) {
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
