let report = "";
//let bilan = "";

$('#concatenation').on('click', function () {
    $('.tile').removeClass('selected-tile');
    $('#concatenation').addClass('selected-tile');
    report = "concatenation";
//    $('#bilans').html("");
    $('#report-content').html("");
    $.post("controller/selectPeriod.php", {plate: $('#plate').val()}, function (data) {
        $('#period').html(data);
    });
} );

$('#montants').on('click', function () {
/*
    $('#period').html("");
    $.post("controller/selectBilan.php", function (data) {
        $('#bilans').html(data);
    });

    
    $.post("controller/generateJsonStructure.php", {plate: $('#plate').val()}, function (data) {
        $('#period').html("");
        $('#bilans').html("");
        $('#report-content').html(data);
    });
    
} );

$(document).on("click", ".bilan", function() {

    bilan = $(this).attr('id');
*/    
    $('.tile').removeClass('selected-tile');
    $('#montants').addClass('selected-tile');
    $('#report-content').html("");
    report = "montants";
    $.post("controller/selectPeriod.php", {plate: $('#plate').val()}, function (data) {
        $('#period').html(data);
    });
} );

$(document).on("change", "#from", function() {
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
    $('#generate').html('<button type="button" class="btn but-line lockable">Générer</button>');
} );

$(document).on("click", "#generate", function() {
    $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
    $(".lockable").prop('disabled', true);
   if(report == "concatenation") {
        window.location.href = "controller/generateConcatenation.php?plate="+$('#plate').val()+"&from="+$('#from').val()+"&to="+$('#to').val();
    }
    else if(report == "montants") {
        $.post("controller/generateBilan.php", {plate: $('#plate').val(), from: $('#from').val(), to: $('#to').val()/*, bilan: bilan*/}, function (data) {
            $('#period').html("");
            $('#message').html("");
    //        $('#bilans').html("");
            $('#report-content').html(data);
        });
    }
    else {    
        $(".lockable").prop('disabled', false);
        $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
            'Pas de générateur correspondant'+
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                '<span aria-hidden="true">&times;</span>'+
            '</button>'+
        '</div>');
    }
} );

$('#download-generated').on('click', function () {
    window.location.href = "controller/download.php?type=generated";
} );

