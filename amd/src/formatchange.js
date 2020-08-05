
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
        const format = document.getElementById('id_responseformat');
        const fileTypeInput = document.getElementById('id_filetypeslist');
        fileTypeInput.setAttribute('readonly', 'true');

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

        format.addEventListener('change', function () {
            changeLabels();
        });
        format.dispatchEvent(new CustomEvent('change'));

        function textWrapper(label, name, key, postfix) { // put (wrap) label text into a span
            label.normalize();
            const TextNodes = [];
            label.childNodes.forEach(function (node) {
                if (node.nodeType === Node.TEXT_NODE) {
                    const text = typeof node.textContent == 'string' ? node.textContent : node.innerText;
                    if (text.trim() !== "")
                        TextNodes.push(node);
                }
            })
            if (TextNodes.length !== 1) {
                console.log('TextNodes.length = ' + TextNodes.length + ' label id ' + label.id);
            }
            const textNode = TextNodes[0];
            const spanNode = document.createElement('span');
            const text = typeof textNode.textContent == 'string' ? textNode.textContent : textNode.innerText;
            spanNode.appendChild(document.createTextNode(text));
            spanNode[key] = name + postfix;
            label.replaceChild(spanNode, textNode);
        }

        function changeLabels() { // set new labels (depending on responseformat)
            const value = format.options[format.selectedIndex].value;
            for (const param of params) {
                const element = document.getElementById('id_' + param + '_label_span');
                element.replaceChild(document.createTextNode(labels[param + '_' + value]),
                    element.firstChild);
            }
            for (const group of groups) {
                const element = document.getElementById('fgroup_id_' + group + '_label_span');
                element.replaceChild(document.createTextNode(labels[group + '_' + value]),
                    element.firstChild);

                const title = document.getElementById('fgroup_id_' + group + '_help_title');
                title.setAttribute('title', labels[group + '_help_title_' + value]);
                title.setAttribute('aria-label', labels[group + '_help_title_' + value]);

                const text = document.getElementById('fgroup_id_' + group + '_help_text');
                text.setAttribute('data-content', labels[group + '_help_text_' + value]);
            }

            fileTypeInput.value = fileTypeDefaults[value]['value'];
            const description = document.querySelector('[data-filetypesdescriptions="id_filetypeslist"]');
            description.firstChild.innerHTML = labels['filetype_description'];
            const descriptionSample = description.firstChild.firstChild.firstChild;
            descriptionSample.firstChild.replaceChild(document.createTextNode(
                fileTypeDefaults[value]['description'] + ' '), descriptionSample.firstChild.firstChild);
            descriptionSample.lastChild.replaceChild(document.createTextNode(fileTypeInput.value),
                descriptionSample.lastChild.firstChild);
        }

        const bodyChecker = function () {
            const body = document.querySelector('[data-filetypesbrowserbody="id_filetypeslist"]');
            if (body === null || body.hasAttribute('data-used')) {
                setTimeout(bodyChecker, 100);
            } else {
                // body is always rerendered, that's why I use attribute to deteriorate the old from the new one
                body.setAttribute('data-used', 'true');
                const value = format.options[format.selectedIndex].value;
                for (const child of body.children) {
                    let option = '';
                    if (child.getAttribute('data-filetypesbrowserkey') !== fileTypesMatches[value]) {
                        option = 'none';
                    }
                    child.style.display = option;
                }
            }
        }

        const spanChecker = function() { // button is loaded by yui, so we wait
            const span = document.querySelector('[data-filetypesbrowser="id_filetypeslist"]');
            if (span.childElementCount > 0) {
                span.firstChild.addEventListener('click', function () {
                    bodyChecker();
                });
            }
            else {
                setTimeout(spanChecker, 100);
            }
        }
        spanChecker();
    }
    return {process: process};
});