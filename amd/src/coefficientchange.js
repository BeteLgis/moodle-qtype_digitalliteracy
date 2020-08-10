
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
            let element = document.getElementById(coef);
            element.addEventListener('input', function () {
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
                for (const id of coefs) {
                    let value = document.getElementById(id).value;
                    sum += value.length === 0 ? 0 : parseInt(value, 10);
                }

                if (sum <= 100) {
                    updatePlaceholders(sum);
                } else {
                    updateValues(sum, element);
                }
            });

            element.addEventListener('dblclick', setPlaceholder);
            function setPlaceholder() {
                if (element.placeholder.length !== 0)
                    element.value = element.placeholder;
            }
        }
        
        function updatePlaceholders(sum) {
            let values = [];
            for (const id of coefs) {
                let item = new Item(id, document.getElementById(id).value);
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

        function updateValues(sum, element) {
            sum -= 100;
            let values = [];
            for (const id of coefs) {
                let item = new Item(id, document.getElementById(id).value);
                if (item.value !== 0 && id !== element.id)
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
            for (const value of values) { // replacing values
                let temp = document.getElementById(value.id);
                temp.value = value.value;
                if (value.value === 0)
                    temp.focus();   // focus to trigger $mform->disabledIf
            }
            element.focus(); // return focus
        }
    }
    return {process: process};
});