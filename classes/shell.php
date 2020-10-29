<?php
global $argc, $argv;
if ($argc < 2)
    return; // in case this file is included

if (!($decoded = base64_decode($argv[1])) || !($data = unserialize($decoded))) {
    echo serialize(array('code' => 'shellex_corrupteddata'));
    exit(1); // qtype_digitalliteracy_shell_result::NOT_SAVED_ERROR
}

require_once($data->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');
require_once($data->dirroot . '/question/type/digitalliteracy/classes/shell_result.php');
require_once($data->dirroot . '/question/type/digitalliteracy/classes/shell_exception.php');
require_once($data->dirroot . '/question/type/digitalliteracy/classes/base_tester.php');
require_once($data->dirroot . '/question/type/digitalliteracy/classes/excel_tester.php');
require_once($data->dirroot . '/question/type/digitalliteracy/classes/powerpoint_tester.php');
require_once($data->dirroot . '/question/type/digitalliteracy/classes/word_tester.php');

$shell = new qtype_digitalliteracy_shell($data->requestdirectory, $data->isteacher);
if ($data->isteacher) {
    ini_set('log_errors', 'on');
    ini_set('error_reporting', E_ALL);
    ini_set('error_log', $data->requestdirectory. qtype_digitalliteracy_shell_result::LOG_FILE);
}
ini_set('memory_limit', $shell::convert(memory_get_usage(true) + $data->maxmemory));

try {
    $shell->run($data);
} catch (qtype_digitalliteracy_shell_exception $ex) {
    $shell->get_result()->add_exception($ex->getMessage(), $ex->a);
} catch (Throwable $ex) {
    $shell->get_result()->add_exception($ex->getMessage());
} catch (Exception $ex) { // for backwards compatibility
    $shell->get_result()->add_exception($ex->getMessage());
}
exit(qtype_digitalliteracy_shell_result::SUCCESS);

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
    private $result = null;
    private $isteacher = false;

    /** @return qtype_digitalliteracy_shell_result */
    public function get_result() {
        return $this->result;
    }

    /**
     * Creates a new instance of a Shell.
     * @param string $requestdirectory see {@link make_request_directory()}
     * @param bool $isteacher log errors to show them to a teacher
     */
    function __construct($requestdirectory, $isteacher = false) {
        $this->result = new qtype_digitalliteracy_shell_result($requestdirectory);
        $this->isteacher = $isteacher;
        $this->init_error_handler();
    }

    /**
     * Registers a shutdown function.
     * The shutdown function is always called (if error hasn't occurred
     * before this method was called), that is why this is the only place when
     * we write the result to the output file {@link qtype_digitalliteracy_shell::write_result()}.
     * Teachers will see full error report!
     */
    function init_error_handler() {
        $this->memory = new SplFixedArray(65536); // This storage is freed on error (case of allowed memory exhausted)
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== NULL && $error['type'] === E_ERROR) { // filter only fatal errors
                if ($this->isteacher) {
                    $a = new stdClass();
                    $a->file = $error['file'];
                    $a->line = $error['line'];
                    $a->msg = $error['message'];
                    $this->result->add_exception('shellex_fatal', $a);
                } else {
                    $filename = pathinfo($error['file'], PATHINFO_FILENAME);
                    $code = 'shellex_'. strtolower($filename). '_'. $error['line'];
                    $this->result->add_exception($code);
                }
            }
            $this->result->write();
        });
    }

    /**
     * Run validation/comparison.
     * @param stdClass $data as returned by {@link qtype_digitalliteracy_question::response_data()}
     * @throws qtype_digitalliteracy_shell_exception|Exception an unexpected (internal) error
     */
    function run($data) {
        switch ($data->responseformat) {
            case 'excel':
                $tester = new qtype_digitalliteracy_excel_tester($data);
                break;
            case 'powerpoint':
                $tester = new qtype_digitalliteracy_powerpoint_tester($data);
                break;
            case 'word':
                $tester = new qtype_digitalliteracy_word_tester($data);
                break;
        }
        if (!isset($tester)) // should never happen unless manual run (as data is validated!)
            throw new qtype_digitalliteracy_shell_exception('shellex_wrongresponseformat', $data->responseformat);

        if ($data->isgrading)
            $tester->compare_files($this->result);
        else
            $tester->validate_file($this->result);
    }

    /**
     * Converts the memory usage in bytes to the highest possible unit (KB, MB and so on).
     */
    static function convert($usage) {
        $unit = array('B','K','M','G');
        return ceil($usage/pow(1024,($i=floor(log($usage,1024))))).$unit[$i];
    }
}