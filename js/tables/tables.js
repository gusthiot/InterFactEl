'use strict';

import TablesEditor from "./tables-editor.js";
import FileTests from "./file-tests.js";
import TablesTests from "./tables-tests.js";

export default class Tables {

    constructor(context, supervisor, messages, paramtext, parameters, noRemove=false) {
        if(parameters.mandatoryCsvs) {
            this.mandatoryCsvs = parameters.mandatoryCsvs;
        }
        else {
            this.mandatoryCsvs = {};
        }
        if(parameters.mandatoryPdfs) {
            this.mandatoryPdfs = parameters.mandatoryPdfs;
        }
        else {
            this.mandatoryPdfs = {};
        }
        if(parameters.optionalPdfs) {
            this.optionalPdfs = parameters.optionalPdfs;
        }
        else {
            this.optionalPdfs = {};
        }
        this.context = context;
        this.messages = messages;
        this.paramtext = paramtext;
        this.supervisor = supervisor;

        this.tablesTest = new TablesTests(parameters);

        if(noRemove) {
            $('#tables-remove').hide();
        }

        this.fileTest = new FileTests(this.messages, this.mandatoryCsvs, this.supervisor);

        this.contents = {};
        this.pdfs = {};
        this.optPdfs = {}

        if(sessionStorage.getItem(this.context + "contents")) {
            this.contents = JSON.parse(sessionStorage.getItem(this.context + "contents"));
            this.pdfs = JSON.parse(sessionStorage.getItem(this.context + "pdfs"));
            this.optPdfs = JSON.parse(sessionStorage.getItem(this.context + "optPdfs"));
            this.displayFiles();
            $('#tables-cancel').removeClass('desactived-tile');
        }

        this.checks = {};
        this.ids = {};

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

        this.tableur = new TablesEditor(this.messages, allParameters, this.paramtext);

        this.save = {"content": [], "ids": {}, "errors": {}, "filename": ""};

        $(document).on("click", ".csv", (evt) => {
            $('#tables-desktop').hide();
            const filename = $(evt.currentTarget).attr('id');
            this.tableur.loadFile(filename, "csv", this.contents);
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
            $('#tables-desktop').hide();
            const filename = $(evt.currentTarget).attr('id');
            this.tableur.loadFile(filename, "pdf");
            let html = this.tableur.header();
            if(filename === "grille") {
                if(this.optPdfs.grille) {
                    html += this.uploadPdf("replace-grille", "Remplacer la grille");
                    html += '<div class="center-tile">' +
                                '<div id="delete-grille" class="tile tight-tile">Effacer la grille</div>' +
                            '</div>';
                }
                else {
                    html += this.uploadPdf("replace-grille", "Charger une grille");
                }
            }
            else {
                if(this.pdfs.logo) {
                    html += this.uploadPdf("replace-logo", "Remplacer le logo");
                }
                else {
                    html += '<div>' + this.messages[filename + "01"] + '</div>';
                    html += this.uploadPdf("replace-logo", "Charger un logo");
                }
            }
            $('#tables-editor').html(html);
        });

        $(document).on("saved", "#tableur-table", (evt, newContent, filename, dimensions=[]) => {
            if(this.mandatoryCsvs[filename].tests) {
                const results = this.fileTest.internalCheck(filename, newContent, this.contents, this.ids, dimensions);
                if(results.ok) {
                    this.checks[filename] = {};
                    this.checks[filename].errors = {};
                    this.checks[filename].ok = false;
                    this.removeGoodChecks();
                    this.ids = results.ids;
                    this.contents[filename] = newContent;
                    sessionStorage.setItem(this.context + "contents", JSON.stringify(this.contents));
                    this.closeTable();
                }
                else {
                    this.save.content = newContent;
                    this.save.ids = results.ids;
                    this.save.errors = results.errors;
                    this.save.filename = filename;
                    this.tableur.displayErrors(results.errors);
                    $("#tableur-table").trigger("error");
                }
            }
            else {
                this.contents[filename] = newContent;
                sessionStorage.setItem(this.context + "contents", JSON.stringify(this.contents));
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
            sessionStorage.setItem(this.context + "contents", JSON.stringify(this.contents));
            this.closeTable();
        });

        $(document).on("click", "#delete-grille", () => {
            delete this.optPdfs.grille;
            this.checks["grille"].ok = false;
            this.closeTable();
        });

        $(document).on("change", ".pdf-file", (evt) => {
            const id = $(evt.currentTarget).attr('id');
            const fileReader = new FileReader();
            fileReader.onload = () => {
                if(id === 'replace-logo') {
                    this.pdfs["logo"] = fileReader.result.split(',')[1];
                    this.checks["logo"].ok = false;
                    sessionStorage.setItem(this.context + "pdfs", JSON.stringify(this.pdfs));
                }
                else {
                    this.optPdfs["grille"] = fileReader.result.split(',')[1];
                    this.checks["grille"].ok = false;
                    sessionStorage.setItem(this.context + "optPdfs", JSON.stringify(this.optPdfs));
                }
            };
            fileReader.readAsDataURL($(evt.currentTarget).prop('files')[0]);
            this.closeTable();
        });

        $(document).on("close", "#tables-editor", () => {
            this.closeTable();
        });

        /** Left */

        $("#tables-read").on("click", () => {
            this.reset();
            $("#tables-desktop").trigger("button-read");
            $("#tables-read").addClass('selected-tile');
        });

        async function blobToBase64(blob) {
        return new Promise((resolve, _) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result.split(',')[1]);
            reader.readAsDataURL(blob);
        });
        }

