'use strict';

export default class TarifsDates {

    constructor(plateforme, tables) {
        this.plateforme = plateforme;
        this.tables = tables;
        this.choices = {};
        this.first = 0;
        this.readPos = 0;
        this.type = "";
        this.date = "";

        $(document).on("click", "#dates-down", () => {
            if(this.first < Object.keys(this.choices).length-6) {
                this.first += 3;
            }
            else {
                this.first = Object.keys(this.choices).length-6;
            }
            this.displayDates();
        });

        $(document).on("click", "#dates-up", () => {
            if(this.first > 3) {
                this.first -= 3;
            }
            else {
                this.first = 0;
            }
            this.displayDates();
        });

        $(document).on("click", "#dates-center", () => {
            if(this.type == "read") {
                if(this.readPos > 5) {
                    this.first = readPos - 5;
                }
                else {
                    this.first = 0;
                }
            }
            else {
                if(Object.keys(this.choices).length > 6) {
                    first = Object.keys(this.choices).length - 6;
                }
                else {
                    this.first = 0;
                }
            }
            this.displayDates();
        });

        $(document).on("click", "#dates-remove .clickable", (evt) => {
            const key = $(evt.currentTarget).data('key');
            this.date = key.split("-")[1];
            $('#save-modal').addClass("show");
            $('#save-modal').css("display", "block");
        });

        $(document).on("click", "#dates-load .clickable", (evt) => {
            const key = $(evt.currentTarget).data('key');
            this.type = key.split("-")[0];
            this.date = key.split("-")[1];
            if(this.type == "replace") {
                $('#save-modal').addClass("show");
                $('#save-modal').css("display", "block");
            }
            else {
                $('#tables-dates').trigger("apply", [this.date]);
                //this.applyTarifs();
            }
        });

        $(document).on("click", "#modal-no", () => {
            $('#save-modal').removeClass("show");
            $('#save-modal').css("display", "none");
            if(this.type == "replace") {
                $('#tables-dates').trigger("apply", [this.date]);
                //this.applyTarifs();
            }
            if(this.type == "remove") {
                $('#tables-dates').trigger("remove", [this.date]);
                //this.removeTarifs();
            }
        });

        $(document).on("click", "#modal-yes", () => {
            $('#save-modal').removeClass("show");
            $('#save-modal').css("display", "none");
            $('#tables-dates').trigger("save", [this.date, this.type]);
        });

    }

    loadDates(choices, first, readPos, type) {
        this.choices = choices;
        this.first = first;
        this.readPos = readPos;
        this.type = type;
        this.displayDates();
    }

    displayDates() {
        if(Object.keys(this.choices).length > 0) {
            let html = '<div>';
            html += '<svg id="dates-center" class="icon icon-selectable date-left" aria-hidden="true">' +
                        '<use xlink:href="#disc"></use>' +
                    '</svg>';
            if(this.first > 0) {
                html += '<svg id="dates-up" class="icon icon-selectable" aria-hidden="true">' +
                            '<use xlink:href="#chevrons-up"></use>' +
                        '</svg>';
            }
            html += '<svg id="dates-remove" class="icon icon-selectable date-right" aria-hidden="true">' +
                        '<use xlink:href="#x"></use>' +
                    '</svg>';
            html += '<table id="dates-' + this.type + '" class="dates-tarifs table table-boxed">';
            for(let pos = 0; pos < Object.keys(this.choices).length; pos++) {
                const key = Object.keys(this.choices)[pos];
                if(pos >= this.first && pos < (this.first + 6)) {
                    const choice = this.choices[key];
                    const dispDate = choice[0];
                    const label = choice[1];
                    let clickable = "clickable";
                    let trClickable = "tr-clickable";
                    if(choice[2] == 0) {
                        clickable = "faded";
                        trClickable = "";
                    }
                    let diode = "";
                    if(choice[3] == 1) {
                        diode = '<svg class="icon" aria-hidden="true">' +
                                    '<use xlink:href="#skip-forward"></use>' +
                                '</svg> ';
                    }
                    let base = "";
                    if(choice[4] == 1) {
                        base = '<svg class="icon" aria-hidden="true">' +
                                    '<use xlink:href="#database"></use>' +
                                '</svg> ';
                    }
                    let warning = "";
                    if(choice[5] != "") {
                        warning = '<button aria-hidden="true" type="button" class="btn-invisible popover-warning" data-toggle="popover" data-trigger="focus"' +
                                            'data-content="' + choice[5] + '">' +
                                        '<svg class="icon icon-selectable red" aria-hidden="true">' +
                                            '<use xlink:href="#alert-triangle"></use>' +
                                        '</svg>' +
                                    '</button>';
                    }

                    html += '<tr class="' + trClickable + '"><td>' + warning + '</td><td class="' + clickable + ' borded" data-key="' + key +'">' + diode + base + dispDate + '</td><td class="' + clickable + ' borded" data-key="' + key +'">' + label + '</td><td></td></tr>';
                }
            }
            html += '</table>';
            if((this.first + 6) < Object.keys(this.choices).length) {
                html += '<svg id="dates-down" class="icon icon-selectable" aria-hidden="true">' +
                            '<use xlink:href="#chevrons-down"></use>' +
                        '</svg>';
            }
            html += '</div>';
            $('#tables-dates').html(html);
            $('.popover-warning').popover();
        }
        else {
            $('#tables-dates').html("Pas de données dans la période autorisée");
        }
    }
}

$(document).on("click", "#dates-remove", function() {
    $('#tables-files').show();
    $('#tables-dates').html("");
});

$(document).on("click", "#close-modal", function() {
    $('#save-modal').removeClass("show");
    $('#save-modal').css("display", "none");
});
