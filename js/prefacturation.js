
$('#label').on('click', function () {
    alert("label");
} );

$('#info').on('click', function () {  
    var csv = $('#dir').val() + "/info.csv";
    $.post("controls/getInfos.php", {csv: csv}, function (data) {
        $('#display').html(data);
    });
} );

$('#bills').on('click', function () {
    var csv = $('#dir').val() + "/sap.csv";
    $.post("controls/getBills.php", {csv: csv}, function (data) {
        $('#display').html(data);
    });
} );

$(document).on("click", "#getSap", function() {
    window.location.href = "controls/download.php?type=sap&dir="+$('#dir').val();
} );

$('#ticket').on('click', function () {
    window.open($('#dir').val()+"/"+"Ticket"+$('#suf').val()+".html");
} );

$('#changes').on('click', function () {
    $.post("controls/getModifs.php", {dir: $('#dir').val(), suf: $('#suf').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#invalidate').on('click', function () {
    alert("invalidate");
} );

$('#bilans').on('click', function () {
    window.location.href = "controls/download.php?type=bilans&dir="+$('#dir').val();
} );

$('#annexes').on('click', function () {
    window.location.href = "controls/download.php?type=annexes&dir="+$('#dir').val();
} );

$('#all').on('click', function () {
    window.location.href = "controls/download.php?type=all&dir="+$('#dir').val();
} );

$('#send').on('click', function () {
    alert("send");
} );

$('#finalize').on('click', function () {
    alert("finalize");
} );

$('#resend').on('click', function () {
    alert("resend");
} );
