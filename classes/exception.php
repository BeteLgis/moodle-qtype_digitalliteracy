<?php

defined('MOODLE_INTERNAL') || die();

/** The class for exceptions thrown in the digitalliteracy plugin */
class qtype_digitalliteracy_exception extends Exception {

    public function __construct($message, $undexpected = true) {
        if ($undexpected)
            $message = get_string('error_unexpected', 'qtype_digitalliteracy', $message);
        parent::__construct($message);
    }
}
