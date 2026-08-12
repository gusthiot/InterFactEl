import * as dates from "../tables/tables-dates.js";
import * as tables from "../tables/tables.js";

const plateforme = $('#container').data('plateforme');
const m0 = $('#tarifs-space').data('m0');
const m0Status = $('#tarifs-space').data('status');

/** LISTE */

$("#list-tab").on("click", function() {
    window.location.href = "tarifs.php?plateforme="+plateforme;
});

$(document).on("click", ".all", function() {
    const tab = $(this).attr('id').split("-");
    const run = $(this).data("run");
    const version = $(this).data("version");
    window.location.href = "controller/download.php?type=alltarifs&plate="+plateforme+"&year="+tab[1]+"&month="+tab[2]+"&version="+version+"&run="+run;
});

$(document).on("click", ".etiquette", function() {
    const tab = $(this).attr('id').split("-");
    $.post("controller/getLabel.php", {plate: plateforme, right: "tarifs", year: tab[1], month: tab[2]}, function (data) {
        $('#label-'+tab[1]+'-'+tab[2]).html(data);
    });
});

$(document).on("click", "#save-label", function() {
    const tab = $(this).parent().parent().attr('id').split("-");
    const txt = $('#label-area').val();
    $.post("controller/saveLabel.php", {txt: txt, right: "tarifs", plate: plateforme, year: tab[1], month: tab[2]}, function () {
        window.location.href = "tarifs.php?plateforme="+plateforme;
    });
});

/** ESPACE */

/** Left */

$("#tarifs-read").on("click", function() {
    tables.reset();
    $.post("controller/getReadDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        let first = 0;
        const dataParsed = JSON.parse(data);
        let readPos = parseInt(dataParsed[1]);
        if(readPos > 5) {
            first = readPos - 5;
        }
        dates.loadDates(dataParsed[0], first, readPos, "read");
        $("#tarifs-read").addClass('selected-tile');
    });
});

$(document).on("click", "#read-dates .clickable", function() {
    const key = $(this).data('key');
    $.post("controller/openTarifs.php", {plate: plateforme, type: key.split("-")[0], date: key.split("-")[1]}, function (data) {
        tables.extract(plateforme, JSON.parse(data));
        tables.saveContents();
        $('#tarifs-select').html("");
        $('#tarifs-cancel').removeClass('desactived-tile');
        $("#tarifs-read").removeClass('selected-tile');
        tables.displayFiles();
    });
});

async function blobToBase64(blob) {
  return new Promise((resolve, _) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(reader.result.split(',')[1]);
    reader.readAsDataURL(blob);
  });
}

$("#tarifs-import").on("change", function(e) {
    tables.reset();
    JSZip.loadAsync(e.target.files[0]).then(function(zip) {
        const promises = Object.keys(zip.files).map(function (fileName) {
            const file = zip.files[fileName];
            return file.async("blob").then(function (blob) {
                return blobToBase64(blob).then(function (result) {
                    return [
                        fileName,
                        result
                    ];
                });
            });
        });
        return Promise.all(promises);
    }).then(function (results) {
        let json = " {";
        let isFirst = 1;
        results.forEach(function(result) {
            if(isFirst == 1) {
                isFirst = 0;
            }
            else {
                json += ",";
            }
            json += '"'+result[0]+'":"'+result[1]+'"';
        });
        json += "}";
        tables.extract(plateforme, JSON.parse(json));

        if(tables.importChecks(plateforme, false)) {
            tables.removeContents();
        }
        else {
            tables.authorizedCheck();
            tables.saveContents();
            tables.displayFiles();
            $('#tarifs-cancel').removeClass('desactived-tile');
        }
    });
});

$("#tarifs-create").on("click", function() {
    tables.reset();
    tables.emptyContents(plateforme);
    tables.displayFiles();
});

/** Right */

$("#tarifs-load").on("click", function() {
    $('#tarifs-files').hide();
    $.post("controller/getLoadDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        let first = 0;
        const choices = JSON.parse(data);
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        dates.loadDates(choices, first, 0, "load");
        $("#tarifs-load").addClass('selected-tile');
    });
});

$("#tarifs-remove").on("click", function() {
    tables.reset();
    $.post("controller/getRemoveDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        let first = 0;
        const choices = JSON.parse(data);
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        dates.loadDates(choices, first, 0, "remove");
        $('#tarifs-cancel').removeClass('desactived-tile');
        $("#tarifs-remove").addClass('selected-tile');
    });
});

/** Bottom */

$("#tarifs-cancel").on("click", function() {
    tables.reset();
});

$("#tarifs-check").on("click", function() {
    if(!tables.checkTables()) {
        $('#tarifs-load').removeClass('desactived-tile');
    }
});

$(document).on("click", "#tarifs-save", function() {
    $.post("controller/saveTarifs.php", {plate: plateforme, files: tables.getEncFiles()}, function (data) {
        window.location.href = "controller/download.php?type=js-tarifs&name="+data+"&plate="+plateforme;
    });
});
