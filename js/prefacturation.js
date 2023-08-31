
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
    $.post("controller/saveLabel.php", {txt: txt, dir: dir}, function (data) {
        window.location.href = window.location.href + "&message=" + data;
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
    $.post("controller/invalidate.php", {dir: $('#dir').val()}, function (data) {
        window.location.href = window.location.href + "&message=" + data;
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
    $.post("controller/selectBills.php", {dir: $('#dir').val(), type: "sendBills"}, function (data) {
        $('#display').html(data);
    });
} );

$(document).on("click", "#sendBills", function() {
    sending("Envoi dans SAP");
} );

$(document).on("click", "#resendBills", function() {
    sending("Renvoi dans SAP");
} );

function sending(type) {
    let bills = [];
    $.each($("input[name='bills']:checked"), function(){
        bills.push($(this).val());
    });
    $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
    $.post("controller/sendBills.php", {bills: bills, dir: $('#dir').val(), type: type}, function (data) {
        window.location.href = window.location.href + "&message=" + data;
    });
}

let all = false;
$(document).on("click", "#allBills", function() {
    if(all) {
        $('#allBills').text("Tout sélectionner");
        $.each($("input[name='bills']"), function(){
            $(this).prop('checked', false);
        });
        all = false;
    }
    else {
        $('#allBills').text("Tout désélectionner");
        $.each($("input[name='bills']"), function(){
            $(this).prop('checked', true);
        });
        all = true;
    }
});

$('#finalize').on('click', function () {
    $.post("controller/finalize.php", {dir: $('#dir').val()}, function (data) {
        window.location.href = window.location.href + "&message=" + data;
    });
} );

$('#resend').on('click', function () {
    if (confirm($(this).data('msg')) == true) {
        $.post("controller/selectBills.php", {dir: $('#dir').val(), type: "resendBills"}, function (data) {
            $('#display').html(data);
        });
        
    } 
} );
