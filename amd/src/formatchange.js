
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
        let params = data['params'];
        let groups = data['groups'];
        let size = parseInt(data['size']);
        let format = document.getElementById('id_responseformat');

        function change(labels) {
            let value = format.options[format.selectedIndex].value;
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
        }

        function text_wrapper(label, name) {
            label.normalize();
            let TextNodes = [];
            label.childNodes.forEach(function (node) {
                if (node.nodeType === Node.TEXT_NODE) {
                    let text = typeof node.textContent == 'string' ? node.textContent : node.innerText;
                    if (text.trim() !== "")
                        TextNodes.push(node);
                }
            })
            if (TextNodes.length !== 1) {
                console.log('TextNodes.length = ' + TextNodes.length + ' label id ' + label.id);
            }
            let textNode = TextNodes[0];
            let spanNode = document.createElement('span');
            let text = typeof textNode.textContent == 'string' ? textNode.textContent : textNode.innerText;
            spanNode.appendChild(document.createTextNode(text));
            spanNode.id = name + '_label_span';
            label.replaceChild(spanNode, textNode);
        }

        let success = {flag : false};
        let active = {flag : false};
        document.body.addEventListener('mouseover', onload_succeed);
        document.body.addEventListener('keydown', onload_succeed);
        document.body.addEventListener('scroll', onload_succeed);
        document.body.addEventListener('touchstart', onload_succeed);
        function onload_succeed() {
            if (active.flag) {
                console.log('Overwriting was caught');
                return;
            }
            if (!success.flag)
                loadWrapper();
            if (!success.flag) { // should not happen
                alert('Javascript error, please contact the developers.');
                console.log('Error: digitalliteracy/amd/src/formatchange.js, line 177');
            }
            document.body.removeEventListener('mouseover', onload_succeed);
            document.body.removeEventListener('keydown', onload_succeed);
            document.body.removeEventListener('scroll', onload_succeed);
            document.body.removeEventListener('touchstart', onload_succeed);
            if (success.flag)
                console.log('Loading was successful');
        }

        function loadWrapper() {
            active.flag = true;
            try {
                onload();
            } catch (err) {
                console.log(err.message);
            }
            active.flag = false;
        }

        function onload() {
            let temp = document.getElementById('id_labels_container');
            if (temp === null || !temp.hasAttribute('data-serialized')) {
                console.log('No hidden data [or it was processed].');
                return;
            }
            let data = temp.getAttribute('data-serialized');
            let labels = PHP.parse(data);
            temp.remove();
            console.log(Object.keys(labels).length + ' labels were successfully parsed, container was removed');
            success.flag = Object.keys(labels).length === size;

            for (const param of params) {
                let key = 'id_' + param;
                let label = document.getElementById(key).parentElement;
                text_wrapper(label, key);
            }
            for (const group of groups) {
                let key = 'fgroup_id_' + group;
                const label = document.querySelector('[for="' + key + '"]');
                text_wrapper(label, key);

                let element = document.getElementById(key);
                let help = element.children.item(0).children.item(0).children.item(0);
                help.id = key + '_help_text';

                let hint = help.children.item(0);
                hint.id = key + '_help_title';
            }
            format.addEventListener('change', function () {
                change(labels);
            });
            format.dispatchEvent(new CustomEvent('change'));
        }

        if (window.addEventListener)
            window.addEventListener('load', onload_succeed, false);
        else if (window.attachEvent)
            window.attachEvent('onload', onload);
        else window.onload = onload_succeed;
    }
    return {process: process};
});