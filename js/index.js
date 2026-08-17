'use strict';

import Tables from "./tables/tables.js";

$.get("controller/getConfigJson.php", function(data){
    const json = JSON.parse(data);
    const paramtext = json.paramtext;
    const configs = json.configs;
    const contents = json.contents;

    Object.keys(contents).forEach(function(name) {
        let titles = [];
        for(let numCol = 0; numCol < configs[name].numcol; numCol++) {
            titles.push(unescape(encodeURIComponent(paramtext["table-"+name+"-"+numCol])));
        }
        contents[name].unshift(titles);
    });

    const table = new Tables({
            "mandatoryCsvs": configs,
            "mandatoryPdfs": {},
            "optionalPdfs": {},
            "messages": json.messages,
            "paramtext": paramtext
        }, true);


    $(document).on("button-read", "#tables-desktop", function() {
        table.import(contents);
        table.saveContents();
        table.displayFiles();
    });

    function hasRight(right, pos) {
        return (parseInt(right) & (1 << pos)) > 0 ? 1 : 0;
    }

    $(document).on("button-import", "#tables-desktop", function(event, json) {
        table.extract(JSON.parse(json));
        const gestionnaire = table.getContent("gestionnaire");
        let content = [];
        let titles = [];
        for(let numCol = 0; numCol < configs["gestionnaire"].numcol; numCol++) {
            titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-"+numCol])));
        }
        content.push(titles);
        for(let numRow = 1; numRow < gestionnaire.length; numRow++) {
            const line = gestionnaire[numRow];
            content.push([line[0], line[1], hasRight(line[2], 2), hasRight(line[2], 1) , hasRight(line[2], 0), line[3]]);
        }
        table.setContent("gestionnaire", content);


        if(table.columnsCheck()) {
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
        table.emptyContents();
        table.displayFiles();
    });

    function getEncFiles() {
        let content = [];
        let titles = [];
        titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-0"])));
        titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-1"])));
        titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-6"])));
        titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-5"])));
        content.push(titles);
        let orders = {};
        let newAdds = {};
        for(let numRow = 1; numRow < table.getContent("classeclient").length; numRow++) {
            const line = table.getContent("classeclient")[numRow];
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
            content.push([line[0], line[1], codage, line[5]]);
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
        return table.getEncFiles({"gestionnaire": content});
    }

    $(document).on("button-save", "#tables-desktop", function() {
        $.post("controller/saveConfigs.php", {files: getEncFiles()}, function (data) {
            window.location.href = "controller/download.php?type=js-configs&name="+data;
        });

    });

    $(document).on("button-load", "#tables-desktop", function() {

        $.post("controller/saveConfigFile.php", {name: filename, content: content}, function(res) {
            if(!runCheck(res)) {
                closeTable();
            }
        });
    });
});

$(document).on("click", "#back", function() {
    $('#supervision-manage').hide();
    $('#index-canevas').show();
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
    $('#supervision-manage').show();
    $('#index-canevas').hide();
});


function runCheck(res) {
    if(res != "") {
        $('#message').html(res);
        return true;
    }
    return false;
}
