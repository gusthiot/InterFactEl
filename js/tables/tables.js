'use strict';

import TablesEditor from "./tables-editor.js";
import FileTests from "./file-tests.js";
import TablesTests from "./tables-tests.js";

export default class Tables {

    constructor(parameters) {
        this.mandatoryCsvs = parameters.mandatoryCsvs;
        this.mandatoryPdfs = parameters.mandatoryPdfs;
        this.optionalPdfs = parameters.optionalPdfs;
        this.messages = parameters.messages;
        this.paramtext = parameters.paramtext;

        this.tablesTest = new TablesTests({
            mandatoryCsvs: this.mandatoryCsvs,
            mandatoryPdfs: this.mandatoryPdfs,
            optionalPdfs: this.optionalPdfs
        });

        this.fileTest = new FileTests(this.messages, this.mandatoryCsvs);

        this.contents = {};
        this.pdfs = {};
        this.optPdfs = {}

        if(sessionStorage.getItem("contents")) {
            this.contents = JSON.parse(sessionStorage.getItem("contents"));
            this.pdfs = JSON.parse(sessionStorage.getItem("pdfs"));
            this.optPdfs = JSON.parse(sessionStorage.getItem("optPdfs"));
            this.displayFiles();
            $('#tables-cancel').removeClass('desactived-tile');
        }

        this.checks = {};
        this.ids = {};

        if(sessionStorage.getItem("checks")) {
            this.checks = JSON.parse(sessionStorage.getItem("checks"));
            this.displayChecks();
        }

        let allParameters =  {};
        for(let params in this.mandatoryCsvs) {
            allParameters[params] = this.mandatoryCsvs[params];
        }
        for(let params in this.mandatoryPdfs) {
            allParameters[params] = this.mandatoryPdfs[params];
        }
        for(let params in this.optionalPdfs) {
            allParameters[params] = this.optionalPdfs[params];
        }

        this.tableur = new TablesEditor(this.messages, allParameters, this.paramtext, true);

        this.save = {"content": [], "ids": {}, "errors": {}, "filename": ""};

        $(document).on("click", ".csv", (evt) => {
            $('#tables-desktop').css("display", "none");
            const filename = $(evt.currentTarget).attr('id');
            this.tableur.init(filename, "csv", this.contents);
            let html = "";
            if(this.mandatoryCsvs[filename].bidim) {
                const sapIds = this.fileTest.retrieveIds("articlesap", this.contents, this.ids);
                html = this.tableur.bidimTableur(sapIds);
            }
            else {
                html = this.tableur.unidimTableur();

            }
            $('#tables-editor').html(html);
            if(this.checks[filename] && this.checks[filename].errors) {
                this.tableur.displayErrors(this.checks[filename].errors);
            }
        });

        $(document).on("click", ".pdf", (evt) => {
            $('#tables-desktop').css("display", "none");
            const filename = $(evt.currentTarget).attr('id');
            this.tableur.init(filename, "pdf");
            let html = this.tableur.header();
            if(filename == "grille") {
                if(this.contents['plateforme'][7][2] == "OUI") {
                    let title = "Ajouter la grille";
                    if(this.optPdfs.grille) {
                        title = "Remplacer la grille";
                    }
                    html += this.uploadPdf(this.messages[filename + "01"], "replace-grille", title);
                }
                else {
                    html += '<div>' + this.messages[filename + "02"] + '</div>';
                    if(this.optPdfs.grille) {
                        html += '<div class="center-tile">' +
                                    '<div id="delete-grille" class="tile tight-tile">Effacer la grille</div>' +
                                '</div>';
                    }
                }
            }
            else {
                html += this.uploadPdf(this.messages[filename + "01"], "replace-logo", "Remplacer le logo");
            }
            $('#tables-editor').html(html);
        });

        $(document).on("saved", "#tableur-table", (evt, newContent, filename, dimensions=[]) => {
            if(this.mandatoryCsvs[filename].tests) {
                const results = this.fileTest.internalCheck(filename, newContent, this.contents, this.ids, dimensions);
                if(this.runCheck(results.result)) {
                    this.save.content = newContent;
                    this.save.ids = results.ids;
                    this.save.errors = results.errors;
                    this.save.filename = filename;
                    this.tableur.displayErrors(results.errors);
                    $("#tableur-table").trigger("error");
                }
                else {
                    this.checks[filename] = {};
                    this.checks[filename].errors = {};
                    this.checks[filename].ok = false;
                    this.removeGoodChecks();
                    this.ids = results.ids;
                    this.contents[filename] = newContent;
                    sessionStorage.setItem("checks", JSON.stringify(this.checks));
                    sessionStorage.setItem("contents", JSON.stringify(this.contents));
                    this.closeTable();
                }
            }
            else {
                this.contents[filename] = newContent;
                sessionStorage.setItem("contents", JSON.stringify(this.contents));
                this.closeTable();
            }
        });

        $(document).on("save-anyway", "#tableur-table", () => {
            this.checks[this.save.filename] = {};
            this.checks[this.save.filename].errors = this.save.errors;
            this.checks[this.save.filename].ok = false;
            this.removeGoodChecks();
            this.ids = this.save.ids;
            this.contents[this.save.filename] = this.save.content;
            sessionStorage.setItem("checks", JSON.stringify(this.checks));
            sessionStorage.setItem("contents", JSON.stringify(this.contents));
            this.closeTable();
        });

        $(document).on("click", "#delete-grille", () => {
            delete this.optPdfs.grille;
            this.closeTable();
        });

        $(document).on("change", ".pdf-file", (evt) => {
            const id = $(evt.currentTarget).attr('id');
            const fileReader = new FileReader();
            fileReader.onload = function () {
                if(id == 'replace-logo') {
                    this.pdfs['logo'] = fileReader.result.split(',')[1];
                }
                else {
                    this.optPdfs['grille'] = fileReader.result.split(',')[1];
                }
            };
            fileReader.readAsDataURL($(evt.currentTarget).prop('files')[0]);
            this.closeTable();
        });

        $(document).on("close", "#tables-editor", () => {
            this.closeTable();
        });
    }

