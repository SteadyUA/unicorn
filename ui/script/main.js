function initOptions() {
    let options = ('; ' + document.cookie).split(`; options=`).pop().split(';')[0];
    options = options ? JSON.parse(options) : {"local": true, "external": false, "no-dev": true};
    const input = document.querySelectorAll('#options input');
    input.forEach(function (input) {
        input.checked = options[input.id] || false;
        input.onchange = function () {
            options[this.id] = this.checked;
            document.cookie = "options=" + JSON.stringify(options) + ";"
                + "expires=" + (new Date(Date.now() + 360*24*60*60*1000)).toUTCString() + ";path=/";
            ln();
        }
    })
}

function ln(params) {
    params = params || {};
    const search = new URLSearchParams(document.location.search);
    for (const [key, value] of Object.entries(params)) {
        if (value === null) {
            search.delete(key)
        } else {
            search.set(key, value.toString())
        }
    }
    document.location = '/?' + search.toString().replaceAll('%2F', '/');
}
