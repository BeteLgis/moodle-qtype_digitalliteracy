define(function() {
    function process(data) {
        const params = {};
        const coefs = {};
        const items = data['items'];
        const errors = data['errors'];
        const sharedObject = {};

        for (const property in items) {
            const item = items[property];
            if (item['type']) {
                params[property] = item['group'];
            } else {
                coefs[property] = item['group'];
            }
        }

        for (const coef in coefs) { // Validate coefficients
            sharedObject[coef] = false;
            const key = 'id_' + coef;
            const element = document.getElementById(key);
            const validate = function () {
                clear();
                validateString(element, coef);
                if (countTrue(0, sharedObject)) {
                    validateSum();
                }
            };
            element.addEventListener('input', validate);
            element.addEventListener('mouseover', validate);
        }

        function showOrHide(group, key) {
            const error = document.getElementById('id_error_' + group);
            if (key.toString().length === 0) {
                error.innerText = '';
                error.setAttribute('style', '');
            } else {
                error.innerText = errors[key];
                error.setAttribute('style', 'display: block');
            }
        }

        function validateString(element, coef) { // Validate for int in range [0;100]
            let str = element.value.toString().replace(/[0-9]/g, '');
            if (str.length !== 0) {
                showOrHide(coefs[coef], 'validatecoef');
                sharedObject[coef] = true;
            } else {
                str = element.value.toString();
                let value = str === '' ? -1 : parseInt(str, 10);
                if (value < 0 || value > 100) {
                    showOrHide(coefs[coef], 'validatecoef');
                    sharedObject[coef] = true;
                } else {
                    showOrHide(coefs[coef], '');
                    sharedObject[coef] = false;
                }
            }
        }

        function countTrue(required, object) {
            let counter = 0;
            for (const property in object) {
                if (object[property])
                    counter++;
            }
            return counter === required;
        }

        function validateSum() { // Validate coefficients sum
            let sum = 0;
            for (const coef in coefs) {
                const key = 'id_' + coef;
                sum += parseInt(document.getElementById(key).value, 10);
            }
            for (const coef in coefs) {
                showOrHide(coefs[coef], sum !== 100 ? 'notahunred' : '');
            }
        }

        function clear() {
            for (const coef in coefs) {
                if (!sharedObject[coef])
                    showOrHide(coefs[coef], '');
            }
        }

        function validateParams() { // at least 1 param in each group should be chosen
            const groups = {};
            for (const param in params) {
                const key = 'id_' + param;
                const element = document.getElementById(key);
                const group = params[param];
                if (typeof groups[group] === "undefined") {
                    groups[group] = {};
                }
                groups[group][param] = element.checked;

                const validate = function () {
                    groups[group][param] = element.checked;
                    showOrHide(group, countTrue(0, groups[group]) ? 'tickacheckbox' : '');
                };
                element.addEventListener('change', validate);
                element.addEventListener('mouseover', validate);
            }
        }
        validateParams();
    }
    return {process: process};
});