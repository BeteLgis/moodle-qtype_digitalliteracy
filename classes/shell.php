<?php
/**
 * Converts the memory usage in bytes to the highest possible unit (KB, MB and so on).
 */
function convert($usage) {
    $unit = array('B','K','M','G');
    return @ceil($usage/pow(1024,($i=floor(log($usage,1024))))).$unit[$i];
}

global $argc, $argv;
if ($argc < 2)
    return; // in case the file is included

if (!($inp = @base64_decode($argv[1])) || !($data = @unserialize($inp))) {
    echo 'Internal error: couldn\'t decode or unserialize data.';
    exit(1);
}

// set memory limit
ini_set('memory_limit', convert(memory_get_usage(true) + $data->maxmemory));
$shell = new qtype_digitalliteracy_shell($data->requestdirectory, $data->errors, true);

global $CFG;
$CFG = new stdClass();
$CFG->dirroot = $data->dirroot;

define('MOODLE_INTERNAL', true);
require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/base_tester.php');
require_once($CFG->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');
require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/excel_tester.php');
require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/powerpoint_tester.php');

try {
    $shell->modify_result($shell->run($data), false);
} catch (Throwable $ex) {
    $shell->modify_result(array('error' => $ex->getMessage()));
} catch (Exception $ex) { // for backwards compatibility
    $shell->modify_result(array('error' => $ex->getMessage()));
}
exit(0);

/**
 * Validation and comparison are executed in a Shell class instance.
 * We pass $data (serialized and base64 encoded) with all needed parameters
 * to this script as an {@link exec()} argument.
 * The script limits memory usage (for a faster validation/comparison processing).
 * Also we register a shutdown function (in case of a fatal error like 'memory exhausted').
 * Result (serialized and base64 encoded) is written into (and afterwards read from)
 * "$requestdirectory. '/result.txt'" file.
 */
class qtype_digitalliteracy_shell {
    private $result = array();
    private $resultdir = '';
    private $errors = array();

    /**
     * Creates a new instance of a Shell.
     * @param string $requestdirectory see {@link make_request_directory()}
     * @param array $errors when we run via {@link exec()}, {@link get_string()}
     * and other moodle functions won't work that is why we pass error messages via $errors array.
     * @param bool $errorhandler initialize error handler {@link qtype_digitalliteracy_shell::initErrorHandler()} or not
     */
    function __construct($requestdirectory, $errors = array(), $errorhandler = false) {
        $this->resultdir = $requestdirectory. '/result.txt';
        if ($errorhandler)
            $this->initErrorHandler();
        $this->errors = $errors;
    }

    /**
     * Registers a shutdown function.
     * The shutdown function is always called (if error hasn't occurred
     * before this method was called), that is why this is the only place when
     * we write the result to the output file {@link qtype_digitalliteracy_shell::write_result()}.
     * Also here we save the error message [to show it to the user]
     * (custom message is issued only when the error message string was registered in
     * {@link qtype_digitalliteracy_base_tester::get_strings()} otherwise
     *  'an unexpected error' message is saved).
     */
    function initErrorHandler() {
        $this->memory = new SplFixedArray(65536); // This storage is freed on error (case of allowed memory exhausted)
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== NULL && $error['type'] === E_ERROR) { // filter only fatal errors
                $filename = pathinfo($error['file'], PATHINFO_FILENAME);
                $key = 'error_'. strtolower($filename). '_'. $error['line'];
                if (!empty($this->errors[$key])) {
                    $msg = $this->errors[$key];
                } else {
                    $pos = strpos($error['message'], '.');
                    $message = $pos ? substr($error['message'], 0, $pos) : $error['message'];

                    $msg = sprintf($this->errors['error_fatal'], $filename. '.php',
                        $error['line'], $message);
                }
                $this->modify_result(array('error' => $msg));
            }
            $this->write_result();
        });
    }

    /**
     * Run validation/comparison.
     * @param stdClass $data as returned by {@link qtype_digitalliteracy_question::response_data()}
     * @return array an array containing an error or the result
     */
    function run($data) {
        switch ($data->responseformat) {
            case 'excel':
                $tester = new qtype_digitalliteracy_excel_tester($data);
                break;
            case 'powerpoint':
                $tester = new qtype_digitalliteracy_powerpoint_tester($data);
                break;
        }
        if (!isset($tester)) // should never happen unless manual run (as data is validated!)
            throw new Exception('Validation error.');

        return $data->isgrading ? $tester->compare_files() : $tester->validate_file();
    }

    /**
     * Modify the resulting array.
     * @param array $array array containing changes (non empty key => non empty value)
     * @param bool $append whether to rewrite or append values from $array
     */
    function modify_result($array, $append = true) {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (!(empty($key) || empty($value))) {
                    $this->result[$key] = $append && !empty($this->result[$key]) ?
                        $this->result[$key]. ' | '. $value : $value;
                }
            }
        }
    }

    /**
     * Save result (serialized and base64 encoded) to the resulting file.
     */
    function write_result() {
        if (empty($this->resultdir))
            throw new Exception('Internal error: resulting directory was not set.');

        file_put_contents($this->resultdir, base64_encode(serialize($this->result)));
    }

    /**
     * Read result from the resulting file.
     */
    function read_result() {
        if (empty($this->resultdir))
            throw new Exception('Internal error: resulting directory was not set.');
        if (!file_exists($this->resultdir))
            throw new Exception('Internal error: no resulting file from shell was created.');

        return unserialize(base64_decode(file_get_contents($this->resultdir)));
    }
}