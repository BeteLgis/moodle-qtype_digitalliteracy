<?php
require_once ('HTML/QuickForm/input.php');

class HTML_QuickForm_slider extends HTML_QuickForm_input {

    var $_text = '';

    public function __construct($elementName=null, $elementLabel=null, $attributes=null) {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->setType('range');
    } //end constructor

}