    displayChecks() {
        let result = true;
        for(let filename in this.checks) {
            $('#'+filename).removeClass('red-file');
            $('#'+filename).removeClass('green-file');
            if(this.checks[filename].errors && (Object.keys(this.checks[filename].errors).length > 0)) {
                $('#'+filename).addClass('red-file');
                result = false;
            }
            if(this.checks[filename].ok) {
                $('#'+filename).addClass('green-file');
            }
        }
        if(result) {
            $('#tables-load').removeClass('desactived-tile');
        }
    }

    displayFiles() {
        let filesList = '';
        for(let key in this.mandatoryCsvs) {
            filesList += '<div id="' + key + '" class="file tile csv">' + this.mandatoryCsvs[key].name + "</div>";
        }
        for(let dict of [this.mandatoryPdfs, this.optionalPdfs]) {
            for(let key in dict) {
                filesList += '<div id="' + key + '" class="file tile pdf">' + dict[key].name + "</div>";
            }
        }
        $('#message').html("");
        $('#tables-files').html(filesList);
        $('#tables-save').removeClass('desactived-tile');
        $('#tables-check').removeClass('desactived-tile');
    }

    importChecks(plateforme) {
        return this.runCheck(this.tablesTest.checkColumnsNumbers(this.contents)) ||
            this.runCheck(this.tablesTest.checkPlateFact(plateforme, this.messages, this.contents, this.optPdfs));
    }

    authorizedCheck() {
        $('#message').html(this.tablesTest.checkAuthorized(this.contents, this.pdfs, this.optPdfs));
    }

    runCheck(res) {
        if(res != "") {
            $('#message').html(res);
            return true;
        }
        return false;
    }

    checkTables() {
        const results = this.tablesTest.checkColumns(this.fileTest, this.contents, this.pdfs, this.optPdfs, this.ids);
        this.checks = results.checks;
        this.ids = results.ids;
        sessionStorage.setItem("checks", JSON.stringify(this.checks));
        return this.runCheck(results.result);
    }

    removeContents() {
        this.contents = {};
    }

    saveContents() {
        sessionStorage.setItem("contents", JSON.stringify(this.contents));
        sessionStorage.setItem("pdfs", JSON.stringify(this.pdfs));
        sessionStorage.setItem("optPdfs", JSON.stringify(this.optPdfs));
    }

