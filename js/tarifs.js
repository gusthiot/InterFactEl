'use strict';

import TarifsDates from "./tables/tables-dates.js";
import Tables from "./tables/tables.js";

const plateforme = $('#container').data('plateforme');

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

const m0 = $('#tarifs-space').data('m0');
const m0Status = $('#tarifs-space').data('status');
const m0Dis = $('#tarifs-space').data('m0dis');

$("#tables-sub").html(m0Dis + " | status : " + m0Status);


const mandatoryPdfs = {"logo": {
                            name: "Logo PDF"
                        }
                    };
const optionalPdfs = {"grille": {
                            name: "Grille PDF"
                        }
                    };

let table = {};
let paramtext = {};
let messages = {};

const tarifsDates = new TarifsDates(plateforme, table);

$.get("controller/getParametersJson.php", function(data){
    const json = JSON.parse(data);
    paramtext = json.paramtext;
    messages = json.messages;
    const mandatoryCsvs = json.parameters;

    table = new Tables({
            "mandatoryCsvs": mandatoryCsvs,
            "mandatoryPdfs": mandatoryPdfs,
            "optionalPdfs": optionalPdfs,
            "messages": messages,
            "paramtext": paramtext
        });
});

$(document).on("remove", "#tables-dates", function(event, date) {
    removeTarifs(date);
});

$(document).on("apply", "#tables-dates", function(event, date) {
    applyTarifs(date);
});

$(document).on("save", "#tables-dates", function(event, date, type) {
    if(type == "replace") {
        window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
        setTimeout(() => { applyTarifs(date); }, 2000);
    }
    if(type == "remove") {
        window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
        setTimeout(() => { removeTarifs(date); }, 2000);
    }
});

function removeTarifs(date) {
    console.log(date);
    $.post("controller/suppressTarifs.php", {plate: plateforme, date: date}, function (data) {
        if(data == "ok" || data.includes("not empty")) {
            table.reset();
            window.location.href = "tarifs.php?plateforme="+plateforme;
        }
        else {
            $('#message').html(data);
        }
    });

}

function applyTarifs(date) {
    $.post("controller/applyTarifs.php", {plate: plateforme, date: date, files: getEncFiles()}, function (data) {
        if(data == "ok") {
            table.reset();
            window.location.href = "tarifs.php?plateforme="+plateforme;
        }
        else {
            $('#message').html(data);
        }
    });
}

/** Left */

$("#tables-read").on("click", function() {
    table.reset();
    $.post("controller/getReadDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        let first = 0;
        const dataParsed = JSON.parse(data);
        let readPos = parseInt(dataParsed[1]);
        if(readPos > 5) {
            first = readPos - 5;
        }
        tarifsDates.loadDates(dataParsed[0], first, readPos, "read");
        $("#tables-read").addClass('selected-tile');
    });
});

$(document).on("click", "#dates-read .clickable", function() {
    const key = $(this).data('key');
    $.post("controller/openTarifs.php", {plate: plateforme, type: key.split("-")[0], date: key.split("-")[1]}, function (data) {
        table.extract(plateforme, JSON.parse(data));
        table.saveContents();
        $('#tables-dates').html("");
        $('#tables-cancel').removeClass('desactived-tile');
        $("#tables-read").removeClass('selected-tile');
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

$("#tables-import").on("change", function(e) {
    table.reset();
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
        table.extract(plateforme, JSON.parse(json));

        if(table.importChecks(plateforme, false)) {
            table.removeContents();
        }
        else {
            table.authorizedCheck();
            table.saveContents();
            table.displayFiles();
            $('#tables-cancel').removeClass('desactived-tile');
        }
    });
});

$("#tables-create").on("click", function() {
    table.reset();
    table.emptyContents(plateforme);
    table.displayFiles();
});

/** Right */

$("#tables-load").on("click", function() {
    $('#tables-files').hide();
    $.post("controller/getLoadDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        let first = 0;
        const choices = JSON.parse(data);
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        tarifsDates.loadDates(choices, first, 0, "load");
        $("#tables-load").addClass('selected-tile');
    });
});

$("#tables-remove").on("click", function() {
    table.reset();
    $.post("controller/getRemoveDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
        let first = 0;
        const choices = JSON.parse(data);
        if(Object.keys(choices).length > 6) {
            first = Object.keys(choices).length - 6;
        }
        tarifsDates.loadDates(choices, first, 0, "remove");
        $('#tables-cancel').removeClass('desactived-tile');
        $("#tables-remove").addClass('selected-tile');
    });
});

/** Bottom */

$("#tables-cancel").on("click", function() {
    table.reset();
});

$("#tables-check").on("click", function() {
    if(!table.checkTables()) {
        $('#tables-load').removeClass('desactived-tile');
    }
});

$(document).on("click", "#tables-save", function() {
    $.post("controller/saveTarifs.php", {plate: plateforme, files: getEncFiles()}, function (data) {
        window.location.href = "controller/download.php?type=js-tarifs&name="+data+"&plate="+plateforme;
    });
});

function getEncFiles() {
    let categprix = [["Id-ClasseClient", "Id_Categorie", "Prix unitaire"]];
    const ccIds = table.retrieveIds("classeclient");
    Object.keys(ccIds).forEach(function(ccKey) {
        const ccLine = table.getContent("classeclient")[ccIds[ccKey]];
        const idBase = ccLine[8];
        Object.keys(table.retrieveIds("categorie")).forEach(function(caKey) {
            const idBaseCateg = idBase+"_"+caKey;
            const bcLine = table.getContent("basecateg")[table.retrieveIds("basecateg")[idBaseCateg]];
            categprix.push([ccKey, caKey, bcLine[2]]);
        });
    });
    return table.getEncFiles({"categprix": categprix});
}
