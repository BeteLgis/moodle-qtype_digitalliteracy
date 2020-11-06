
define(function() {
    var errors = {};
    var groupByCoef = {};
    var paramsByGroup = {};
    var coefValueByGroup = {}; // [groupname] => coef value (-1 means parse error)
    var errorsContainer = {}; // [groupname] => [errorType] => string (errorType - 'coef' or 'param')

    function process(data) {
        // quit if the form wasn't created (an exception was thrown)
        if (!document.getElementById('id_responseformat')) {
            return;
        }

        var coef,
            group;
        errors = data['errors'];
        groupByCoef = data['coefs_map'];
        paramsByGroup = data['params_map'];

        for (coef in groupByCoef) {
            group = groupByCoef[coef];
            coefValueByGroup[group] = 0;
            errorsContainer[group] = {'coef': '', 'param': ''};
        }

        for (coef in groupByCoef) { // Validate coefficients
            // eslint-disable-next-line no-loop-func
            (function() {
                var group = groupByCoef[coef];
                var element = document.getElementById('id_' + coef);
                element.addEventListener('update', function() {
                    validateCoef(element.value.toString(), group, false);
                });
                validateCoef(element.value.toString(), group, true);
            }());
        }

        for (group in paramsByGroup) {
            // eslint-disable-next-line no-loop-func
            (function(grp) {
                paramsByGroup[grp].forEach(function(param) {
                    document.getElementById('id_' + param).addEventListener('change', function() {
                        validateParams(grp, false);
                    });
                });
                validateParams(grp, true);
            })(group);
        }
    }

    function validateCoef(elementValue, group, load) {
        var res = isValid(elementValue);
        coefValueByGroup[group] = res;
        errorsContainer[group].coef = res < 0 ? 'validatecoef' : '';

        var values = Object.values(coefValueByGroup);
        var hasParseErrors = values.includes(-1);
        var notAHundred = !hasParseErrors ? values.reduce(function(a, b) {
            return a + b;
        }, 0) !== 100 : false;
        for (var groupName in coefValueByGroup) {
            if (coefValueByGroup[groupName] >= 0) { // === errorsContainer[groupName]['coef'] === ''
                errorsContainer[groupName].coef = notAHundred ? 'notahundred' : '';
            }
            if (!load) {
                showOrHide(groupName);
            }
        }
    }

    function showOrHide(group) {
        var message = [];
        var groupErrors = errorsContainer[group];
        for (var type in groupErrors) {
            if (type === 'param' && coefValueByGroup[group] === 0) {
                continue;
            }
            if (groupErrors[type].toString().length !== 0) {
                message.push(errors[groupErrors[type]]);
            }
        }
        var error = document.getElementById('id_error_' + group);
        var res = message.join(' | ');
        if (res.length === 0) {
            error.innerText = '';
            error.style.display = '';
        } else {
            error.innerText = res;
            error.style.display = 'block';
        }
    }

    // Validating string (int in range [0;100] is needed)
    function isValid(string) {
        var str = string.replace(/[^0-9]/g, '');
        if (str.length !== 0) {
            var value = parseInt(str, 10);
            return value >= 0 && value <= 100 ? value : -1;
        }
        return -1;
    }

    // At least 1 param in each group should be chosen
    function validateParams(group, load) {
        var flag = false;
        var groupParams = paramsByGroup[group];
        var counter = 0; // Used to check if all group is hidden
        for (var i = 0; i < groupParams.length; i++) {
            var param = groupParams[i];
            var element = document.getElementById('id_' + param);
            if (element.hidden) {
                counter++;
            } else if (element.checked) {
                flag = true;
                break;
            }
        }
        errorsContainer[group].param = flag || counter === groupParams.length ? '' : 'tickacheckbox';
        if (!load) {
            showOrHide(group);
        }
    }
    return {process: process};
});