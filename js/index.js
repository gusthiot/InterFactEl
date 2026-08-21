'use strict';

import Tables from "./tables/tables.js";

$.get("controller/getDroitJson.php", function(data){
    const json = JSON.parse(data);
    const paramtext = json.paramtext;
    const droits = json.droits;
    const contents = json.contents;

    for(let filename in contents) {
        let titles = [];
        for(let numCol = 0; numCol < droits[filename].numcol; numCol++) {
            titles.push(unescape(encodeURIComponent(paramtext["table-"+filename+"-"+numCol])));
        }
        contents[filename].unshift(titles);
    }

    const table = new Tables(json.messages, paramtext, {
                                "mandatoryCsvs": droits,
                                "mandatoryPdfs": {},
                                "optionalPdfs": {}
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
        const files = JSON.parse(json);
        $('#message').html(table.authorizedCheck(files));
        table.extract(files);
        const gestionnaire = table.getContent("gestionnaire");
        let content = [];
        let titles = [];
        for(let numCol = 0; numCol < droits["gestionnaire"].numcol; numCol++) {
            titles.push(unescape(encodeURIComponent(paramtext["table-gestionnaire-"+numCol])));
        }
        content.push(titles);
        let len = gestionnaire.length;
        for(let numRow = 1; numRow < len; numRow++) {
            const line = gestionnaire[numRow];
            content.push([line[0], line[1], hasRight(line[2], 2), hasRight(line[2], 1) , hasRight(line[2], 0), line[3]]);
        }
        table.setContent("gestionnaire", content);


        if(table.columnsCheck()) {
            table.removeContents();
        }
        else {
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
        let len = table.getContent("gestionnaire").length;
        for(let numRow = 1; numRow < len; numRow++) {
            const line = table.getContent("gestionnaire")[numRow];
            if(line[5] === "") {
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
        for(let login in newAdds) {
            for(let row in newAdds[login]) {
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
            }
        }
        return table.getEncFiles({"gestionnaire": content});
    }

    $(document).on("button-save", "#tables-desktop", function() {
        $.post("controller/saveDroits.php", {files: getEncFiles()}, function (data) {
            window.location.href = "controller/download.php?type=js-droits&name="+data;
        });

    });

    $(document).on("button-load", "#tables-desktop", function() {
        $.post("controller/writeDroits.php", {files: getEncFiles()}, function(res) {
            table.reset();
            window.location.href = "index.php";
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
