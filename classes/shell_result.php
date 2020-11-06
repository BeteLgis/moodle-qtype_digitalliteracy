<?php

/**
 * Represent a shell result, provides methods for the result management (modify, write, read).
 */
class qtype_digitalliteracy_shell_result {
    const SUCCESS = 0;
    const NOT_SAVED_ERROR = 1;

    const RESULT_FILE = '/result.txt';

    private $result = array('errors' => array(), 'exceptions' => array(),
        'fraction' => 0, 'files' => array());
    private $requestdir = '';

    public function __construct($requestdirectory) {
        $this->requestdir = $requestdirectory;
    }

    /** Params as in {@link qtype_digitalliteracy_exception::__construct()} */
    function add_error($errorcode, $a = null) {
        $this->add_message('errors', $errorcode, $a);

    }

    /** Params as in {@link qtype_digitalliteracy_exception::__construct()} */
    function add_exception($errorcode, $a = null) {
        $this->add_message('exceptions', $errorcode, $a);
    }

    /** Params as in {@link qtype_digitalliteracy_exception::__construct()} */
    private function add_message($field, $errorcode, $a = null) {
        if (empty($errorcode) || !is_string($errorcode))
            return;
        array_push($this->result[$field], array('code' => $errorcode, 'a' => $a));
    }

    function set_fraction($fraction) {
        if (empty($fraction) || !is_numeric($fraction))
            return;
        $fraction = floatval($fraction);
        if ($fraction >= 1)
            $fraction = 1;
        if ($fraction <= 0)
            $fraction = 0;
        $this->result['fraction'] = $fraction;
    }

    function set_files($files) {
        if (empty($files) || !is_array($files))
            return;
        $this->result['files'] = $files;
    }

    /**
     * Save the result (serialized and base64 encoded) to the resulting file.
     */
    function write() {
        if (!file_put_contents($this->requestdir. self::RESULT_FILE, base64_encode(serialize($this->result)))) {
            echo serialize(array('code' => 'shellex_resultwrite', 'a' => $this->requestdir));
            exit(self::NOT_SAVED_ERROR);
        }
    }

    /**
     * Read the result from the resulting file.
     * @throws qtype_digitalliteracy_exception
     */
    function read() {
        if (!defined('MOODLE_INTERNAL'))
            return array(false, qtype_digitalliteracy_exception::conditional_throw(false,
                'shellex_prohibitedread'));
        if (!file_exists($this->requestdir. self::RESULT_FILE))
            return array(false, qtype_digitalliteracy_exception::conditional_throw(false,
                'shellex_noresultfile', $this->requestdir));
        if (!($content = file_get_contents($this->requestdir. self::RESULT_FILE)) ||
            !($decoded = base64_decode($content)) || !($res = unserialize($decoded)))
            return array(false, qtype_digitalliteracy_exception::conditional_throw(false,
                'shellex_resultread', $this->requestdir. self::RESULT_FILE));
        return array(true, $res);
    }
}
