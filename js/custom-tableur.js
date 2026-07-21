export class CustomTableur {

    constructor(messages, parameters, paramtext, onClose) {
        this.messages = messages;
        this.parameters = parameters;
        this.paramtext = paramtext;
        this.contents = {};
        this.filename = "";
        this.extension = "";

        $(document).on("click", ".tableur-remove", function() {
            onClose();
        });

        $(document).on("click", "#tableur-save-bidim", () => {
            let newContent = this.getTitles();
            const lines = $('.values');
            const dim1 = $('#dim1').find('.cell');
            for(let numRow = 0; numRow < lines.length; numRow++) {
                const cells = $(lines[numRow]).find('.cell');
                for(let numCol = 0; numCol < cells.length; numCol++) {
                    const input = $(cells[numCol]).find('input');
                    if($(input).val() !== "") {
                        let line = [];
                        line[0] = $(lines[numRow]).find('.dim0').data('id').toString();
                        line[1] = $(dim1[numCol]).data('id').toString();
                        line[2] = $(input).val();
                        newContent.push(line);
                    }
                }
            }
            $( "#tableur-table").trigger("saved", [newContent, this.filename]);
        });

        $(document).on("click", "#tableur-save-unidim", () => {
            let newContent = this.getTitles();
            const lines = $('.values');
            for(let numRow = 0; numRow < lines.length; numRow++) {
                let line = [];
                const cells = $(lines[numRow]).find('.cell');
                for(let numCol = 0; numCol < cells.length; numCol++) {
                    const input = $(cells[numCol]).find('input');
                    if(input.length > 0) {
                        line[numCol] = $(input).val();
                        continue;
                    }
                    const select = $(cells[numCol]).find('select');
                    if(select.length > 0) {
                        const selected = $(select).find('option:selected');
                        line[numCol] = $(selected).attr('value');
                        continue;
                    }
                    const numline = $(cells[numCol]).find('.num-line');
                    if(numline.length > 0) {
                        line[numCol] = $(numline).html();
                        continue;
                    }
                    line[numCol]= $(cells[numCol]).html();
                }
                newContent.push(line);
            }
            $("#tableur-table").trigger("saved", [newContent, this.filename]);
        });

        $(document).on("error", "#tableur-table", function() {
            $('#error-modal').addClass("show");
            $('#error-modal').css("display", "block");
        });

        $(document).on("click", "#error-modal-save", function() {
            $('#error-modal').removeClass("show");
            $('#error-modal').css("display", "none");
            $("#tableur-table").trigger("save-anyway");
        });

        $(document).on("click", "#tableur-info", () => {
            $('#file-modal-title').html("Informations concernant le fichier " + this.filename + "." + this.extension);
            $('#file-modal-body').html(this.messages[this.filename + "00"]);
            $('#info-modal').addClass("show");
            $('#info-modal').css("display", "block");
        });

        $(document).on("click", "#line-remove", (evt) => {
            const tr = $(evt.currentTarget).closest('tr');
            this.nextTr(tr, 0);
        });

        $(document).on("click", "#line-up", (evt) => {
            const tr = $(evt.currentTarget).closest('tr');
            this.trToggle(tr, tr.prev());
        });

        $(document).on("click", "#line-down", (evt) => {
            const tr = $(evt.currentTarget).closest('tr');
            this.trToggle(tr, tr.next());
        });

        $(document).on("click", "#line-plus", (evt) => {
            const tr = $(evt.currentTarget).closest('tr').prev();
            let num = 1;
            if(tr.hasClass('values')) {
                const tab = tr.attr('id').split("-");
                num = parseInt(tab[1]) + 1;
            }
            let html = '<tr class="values" id="line-' + num + '">';
            let notitles = false;
            if(this.parameters[this.filename].notitles) {
                notitles = true;
            }
            for(let numCol = 0; numCol < this.parameters[this.filename].columns.length; numCol++) {
                const paramCol = this.parameters[this.filename].columns[numCol];
                let cell = "";
                html += '<td class="border-around cell">';
                html += this.input(paramCol, cell, num, notitles);
                html += '</td>';
            }
            html += '<td class="border-around td-tools">';
            if(tr.hasClass('values')) {
                html += this.lineUp();
                let tools = "";
                if(tr.prev().hasClass('values')) {
                    tools += this.lineUp();
                }
                tools += this.lineDown() + this.lineRemove();
                tr.find('.td-tools').html(tools);
            }
            html += this.lineRemove() + '</td></tr>';
            tr.after(html);
        });

    }

    init(filename, extension, contents={}) {
        this.contents = contents;
        this.filename = filename;
        this.extension = extension;
    }

    getTitles() {
        if(!this.parameters[this.filename].notitles) {
            let titles = [];
            for(let i = 0; i < this.parameters[this.filename].numcol; i++) {
                titles[i] = this.paramtext["table-"+this.filename+"-"+i];
            }
            return [titles];
        }
        return [];
    }

    trToggle(tr, tr1) {
        const cont = tr.html();
        const tools = tr.find('.td-tools').html();
        const numLine = tr.find('.num-line').html();
        const cont1 = tr1.html();
        const tools1 = tr1.find('.td-tools').html();
        const numLine1 = tr1.find('.num-line').html();
        tr.html(cont1);
        tr1.html(cont);
        tr.find('.td-tools').html(tools);
        tr1.find('.td-tools').html(tools1);
        if(numLine && numLine1) {
            tr.find('.num-line').html(numLine);
            tr1.find('.num-line').html(numLine1);
        }
    }

    nextTr(tr, level) {
        if(tr.next().hasClass('values')) {
            tr.html(tr.next().html());
            this.nextTr(tr.next(), level++);
        }
        else {
            if((level == 0) && (tr.prev().hasClass('values'))) {
                let tools = "";
                if(tr.prev().prev().hasClass('values')) {
                    tools +=this.lineUp();
                }
                tools += this.lineRemove();
                tr.prev().find('.td-tools').html(tools);
            }
            tr.remove();
        }
    }

    header() {
        return '<div id="tableur-header">' +
                    '<svg id="tableur-info" data-id="' + this.filename + '" class="icon icon-selectable date-left" aria-hidden="true">' +
                        '<use xlink:href="#info"></use>' +
                    '</svg>' +
                    '<svg class="icon icon-selectable date-right tableur-remove" aria-hidden="true">' +
                        '<use xlink:href="#x"></use>' +
                    '</svg>' +
                '</div>';
    }

    templateTableur(inside, dim) {
        let html = this.header(this.filename, "csv");
        html += '<div id="tableur-table"><form id="tableur-form"><table class="table" data-filname="' + this.filename + '" id="table-tableur">';
        html += inside;
        html += '</table></form></div>';
        html += '<div class="center-tile"><div class="tile tight-tile tableur-remove">Annuler</div><div id="tableur-save-' + dim + '" class="tile tight-tile">Enregistrer les modifications</div></div>';
        return html;
    }

    displayErrors(errors) {
        const lines = $("#tableur-table").find(".values");
        Object.keys(errors).forEach(function(keyRow) {
            const numRow = keyRow.split('-')[1];
            const line = lines[numRow-1];
            const cells = $(line).find(".cell");
            Object.keys(errors[keyRow]).forEach(function(keyCol) {
                const numCol = keyCol.split('-')[1];
                const cell = cells[numCol];
                $(cell).addClass("background-red");
                $(cell).data("msg", errors[keyRow][keyCol]);
            });
        });
    }

    unidimTableur() {
        let html = '<tr>';
        for (let i = 0; i < this.parameters[this.filename].numcol; i++) {
            html += '<th>' + this.paramtext["table-"+this.filename+"-"+i] + '</th>';
        }
        html += '</tr>';
        let notitles = false;
        if(this.parameters[this.filename].notitles) {
            notitles = true;
        }
        for(let numRow = 0; numRow < this.contents[this.filename].length; numRow++) {
            if(notitles || (numRow > 0)) {
                html += '<tr class="values" id="line-' + numRow + '">';
                for(let numCol = 0; numCol < this.contents[this.filename][numRow].length; numCol++) {
                    let paramCol = this.parameters[this.filename].columns[numCol];
                    html += '<td class="border-around cell">';
                    if(paramCol.type == "specific") {
                        paramCol = paramCol.lines[numRow];
                    }
                    html += this.input(paramCol, this.contents[this.filename][numRow][numCol], numRow, notitles);
                    html += '</td>';
                }
                if(this.parameters[this.filename].tools) {
                    html += '<td class="border-around td-tools">';
                    if((notitles && (numRow > 0)) || (numRow > 1)) {
                        html += this.lineUp();
                    }
                    if(numRow < (this.contents[this.filename].length-1)) {
                        html += this.lineDown();
                    }
                    html += this.lineRemove() + '</td>';
                }
                html += '</tr>';
            }
        }
        if(this.parameters[this.filename].tools) {
            html += '<tr><td class="border-around left" colspan="' + (this.parameters[this.filename].numcol + 1) + '">' +
                    '<svg id="line-plus" class="icon icon-selectable" aria-hidden="true">' +
                        '<use xlink:href="#plus"></use>' +
                    '</svg></td></tr>';
        }
        return this.templateTableur(html, "unidim");
    }

    bidimTableur(sapIds) {
        const dim0 = this.contents[this.parameters[this.filename].columns[0].origin];
        const dim1 = this.contents[this.parameters[this.filename].columns[1].origin];
        let html = '<tr><th></th><th></th><th colspan="' + (dim1.length-1) + '">' + this.paramtext["table-"+this.filename+"-"+1] + '</th></tr>';
        html += '<tr id="dim1"><td class="border-around-no"></td><td class="border-around-no"></td>';
        for(let num1 = 1; num1 < dim1.length; num1++) {
            if(this.filename == "coeffprestation") {
                if(dim1[num1][3] != "OUI") {
                    continue;
                }
            }
            const positions = this.parameters[this.filename].bidim[0].intitule;
            let line1 = "";
            if(positions[0] == "codeD") {
                const idSap = dim1[num1][2];
                line1 = this.contents["articlesap"][sapIds[idSap]][2];
            }
            else {
                line1 = dim1[num1][positions[0]];
            }
            html += '<td class="border-around-black cell" data-id="' + dim1[num1][0] + '">' + line1 + " - " + dim1[num1][positions[1]] + '</td>';
        };
        html += '</tr>';
        for(let num0 = 1; num0 < dim0.length; num0++) {
            html += '<tr class="values">';
            if(num0 == 1) {
                html += '<th rowspan="' + (dim0.length-1) + '" class="vert-th">' + this.paramtext["table-"+this.filename+"-"+0] + '</th>';
            }
            const positions = this.parameters[this.filename].bidim[1].intitule;
            let intitule = dim0[num0][positions[0]];
            if(positions.length > 1) {
                intitule += " - " + dim0[num0][positions[1]];
            }
            html += '<td class="border-around-black dim0" data-id="' + dim0[num0][0] + '">' + intitule + '</td>';
            for(let num1 = 1; num1 < dim1.length; num1++) {
                if(this.filename == "coeffprestation") {
                    if(dim1[num1][3] != "OUI") {
                        continue;
                    }
                }
                let value = "";
                for(const line of this.contents[this.filename]) {
                    if((line[0] == dim0[num0][0]) && (line[1] == dim1[num1][0])) {
                        value = line[2];
                        break;
                    }
                };
                html += '<td class="border-around cell">' + this.number(value, this.parameters[this.filename].columns[2]) + '</td>';
            }
            html += '</tr>';
        }
        return this.templateTableur(html, "bidim");
    }

    input(paramCol, cell, num, notitles) {
        switch(paramCol.type) {
            case "num":
                return this.number(cell, paramCol);
            case "txt":
                return this.text(cell);
            case "alphanum":
                return this.alphanum(cell);
            case "menu":
                return this.menu(cell, paramCol);
            case "ref":
                return this.ref(cell, paramCol, notitles);
            case "line":
                return '<div class="num-line">' + num + '</div>';
            default:
                return cell;
        }
    }

    number(value, params) {
        let ret = '<input class="tableur-input" type="number" size="6" value="' + value + '" ';
        if(params.max) {
            ret += ' max="' + params.max + '" ';
        }
        if(params.int) {
            ret += ' step="1" ';
        }
        else {
            ret += ' step="any" ';
        }
        if(params.zero) {
            ret += ' min="0" ';
        }
        else {
            ret += ' min="1" ';
        }
        ret += ' >';
        return ret;
    }

    text(value) {
        return '<input class="tableur-input" type="text" value="' + value + '">';
    }

    alphanum(value) {
        return '<input class="tableur-input" type="text" value="' + value + '" pattern="[A-Za-z0-9]*" >';
    }

    menu(value, params) {
        let ret = '<select class="tableur-select">';
        if(params.list) {
            params.list.forEach(function(el) {
                ret += '<option value="' + el + '"';
                if(value == el) {
                    ret += ' selected ';
                }
                ret += '>' + el + '</option>';
            });
        }
        else {
            Object.keys(params.map).forEach(function(key) {
                ret += '<option value="' + key + '"';
                if(value == key) {
                    ret += ' selected ';
                }
                ret += '>' + params.map[key] + '</option>';
            });
        }
        ret += '</select>';
        return ret;
    }

    ref(value, params, notitles) {
        const ref = this.contents[params.origin];
        let ret = '<select class="tableur-select">';
        if(params.zero) {
            ret += '<option value="0"';
            if(value == "0") {
                ret += ' selected ';
            }
            ret += '>0 - Aucun</option>';
        }
        let num = 0;
        for(const key in ref) {
            if(num > 0 || notitles) {
                let refCol = 0;
                if(params.refCol) {
                    refCol = params.refCol;
                }
                if(params.col && (params.value != ref[key][params.col])) {
                    continue;
                }
                ret += '<option value="' + ref[key][0] + '"';
                if(value == ref[key][refCol]) {
                    ret += ' selected ';
                }
                ret += '>' + ref[key][0];
                if(params.intitule) {
                    if(Array.isArray(params.intitule)) {
                        params.intitule.forEach(function(pos) {
                            if(Array.isArray(pos)) {
                                ret += " -";
                                pos.forEach(function(posIn) {
                                    ret += " " + ref[key][posIn];
                                });
                            }
                            else {
                                ret += " - " + ref[key][pos];
                            }
                        });
                    }
                    else {
                        ret += " - " + ref[key][params.intitule];
                    }
                }
                if(params.plus) {
                    ret += params.plus;
                }
                ret += '</option>';
            }
            num++;
        };
        ret += '</select>';
        return ret;
    }

    lineUp() {
        return '<svg id="line-up" class="icon icon-selectable" aria-hidden="true">' +
            '<use xlink:href="#chevron-up"></use>' +
        '</svg>&nbsp;';
    }

    lineDown() {
        return '<svg id="line-down" class="icon icon-selectable" aria-hidden="true">' +
            '<use xlink:href="#chevron-down"></use>' +
        '</svg>&nbsp;';
    }

    lineRemove() {
        return '<svg id="line-remove" class="icon icon-selectable" aria-hidden="true">' +
            '<use xlink:href="#minus-circle"></use>' +
        '</svg>';
    }
}

$(document).on("input", ".tableur-input", function() {
    $(this).attr('value',$(this).val());
});

$(document).on("input", ".tableur-select", function() {
    const oldSelected = $(this).find('option[selected]');
    oldSelected.removeAttr('selected');
    const newSelected = $(this).find('option[value="' + $(this).find(":selected").val() + '"]');
    newSelected.attr('selected', true);
});

$(document).on('input propertychange', '.tableur-input', function() {
    document.forms["tableur-form"].reportValidity();
});

$(document).on("click", ".info-ok", function() {
    $('#info-modal').removeClass("show");
    $('#info-modal').css("display", "none");
});

$(document).on("click", "#error-modal-cancel", function() {
    $('#error-modal').removeClass("show");
    $('#error-modal').css("display", "none");
});

$(document).on("click", "#error-modal-correct", function() {
    $('#error-modal').removeClass("show");
    $('#error-modal').css("display", "none");
});

$(document).on("click", ".background-red", function() {
    $('#message').html($(this).data('msg'));
});
