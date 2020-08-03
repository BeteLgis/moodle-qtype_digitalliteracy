
define(function() {
    function process(data) {
        let params = data['params'];
        let labels = data['labels'];
        let groups = data['groups'];

        let format = document.getElementById('id_responseformat');
        format.onchange = function () {
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
                console.error("TextNodes.length !== 1.")
                return;
            }
            let textNode = TextNodes[0];
            let spanNode = document.createElement('span');
            let text = typeof textNode.textContent == 'string' ? textNode.textContent : textNode.innerText;
            spanNode.appendChild(document.createTextNode(text));
            spanNode.id = name + '_label_span';
            label.replaceChild(spanNode, textNode);
        }

        document.body.addEventListener('mouseover', onload_succeed);
        document.body.addEventListener('keydown', onload_succeed);
        document.body.addEventListener('touchstart', onload_succeed);
        function onload_succeed() {
            if (document.getElementById('id_' + params[0] + '_label_span') === null) {
                onload();
            }
            if (document.getElementById('id_' + params[0] + '_label_span') === null)
                alert("Javascript error, please contact the developers.");
            document.body.removeEventListener('mouseover', onload_succeed);
            document.body.removeEventListener('keydown', onload_succeed);
            document.body.removeEventListener('touchstart', onload_succeed);
        }

        function onload() {
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

                if (!help.hasAttribute('data-content')) {
                    console.error('Help button parsing error');
                }
                help.id = key + '_help_text';
                let hint = help.children.item(0);
                if (!hint.hasAttribute('title') || !help.hasAttribute('aria-label')) {
                    console.error('Help button parsing error');
                }
                hint.id = key + '_help_title';
            }
            format.dispatchEvent(new CustomEvent('change'));
        }

        if (window.addEventListener)
            window.addEventListener("load", onload, false);
        else if (window.attachEvent)
            window.attachEvent("onload", onload);
        else window.onload = onload;
    }
    return {process: process};
});