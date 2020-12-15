
define(function() {
    // Source https://stackoverflow.com/questions/14227388/unserialize-php-array-in-javascript
    var PHP = {
        parse: function parse(str) {
            var offset = 0,
                values = [null];

            var kick = function kick(msg, i) {
                i = i || offset;
                throw new Error("Error at " + i + ": " + msg + "\n" + str + "\n" + " ".repeat(i) + "^");
            };
            var read = function(expected, ret) {
                // eslint-disable-next-line no-return-assign
                return expected === str.slice(offset, offset += expected.length) ? ret :
                    kick("Expected '" + expected + "'", offset - expected.length);
            };

            function readMatch(regex, msg, terminator) {
                terminator = terminator || ";";
                read(":");
                var match = regex.exec(str.slice(offset));
                if (!match) {
                    kick("Expected " + msg + ", but got '" + str.slice(offset).match(/^[:;{}]|[^:;{}]*/)[0] + "'");
                }
                offset += match[0].length;
                return read(terminator, match[0]);
            }

            function readUtf8chars(numUtf8Bytes, terminator) {
                terminator = terminator || "";
                var i = offset;
                while (numUtf8Bytes > 0) {
                    var code = str.charCodeAt(offset++);
                    // eslint-disable-next-line no-bitwise,no-nested-ternary
                    numUtf8Bytes -= code < 0x80 ? 1 : code < 0x800 || code >> 11 === 0x1B ? 2 : 3;
                }
                return numUtf8Bytes ? kick("Invalid string length", i - 2) : read(terminator, str.slice(i, offset));
            }

            var readUInt = function readUInt(terminator) {
                return +readMatch(/^\d+/, "an unsigned integer", terminator);
            };
            var readString = function readString(terminator) {
                terminator = terminator || "";
                return readUtf8chars(readUInt(':"'), '"' + terminator);
            };

            function readKey() {
                var typ = str[offset++];
                // eslint-disable-next-line no-nested-ternary
                return typ === "s" ? readString(";")
                    : typ === "i" ? readUInt(";")
                        : kick("Expected 's' or 'i' as type for a key, but got ${str[offset-1]}", offset - 1);
            }

            function readObject(obj) {
                for (var i = 0, length = readUInt(":{"); i < length; i++) {
                    obj[readKey()] = readValue();
                }
                return read("}", obj);
            }

            function readArray() {
                var obj = readObject({});
                return Object.keys(obj).some(function(key, i) {
                    return key !== i.toString();
                }) ? obj : Object.values(obj);
            }

            function readValue() {
                var typ = str[offset++].toLowerCase();
                var ref = values.push(null) - 1;
                // eslint-disable-next-line no-nested-ternary
                var val = typ === "s" ? readString(";")
                        : typ === "a" ? readArray() // Associative array
                            : kick("Unexpected type " + typ, offset - 1);
                if (typ !== "r") {
                    values[ref] = val;
                }
                return val;
            }

            var val = readValue();
            if (offset !== str.length) {
                kick("Unexpected trailing character");
            }
            return val;
        }
    };

    var params = {},
        groups = {},
        fileTypesMatches = {},
        fileTypeDefaults = {},
        responseFormat = null,
        fileTypeInput = null,
        labels = {};

    function process(data) {
        // quit if the form wasn't created (an exception was thrown)
        if (!document.getElementById('id_responseformat')) {
            return;
        }

        params = data['params'];
        groups = data['groups'];
        fileTypesMatches = data['types']['matches'];
        fileTypeDefaults = data['types']['defaults'];
        responseFormat = document.getElementById('id_responseformat');
        fileTypeInput = document.getElementById('id_filetypeslist');
        fileTypeInput.setAttribute('readonly', 'true');

        // Parsing (and after removing) our container
        var temp = document.getElementById('id_labels_container');
        var serializedData = temp.getAttribute('data-serialized');
        labels = PHP.parse(serializedData);
        temp.remove();
        // eslint-disable-next-line no-console
        console.log(Object.keys(labels).length + ' labels were successfully parsed, container was removed');

        for (var group in groups) {
            if (recursiveTextWrapper(document.getElementById('fgroup_id_' + group)) !== groups[group] + 1) {
                // eslint-disable-next-line no-console
                console.log('Group ' + group + ' was incorrectly labeled');
            }
        }

        responseFormat.addEventListener('change', function() {
            changeLabels(false);
        });
        changeLabels(true);
        spanChecker();
    }

    function recursiveTextWrapper(element) {
        var counter = 0;
        for (var i = 0; i < element.childNodes.length; i++) {
            var child = element.childNodes[i];
            if (child.nodeType === Node.TEXT_NODE) {
                var text = child.textContent.toString().trim();
                if (text.length !== 0) {
                    var spanNode = document.createElement('span');
                    spanNode.appendChild(document.createTextNode(text));
                    // eslint-disable-next-line no-nested-ternary
                    spanNode.id = groups[text] !== undefined ? 'fgroup_id_' + text + '_label_span' :
                        (params[text] !== undefined ? 'id_' + text + '_label_span' : '');
                    if (spanNode.id.length !== 0) {
                        element.replaceChild(spanNode, child);
                        counter++;
                    }
                }
            } else if (child.childNodes.length !== 0) {
                counter += recursiveTextWrapper(child);
            }
            // I don't interrupt if counter === maxCount for the test purposes!
        }
        return counter;
    }

    // Set new labels (depending on responseformat value)
    function changeLabels(load) {
        var label,
            element,
            format = responseFormat.options[responseFormat.selectedIndex].value;

        for (var param in params) {
            label = document.getElementById('id_' + param + '_label_span');
            element = document.getElementById('id_' + param);
            hideOrUnhideAndRename([element, element.parentElement], labels[param + '_' + format], label);
            element.dispatchEvent(new CustomEvent('change'));
        }

        for (var group in groups) {
            label = document.getElementById('fgroup_id_' + group + '_label_span');
            element = document.getElementById('fgroup_id_' + group);
            var coef = document.getElementById('id_' + group + 'coef');
            if (hideOrUnhideAndRename([element, coef], labels[group + '_' + format], label)) {
                coef.value = 0;
            }
            coef.dispatchEvent(new CustomEvent('update'));
        }
        filetypesDescription(format, load);
    }

    function hideOrUnhideAndRename(elements, newText, label) {
        if (newText === undefined) {
            elements.forEach(function(param) {
                param.hidden = true; // Flag (needed in other js scripts)
                param.style.display = 'none';
            });
        } else {
            elements.forEach(function(param) {
                param.hidden = false;
                param.style.display = '';
            });
            if (label) {
                label.replaceChild(document.createTextNode(newText), label.firstChild);
            }
        }
        return newText === undefined;
    }

    // Changing filetypelist labels
    function filetypesDescription(format, load) {
        if (!load) {
            fileTypeInput.value = fileTypeDefaults[format].value;
            var description = document.querySelector('[data-filetypesdescriptions="id_filetypeslist"]');
            if (!description) {
                return;
            }
            description.firstChild.innerHTML = labels['filetype_description'];
            var descriptionSample = description.firstChild.firstChild.firstChild;
            descriptionSample.firstChild.replaceChild(document.createTextNode(
                fileTypeDefaults[format].description + ' '), descriptionSample.firstChild.firstChild);
            descriptionSample.lastChild.replaceChild(document.createTextNode(fileTypeInput.value),
                descriptionSample.lastChild.firstChild);
        }
    }

    function bodyChecker() {
        var body = document.querySelector('[data-filetypesbrowserbody="id_filetypeslist"]');
        if (body === null || body.hasAttribute('data-used')) {
            setTimeout(bodyChecker, 100); // File type browser body (Modal) is firstly
            // loaded when user click 'Choose' for the first time.
            // Here I wait for that moment
        } else {
            // Body is always rerendered, that's why I use attribute to deteriorate the old from the new one
            body.setAttribute('data-used', 'true');
            var format = responseFormat.options[responseFormat.selectedIndex].value;
            for (var i = 0; i < body.children.length; i++) { // Leaving only type, which are acceptable now
                // eslint-disable-next-line no-loop-func
                (function() {
                    var child = body.children[i];
                    var option = '';
                    if (child.getAttribute('data-filetypesbrowserkey') !== fileTypesMatches[format]) {
                        option = 'none';
                    } else {
                        var browserkeys = child.querySelectorAll('input[data-filetypesbrowserkey]' +
                            '[type="checkbox"]');
                        var count = fileTypeInput.value.toString();
                        count = count === "" ? 0 : count.replace(',',
                            ' ').split(' ').length;
                        var checked = {count: count, last: null};
                        disableLastKey(browserkeys, checked);
                        for (var j = 0; j < browserkeys.length; j++) {
                            // eslint-disable-next-line no-loop-func
                            (function() {
                                var browserkey = browserkeys[j];
                                browserkey.addEventListener('change', function() {
                                    checked.count += browserkey.checked ? 1 : -1;
                                    disableLastKey(browserkeys, checked);
                                });
                            }());
                        }
                    }
                    child.style.display = option;
                }());
            }
        }
    }

    function disableLastKey(browserkeys, checked) {
        if (checked.count === 1) {
            checked.last = Array.from(browserkeys).find(function(el) {
                return el.checked;
            });
        }
        if (checked.last) {
            checked.last.disabled = checked.count === 1;
        }
    }

    function spanChecker() {
        var span = document.querySelector('[data-filetypesbrowser="id_filetypeslist"]');
        if (!span) {
            return;
        }
        if (span.childElementCount > 0) {
            span.firstChild.addEventListener('click', function() {
                bodyChecker();
            });
        } else { // Button is loaded by yui [not instantly], so we wait
            setTimeout(spanChecker, 100);
        }
    }
    return {process: process};
});