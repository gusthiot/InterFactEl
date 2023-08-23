
$('#label').on('click', function () {
    $.post("controller/getLabel.php", {dir: $('#dir').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#info').on('click', function () {  
    $.post("controller/getInfos.php", {dir: $('#dir').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#bills').on('click', function () {
    $.post("controller/displaySap.php", {dir: $('#dir').val()}, function (data) {
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
    $.get("controller/invalidate.php?dir="+$('#dir').val(), function (data) {
        $('#display').html(data);
    });
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
    $.post("controller/selectBills.php", {dir: $('#dir').val()}, function (data) {
        $('#display').html(data);
    });
} );

$(document).on("click", "#sendBills", function() {
    let bills = [];
    $.each($("input[name='bills']:checked"), function(){
        bills.push($(this).val());
    });
    $.post("controller/sendBills.php", {bills: bills, dir: $('#dir').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#finalize').on('click', function () {
    alert("finalize");
} );

$('#resend').on('click', function () {
    alert("resend");
} );
