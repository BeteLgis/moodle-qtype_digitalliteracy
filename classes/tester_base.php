<?php
/** Base class - instantiates methods to override and
 * provides mechanism for memory consumption tracking
 */
class qtype_digitalliteracy_tester_base {
    protected $log = '';
    protected $usage = -1;

    /** Memorize current memory consumption */
    protected function start() {
        $this->usage = memory_get_usage(true);
    }

    /** @return int script memory consumtion */
    protected function get_usage() {
        if ($this->usage < 0)
            throw new Exception('Call function \'start\' first!');
        return memory_get_usage(true) - $this->usage;
    }

    /** Converts memory usage into more readable format */
    protected function get_usage_formatted() {
        $units = array('b','kb','mb','gb');
        $memory = $this->get_usage();
        $result = array();
        if ($memory === 0) {
            $result[] = '0 b';
        } elseif ($memory < 0) {
            $memory = abs($memory);
            $result[] = '-';
        }
        while ($memory != 0) {
            $index = floor(log($memory,1024));
            if ($index < count($units)) {
                $temp = pow(1024, $index);
                $unit = floor($memory / $temp);
                $result[] = $unit. ' '. $units[$index];
                $memory -= $temp * $unit;
            } else {
                $result[] = '0 b';
                break;
            }
        }
        return 'Memory used: '. implode(' ', $result). ' ';
    }

    /** Saves usage into log */
    protected function log_usage() {
        $this->log .= $this->get_usage_formatted();
    }

    /** Determines when a file loaded is too big to be processed */
    public static function is_memory_exhausted($error) {
        $memory_limit = self::return_bytes(ini_get('memory_limit'));
        if (memory_get_usage(true) / $memory_limit > 0.8)
            throw new Exception(get_string('error_'. $error, 'qtype_digitalliteracy'));
    }

    /** Convert M, K or G key into bytes size */
    public static function return_bytes($size_str) {
        switch (substr($size_str, -1))
        {
            case 'M': case 'm': return (int)$size_str * 1048576;
            case 'K': case 'k': return (int)$size_str * 1024;
            case 'G': case 'g': return (int)$size_str * 1073741824;
            default: return $size_str;
        }
    }

    /** Main comparison method
     * @param $data {@link qtype_digitalliteracy_question::response_data()}
     * @return array {@link question_file_saver} and fraction
     */
    public function compare_files($data) {
        return array();
    }

    /** Validates a file inside a determined comparator
     * @return string containing error, empty string otherwise
     */
    public function validate_file($filepath, $filename) {
        return '';
    }
}

class Describer {

    public function compare_sheets($data, &$result, $source, &$response) {}

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