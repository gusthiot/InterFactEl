
$('#label').on('click', function () {
    const dir = "../" + $('#dir').val();
    $.post("controller/getLabel.php", {dir: dir}, function (data) {
        $('#display').html(data);
    });
} );

$('#info').on('click', function () {  
    const csv = $('#dir').val() + "/info.csv";
    $.post("controller/getInfos.php", {csv: csv}, function (data) {
        $('#display').html(data);
    });
} );

$('#bills').on('click', function () {
    const csv = $('#dir').val() + "/sap.csv";
    $.post("controller/getBills.php", {csv: csv}, function (data) {
        $('#display').html(data);
    });
} );

$(document).on("click", "#getSap", function() {
    window.location.href = "controller/download.php?type=sap&dir="+$('#dir').val();
} );

$(document).on("click", "#saveLabel", function() {
    const txt = $('#labelArea').val();
    const dir = $('#dir').val();
    $.post("controller/saveLabel.php", {txt: txt, dir: dir}, function (msg) {
        if(msg == "ko") {
            $('#message').html(msg);
        }
        else {
            window.location.reload();
        }
    });
} );

$('#ticket').on('click', function () {
    window.open($('#dir').val()+"/"+"Ticket"+$('#suf').val()+".html");
} );

$('#changes').on('click', function () {
    $.post("controller/getModifs.php", {dir: $('#dir').val(), suf: $('#suf').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#invalidate').on('click', function () {
    alert("invalidate");
} );

$('#bilans').on('click', function () {
    window.location.href = "controller/download.php?type=bilans&dir="+$('#dir').val();
} );

$('#annexes').on('click', function () {
    window.location.href = "controller/download.php?type=annexes&dir="+$('#dir').val();
} );

$('#all').on('click', function () {
    window.location.href = "controller/download.php?type=all&dir="+$('#dir').val();
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
