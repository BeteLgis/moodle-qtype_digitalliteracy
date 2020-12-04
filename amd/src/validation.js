
define(function() {
    var errors = {}, // error messages (localized)
        paramsByGroup = {},
        paramsValuesByGroup = {}, // [groupname] => [index] => {'hidden', 'checked'} for each parameter
        groupByCoef = {},
        coefValueByGroup = {}, // [groupname] => coefficient value (-1 means parse error)
        errorsContainer = {}, // [groupname] => [errorType] => string (errorType - 'coef' or 'params')
        version = 0,
        fontparams = null;

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
        fontparams = document.getElementById('fitem_id_fontparams');

        for (coef in groupByCoef) {
            group = groupByCoef[coef];
            coefValueByGroup[group] = 0;
            errorsContainer[group] = {'coef': '', 'params': ''};
        }

        for (group in paramsByGroup) {
            paramsValuesByGroup[group] = [];
            // eslint-disable-next-line no-loop-func
            paramsByGroup[group].forEach(function(param, index) {
                paramsValuesByGroup[group][index] = {'hidden': false, 'checked': false};
            });
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
                paramsByGroup[grp].forEach(function(param, index) {
                    var element = document.getElementById('id_' + param);
                    element.addEventListener('change', function() {
                        validateParam(element.hidden, element.checked, index, grp, false);
                    });
                    validateParam(element.hidden, element.checked, index, grp, true);
                });
            })(group);
        }
    }

    function validateCoef(elementValue, group, load) {
        var res = isValid(elementValue);
        coefValueByGroup[group] = res;
        toggleAutocompletes(group);
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
            if (type === 'params' && coefValueByGroup[group] === 0) {
                continue;
            }
            if (groupErrors[type].toString().length !== 0) {
                message.push(errors[groupErrors[type]]);
            }
        }
        var error = version > 2020000000 ? // TODO find the exact version
            document.getElementById('fgroup_id_error_' + group) :
            document.getElementById('id_error_' + group);
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
    function validateParam(hidden, checked, index, group, load) {
        paramsValuesByGroup[group][index].hidden = hidden;
        paramsValuesByGroup[group][index].checked = checked;

        var atLeastOneTicked = !hidden && checked;
        if (!atLeastOneTicked) {
            var counter = hidden ? 1 : 0; // Used to check if all group is hidden
            var groupParams = paramsValuesByGroup[group];
            for (var i = 0; !atLeastOneTicked && i < groupParams.length; i++) {
                if (i === index) {
                    continue;
                }
                var param = groupParams[i];
                atLeastOneTicked = !param.hidden && param.checked;
                if (param.hidden) {
                    counter++;
                }
            }
            if (!atLeastOneTicked) {
                atLeastOneTicked = counter === groupParams.length;
            }
        }

        errorsContainer[group].params = atLeastOneTicked ? '' : 'tickacheckbox';
        toggleAutocompletes(group);

        if (!load) {
            showOrHide(group);
        }
    }

    function toggleAutocompletes(group) {
        var visible = coefValueByGroup[group] > 0 && errorsContainer[group].params.length === 0;
        switch (group) {
            case 'group2':
                toggleVisibility(fontparams, visible);
                break;
        }
    }

    function toggleVisibility(element, visible) {
        if (visible) {
            element.hidden = false;
            element.style.display = '';
        } else {
            element.hidden = true;
            element.style.display = 'none';
        }
    }
    return {process: process};
});