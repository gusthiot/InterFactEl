
export function input(paramCol, cell, num) {
    switch(paramCol.type) {
        case "num":
            return number(cell, paramCol);
        case "txt":
            return text(cell);
        case "alphanum":
            return alphanum(cell);
        case "menu":
            return menu(cell, paramCol);
        case "ref":
            return ref(cell, paramCol);
        case "line":
            return num;
        default:
            return cell;
    }
}

export function number(value, params) {
    let ret = '<input class="param-input" type="number" size="6" value="' + value + '" ';
    if(params.max) {
        ret += ' max="' + params.max + '" ';
    }
    if(params.int) {
        ret += ' step="1" ';
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

export function text(value) {
    return '<input class="param-input" type="text" value="' + value + '">';
}

export function alphanum(value) {
    return '<input class="param-input" type="text" value="' + value + '" pattern="[A-Za-z]{3}" >';
}

export function menu(value, params) {
    let ret = '<select class="param-select">';
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

export function ref(value, params) {
    const contents = JSON.parse(sessionStorage.getItem("contents"));
    const ref = contents[params.origin];
    let ret = '<select class="param-select">';
    if(params.zero) {
        ret += '<option value="0"';
        if(value == "0") {
            ret += ' selected ';
        }
        ret += '>0 - Aucun</option>';
    }
    let num = 0;
    for(const key in ref) {
        if(num > 0) {
            if(params.col && (params.value != ref[key][params.col])) {
                continue;
            }
            ret += '<option value="' + ref[key][0] + '"';
            if(value == ref[key][0]) {
                ret += ' selected ';
            }
            ret += '>' + ref[key][0];
            if(params.intitule) {
                ret += " - " + ref[key][params.intitule];
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

export function lineUp() {
    return '<svg id="line-up" class="icon icon-selectable" aria-hidden="true">' +
        '<use xlink:href="#chevron-up"></use>' +
    '</svg>&nbsp;';
}

export function lineDown() {
    return '<svg id="line-down" class="icon icon-selectable" aria-hidden="true">' +
        '<use xlink:href="#chevron-down"></use>' +
    '</svg>&nbsp;';
}

export function lineRemove() {
    return '<svg id="line-remove" class="icon icon-selectable" aria-hidden="true">' +
        '<use xlink:href="#minus-circle"></use>' +
    '</svg>';
}
