<?php
require_once($CFG->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');

interface qtype_digitalliteracy_comparator
{
    public function validate(); // file validation (check whether it is correctly loaded)
                                // number of pages (slides) not 0!!!!
    public function compareFiles($source, $sample);

}