
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
    $.post("controller/saveLabel.php", {txt: txt, dir: dir}, function (message) {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
} );

$('#ticket').on('click', function () {
    window.open("ticket.php"+$(this).data('param')); //$('#dir').val()+"/"+"Ticket"+$('#suf').val()+".html");
} );

$('#changes').on('click', function () {
    $.post("controller/getModifs.php", {dir: $('#dir').val(), suf: $('#suf').val()}, function (data) {
        $('#display').html(data);
    });
} );

$('#invalidate').on('click', function () {
    $.post("controller/invalidate.php", {dir: $('#dir').val()}, function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
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

$(document).on("click", "#getModif", function() {
    window.location.href = "controller/download.php?type=modif&dir="+$('#dir').val()+"&name=Modif-factures"+$('#suf').val();
} );

$(document).on("click", "#getJournal", function() {
    window.location.href = "controller/download.php?type=modif&dir="+$('#dir').val()+"&name=Journal-corrections"+$('#suf').val();
} );

$(document).on("click", "#getClient", function() {
    window.location.href = "controller/download.php?type=modif&dir="+$('#dir').val()+"&name=Clients-modifs"+$('#suf').val();
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
    $(".lockable").prop('disabled', true);
    $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
    $.post("controller/sendBills.php", {bills: bills, dir: $('#dir').val(), dirPrevMonth: $('#dirPrevMonth').val(), type: type, dirTarifs: $('#dirTarifs').val()}, function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
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
    $.post("controller/finalize.php", {dir: $('#dir').val(), dirTarifs: $('#dirTarifs').val()}, function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
} );

$('#resend').on('click', function () {
    if (confirm($(this).data('msg')) == true) {
        $.post("controller/selectBills.php", {dir: $('#dir').val(), type: "resendBills"}, function (data) {
            $('#display').html(data);
        });
        
    } 
} );
