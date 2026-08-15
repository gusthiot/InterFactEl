'use strict';

import TablesEditor from "./tables/tables-editor.js";
import FileTests from "./tables/file-tests.js";

let fileTest = undefined;
let ids = {};
let contents = {};

$.get("controller/getConfigJson.php", function(data){
    const json = JSON.parse(data);
    const paramtext = json.paramtext;
    const messages = json.messages;
    const configs = json.configs;
    contents = json.contents;

    Object.keys(contents).forEach(function(name) {
        let titles = [];
        for(let numCol = 0; numCol < configs[name].numcol; numCol++) {
            titles.push(unescape(encodeURIComponent(paramtext["table-"+name+"-"+numCol])));
        }
        contents[name].unshift(titles);
    });

    const tableur = new TablesEditor(messages, configs, paramtext);

    fileTest = new FileTests(messages, configs);

    $(document).on("click", ".csv", function() {
        $('#index-canevas').css("display", "none");
        const filename = $(this).attr('id');
        tableur.init(filename, "csv", contents);
        $('#supervision-manage').html(tableur.unidimTableur());
    });

    $(document).on("saved", "#tableur-table", function(event, newContent, filename) {
        const results = fileTest.internalCheck(filename, newContent, contents, ids);
        if(runCheck(results.result)) {
            tableur.displayErrors(results.errors);
            $("#tableur-table").trigger("error");
        }
        else {
            ids = results.ids;
            contents[filename] = newContent;
            let content = structuredClone(newContent);
            if(filename == "gestionnaire") {
                let titles = [];
                titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-0"])));
                titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-1"])));
                titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-6"])));
                titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-5"])));
                content[0] = titles;
                let orders = {};
                let newAdds = {};
                for(let numRow = 1; numRow < content.length; numRow++) {
                    const line = content[numRow];
                    if(line[5] == "") {
                        if(!Object.keys(newAdds).includes(line[0])) {
                            newAdds[line[0]] = [];
                        }
                        newAdds[line[0]].push(numRow);
                    }
                    else {
                        if(!Object.keys(orders).includes(line[0]) || (line[5] > orders[line[0]])) {
                            orders[line[0]] = line[5];
                        }
                    }
                    const codage = 4*parseInt(line[2]) + 2*parseInt(line[3]) + parseInt(line[4]);
                    content[numRow] = [line[0], line[1], codage, line[5]];
                }
                Object.keys(newAdds).forEach(function(login) {
                    newAdds[login].forEach(function(row) {
                        if(!Object.keys(orders).includes(login)) {
                            orders[login] = 1;
                            content[row][3] = 1;
                            contents[filename][row][5] = 1;
                        }
                        else {
                            const order = parseInt(orders[login]) + 1;
                            orders[login] = order;
                            content[row][3] = order;
                            contents[filename][row][5] = order;
                        }
                    });
                });
            }
            $.post("controller/saveConfigFile.php", {name: filename, content: content}, function(res) {
                if(!runCheck(res)) {
                    closeTable();
                }
            });
        }
    });
});

function zipError() {
    $('#message').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">'+
                            'Vous devez uploader une archive zip !'+
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
                                '<span aria-hidden="true">&times;</span>'+
                            '</button>'+
                        '</div>');
}

$('#download-generated').on('click', function () {
    window.location.href = "controller/download.php?type=generated";
});

$('.download-config').on('click', function () {
    window.location.href = "controller/download.php?type=config";
});

$('#zip-config').on('change', function () {
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#form-config').submit();
        $('#message').text('');
    }
    else {
    }
});

$('.facturation').on('click', function () {
    window.location.href = "facturation.php?plateforme="+$(this).find('#plate-fact').val();
});

$('.tarifs').on('click', function () {
    window.location.href = "tarifs.php?plateforme="+$(this).find('#plate-tarifs').val();
});

$('.reporting').on('click', function () {
    window.location.href = "reporting.php?plateforme="+$(this).find('#plate-report').val();
});

$(document).on("change", ".zip-simu", function () {
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $(this).closest("form").submit();
        $('#message').html('<div>Veuillez patienter, cela peut prendre plusieurs minutes...</div><div class="loader"></div>');
        $(".lockable").prop('disabled', true);
    }
    else {
        zipError();
    }
});

$('#zip-view').on('change', function () {
    const file = $(this).val();
    if(file.indexOf('.zip') > -1) {
        $('#form-view').submit();
        $('#message').text('');
    }
    else {
        zipError();
    }
});

$('#modal-save').on('click', function () {
    let content = [];
    const num = $('#msg-num').val();
    for(let i=0;i<num;i++) {
        if(!$('#del-'+i).is(':checked')) {
            let display = 0;
            if($('#dis-'+i).is(':checked')) {
                display = 1;
            }
            content.push([display, $('#msg-'+i).val()]);
        }
    }
    if($('#msg-new').val() != "") {
        content.push([1, $('#msg-new').val()]);
    }
    $.post("controller/saveMessages.php", {content: content}, function () {
        window.location.href = "index.php";
    });
});

$('.manage-files').on('click', function () {
    if($('#supervision-files').css("display") == "flex") {
        $('#supervision-files').css("display", "none");
    }
    else {
        $('#supervision-files').css("display", "flex");
    }
});

$(document).on("click", ".tableur-remove", function() {
    closeTable();
});

$(document).on("close", "#tables-editor", () => {
    closeTable();
});

function closeTable() {
    $('#index-canevas').css("display", "block");
    $('#supervision-manage').html("");
}

function runCheck(res) {
    if(res != "") {
        $('#message').html(res);
        return true;
    }
    return false;
}
