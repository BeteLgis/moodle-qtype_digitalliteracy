
define(function() {
    function process(data) {
        const groupByCoef = data['coefs_map'];
        const paramsByGroup = data['params_map'];
        const coefValueByGroup = {}; // [groupname] => coef value (-1 means parse error)
        const errorsContainer = {}; // [groupname] => [errorType] => string (errorType - 'coef' or 'param')

        for (const coef in groupByCoef) {
            const group = groupByCoef[coef];
            coefValueByGroup[group] = 0;
            errorsContainer[group] = {'coef' : '', 'param' : ''};
        }

        for (const coef in groupByCoef) { // Validate coefficients
            const group = groupByCoef[coef];
            const element = document.getElementById('id_' + coef);
            function validateCoef(load = false) {
                const res = isValid(element.value.toString());
                coefValueByGroup[group] = res;
                errorsContainer[group]['coef'] = res < 0 ? 'validatecoef' : '';

                const values = Object.values(coefValueByGroup);
                const hasParseErrors = values.includes(-1);
                const notAHundred = !hasParseErrors ? values.reduce((a, b) => a + b, 0) !== 100 : false;
                for (const groupName in coefValueByGroup) {
                    if (coefValueByGroup[groupName] >= 0) // === errorsContainer[groupName]['coef'] === ''
                        errorsContainer[groupName]['coef'] = notAHundred ? 'notahundred' : '';
                    if (!load)
                        showOrHide(groupName);
                }
            }
            element.addEventListener('update', function () {
                validateCoef();
            });
            validateCoef(true);
        }

        function showOrHide(group) {
            let message = [];
            for (const type in errorsContainer[group]) {
                if (type === 'param' && coefValueByGroup[group] === 0)
                    continue;
                if (errorsContainer[group][type].toString().length !== 0) {
                    message.push(data['errors'][errorsContainer[group][type]]);
                }
            }
            const error = document.getElementById('id_error_' + group);
            const res = message.join(' | ');
            if (res.length === 0) {
                error.innerText = '';
                error.style.display = '';
            } else {
                error.innerText = res;
                error.style.display = 'block';
            }
        }

        function isValid(string) { // Validating string (int in range [0;100] is needed)
            const str = string.replace(/[^0-9]/g, '');
            if (str.length !== 0) {
                const value = parseInt(str, 10);
                return value >= 0 && value <= 100 ? value : -1;
            }
            return -1;
        }

        for (const group in paramsByGroup) {
            function validateParams (load = false) { // at least 1 param in each group should be chosen
                let flag = false;
                let counter = 0; // used to check if all group is hidden
                for (const param of paramsByGroup[group]) {
                    const element = document.getElementById('id_' + param);
                    if (element.hidden) {
                        counter++;
                    } else if (element.checked) {
                        flag = true;
                        break;
                    }
                }
                errorsContainer[group]['param'] = flag || counter === paramsByGroup[group].length
                    ? '' : 'tickacheckbox';
                if (!load)
                    showOrHide(group);
            }
            paramsByGroup[group].forEach(param => document.getElementById('id_' + param).
                addEventListener('change', function () {
                    validateParams();
            }));
            validateParams(true);
        }
    }
    return {process: process};
});