
define(function() {
    class Item {
        constructor(id, value) {
            this.id = id;
            this.value = value;
        }

        toString() {
            return `${this.id} : ${this.value} `;
        }
    }

    function process(coefs) {
        for (const coef of coefs) {
            let element = document.getElementById(coef);
            element.addEventListener('input', function () {
                let str = element.value.toString().replace(/[^0-9]/g, '');
                let value = str === '' ? 0 : parseInt(str, 10);
                if (value > 100)
                    value = 100;
                element.value = value;

                let sum = 0;
                let values = [];
                for (const id of coefs) {
                    let item = new Item(id, parseInt(document.getElementById(id).value));
                    sum += item.value;
                    if (item.value !== 0 && id !== coef)
                        values.push(item);
                }
                if (sum <= 100)
                    return;

                sum -= 100;
                const part = Math.floor(sum / values.length);
                for (const value of values) {
                    if (value.value - part >= 0) {
                        value.value -= part;
                        sum -= part;
                    }
                    else {
                        sum -= value.value;
                        value.value = 0;
                    }
                }
                values.sort((a, b) => b.value - a.value);
                if (values.length > 1 && sum !== 0) {
                    while (sum-- !== 0) {
                        let temp = values.shift();
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
                for (const value of values) {
                    let temp = document.getElementById(value.id);
                    temp.value = value.value;
                    if (value.value === 0)
                        temp.focus();
                }
                element.focus();
            });
        }
    }
    return {process: process};
});