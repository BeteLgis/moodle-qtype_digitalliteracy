<?php

use PhpOffice\PhpPresentation as PP;
use PhpOffice\PhpSpreadsheet as Excel;
use PhpOffice\PhpWord as Word;

/**
 * Parent class for the testers.
 */
abstract class qtype_digitalliteracy_base_tester {
    protected $data;
    protected $result;
    protected $config;

    /**
     * @param stdClass $data as returned by {@link qtype_digitalliteracy_question::response_data()}
     * @param qtype_digitalliteracy_shell_result $result
     * + shell needed fields
     */
    public function __construct($data, $result) {
        $this->data = $data;
        $this->result = $result;
        $this->config = new stdClass();
    }

    /**
     * Compares files and calculates the result (a fraction and the mistakes files).
     * Note that $data->templatepath is set only when the question has a template and it has to be excluded!
     */
    abstract public function compare_files();

    /** Validate a file. */
    abstract public function validate_file();

    /** @return string */
    abstract protected function get_reader_from_extension($filename);

    /** @return null|Excel\Reader\IReader|Word\Reader\ReaderInterface|PP\Reader\ReaderInterface */
    abstract protected function IOFactory($reader);

    protected function reader_apply_config($reader) { }

    /**
     * Tries to read a file with a configured reader.
     * @param string $filename
     * @return null|Excel\Spreadsheet|Word\PhpWord|PP\PhpPresentation
     */
    protected function read($filename) {
        $readable = true;
        if (!is_file($filename)) {
            $this->result->add_error('shellerr_notfile');
            $readable = false;
        }
        if (!is_readable($filename)) {
            $this->result->add_error('shellerr_notreadable');
            $readable = false;
        }
        if ($readable) {
            $filetype = $this->get_reader_from_extension($filename);
            if ($filetype) {
                $reader = $this->IOFactory($filetype);
                if (isset($reader) && $reader->canRead($filename)) {
                    $this->reader_apply_config($reader);
                    return $reader->load($filename);
                }
            }
        }
        return null;
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
abstract class qtype_digitalliteracy_object_describer {

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
    abstract protected function get_settings($data);

    /**
     * Simply runs a criterion function in a try catch block.
     * @param object $object Cell, Chart and so on
     * @param string|array $function a function to run (for example from {@link excel_chart_criterions})
     * and, possibly, parameters to pass to it
     * @return mixed empty array means value can't be calculated (for example, an exception occurred
     * or $object is not set).
     */
    abstract protected function wrapper($object, $function);

    /**
     * A description is an array of "key => function return value" pairs for each criterion.
     * @param array $criterions as returned by {@link qtype_digitalliteracy_object_describer::get_settings()}
     * @param object $object Cell, Chart and so on
     * @return array
     */
    protected function describe_by_group($criterions, $object) {
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
    protected function compare_counter($object1_description, $object2_description, &$log = null) {
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
    protected function recursive_diff_count($array1, $array2, &$matches, &$total) {
        $flag = is_array($array1) && is_array($array2) && count($array2) > count($array1);

        if (is_array($array1) && count($array1) > 0 && !$flag) {
            foreach ($array1 as $key => $value) {
                $this->recursive_diff_count($value, is_array($array2) &&
                    array_key_exists($key, $array2) ? $array2[$key] : $array2,
                    $matches, $total);
            }
        } elseif ($flag || is_array($array2) && count($array2) > 0) {
            foreach ($array2 as $key => $value) {
                $this->recursive_diff_count(is_array($array1) &&
                    array_key_exists($key, $array1) ? $array1[$key] : $array1, $value,
                    $matches, $total);
            }
        } else {
            if ($array1 === $array2)
                $matches++;
            $total++;
        }
    }
}