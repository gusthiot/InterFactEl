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

$.get("controller/getParametersJson.php", function(data){
    const json = JSON.parse(data);

    const table = new Tables({
            "mandatoryCsvs": json.parameters,
            "mandatoryPdfs": mandatoryPdfs,
            "optionalPdfs": optionalPdfs,
            "messages": json.messages,
            "paramtext": json.paramtext
        });

    const tarifsDates = new TarifsDates(plateforme, table);

    $(document).on("button-read", "#tables-desktop", function() {
        $.post("controller/getReadDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
            let first = 0;
            const dataParsed = JSON.parse(data);
            let readPos = parseInt(dataParsed[1]);
            if(readPos > 5) {
                first = readPos - 5;
            }
            tarifsDates.loadDates(dataParsed[0], first, readPos, "read");
        });
    });

    $(document).on("read", "#tables-dates", function(event, key) {
        $.post("controller/openTarifs.php", {plate: plateforme, type: key.split("-")[0], date: key.split("-")[1]}, function (data) {
            table.extract(JSON.parse(data), plateforme);
            table.saveContents();
            table.displayFiles();
        });
    });

    $(document).on("button-import", "#tables-desktop", function(event, json) {
        table.extract(JSON.parse(json), plateforme);

        if(table.columnsCheck() || table.plateFactCheck(plateforme)) {
            table.removeContents();
        }
        else {
            table.authorizedCheck();
            table.saveContents();
            table.displayFiles();
            $('#tables-cancel').removeClass('desactived-tile');
        }
    });

    $(document).on("button-create", "#tables-desktop", function() {
        table.emptyContents(plateforme);
        table.displayFiles();
    });

    $(document).on("button-load", "#tables-desktop", function() {
        $.post("controller/getLoadDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
            let first = 0;
            const choices = JSON.parse(data);
            if(Object.keys(choices).length > 6) {
                first = Object.keys(choices).length - 6;
            }
            tarifsDates.loadDates(choices, first, 0, "load");
        });
    });

    $(document).on("button-remove", "#tables-desktop", function() {
        $.post("controller/getRemoveDates.php", {plate: plateforme, m0: m0, status: m0Status}, function (data) {
            let first = 0;
            const choices = JSON.parse(data);
            if(Object.keys(choices).length > 6) {
                first = Object.keys(choices).length - 6;
            }
            tarifsDates.loadDates(choices, first, 0, "remove");
        });
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

$(document).on("button-save", "#tables-desktop", function() {
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
