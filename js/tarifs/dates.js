import * as tables from "./tables.js";

const plateforme = $('#container').data('plateforme');

let choices = "";
let first = 0;
let type = "";
let readPos = 0;
let date = "";

export function loadDates(ch, f, rp, t) {
    choices = ch;
    first = f;
    type = t;
    readPos = rp;
    displayDates();
}

function displayDates() {
    if(Object.keys(choices).length > 0) {
        let html = '<div>';
        html += '<svg id="dates-center" class="icon icon-selectable date-left" aria-hidden="true">' +
                    '<use xlink:href="#disc"></use>' +
                '</svg>';
        if(first > 0) {
            html += '<svg id="dates-up" class="icon icon-selectable" aria-hidden="true">' +
                        '<use xlink:href="#chevrons-up"></use>' +
                    '</svg>';
        }
        html += '<svg id="dates-remove" class="icon icon-selectable date-right" aria-hidden="true">' +
                    '<use xlink:href="#x"></use>' +
                '</svg>';
        html += '<table id="' + type + '-dates" class="dates-tarifs table table-boxed">';
        let pos = 0;
        Object.keys(choices).forEach(function(key) {
            if(pos >= first && pos < (first + 6)) {
                const choice = choices[key];
                const date = choice[0];
                const label = choice[1];
                let clickable = "clickable";
                let trClickable = "tr-clickable";
                if(choice[2] == 0) {
                    clickable = "faded";
                    trClickable = "";
                }
                let diode = "";
                if(choice[3] == 1) {
                    diode = '<svg class="icon" aria-hidden="true">' +
                                '<use xlink:href="#skip-forward"></use>' +
                            '</svg> ';
                }
                let base = "";
                if(choice[4] == 1) {
                    base = '<svg class="icon" aria-hidden="true">' +
                                '<use xlink:href="#database"></use>' +
                            '</svg> ';
                }
                let warning = "";
                if(choice[5] != "") {
                    warning = '<button aria-hidden="true" type="button" class="btn-invisible popover-warning" data-toggle="popover" data-trigger="focus"' +
                                        'data-content="' + choice[5] + '">' +
                                    '<svg class="icon icon-selectable red" aria-hidden="true">' +
                                        '<use xlink:href="#alert-triangle"></use>' +
                                    '</svg>' +
                                '</button>';
                }

                html += '<tr class="' + trClickable + '"><td>' + warning + '</td><td class="' + clickable + ' borded" data-key="' + key +'">' + diode + base + date + '</td><td class="' + clickable + ' borded" data-key="' + key +'">' + label + '</td><td></td></tr>';
            }
            pos++;
        });
        html += '</table>';
        if((first + 6) < Object.keys(choices).length) {
            html += '<svg id="dates-down" class="icon icon-selectable" aria-hidden="true">' +
                        '<use xlink:href="#chevrons-down"></use>' +
                    '</svg>';
        }
        html += '</div>';
        $('#tarifs-select').html(html);
        $('.popover-warning').popover();
    }
    else {
        $('#tarifs-select').html("Pas de données dans la période autorisée");
    }
}

$(document).on("click", "#dates-remove", function() {
    $('#tarifs-files').show();
    $('#tarifs-select').html("");
});

$(document).on("click", "#dates-down", function() {
    if(first < Object.keys(choices).length-6) {
        first += 3;
    }
    else {
        first = Object.keys(choices).length-6;
    }
    displayDates();
});

$(document).on("click", "#dates-up", function() {
    if(first > 3) {
        first -= 3;
    }
    else {
        first = 0;
    }
    displayDates();
});

$(document).on("click", "#dates-center", function() {
    if(type == "read") {
        if(readPos > 5) {
            first = readPos - 5;
        }
        else {
            first = 0;
        }
    }
    else {
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        else {
            first = 0;
        }
    }
    displayDates();
});

$(document).on("click", "#remove-dates .clickable", function() {
    const key = $(this).data('key');
    date = key.split("-")[1];
    $('#save-modal').addClass("show");
    $('#save-modal').css("display", "block");
});

$(document).on("click", "#load-dates .clickable", function() {
    const key = $(this).data('key');
    type = key.split("-")[0];
    date = key.split("-")[1];
    if(type == "replace") {
        $('#save-modal').addClass("show");
        $('#save-modal').css("display", "block");
    }
    else {
        applyTarifs(date);
    }
});

$(document).on("click", "#modal-no", function() {
    $('#save-modal').removeClass("show");
    $('#save-modal').css("display", "none");
    if(type == "replace") {
        applyTarifs(date);
    }
    if(type == "remove") {
        removeTarifs(date);
    }
});

$(document).on("click", "#modal-yes", function() {
    $('#save-modal').removeClass("show");
    $('#save-modal').css("display", "none");
    if(type == "replace") {
        window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
        setTimeout(() => {  applyTarifs(date); }, 2000);
    }
    if(type == "remove") {
        window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
        setTimeout(() => {  removeTarifs(date); }, 2000);
    }
});

$(document).on("click", "#close-modal", function() {
    $('#save-modal').removeClass("show");
    $('#save-modal').css("display", "none");
});

function removeTarifs(date) {
    $.post("controller/suppressTarifs.php", {plate: plateforme, date: date}, function (data) {
        if(data == "ok" || data.includes("not empty")) {
            tables.reset();
            window.location.href = "tarifs.php?plateforme="+plateforme;
        }
        else {
            $('#message').html(data);
        }
    });

}

function applyTarifs(date) {
    $.post("controller/applyTarifs.php", {plate: plateforme, date: date, files: tables.getEncFiles()}, function (data) {
        if(data == "ok") {
            tables.reset();
            window.location.href = "tarifs.php?plateforme="+plateforme;
        }
        else {
            $('#message').html(data);
        }
    });
}
