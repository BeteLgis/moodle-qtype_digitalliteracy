
define(function() {
    // source https://stackoverflow.com/questions/14227388/unserialize-php-array-in-javascript
    const PHP = {
        parse(str) {
            let offset = 0;
            const values = [null];

            const kick = (msg, i = offset) => { throw new Error(`Error at ${i}: ${msg}\n${str}\n${" ".repeat(i)}^`) }
            const read = (expected, ret) => expected === str.slice(offset, offset+=expected.length) ? ret
                : kick(`Expected '${expected}'`, offset-expected.length);

            function readMatch(regex, msg, terminator=";") {
                read(":");
                const match = regex.exec(str.slice(offset));
                if (!match) kick(`Exected ${msg}, but got '${str.slice(offset).match(/^[:;{}]|[^:;{}]*/)[0]}'`);
                offset += match[0].length;
                return read(terminator, match[0]);
            }

            function readUtf8chars(numUtf8Bytes, terminator="") {
                const i = offset;
                while (numUtf8Bytes > 0) {
                    const code = str.charCodeAt(offset++);
                    numUtf8Bytes -= code < 0x80 ? 1 : code < 0x800 || code>>11 === 0x1B ? 2 : 3;
                }
                return numUtf8Bytes ? kick("Invalid string length", i-2) : read(terminator, str.slice(i, offset));
            }

            const readUInt    = terminator => +readMatch(/^\d+/, "an unsigned integer", terminator);
            const readString  = (terminator="") => readUtf8chars(readUInt(':"'), '"'+terminator);

            function readKey() {
                const typ = str[offset++];
                return typ === "s" ? readString(";")
                    : typ === "i" ? readUInt(";")
                        : kick("Expected 's' or 'i' as type for a key, but got ${str[offset-1]}", offset-1);
            }

            function readObject(obj) {
                for (let i = 0, length = readUInt(":{"); i < length; i++) obj[readKey()] = readValue();
                return read("}", obj);
            }

            function readArray() {
                const obj = readObject({});
                return Object.keys(obj).some((key, i) => key !== i.toString()) ? obj : Object.values(obj);
            }

            function readValue() {
                const typ = str[offset++].toLowerCase();
                const ref = values.push(null)-1;
                const val = typ === "s" ? readString(";")
                        : typ === "a" ? readArray()  // Associative array
                            : kick(`Unexpected type ${typ}`, offset-1);
                if (typ !== "r") values[ref] = val;
                return val;
            }

            const val = readValue();
            if (offset !== str.length) kick("Unexpected trailing character");
            return val;
        }
    }

    function process(data) {
        const params = data['params'];
        const groups = data['groups'];
        const types = data['types'];

        const fileTypesMatches = types['matches'];
        const fileTypeDefaults = types['defaults'];
        const responseFormat = document.getElementById('id_responseformat');
        const fileTypeInput = document.getElementById('id_filetypeslist');
        fileTypeInput.setAttribute('readonly', 'true');

        // parsing (and after removing) our container
        const temp = document.getElementById('id_labels_container');
        const serializedData = temp.getAttribute('data-serialized');
        const labels = PHP.parse(serializedData);
        temp.remove();
        console.log(Object.keys(labels).length + ' labels were successfully parsed, container was removed');

        for (const param of params) { // setting unique keys for params labels
            const key = 'id_' + param;
            const label = document.getElementById(key).parentElement;
            textWrapper(label, key, 'id', '_label_span');
        }

        for (const group of groups) { // setting unique keys for groups labels
            const key = 'fgroup_id_' + group;
            const label = document.querySelector('[for="' + key + '"]');
            textWrapper(label, key, 'id', '_label_span');

            // setting unique keys for groups help buttons
            const element = document.getElementById(key);
            const help = element.children.item(0).children.item(0).children.item(0);
            help.id = key + '_help_text';

            const hint = help.children.item(0);
            hint.id = key + '_help_title';
        }

        responseFormat.addEventListener('change', function () {
            changeLabels();
        });
        changeLabels(true);

        function textWrapper(label, name, key, postfix) { // put (wrap) label text into a span
            label.normalize();
            let text = "";
            const textNode = Array.from(label.childNodes).find(function (node) {
                if (node.nodeType === Node.TEXT_NODE) {
                    text = typeof node.textContent == 'string' ? node.textContent : node.innerText;
                    return text.trim() !== "";
                }
                return false;
            });
            if (!textNode) {
                console.log('TextNode not found, label id ' + label.id);
                return;
            }
            const spanNode = document.createElement('span');
            spanNode.appendChild(document.createTextNode(text));
            spanNode[key] = name + postfix;
            label.replaceChild(spanNode, textNode);
        }

        function changeLabels(load = false) { // set new labels (depending on responseformat value)
            const value = responseFormat.options[responseFormat.selectedIndex].value;
            for (const param of params) {
                const label = document.getElementById('id_' + param + '_label_span');
                const element = document.getElementById('id_' + param);
                hideOrUnhideAndRename(label, element, element.parentElement, labels[param + '_' + value]);
                element.dispatchEvent(new CustomEvent('change'));
            }
            for (const group of groups) {
                const label = document.getElementById('fgroup_id_' + group + '_label_span');
                const element = document.getElementById('fgroup_id_' + group);
                const newText = labels[group + '_' + value];
                if (!hideOrUnhideAndRename(label, element, element, newText)) {
                    const title = document.getElementById('fgroup_id_' + group + '_help_title');
                    title.setAttribute('title', labels[group + '_help_title_' + value]);
                    title.setAttribute('aria-label', labels[group + '_help_title_' + value]);

                    const text = document.getElementById('fgroup_id_' + group + '_help_text');
                    text.setAttribute('data-content', labels[group + '_help_text_' + value]);
                }
                const coef = document.getElementById('id_' + group + 'coef');
                if (hideOrUnhideAndRename(null, coef, coef.parentElement.parentElement, newText)) {
                    coef.value = 0;
                }
                coef.dispatchEvent(new CustomEvent('update'));
            }
            filetypes_description(load, value);
        }

        function hideOrUnhideAndRename(label, element, container, newText) {
            if (newText === undefined) {
                element.hidden = true; // flag (needed in other js scripts)
                container.style.display = 'none';
            } else {
                element.hidden = false;
                container.style.display = '';
                if (label) {
                    label.replaceChild(document.createTextNode(newText), label.firstChild);
                }
            }
            return element.hidden;
        }

        // changing filetypelist labels
        function filetypes_description(load, value) {
            if (!load) {
                fileTypeInput.value = fileTypeDefaults[value]['value'];
                const description = document.querySelector('[data-filetypesdescriptions="id_filetypeslist"]');
                description.firstChild.innerHTML = labels['filetype_description'];
                const descriptionSample = description.firstChild.firstChild.firstChild;
                descriptionSample.firstChild.replaceChild(document.createTextNode(
                    fileTypeDefaults[value]['description'] + ' '), descriptionSample.firstChild.firstChild);
                descriptionSample.lastChild.replaceChild(document.createTextNode(fileTypeInput.value),
                    descriptionSample.lastChild.firstChild);
            }
        }

        const bodyChecker = function () {
            const body = document.querySelector('[data-filetypesbrowserbody="id_filetypeslist"]');
            if (body === null || body.hasAttribute('data-used')) {
                setTimeout(bodyChecker, 100); // File type browser body (Modal) is firstly
                                                     // loaded when user click 'Choose' for the first time.
                                                     // Here I wait for that moment
            } else {
                // body is always rerendered, that's why I use attribute to deteriorate the old from the new one
                body.setAttribute('data-used', 'true');
                const value = responseFormat.options[responseFormat.selectedIndex].value;
                for (const child of body.children) { // leaving only type, which are acceptable now
                    let option = '';
                    if (child.getAttribute('data-filetypesbrowserkey') !== fileTypesMatches[value]) {
                        option = 'none';
                    } else {
                        const browserkeys = child.querySelectorAll('input[data-filetypesbrowserkey]' +
                            '[type="checkbox"]');
                        let count = fileTypeInput.value.toString();
                        count = count === "" ? 0 : count.replace(',',
                            ' ').split(' ').length;
                        const checked = {count : count, last : null};
                        disableLastKey(browserkeys, checked);
                        for (const browserkey of browserkeys) {
                            browserkey.addEventListener('change', function () {
                                checked.count += browserkey.checked ? 1 : -1;
                                disableLastKey(browserkeys, checked);
                            });
                        }
                    }
                    child.style.display = option;
                }
            }
        }

        function disableLastKey(browserkeys, checked) {
            if (checked.count === 1) {
                checked.last = Array.from(browserkeys).find(function (el) {
                    return el.checked;
                });
            }
            if (checked.last) {
                checked.last.disabled = checked.count === 1;
            }
        }

        const spanChecker = function() {
            const span = document.querySelector('[data-filetypesbrowser="id_filetypeslist"]');
            if (span.childElementCount > 0) {
                span.firstChild.addEventListener('click', function () {
                    bodyChecker();
                });
            }
            else { // button is loaded by yui [not instantly], so we wait
                setTimeout(spanChecker, 100);
            }
        }
        spanChecker();
    }
    return {process: process};
});