<?php
ini_set('memory_limit', convert(memory_get_usage(true) + 20 * pow(2, 20)));
function convert($usage) {
    $unit = array('B','K','M','G');
    return @round($usage/pow(1024,($i=floor(log($usage,1024)))),2).$unit[$i];
}

global $argc, $argv;
if ($argc < 2)
    return; // in case the file is included

if (!($data = @unserialize(base64_decode($argv[1])))) {
    echo serialize(array('error' => 'Internal error: couldn\'t unserialize data.'));
    exit(1);
}

$shell = new Shell($data->request_directory, $data->errors, true);

unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dirroot = $data->dirroot;

define('MOODLE_INTERNAL', true);
require_once($CFG->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');
require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/excel_tester.php');
require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/powerpoint_tester.php');

try {
    $shell->modify_result($shell->run($data), false);
} catch (Throwable $ex) {
    $shell->modify_result(array('error' => $ex->getMessage()));
} catch (Exception $ex) { // made for backwards compatibility
    $shell->modify_result(array('error' => $ex->getMessage()));
}
exit('Success');


class Shell {
    private $result = array();
    private $result_dir = '';
    private $errors = array();

    function __construct($request_directory, $errors = array(), $errorHandler = false) {
        $this->result_dir = $request_directory. '/result.txt';
        if ($errorHandler)
            $this->initErrorHandler();
        $this->errors = $errors;
    }

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
                    $msg = sprintf($this->errors['fatalerror'], $filename. '.php',
                        $error['line'], substr($error['message'], 0, 48));
                }
                $this->modify_result(array('error' => $msg));
            }
            $this->write_result();
        });
    }

    function run($data) {
        switch ($data->responseformat) {
            case 'excel':
                $tester = new qtype_digitalliteracy_excel_tester($data);
                break;
            case 'powerpoint':
                $tester = new qtype_digitalliteracy_powerpoint_tester($data);
                break;
        }
        if (!isset($tester))
            throw new dml_read_exception('Unexpected error.');

        return isset($data->grade_response) ? $tester->compare_files() : $tester->validate_file();
    }

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

    function write_result() {
        if (empty($this->result_dir))
            throw new Exception('Internal error: resulting directory was not set.');

        file_put_contents($this->result_dir, base64_encode(serialize($this->result)));
    }

    /** Used by {@link qtype_digitalliteracy_comparator} */
    function read_result() {
        if (empty($this->result_dir))
            throw new Exception('Internal error: resulting directory was not set.');
        if (!file_exists($this->result_dir))
            throw new Exception('Internal error: no resulting file from shell was created.');

        return unserialize(base64_decode(file_get_contents($this->result_dir)));
    }
}

/** Performs comparison in shell
 */
class qtype_digitalliteracy_tester_base {
    protected $data;

    function __construct($data) {
        $this->data = $data;
    }

    public static function get_strings() {
        return array('fatalerror' => get_string('fatalerror', 'qtype_digitalliteracy'),
            'error_noreader' => get_string('error_noreader', 'qtype_digitalliteracy'));
    }

    /** Main comparison method
     * @param $data {@link qtype_digitalliteracy_question::response_data()}
     * @return array {@link question_file_saver} and fraction (or possibly error)
     */
    public function compare_files() {
        return array();
    }

    /** Validates a file inside a determined comparator
     * @return array with a string containing error, empty string otherwise
     */
    public function validate_file() {
        return array();
    }
}

class Describer {

    function get_settings($data) {
        return array();
    }

    function wrapper($object, $function) {
        return array();
    }

    /**
     * We describe object (cell or chart) - put all needed data into array, that is gonna help
     * save comparison time and unify comparison itself!
     */
    function describe_by_group($criterions, $object) {
        $description = array();
        foreach ($criterions as $key => $function) {
            $description[$key] = $this->wrapper($object, $function);
        }
        return $description;
    }

    function compare_counter($object1_description, $object2_description, &$log = null) {
        $matches = 0;
        $total = 0;
        foreach ($object1_description as $name => $value) { // always set (look at description method)
            $matches_local = 0;
            $total_local = 0;
            $this->recursive_diff_count($value, $object2_description[$name], // always set
                $matches_local, $total_local);
            if (isset($log) && ($total_local === 0 || $matches_local / $total_local !== 1)) {
                $log[] = 'Mistake in '. $name.' : scored '. $matches_local. ' out of '. $total_local;
            }
            $matches += $matches_local;
            $total += $total_local;

        }
        return $total === 0 ? 0 : $matches / $total;
    }

    function recursive_diff_count($array1, $array2, &$matches, &$total) {
        $flag = is_array($array1) && is_array($array2) && count($array2) > count($array1);

        if (is_array($array1) && count($array1) > 0 && !$flag) {
            foreach ($array1 as $key => $value) {
                $this->recursive_diff_count($value, isset($array2[$key]) ? $array2[$key] : array(),
                    $matches, $total);
            }
        } elseif ($flag || is_array($array2) && count($array2) > 0) {
            foreach ($array2 as $key => $value) {
                $this->recursive_diff_count(isset($array1[$key]) ? $array1[$key] : array(), $value,
                    $matches, $total);
            }
        } else {
            if ($array1 === $array2)
                $matches++;
            $total++;
        }
    }
}