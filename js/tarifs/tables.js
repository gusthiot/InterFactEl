import * as customTableur from "../custom-tableur.js";
import * as fileTests from "../file-tests.js";
import * as tests from "./tests.js";

let messages = {};
let contents = {};
let optCsvs = {};
let pdfs = {};
let optPdfs = {}
let ids = {};
let checks = {};
let fileTest = undefined;

if(sessionStorage.getItem("contents")) {
    contents = JSON.parse(sessionStorage.getItem("contents"));
    pdfs = JSON.parse(sessionStorage.getItem("pdfs"));
    optPdfs = JSON.parse(sessionStorage.getItem("optPdfs"));
    displayFiles();
    $('#tarifs-cancel').removeClass('desactived-tile');
}

if(sessionStorage.getItem("checks")) {
    checks = JSON.parse(sessionStorage.getItem("checks"));
    displayChecks();
}

$.get("controller/getParametersJson.php", function(data){
    const json = JSON.parse(data);
    const paramtext = json.paramtext;
    messages = json.messages;
    const parameters = json.parameters;

    tests.setMandatoryCsvs(parameters);

    const tableur = new customTableur.CustomTableur(messages, parameters, paramtext, closeTable);

    fileTest = new fileTests.FileTests(messages, parameters);

    $(document).on("click", ".csv", function() {
        $('#tarifs-desktop').css("display", "none");
        const filename = $(this).attr('id');
        tableur.init(filename, "csv", contents);
        let html = "";
        if(parameters[filename].bidim) {
            const sapIds = fileTest.retrieveIds("articlesap", contents, ids);
            html = tableur.bidimTableur(sapIds);
        }
        else {
            html = tableur.unidimTableur();

        }
        $('#tarifs-manage').html(html);
        if(checks[filename] && checks[filename].errors) {
            tableur.displayErrors(checks[filename].errors);
        }
    });

    $(document).on("click", ".pdf", function() {
        $('#tarifs-desktop').css("display", "none");
        const filename = $(this).attr('id');
        tableur.init(filename, "pdf");
        let html = tableur.header();
        if(filename == "grille") {
            if(contents['plateforme'][7][2] == "OUI") {
                let title = "Ajouter la grille";
                if(optPdfs.grille) {
                    title = "Remplacer la grille";
                }
                html += uploadPdf(messages[filename + "01"], "replace-grille", titre);
            }
            else {
                html += '<div>' + messages[filename + "02"] + '</div>';
                if(optPdfs.grille) {
                    html += '<div class="center-tile">' +
                                '<div id="delete-grille" class="tile tight-tile">Effacer la grille</div>' +
                            '</div>';
                }
            }
        }
        else {
            html += uploadPdf(messages[filename + "01"], "replace-logo", "Remplacer le logo");
        }
        $('#tarifs-manage').html(html);
    });

    let saveContent = [];
    let saveIds = {};
    let saveErrors = {};
    let saveFilename = "";

    $(document).on("saved", "#tableur-table", function(event, newContent, filename) {
        if(parameters[filename].tests) {
            const results = fileTest.internalCheck(filename, newContent, contents, ids);
            if(runCheck(results.result)) {
                saveContent = newContent;
                saveIds = results.ids;
                saveErrors = results.errors;
                saveFilename = filename;
                tableur.displayErrors(results.errors);
                $("#tableur-table").trigger("error");
            }
            else {
                checks[filename] = {};
                checks[filename].errors = {};
                checks[filename].ok = false;
                removeGoodChecks();
                ids = results.ids;
                contents[filename] = newContent;
                sessionStorage.setItem("checks", JSON.stringify(checks));
                sessionStorage.setItem("contents", JSON.stringify(contents));
                closeTable();
            }
        }
        else {
            contents[filename] = newContent;
            sessionStorage.setItem("contents", JSON.stringify(contents));
            closeTable();
        }
    });

    $(document).on("save-anyway", "#tableur-table", function() {
        checks[saveFilename] = {};
        checks[saveFilename].errors = saveErrors;
        checks[saveFilename].ok = false;
        removeGoodChecks();
        ids = saveIds;
        contents[saveFilename] = saveContent;
        sessionStorage.setItem("checks", JSON.stringify(checks));
        sessionStorage.setItem("contents", JSON.stringify(contents));
        closeTable();
    });

});

function displayChecks() {
    let result = true;
    Object.keys(checks).forEach(function(filename) {
        $('#'+filename).removeClass('red-file');
        $('#'+filename).removeClass('green-file');
        if(checks[filename].errors && (Object.keys(checks[filename].errors).length > 0)) {
            $('#'+filename).addClass('red-file');
            result = false;
        }
        if(checks[filename].ok) {
            $('#'+filename).addClass('green-file');
        }
    });
    if(result) {
        $('#tarifs-load').removeClass('desactived-tile');
    }
}

export function displayFiles() {
    let filesList = '';
    Object.keys(tests.getMandatoryCsvs()).forEach(function(key) {
        filesList += '<div id="' + key + '" class="file tile csv">' + tests.getMandatoryCsvs()[key].name + "</div>";
    });
    [tests.mandatoryPdfs, tests.optionalPdfs].forEach(function(dict) {
        Object.keys(dict).forEach(function(key) {
            filesList += '<div id="' + key + '" class="file tile pdf">' + dict[key].name + "</div>";
        });
    });
    $('#message').html("");
    $('#tarifs-files').html(filesList);
    $('#tarifs-save').removeClass('desactived-tile');
    $('#tarifs-check').removeClass('desactived-tile');
}