    emptyContents(plateforme) {
        for(let filename in this.mandatoryCsvs) {
            this.contents[filename] = this.emptyContent(filename, plateforme);
        }
    }

    emptyContent(filename, plateforme) {
        if(this.mandatoryCsvs[filename].tools || this.mandatoryCsvs[filename].bidim) {
            return [];
        }
        else {
            let content = [];
            for(let label of this.mandatoryCsvs[filename].labels) {
                let line = [label];
                for(let numCol = 1; numCol < this.mandatoryCsvs[filename].numcol; numCol++) {
                    if((filename == "plateforme") && (label == "Id-Plateforme") && (numCol == 2)) {
                        line.push(plateforme);
                    }
                    else {
                        line.push("");
                    }
                }
                content.push(line);
            }
            return content;
        }
    }

    extract(plateforme, files, check) {
        for(let filename in this.mandatoryCsvs) {
            if(Object.keys(files).includes(filename + ".csv")) {
                this.contents[filename] = Papa.parse(atob(files[filename + ".csv"]), {delimiter: ";", skipEmptyLines: true}).data;
            }
            else {
                this.contents[filename] = this.emptyContent(filename, plateforme);
            }
        }
        for(let filename in this.mandatoryPdfs) {
            if(Object.keys(files).includes(filename + ".pdf")) {
                this.pdfs[filename] = files[filename + ".pdf"];
            }
        }
        for(let filename in this.optionalPdfs) {
            if(Object.keys(files).includes(filename + ".pdf")) {
                this.optPdfs[filename] = files[filename + ".pdf"];
            }
        }
    }

    reset() {
        this.contents = {};
        this.pdfs = {};
        this.optPdfs = {}
        this.ids = {};
        this.checks = {};
        sessionStorage.removeItem("contents");
        sessionStorage.removeItem("pdfs");
        sessionStorage.removeItem("optPdfs");
        sessionStorage.removeItem("checks");
        $('#tables-files').html("");
        $('#tables-dates').html("");
        $('#message').html("");
        $('#tables-load').addClass('desactived-tile');
        $('#tables-save').addClass('desactived-tile');
        $('#tables-check').addClass('desactived-tile');
        $('#tables-cancel').addClass('desactived-tile');
        $("#tables-read").removeClass('selected-tile');
        $("#tables-remove").removeClass('selected-tile');
        $("#tables-load").removeClass('selected-tile');
    }

    uploadPdf(message, id, titre) {
        return '<div>' + message + '</div>' +
                '<div class="center-tile">' +
                    '<input id="' + id + '" type="file" name="' + id + '" class="pdf-file" accept=".pdf">' +
                    '<label class="tile tight-tile" for="' + id + '">' + titre + '</label>' +
                '</div>';
    }

    closeTable() {
        $('#tables-desktop').css("display", "block");
        $('#tables-editor').html("");
        this.displayChecks();
    }


    removeGoodChecks() {
        for(let name in this.checks) {
            if(this.checks[name].ok) {
                this.checks[name].ok = false;
            }
        }
    }

    retrieveIds(filename) {
        return  this.fileTest.retrieveIds(filename, this.contents, this.ids);
    }

    getContent(filename) {
        return this.contents[filename];
    }

    getEncFiles(specials={}) {
        let files = {};
        for(let name in this.contents) {
            let content = this.contents[name];
            if(!this.mandatoryCsvs[name].notitles) {
                let titles = [];
                for(let numCol = 0; numCol < this.mandatoryCsvs[name].numcol; numCol++) {
                    titles.push(unescape(encodeURIComponent(this.paramtext["table-"+name+"-"+numCol])));
                }
                content[0] = titles;
            }
            files[name+".csv"] = btoa(Papa.unparse(content, {delimiter: ";", skipEmptyLines: true}));
        }

        for(let name in specials) {
            console.log(name);
            files[name+".csv"] = btoa(Papa.unparse(specials[name], {delimiter: ";", skipEmptyLines: true}));
        }

        for(let name in this.pdfs) {
            files[name+".pdf"] = this.pdfs[name];
        }
        for(let name in this.optPdfs) {
            files[name+".pdf"] = this.optPdfs[name];
        }
        return JSON.stringify(files);
    }

}
