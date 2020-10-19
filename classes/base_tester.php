<?php
/**
 * Parent class for the testers.
 */
class qtype_digitalliteracy_base_tester {
    protected $data;

    /**
     * @param stdClass $data as returned by {@link qtype_digitalliteracy_question::response_data()}
     * + shell needed fields
     */
    function __construct($data) {
        $this->data = $data;
    }

    /**
     * @return array an array containing all string (usually returned by {@link get_string()})
     * needed to be passed to the shell
     */
    public static function get_strings() {
        return array('error_fatal' => get_string('error_unexpected', 'qtype_digitalliteracy',
            get_string('error_fatal', 'qtype_digitalliteracy')),
            'error_noreader' => get_string('error_noreader', 'qtype_digitalliteracy'));
    }

    /**
     * Compares files and calculates the result (a fraction and the mistakes files).
     * Note that $data->templatepath is set only when the question has a template and it has to be excluded!
     * @return array an array: {@link question_file_saver} and a fraction (or possibly an error)
     */
    public function compare_files() {
        return array();
    }

    /**
     * Validate a file.
     * @return array an empty array or an array containing the error message
     */
    public function validate_file() {
        return array();
    }
}

/**
 * Parent class for describing and comparing an object (for example, a cell or a chart).
 * Comparison algorithm
 * 1) Map (convert) comparison settings into local functions using {@link qtype_digitalliteracy_object_describer::get_settings()};
 * 2) Describe the object for each group [coef] according to the settings
 * using {@link qtype_digitalliteracy_object_describer::describe_by_group()});
 * 3) Compare different entities (for example, a source cell, a response cell and a template cell)
 * using {@link qtype_digitalliteracy_object_describer::compare_counter()}.
 * For example, see {@link excel_cell_describer}.
 * For more detail, read comments on each of the mentioned methods.
 */
class qtype_digitalliteracy_object_describer {

    /**
     * Maps comparison settings (like grouponecoef and grouponeparamone | grouponeparamtwo)
     * into local functions (it's better to define all function in a separate class
     * like {@link excel_cell_criterions}) that return a criteria value representation.
     * For more details see {@link excel_chart_criterions}.
     * Each criterion is a "uniquekey => function" pair.
     * @param stdClass $data {@link qtype_digitalliteracy_base_tester::$data}
     * @return array an array of groups, each group has a group name, a coef name and a criterions array.
     * (if group is not present in the array then coef is 0!, same for the criterions [comparison params]:
     * criterion is only present when checkbox it represents is checked).
     */
    function get_settings($data) {
        return array();
    }

    /**
     * Simply runs a criterion function in a try catch block.
     * @param object $object Cell, Chart and so on
     * @param string $function a function to run (for example from {@link excel_chart_criterions})
     * @return mixed empty array means value can't be calculated (for example, an exception occurred
     * or $object is not set).
     */
    function wrapper($object, $function) {
        return array();
    }

    /**
     * A description is an array of "key => function return value" pairs for each criterion.
     */
    function describe_by_group($criterions, $object) {
        $description = array();
        foreach ($criterions as $key => $function) {
            $description[$key] = $this->wrapper($object, $function);
        }
        return $description;
    }

    /**
     * Compares two descriptions while counting $match / $total ratio
     * and, optionally, logging mistakes.
     * @param array $object1_description {@link qtype_digitalliteracy_object_describer::describe_by_group()}
     * @param array $object2_description {@link qtype_digitalliteracy_object_describer::describe_by_group()}
     * @param null|array $log
     * @return float|int $match / $total ratio
     */
    function compare_counter($object1_description, $object2_description, &$log = null) {
        $matches = 0;
        $total = 0;
        foreach ($object1_description as $name => $value) { // always set (look at description method)
            $group_matches = 0;
            $group_total = 0;
            $this->recursive_diff_count($value, $object2_description[$name], // always set
                $group_matches, $group_total);
            if (isset($log) && ($group_total === 0 || $group_matches / $group_total !== 1)) {
                $log[] = 'Mistake in '. $name.' : scored '. $group_matches. ' out of '. $group_total;
            }
            $matches += $group_matches;
            $total += $group_total;

        }
        return $total === 0 ? 0 : $matches / $total;
    }

    /**
     * Recursive array difference count.
     */
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