        $("#tables-import").on("change", (evt) => {
            this.reset();
            JSZip.loadAsync(evt.target.files[0]).then(function(zip) {
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
                for(let result of results) {
                    if(isFirst === 1) {
                        isFirst = 0;
                    }
                    else {
                        json += ",";
                    }
                    json += '"'+result[0]+'":"'+result[1]+'"';
                }
                json += "}";
                $("#tables-desktop").trigger("button-import", [json]);
            });
        });

        $("#tables-create").on("click", () => {
            this.reset();
            $("#tables-desktop").trigger("button-create");
        });

        /** Right */

        $("#tables-load").on("click", () => {
            $('#tables-files').hide();
            $("#tables-desktop").trigger("button-load");
            $("#tables-load").addClass('selected-tile');
        });

        $("#tables-remove").on("click", () => {
            this.reset();
            $("#tables-desktop").trigger("button-remove");
            $('#tables-cancel').removeClass('desactived-tile');
            $("#tables-remove").addClass('selected-tile');
        });

        /** Bottom */

        $("#tables-cancel").on("click", () => {
            this.reset();
        });

        $("#tables-check").on("click", () => {
            this.removeGoodChecks();
            const results = this.tablesTest.checkColumns(this.fileTest, this.contents, this.pdfs, this.optPdfs, this.ids);
            this.checks = results.checks;
            this.ids = results.ids;
            if(results.ok) {
                $('#tables-load').removeClass('desactived-tile');
            }
        });

        $(document).on("click", "#tables-save", () => {
            $("#tables-desktop").trigger("button-save");
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
        $('#tables-files').html(filesList);
        $('#tables-save').removeClass('desactived-tile');
        $('#tables-check').removeClass('desactived-tile');
    }

    plateFactCheck(files, plateforme) {
        return this.tablesTest.checkPlateFact(files, plateforme, this.messages, this.contents);
    }

    columnsCheck() {
        return this.tablesTest.checkColumnsNumbers(this.contents);
    }

    authorizedCheck(files) {
        return this.tablesTest.checkAuthorized(files);
    }

    saveContents() {
        sessionStorage.setItem(this.context + "contents", JSON.stringify(this.contents));
        sessionStorage.setItem(this.context + "pdfs", JSON.stringify(this.pdfs));
        sessionStorage.setItem(this.context + "optPdfs", JSON.stringify(this.optPdfs));
    }

    emptyContents() {
        for(let filename in this.mandatoryCsvs) {
            this.contents[filename] = [];
        }
    }

    import(contents) {
        for(let filename in this.mandatoryCsvs) {
            if(Object.keys(contents).includes(filename)) {
                this.contents[filename] = contents[filename];
            }
            else {
                this.contents[filename] = [];
            }
        }
    }

    extract(files) {
        for(let filename in this.mandatoryCsvs) {
            if(Object.keys(files).includes(filename + ".csv")) {
                this.contents[filename] = Papa.parse(atob(files[filename + ".csv"]), {delimiter: ";", skipEmptyLines: true}).data;
            }
            else {
                this.contents[filename] = [];
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
        $('#tables-message').html("");
        $('#tables-load').addClass('desactived-tile');
        $('#tables-save').addClass('desactived-tile');
        $('#tables-check').addClass('desactived-tile');
        $('#tables-cancel').addClass('desactived-tile');
        $("#tables-read").removeClass('selected-tile');
        $("#tables-remove").removeClass('selected-tile');
        $("#tables-load").removeClass('selected-tile');
    }

    uploadPdf(id, titre) {
        return '<div class="center-tile">' +
                    '<input id="' + id + '" type="file" name="' + id + '" class="pdf-file" accept=".pdf">' +
                    '<label class="tile tight-tile" for="' + id + '">' + titre + '</label>' +
                '</div>';
    }

    closeTable() {
        $('#tables-message').html("");
        $('#tables-desktop').show();
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

    txtToBase64(txt) {
        const bytes = new TextEncoder().encode(txt);
        const binString = Array.from(bytes, (byte) =>
            String.fromCodePoint(byte),
        ).join("");
        return btoa(binString);
    }
    getEncFiles(specials={}) {
        let files = {};
        for(let name in this.contents) {
            if(Object.hasOwn(specials, name)) {
                continue;
            }
            let content = this.contents[name];
            if(!this.mandatoryCsvs[name].notitles) {
                let titles = [];
                for(let numCol = 0; numCol < this.mandatoryCsvs[name].numcol; numCol++) {
                    titles.push(this.paramtext["table-"+name+"-"+numCol]);
                }
                content[0] = titles;
            }
            files[name+".csv"] = this.txtToBase64(Papa.unparse(content, {delimiter: ";", skipEmptyLines: true}));
        }

        for(let name in specials) {
            files[name+".csv"] = this.txtToBase64(Papa.unparse(specials[name], {delimiter: ";", skipEmptyLines: true}));
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
