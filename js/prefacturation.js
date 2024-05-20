const dir = $('#plate').val()+"/"+$('#year').val()+"/"+$('#month').val()+"/"+$('#version').val()+"/"+$('#run').val();
const suf = "_"+$('#name').val()+"_"+$('#year').val()+"_"+$('#month').val()+"_"+$('#version').val();


$('#label').on('click', function () {
    $.post("controller/getLabel.php", {dir: dir}, function (data) {
        $('#content').html(data);
    });
} );

$('#dl_prefa').on('click', function () {
    window.location.href = "controller/download.php?type=prefa";
} );

$('#info').on('click', function () {  
    $.post("controller/getInfos.php", {dir: dir}, function (data) {
        $('#content').html(data);
    });
} );

$('#bills').on('click', function () {
    $.post("controller/displaySap.php", {dir: dir}, function (data) {
        $('#content').html(data);
    });
} );

$(document).on("click", "#getSap", function() {
    window.location.href = "controller/download.php?type=sap&dir="+dir;
} );

$(document).on("click", "#saveLabel", function() {
    const txt = $('#labelArea').val();
    $.post("controller/saveLabel.php", {txt: txt, dir: dir}, function (message) {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
} );

$('#ticket').on('click', function () {
    window.open("ticket.php"+$(this).data('param'));
} );

$('#changes').on('click', function () {
    $.post("controller/getModifs.php", {dir: dir, suf: suf}, function (data) {
        $('#content').html(data);
    });
} );

$('#invalidate').on('click', function () {
    $.post("controller/invalidate.php", {dir: dir}, function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
} );

$('#bilans').on('click', function () {
    window.location.href = "controller/download.php?type=bilans&dir="+dir;
} );

$('#annexes').on('click', function () {
    window.location.href = "controller/download.php?type=annexes&dir="+dir;
} );

$('#all').on('click', function () {
    window.location.href = "controller/download.php?type=all&dir="+dir;
} );

$('#send').on('click', function () {
    $.post("controller/selectBills.php", {dir: dir, type: "sendBills"}, function (data) {
        $('#content').html(data);
    });
} );

$(document).on("click", "#getModif", function() {
    window.location.href = "controller/download.php?type=modif&dir="+dir+"&name=Modif-factures"+suf;
} );

$(document).on("click", "#getJournal", function() {
    window.location.href = "controller/download.php?type=modif&dir="+dir+"&name=Journal-corrections"+suf;
} );

$(document).on("click", "#getClient", function() {
    window.location.href = "controller/download.php?type=modif&dir="+dir+"&name=Clients-modifs"+suf;
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
    $.post("controller/sendBills.php", {bills: bills, type: type, plate: $('#plate').val(), year: $('#year').val(), month: $('#month').val(), version: $('#version').val(), run: $('#run').val()}, function () {
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
    $.post("controller/finalize.php", {plate: $('#plate').val(), year: $('#year').val(), month: $('#month').val(), version: $('#version').val(), run: $('#run').val()}, function () {
        window.location.href = "plateforme.php?plateforme="+$('#plate').val();
    });
} );

$('#resend').on('click', function () {
    if (confirm($(this).data('msg')) == true) {
        $.post("controller/selectBills.php", {dir: dir, type: "resendBills"}, function (data) {
            $('#content').html(data);
        });
        
    } 
} );
