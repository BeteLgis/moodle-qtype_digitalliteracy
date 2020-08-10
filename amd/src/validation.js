
define(function() {
    function process(data) {
        const params = {};
        const coefs = {};
        const groupByCoef = {};
        const paramsByGroup = {};
        const sharedObject = {}; // [groupname] => bool - coef has a value parse error
        const errorsContainer = {}; // [groupname] => [errorType] => string (errorType - 'coef' or 'param')

        for (const property in data['items']) {
            const item = data['items'][property];
            const group = item['group'];
            if (item['type']) {
                params[property] = group;
            } else {
                coefs[property] = group;
                errorsContainer[group] = {'coef' : '', 'param' : ''};
                groupByCoef[group] = property;
            }
        }

        for (const coef in coefs) { // Validate coefficients
            sharedObject[coef] = false;
            const group = coefs[coef];
            const key = 'id_' + coef;
            const element = document.getElementById(key);
            const validate = function () {
                validateString(element, coef, group);
                if (countTrue(0, sharedObject)) {
                    validateSum();
                }
                showOrHide(group);
            };
            const validateGroup = function() {
               validate();
               for (const param in paramsByGroup[group]) {
                   const paramElement = document.getElementById('id_' + param);
                   paramElement.dispatchEvent(new CustomEvent('change'));
               }
            };
            element.addEventListener('input', validateGroup);
            element.addEventListener('dblclick', validateGroup);
            validate();
        }

        function showOrHide(group) {
            let message = [];
            for (const type in errorsContainer[group]) {
                if (errorsContainer[group][type].toString().length !== 0) {
                    message.push(data['errors'][errorsContainer[group][type]]);
                }
            }
            const error = document.getElementById('id_error_' + group);
            const res = message.join(' | ');
            if (res.length === 0) {
                error.innerText = '';
                error.setAttribute('style', '');
            } else {
                error.innerText = res;
                error.setAttribute('style', 'display: block');
            }
        }

        function validateString(element, coef, group) { // Validating string (int in range [0;100] is needed)
            const res = isValid(element.value.toString());
            if (res < 0) {
                errorsContainer[group]['coef'] = 'validatecoef';
                sharedObject[coef] = true;
            } else {
                if (res === 0)
                    errorsContainer[group]['param'] = '';
                errorsContainer[group]['coef'] = '';
                sharedObject[coef] = false;
            }
        }

        function isValid(string) {
            const str = string.replace(/[^0-9]/g, '');
            if (str.length !== 0) {
                const value = parseInt(str, 10);
                return value >= 0 && value <= 100 ? value : -1;
            }
            return -1;
        }

        function countTrue(required, object) { // counting true properties of object
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
                const group = coefs[coef];
                errorsContainer[group]['coef'] = sum !== 100 ? 'notahunred' : '';
                showOrHide(group);
            }
        }

        function validateParams() { // at least 1 param in each group should be chosen
            for (const param in params) {
                const key = 'id_' + param;
                const element = document.getElementById(key);
                const group = params[param];
                if (typeof paramsByGroup[group] === "undefined") {
                    paramsByGroup[group] = {};
                }
                paramsByGroup[group][param] = element.checked;

                const validate = function () {
                    const coef = document.getElementById('id_' + groupByCoef[group]);
                    const res = isValid(coef.value.toString());
                    paramsByGroup[group][param] = element.checked;
                    if (res !== 0) {
                        errorsContainer[group]['param'] = countTrue(0, paramsByGroup[group]) ? 'tickacheckbox' : '';
                    } else {
                        errorsContainer[group]['param'] = '';
                    }
                    showOrHide(group);
                };
                element.addEventListener('change', validate);
                validate();
            }
        }
        validateParams();
    }
    return {process: process};
});