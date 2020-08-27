
define(function() {
    class Item {
        constructor(id, value) {
            this.id = id;
            this.placeHolder = value.length === 0;
            this.value = this.placeHolder ? 0 : parseInt(value);
        }

        toString() {
            return `${this.id} : ${this.value} `;
        }
    }

    function process(coefs) {
        for (const coef of coefs) {
            const element = document.getElementById(coef);
            function onInput (load = false) {
                const str = element.value.toString().replace(/[^0-9]/g, '');
                if (str.length !== 0) {
                    let value = parseInt(str, 10);
                    if (value > 100)
                        value = 100;
                    element.value = value;
                    element.placeholder = '';
                } else {
                    element.value = '';
                }

                let sum = 0;
                const newCoefs = [];
                for (const coefId of coefs) {
                    const coefIterator = document.getElementById(coefId);
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
                if (!load)
                    updateErrors(coefs);
            }
            element.addEventListener('input', function () {
                onInput();
            });
            onInput(true);

            function updateErrors(coefs) {
                for (const coefId of coefs)
                    document.getElementById(coefId).dispatchEvent(new CustomEvent('update'));
            }

            element.addEventListener('dblclick', function () {
                if (element.placeholder.length !== 0)
                    element.value = element.placeholder;
                element.dispatchEvent(new CustomEvent('update'));
            });
        }
        
        function updatePlaceholders(sum, newCoefs) {
            let values = [];
            for (const coefId of newCoefs) {
                let item = new Item(coefId, document.getElementById(coefId).value);
                if (item.placeHolder)
                    values.push(item);
            }

            if (values.length === 0)
                return;

            sum = 100 - sum;
            const part = Math.floor(sum / values.length);
            for (const value of values) {
                value.value = part;
                sum -= part;
            }
            if (values.length > 1 && sum !== 0) {
                for (let index = 0; sum !== 0; sum--) {
                    values[index].value++;
                    index = index + 1 === values.length ? 0 : index + 1;
                }
            }
            for (const value of values)
                document.getElementById(value.id).placeholder = value.value;
        }

        function updateValues(sum, coef, newCoefs) {
            sum -= 100;
            let values = [];
            for (const coefId of newCoefs) {
                let item = new Item(coefId, document.getElementById(coefId).value);
                if (item.value !== 0 && coefId !== coef)
                    values.push(item);
            }

            const part = Math.floor(sum / values.length);
            for (const value of values) { // subtract as much as we can
                if (value.value - part >= 0) {
                    value.value -= part;
                    sum -= part;
                }
                else {
                    sum -= value.value;
                    value.value = 0;
                }
            }
            if (values.length > 1 && sum !== 0) {
                values.sort((a, b) => b.value - a.value); // sort values
                while (sum-- !== 0) {             // now start subtracting 1
                    let temp = values.shift();    // and keeping the sort order
                    temp.value--;
                    let index = 0;
                    for (let i = 0; i < values.length; i++) {
                        if (values[i] <= temp.value) {
                            index = i;
                            break;
                        }
                    }
                    values.splice(index, 0, temp);
                }
            }
            for (const value of values) // replacing values
                document.getElementById(value.id).value = value.value;
        }
    }
    return {process: process};
});