
define(function() {
    function process() {
        let element = document.getElementById('id_firstcoef');
        element.onchange = function () {
            let temp = document.getElementById('id_paramvalue').parentElement;
            temp.innerHTML.
            alert(temp.innerHTML);
        }
    }
    return {process: process};
});