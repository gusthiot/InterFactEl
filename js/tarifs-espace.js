import * as dates from "./tarifs-dates.js";
import * as tables from "./tarifs-tables.js";

const plateforme = $('#plate').val();
const m0 = $('#m0').val();
const m0Status = $('#status').val();

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
        tables.extract(JSON.parse(data));
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
        tables.extract(JSON.parse(json));

        if(tables.firstChecks(false)) {
            tables.removeContents();
        }
        else {
            tables.saveContents();
            tables.displayFiles();
            $('#tarifs-cancel').removeClass('desactived-tile');
        }
    });
});


/** Right */

$("#tarifs-load").on("click", function() {
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

    if(tables.firstChecks(true)) {
        return;
    }
    if(tables.checkColumns()) {
        return;
    }
    $('#tarifs-load').removeClass('desactived-tile');
});

$(document).on("click", "#tarifs-save", function() {
    console.log("to be improved");
    /*
    const enc_files = JSON.stringify(files);
    $.post("controller/saveTarifs.php", {plate: plateforme, files: enc_files}, function (data) {
        window.location.href = "controller/download.php?type=js-tarifs&name="+data+"&plate="+plateforme;
        $('#tarifs-save').addClass('desactived-tile');
    });
    */
});
