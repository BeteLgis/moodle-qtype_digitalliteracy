<?php

defined('MOODLE_INTERNAL') || die();

/** The class for exceptions thrown in the digitalliteracy plugin */
class qtype_digitalliteracy_exception extends moodle_exception {
    /**
     * @param string $errorcode exception description identifier
     * @param mixed $debuginfo debugging data to display
     */
    public function __construct($errorcode, $a = null, $debuginfo = null) {
        global $PAGE;
        parent::__construct($errorcode, 'qtype_digitalliteracy', $PAGE->url, $a, $debuginfo);
    }

    public static function conditional_throw($isteacher, $errorcode, $a = null, $debuginfo = null, $shell = false) {
        $module = 'qtype_digitalliteracy';
        $prefix = $shell ? get_string('exception_shell', $module). ': ' : '';
        if ($isteacher) {
            throw new qtype_digitalliteracy_exception($errorcode, $a, $debuginfo);
        }
        return get_string_manager()->string_exists($errorcode, $module) ?
            $prefix. get_string($errorcode, $module, $a) : $prefix. $errorcode;
    }
}
