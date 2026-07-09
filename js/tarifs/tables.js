/* TODO
    - apply/remove tarifs
*/
import * as inputs from "../custom-inputs.js";
import * as tests from "./tests.js";

const paramtext = JSON.parse($('#paramtext').val());

let contents = {};
let optCsvs = {};
let pdfs = {};
let optPdfs = {}
let ids = {};
let checks = {};

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

function displayChecks() {
    let result = true;
    Object.keys(checks).forEach(function(filename) {
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
    Object.keys(tests.mandatoryCsvs).forEach(function(key) {
        filesList += '<div id="' + key + '" class="file tile csv">' + tests.mandatoryCsvs[key].name + "</div>";
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

export function firstChecks(verify) {
    return runCheck(tests.checkMandatory(contents, pdfs)) ||
        runCheck(tests.checkAuthorized(contents, pdfs, optCsvs, optPdfs)) ||
        runCheck(tests.checkColumnsNumbers(contents)) ||
        runCheck(tests.checkPlateFact(contents, optPdfs, verify));
}

function runCheck(res) {
    if(res != "") {
        $('#message').html(res);
        return true;
    }
    return false;
}

export function checkTables() {
    const results = tests.checkColumns(contents, pdfs, optPdfs, ids);
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

export function extract(files, check) {
    Object.keys(tests.mandatoryCsvs).forEach(function(filename) {
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

    if(check && tests.firstChecks(contents, pdfs, optCsvs, optPdfs, false)) {
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

$(document).on("click", ".pdf", function() {
    $('#tarifs-desktop').css("display", "none");
    $('#tarifs-files').html("");
    const id = $(this).attr('id');
    let html = '<div id="param-header"><svg id="param-info" data-id="' + id + '" class="icon icon-selectable date-left" aria-hidden="true">' +
                        '<use xlink:href="#info"></use>' +
                    '</svg>';
    html += '<svg class="icon icon-selectable date-right manage-remove" aria-hidden="true">' +
                    '<use xlink:href="#x"></use>' +
                '</svg></div>';
    if(id == "grille") {
        if(contents['plateforme'][7][2] == "OUI") {
            html += '<div>' + tests.messages[id + "01"] + '</div>';
            let title = "Ajouter";
            if(optPdfs.grille) {
                title = "Remplacer";
            }
            html += '<div class="center-tile">' +
                        '<input id="replace-grille" type="file" name="replace-grille" class="pdf-file" accept=".pdf">' +
                        '<label class="tile tight-tile" for="replace-grille">' + title + ' la grille</label>' +
                    '</div>';
        }
        else {
            html += '<div>' + tests.messages[id + "02"] + '</div>';
            if(optPdfs.grille) {
                html += '<div class="center-tile">' +
                            '<div id="delete-grille" class="tile tight-tile">Effacer la grille</div>' +
                        '</div>';
            }
        }
    }
    else {
        html += '<div>' + tests.messages[id + "01"] + '</div>';
        html += '<div class="center-tile">' +
                    '<input id="replace-logo" type="file" name="replace-logo" class="pdf-file" accept=".pdf">' +
                    '<label class="tile tight-tile" for="replace-logo">Remplacer le logo</label>' +
                '</div>';
    }
    $('#tarifs-manage').html(html);
});

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
    displayFiles();
    displayChecks();
}

$(document).on("click", ".csv", function() {
    $('#tarifs-desktop').css("display", "none");
    $('#tarifs-files').html("");
    const id = $(this).attr('id');
    const parameters = tests.mandatoryCsvs[id];
    let html = '<div id="param-header"><svg id="param-info" data-id="' + id + '" class="icon icon-selectable date-left" aria-hidden="true">' +
                        '<use xlink:href="#info"></use>' +
                    '</svg>';
    html += '<svg class="icon icon-selectable date-right manage-remove" aria-hidden="true">' +
                    '<use xlink:href="#x"></use>' +
                '</svg></div>';
    html += '<div id="param-table"><form id="csv-form"><table class="table" data-id="' + id + '" id="table-params">';
    if(parameters.bidim) {
        const dim0 = contents[parameters.columns[0].origin];
        const dim1 = contents[parameters.columns[1].origin];
        html += '<tr><th></th><th></th><th colspan="' + (dim1.length-1) + '">' + paramtext["table-"+id+"-"+1] + '</th></tr>';
        html += '<tr id="dim1"><td class="border-around-no"></td><td class="border-around-no"></td>';
        let num = 0;
        for(const key1 in dim1) {
            if(num > 0) {
                let line = dim1[key1];
                if(id == "coeffprestation") {
                    if(line[3] != "OUI") {
                        num++;
                        continue;
                    }
                }
                const positions = parameters.bidim[0].intitule;
                let line1 = "";
                if(positions[0] == "codeD") {
                    const idSap = dim1[key1][2];
                    line1 = contents["articlesap"][tests.retrieveIds("articlesap", contents, ids)[idSap]][2];
                }
                else {
                    line1 = line[positions[0]];
                }

                html += '<td class="border-around-black cell" data-id="' + line[0] + '">' + line1 + " - " + line[positions[1]] + '</td>';
            }
            num++;
        };
        html += '</tr>';
        num = 0
        for(const key0 in dim0) {
            if(num > 0) {
                html += '<tr class="values">';
                if(num == 1) {
                    html += '<th rowspan="' + (dim0.length-1) + '" class="vert-th">' + paramtext["table-"+id+"-"+0] + '</th>';
                }
                const positions = parameters.bidim[1].intitule;
                let line = dim0[key0];
                let intitule = line[positions[0]];
                if(positions.length > 1) {
                    intitule += " - " + line[positions[1]];
                }
                html += '<td class="border-around-black dim0" data-id="' + line[0] + '">' + intitule + '</td>';
                let num1 = 0;
                for(const key1 in dim1) {
                    if(num1 > 0) {
                        let prestLine = dim1[key1];
                        if(id == "coeffprestation") {
                            if(prestLine[3] != "OUI") {
                                num1++;
                                continue;
                            }
                        }
                        let value = "";
                        for(const line of contents[id]) {
                            if((line[0] == dim0[key0][0]) && (line[1] == dim1[key1][0])) {
                                value = line[2];
                                break;
                            }
                        };
                        html += '<td class="border-around cell">' + inputs.number(value, parameters.columns[2]) + '</td>';
                    }
                    num1++;
                };
                html += '</tr>';
            }
            num++;
        };
    }
    else {
        html += '<tr>';
        for (let i = 0; i < parameters.numcol; i++) {
            html += '<th>' + paramtext["table-"+id+"-"+i] + '</th>';
        }
        html += '</tr>';
        let num = 0;
        contents[id].forEach(function(line) {
            if(["paramfact", "plateforme"].includes(id) ||(num > 0)) {
                html += '<tr class="values" id="line-' + num + '">';
                let num1 = 0;
                line.forEach(function(cell) {
                    let paramCol = parameters.columns[num1];
                    let color = "";
                    let data = "";
                    if(checks[id] && checks[id]["errors"] && checks[id]["errors"]["row-"+num] && checks[id]["errors"]["row-"+num]["col-"+num1]) {
                        color = "background-red";
                        data = 'data-msg="' + checks[id]["errors"]["row-"+num]["col-"+num1] + '"';
                    }
                    html += '<td class="border-around ' + color + ' cell" ' + data + '>';
                    if(paramCol.type == "specific") {
                        paramCol = paramCol.lines[num];
                    }
                    html += inputs.input(paramCol, cell, num);
                    html += '</td>';
                    num1++;
                });
                if(parameters.tools) {
                    html += '<td class="border-around td-tools">';
                    if(num > 1) {
                        html += inputs.lineUp();
                    }
                    if(num < (contents[id].length-1)) {
                        html += inputs.lineDown();
                    }
                    html += inputs.lineRemove() + '</td>';
                }
                html += '</tr>';
            }
            num++;
        });
        if(parameters.tools) {
            html += '<tr><td class="border-around left" colspan="' + (parameters.numcol + 1) + '">' +
                    '<svg id="line-plus" class="icon icon-selectable" aria-hidden="true">' +
                        '<use xlink:href="#plus"></use>' +
                    '</svg></td></tr>';
        }
    }
    html += '</table></form></div>';
    html += '<div class="center-tile"><div class="tile tight-tile manage-remove">Annuler</div><div id="param-save" class="tile tight-tile">Enregistrer les modifications</div></div>';
    $('#tarifs-manage').html(html);
});

$(document).on('input propertychange', '.param-input', function() {
    document.forms["csv-form"].reportValidity();
});

$(document).on("click", ".background-red", function() {
    $('#message').html($(this).data('msg'));
});

$(document).on("click", ".manage-remove", function() {
    closeTable();
});

$(document).on("click", "#line-plus", function() {
    const table = $(this).closest('table');
    const name = table.data('id');
    const tr = $(this).closest('tr').prev();
    let num = 1;
    if(tr.hasClass('values')) {
        const tab = tr.attr('id').split("-");
        num = parseInt(tab[1]) + 1;
    }
    let html = '<tr class="values" id="line-' + num + '">';
    let num1 = 0;
    const parameters = tests.mandatoryCsvs[name];
    parameters.columns.forEach(function(paramCol) {
        let cell = "";
        html += '<td class="border-around cell">';
        html += inputs.input(paramCol, cell, num);
        html += '</td>';
        num1++;
    });
    html += '<td class="border-around td-tools">';
    if(tr.hasClass('values')) {
        html += inputs.lineUp();
        let tools = "";
        if(tr.prev().hasClass('values')) {
            tools += inputs.lineUp();
        }
        tools += inputs.lineDown() + inputs.lineRemove();
        tr.find('.td-tools').html(tools);
    }
    html += inputs.lineRemove() + '</td></tr>';
    tr.after(html);
});

let newContent = [];
let newIds = {};
let newErrors = {};

$(document).on("click", "#param-save", function() {
    const name = $('#table-params').data('id');
    const parameters = tests.mandatoryCsvs[name];
    newContent = [];
    if(!["paramfact", "plateforme"].includes(name)) {
        let titles = [];
        for(let i = 0; i < parameters.numcol; i++) {
            titles[i] = paramtext["table-"+name+"-"+i];
        }
        newContent[0] = titles;
    }
    const lines = $('.values');
    if(parameters.bidim) {
        const dim1 = $('#dim1').find('.cell');
        let num = 1;
        for(let i = 0; i < lines.length; i++) {
            const cells = $(lines[i]).find('.cell');
            for(let j = 0; j < cells.length; j++) {
                const input = $(cells[j]).find('input');
                if($(input).val() !== "") {
                    let line = [];
                    line[0] = $(lines[i]).find('.dim0').data('id').toString();
                    line[1] = $(dim1[j]).data('id').toString();
                    line[2] = $(input).val();
                    newContent.push(line);
                    num++;
                }
            }
        }
    }
    else {
        for(let i = 0; i < lines.length; i++) {
            let line = [];
            const cells = $(lines[i]).find('.cell');
            for(let j = 0; j < cells.length; j++) {
                const input = $(cells[j]).find('input');
                if(input.length > 0) {
                    line[j] = $(input).val();
                    continue;
                }
                const select = $(cells[j]).find('select');
                if(select.length > 0) {
                    const selected = $(select).find('option:selected');
                    line[j] = $(selected).attr('value');
                    continue;
                }
                line[j]= $(cells[j]).html();
            }
            newContent.push(line);
        }
    }
    if(tests.mandatoryCsvs[name].tests) {
        const results = tests.internalCheck(name, newContent, contents, ids);
        if(runCheck(results.result)) {
            newIds = results.ids;
            newErrors = results.errors;
            $('#error-modal').addClass("show");
            $('#error-modal').data("name", name);
            $('#error-modal').css("display", "block");
        }
        else {
            checks[name] = {};
            checks[name].errors = {};
            checks[name].ok = false;
            removeGoodChecks();
            ids = results.ids;
            contents[name] = newContent;
            closeTable();
        }
    }
    else {
        contents[name] = newContent;
        closeTable();
    }
});

function removeGoodChecks() {
    Object.keys(checks).forEach(function(name) {
        if(checks[name].ok) {
            checks[name].ok = false;
        }
    });
    sessionStorage.removeItem("checks");
}

$(document).on("click", "#cancel-modal", function() {
    $('#error-modal').removeClass("show");
    $('#error-modal').css("display", "none");
});

$(document).on("click", "#modal-correct", function() {
    $('#error-modal').removeClass("show");
    $('#error-modal').css("display", "none");

});

$(document).on("click", "#modal-save", function() {
    $('#error-modal').removeClass("show");
    $('#error-modal').css("display", "none");
    const name = $('#error-modal').data("name");
    checks[name] = {};
    checks[name].errors = newErrors;
    checks[name].ok = false;
    removeGoodChecks();
    ids = newIds;
    contents[name] = newContent;
    closeTable();
});

$(document).on("input", ".param-input", function() {
    $(this).attr('value',$(this).val());
});

$(document).on("input", ".param-select", function() {
    const oldSelected = $(this).find('option[selected]');
    oldSelected.removeAttr('selected');
    const newSelected = $(this).find('option[value="' + $(this).find(":selected").val() + '"]');
    newSelected.attr('selected', true);
});

$(document).on("click", "#line-up", function() {
    const tr = $(this).closest('tr');
    const cont = tr.html();
    const tools = tr.find('.td-tools').html();
    const numLine = tr.find('.num-line').html();
    const cont1 = tr.prev().html();
    const tools1 = tr.prev().find('.td-tools').html();
    const numLine1 = tr.prev().find('.num-line').html();
    tr.html(cont1);
    tr.prev().html(cont);
    tr.find('.td-tools').html(tools);
    tr.prev().find('.td-tools').html(tools1);
    if(numLine && numLine1) {
        tr.find('.num-line').html(numLine);
        tr.prev().find('.num-line').html(numLine1);
    }
});

$(document).on("click", "#line-down", function() {
    const tr = $(this).closest('tr');
    const cont = tr.html();
    const tools = tr.find('.td-tools').html();
    const numLine = tr.find('.num-line').html();
    const cont1 = tr.next().html();
    const tools1 = tr.next().find('.td-tools').html();
    const numLine1 = tr.next().find('.num-line').html();
    tr.html(cont1);
    tr.next().html(cont);
    tr.find('.td-tools').html(tools);
    tr.next().find('.td-tools').html(tools1);
    if(numLine && numLine1) {
        tr.find('.num-line').html(numLine);
        tr.next().find('.num-line').html(numLine1);
    }
});

$(document).on("click", "#line-remove", function() {
    const tr = $(this).closest('tr');
    nextTr(tr, 0);
});

function nextTr(tr, level) {
    if(tr.next().hasClass('values')) {
        tr.html(tr.next().html());
        nextTr(tr.next(), level++);
    }
    else {
        if((level == 0) && (tr.prev().hasClass('values'))) {
            let tools = "";
            if(tr.prev().prev().hasClass('values')) {
                tools +=inputs.lineUp();
            }
            tools += inputs.lineRemove();
            tr.prev().find('.td-tools').html(tools);
        }
        tr.remove();
    }
}

$(document).on("click", "#param-info", function() {
    const id = $(this).data('id');
    $('#csv-modal-title').html("Informations concernant le fichier " + id + ".csv");
    $('#csv-modal-body').html(tests.messages[id + "00"]);
    $('#info-modal').addClass("show");
    $('#info-modal').css("display", "block");
});

$(document).on("click", ".modal-ok", function() {
    $('#info-modal').removeClass("show");
    $('#info-modal').css("display", "none");
});
