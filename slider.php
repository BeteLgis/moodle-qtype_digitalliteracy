<?php
// require_once($CFG->libdir . '/pear/HTML/QuickForm/input.php');
require_once ('formslider.php');

require_once($CFG->libdir . '/form/templatable_form_element.php');
require_once($CFG->libdir . '/form/templatable_form_element.php');

class MoodleQuickForm_slider extends HTML_QuickForm_slider implements templatable {

    use templatable_form_element {
        export_for_template as export_for_template_base;
    }


    /** @var string html for help button, if empty then no help */
    var $_helpbutton='';

    /** @var bool if true label will be hidden */
    var $_hiddenLabel=false;

    public function __construct($elementName=null, $elementLabel=null, $attributes=null) {
        parent::__construct($elementName, $elementLabel, $attributes);
    }

    /**
     * Sets label to be hidden
     *
     * @param bool $hiddenLabel sets if label should be hidden
     */
    function setHiddenLabel($hiddenLabel){
        $this->_hiddenLabel = $hiddenLabel;
    }

    /**
     * Freeze the element so that only its value is returned and set persistantfreeze to false
     *
     * @since     Moodle 2.4
     * @access    public
     * @return    void
     */
    function freeze()
    {
        $this->_flagFrozen = true;
        // No hidden element is needed refer MDL-30845
        $this->setPersistantFreeze(false);
    } //end func freeze

    /**
     * Returns the html to be used when the element is frozen
     *
     * @since     Moodle 2.4
     * @return    string Frozen html
     */
    function getFrozenHtml()
    {
        $attributes = array('readonly' => 'readonly');
        $this->updateAttributes($attributes);
        return $this->_getTabs() . '<input' . $this->_getAttrString($this->_attributes) . ' />' . $this->_getPersistantData();
    } //end func getFrozenHtml

    /**
     * Returns HTML for this form element.
     *
     * @return string
     */
    function toHtml() {
        $this->_generateId();
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }
        $html = $this->_getTabs() . '<input' . $this->_getAttrString($this->_attributes) . ' />';
//        throw new Exception("a");
        if ($this->_hiddenLabel){
            return '<label class="accesshide" for="'.$this->getAttribute('id').'" >'.
                $this->getLabel() . '</label>' . $html;
        } else {
            return $html;
        }
    }

    /**
     * get html for help button
     *
     * @return string html for help button
     */
    function getHelpButton(){
        return $this->_helpbutton;
    }

    public function export_for_template(renderer_base $output) {
        $context = $this->export_for_template_base($output);
        $context['value'] = $this->getValue();

        return $context;
    }
}