export function firstChecks(plateforme, verify) {
    return runCheck(tests.checkMandatory(contents, pdfs)) ||
        runCheck(tests.checkAuthorized(contents, pdfs, optCsvs, optPdfs)) ||
        runCheck(tests.checkColumnsNumbers(contents)) ||
        runCheck(tests.checkPlateFact(plateforme, messages, contents, optPdfs, verify));
}

function runCheck(res) {
    if(res != "") {
        $('#message').html(res);
        return true;
    }
    return false;
}

export function checkTables() {
    const results = tests.checkColumns(fileTest, contents, pdfs, optPdfs, ids);
    checks = results.checks;
    ids = results.ids;
    sessionStorage.setItem("checks", JSON.stringify(checks));
    return runCheck(results.result);
}

export function removeContents() {
    contents = {};
}

export function saveContents() {
    sessionStorage.setItem("contents", JSON.stringify(contents));
    sessionStorage.setItem("pdfs", JSON.stringify(pdfs));
    sessionStorage.setItem("optPdfs", JSON.stringify(optPdfs));
}

export function extract(plateforme, files, check) {
    Object.keys(tests.getMandatoryCsvs()).forEach(function(filename) {
        if(Object.keys(files).includes(filename + ".csv")) {
            contents[filename] = Papa.parse(atob(files[filename + ".csv"]), {delimiter: ";", skipEmptyLines: true}).data;
        }
    });
    tests.optionalCsvs.forEach(function(filename) {
        if(Object.keys(files).includes(filename + ".csv")) {
            optCsvs[filename] = Papa.parse(atob(files[filename + ".csv"]), {delimiter: ";", skipEmptyLines: true}).data;
        }
    });
    Object.keys(tests.mandatoryPdfs).forEach(function(filename) {
        if(Object.keys(files).includes(filename + ".pdf")) {
            pdfs[filename] = files[filename + ".pdf"];
        }
    });
    Object.keys(tests.optionalPdfs).forEach(function(filename) {
        if(Object.keys(files).includes(filename + ".pdf")) {
            optPdfs[filename] = files[filename + ".pdf"];
        }
    });

    if(check && tests.firstChecks(plateforme, contents, pdfs, optCsvs, optPdfs, false)) {
        return;
    }
}

export function reset() {
    contents = {};
    optCsvs = {};
    pdfs = {};
    optPdfs = {}
    ids = {};
    checks = {};
    sessionStorage.removeItem("contents");
    sessionStorage.removeItem("pdfs");
    sessionStorage.removeItem("optPdfs");
    sessionStorage.removeItem("checks");
    $('#tarifs-files').html("");
    $('#tarifs-select').html("");
    $('#message').html("");
    $('#tarifs-load').addClass('desactived-tile');
    $('#tarifs-save').addClass('desactived-tile');
    $('#tarifs-check').addClass('desactived-tile');
    $('#tarifs-cancel').addClass('desactived-tile');
    $("#tarifs-read").removeClass('selected-tile');
    $("#tarifs-remove").removeClass('selected-tile');
    $("#tarifs-load").removeClass('selected-tile');
}

function uploadPdf(message, id, titre) {
    return '<div>' + message + '</div>' +
            '<div class="center-tile">' +
                '<input id="' + id + '" type="file" name="' + id + '" class="pdf-file" accept=".pdf">' +
                '<label class="tile tight-tile" for="' + id + '">' + titre + '</label>' +
            '</div>';
}

$(document).on("click", "#delete-grille", function() {
    delete optPdfs.grille;
    closeTable();
});

$(document).on("change", ".pdf-file", function() {
    const id = $(this).attr('id');
    const fileReader = new FileReader();
    fileReader.onload = function () {
        if(id == 'replace-logo') {
            pdfs['logo'] = fileReader.result.split(',')[1];
        }
        else {
            optPdfs['grille'] = fileReader.result.split(',')[1];
        }
    };
    fileReader.readAsDataURL($(this).prop('files')[0]);
    closeTable();
});

function closeTable() {
    $('#tarifs-desktop').css("display", "block");
    $('#tarifs-manage').html("");
    displayChecks();
}


function removeGoodChecks() {
    Object.keys(checks).forEach(function(name) {
        if(checks[name].ok) {
            checks[name].ok = false;
        }
    });
}

export function getEncFiles() {
    let files = {};
    Object.keys(contents).forEach(function(name) {
        files[name+".csv"] = btoa(Papa.unparse(contents[name], {delimiter: ";", skipEmptyLines: true}));
    });

    let categprix = [["Id-ClasseClient", "Id_Categorie", "Prix unitaire"]];
    const ccIds = fileTest.retrieveIds("classeclient", contents, ids);
    Object.keys(ccIds).forEach(function(ccKey) {
        const ccLine = contents["classeclient"][ccIds[ccKey]];
        const idBase = ccLine[8];
        Object.keys(fileTest.retrieveIds("categorie", contents, ids)).forEach(function(caKey) {
            const idBaseCateg = idBase+"_"+caKey;
            const bcLine = contents["basecateg"][fileTest.retrieveIds("basecateg", contents, ids)[idBaseCateg]];
            categprix.push([ccKey, caKey, bcLine[2]]);
        });
    });
    files["categprix.csv"] = btoa(Papa.unparse(categprix, {delimiter: ";", skipEmptyLines: true}));

    Object.keys(pdfs).forEach(function(name) {
        files[name+".pdf"] = pdfs[name];
    });
    Object.keys(optPdfs).forEach(function(name) {
        files[name+".pdf"] = optPdfs[name];
    });
    return JSON.stringify(files);
}
