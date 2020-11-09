
define(function() {
    var coefs = [];

    function process(data) {
        // quit if the form wasn't created (an exception was thrown)
        if (!document.getElementById('id_responseformat')) {
            return;
        }

        coefs = data;
        for (var i = 0; i < coefs.length; i++) {
            // eslint-disable-next-line no-loop-func
            (function() {
                var coef = coefs[i];
                var element = document.getElementById(coef);
                element.addEventListener('input', function() {
                    onInput(element, coef, false);
                });
                onInput(element, coef, true);

                element.addEventListener('dblclick', function() {
                    if (element.placeholder.length !== 0) {
                        element.value = element.placeholder;
                    }
                    element.dispatchEvent(new CustomEvent('update'));
                });
            }());
        }
    }

    function Item(id, value) {
        this.id = id;
        this.placeHolder = value.length === 0;
        this.value = this.placeHolder ? 0 : parseInt(value);
    }

    Item.prototype.toString = function() {
        return this.id + " : " + this.value;
    };

    function onInput(element, coef, load) {
        var str = element.value.toString().replace(/[^0-9]/g, '');
        if (str.length !== 0) {
            var value = parseInt(str, 10);
            if (value > 100) {
                value = 100;
            }
            element.value = value;
            element.placeholder = '';
        } else {
            element.value = '';
        }

        var sum = 0;
        var newCoefs = [];
        for (var i = 0; i < coefs.length; i++) {
            var coefId = coefs[i];
            var coefIterator = document.getElementById(coefId);
            if (!coefIterator.hidden) {
                newCoefs.push(coefId);
                sum += coefIterator.value.length === 0 ? 0 : parseInt(coefIterator.value, 10);
            }
        }

        if (sum <= 100) {
            updatePlaceholders(sum, newCoefs);
        } else {
            updateValues(sum, coef, newCoefs);
        }
        if (!load) {
            updateErrors();
        }
    }

    function updatePlaceholders(sum, newCoefs) {
        var i,
            values = [];
        for (i = 0; i < newCoefs.length; i++) {
            var coefId = newCoefs[i];
            var item = new Item(coefId, document.getElementById(coefId).value);
            if (item.placeHolder) {
                values.push(item);
            }
        }

        if (values.length === 0) {
            return;
        }

        sum = 100 - sum;
        var part = Math.floor(sum / values.length);
        for (i = 0; i < values.length; i++) {
            values[i].value = part;
            sum -= part;
        }
        if (values.length > 1 && sum !== 0) {
            for (var index = 0; sum !== 0; sum--) {
                values[index].value++;
                index = index + 1 === values.length ? 0 : index + 1;
            }
        }
        for (i = 0; i < values.length; i++) {
            var value = values[i];
            document.getElementById(value.id).placeholder = value.value;
        }
    }

    function updateValues(sum, coef, newCoefs) {
        var value,
            i,
            values = [];
        sum -= 100;
        for (i = 0; i < newCoefs.length; i++) {
            var coefId = newCoefs[i];
            var item = new Item(coefId, document.getElementById(coefId).value);
            if (item.value !== 0 && coefId !== coef) {
                values.push(item);
            }
        }

        var part = Math.floor(sum / values.length);
        for (i = 0; i < values.length; i++) { // subtract as much as we can
            value = values[i];
            if (value.value - part >= 0) {
                value.value -= part;
                sum -= part;
            } else {
                sum -= value.value;
                value.value = 0;
            }
        }
        if (values.length > 1 && sum !== 0) {
            values.sort(function(a, b) {
                return b.value - a.value;
            }); // sort values
            while (sum-- !== 0) {             // now start subtracting 1
                var temp = values.shift();    // and keeping the sort order
                temp.value--;
                var index = 0;
                for (i = 0; i < values.length; i++) {
                    if (values[i] <= temp.value) {
                        index = i;
                        break;
                    }
                }
                values.splice(index, 0, temp);
            }
        }
        for (i = 0; i < values.length; i++) { // replacing values
            value = values[i];
            document.getElementById(value.id).value = value.value;
        }
    }

    function updateErrors() {
        for (var i = 0; i < coefs.length; i++) {
            document.getElementById(coefs[i]).dispatchEvent(new CustomEvent('update'));
        }
    }
    return {process: process};
});