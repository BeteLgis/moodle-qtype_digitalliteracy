<?php
class qtype_digitalliteracy_shell_exception extends Exception {
    public $a;
    public function __construct($errorcode, $a = null) {
        $this->a = $a;
        parent::__construct($errorcode);
    }
}