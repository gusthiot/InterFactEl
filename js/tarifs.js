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
                            name: "Grille PDF",
                            test: {origin: "plateforme", row: 7, col: 2, msg_yes: "01", msg_no: "02"}
                        }
                    };

$.get("controller/getParametresJson.php", function(data){
    const json = JSON.parse(data);
    const paramtext = json.paramtext;
    const messages = json.messages;
    const parametres = json.parametres;

    const table = new Tables("parametres", "", messages, paramtext, {
                                "mandatoryCsvs": parametres,
                                "mandatoryPdfs": mandatoryPdfs,
                                "optionalPdfs": optionalPdfs
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

    $(document).on("read", "#tables-dates", function(evt, key) {
        $.post("controller/openTarifs.php", {plate: plateforme, type: key.split("-")[0], date: key.split("-")[1]}, function (data) {
            table.extract(JSON.parse(data), plateforme);
            table.saveContents();
            table.displayFiles();
        });
    });

    $(document).on("button-import", "#tables-desktop", function(evt, json) {
        const files = JSON.parse(json);
        const ac = table.authorizedCheck(files);
        table.extract(files);
        const cc = table.columnsCheck();
        const pfc = table.plateFactCheck(files, plateforme);
        if((cc != "") || (pfc != "")) {
            $('#tables-message').html(cc + "<br />" + pfc + "<br />" + ac);
            table.contents = {};
        }
        else {
            if(!Object.keys(files).includes("plateforme.csv")) {
                table.contents["plateforme"] = formatOne("plateforme");
                table.contents["plateforme"][0][2] = plateforme;
                table.contents["plateforme"][7][2] = "NON";
            }
            else {
                table.contents["plateforme"] = formatOne("plateforme", false, table.contents["plateforme"]);
            }
            if(!Object.keys(files).includes("paramfact.csv")) {
                table.contents["paramfact"] = formatOne("paramfact");
            }
            else {
                table.contents["paramfact"] = formatOne("paramfact", false, table.contents["paramfact"]);

            }
            table.saveContents();
            table.displayFiles();
            $('#tables-message').html('Importation réussie<br/>' + ac);
            $('#tables-cancel').removeClass('desactived-tile');
        }
    });

    function formatOne(filename, empty=true, content=[]) {
        let lines = [];
        for(let numRow = 0; numRow < parametres[filename].labels.length; numRow++) {
            const label = parametres[filename].labels[numRow];
            let line = [label, paramtext[filename + "-" + label]];
            for(let numCol = 2; numCol < parametres[filename].numcol; numCol++) {
                if(empty) {
                    line.push("");
                }
                else {
                    line.push(content[numRow][numCol-1]);
                }
            }
            lines.push(line);
        }
        return lines;
    }

    $(document).on("button-create", "#tables-desktop", function() {
        table.emptyContents();
        for(let filename of ["plateforme", "paramfact"]) {
            table.contents[filename] = formatOne(filename);
        }
        table.contents["plateforme"][0][2] = plateforme;
        table.contents["plateforme"][7][2] = "NON";
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



    $(document).on("remove", "#tables-dates", function(event, date) {
        removeTarifs(date);
    });

    $(document).on("apply", "#tables-dates", function(event, date) {
        applyTarifs(date);
    });

    $(document).on("save", "#tables-dates", function(event, date, type) {
        if(type === "replace") {
            window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
            setTimeout(() => { applyTarifs(date); }, 2000);
        }
        if(type === "remove") {
            window.location.href = "controller/download.php?type=zip-tarifs&date="+date+"&plate="+plateforme;
            setTimeout(() => { removeTarifs(date); }, 2000);
        }
    });

    function removeTarifs(date) {
        $.post("controller/suppressTarifs.php", {plate: plateforme, date: date}, function (data) {
            if(data === "ok" || data.includes("not empty")) {
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
            if(data === "ok") {
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
        let categprix = [];
        let titles = [];
        titles.push(paramtext["table-categprix-0"]);
        titles.push(paramtext["table-categprix-1"]);
        titles.push(paramtext["table-categprix-2"]);
        categprix.push(titles);
        const ccIds = table.retrieveIds("classeclient");
        for(let ccKey in ccIds) {
            const ccLine = table.contents["classeclient"][ccIds[ccKey]];
            const idBase = ccLine[8];
            for(let caKey in table.retrieveIds("categorie")) {
                let price = 0;
                if(idBase != "0") {
                    const idBaseCateg = idBase+"_"+caKey;
                    const bcLine = table.contents["basecateg"][table.retrieveIds("basecateg")[idBaseCateg]];
                    price = bcLine[2];
                }
                categprix.push([ccKey, caKey, price]);
            }
        }
        let plateContent = [];
        for(let line of table.contents["plateforme"]) {
            plateContent.push([line[0], line[2]]);
        }
        let paramfact = [];
        for(let line of table.contents["paramfact"]) {
            paramfact.push([line[0], line[2], line[3]]);
        }
        return table.getEncFiles({categprix: categprix, plateforme: plateContent, paramfact: paramfact});
    }

});
