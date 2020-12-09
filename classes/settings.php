<?php
/**
 * Represents test settings that are displayed to the teacher in {@link qtype_digitalliteracy_edit_form}.
 */
class qtype_digitalliteracy_settings {
    private $structures;
    private $groups = array();
    private $coefs = array();
    private $params = array();

    /**
     * Create a settings container for a specified $responseformat,
     * '' means the place holder array (array with max values from
     * {@link qtype_digitalliteracy_settings_structures::$structures}).
     * @param string $responseformat empty string or a response format
     * from {@link qtype_digitalliteracy::response_formats()}
     */
    public function __construct($responseformat = '') {
        $this->structures = new qtype_digitalliteracy_settings_structures();
        $this->set_groups($responseformat);
    }

    /**
     * @return array an array of groups (coef + params)
     * as returned by {@link qtype_digitalliteracy_settings_describer::describe_group()}
     */
    public function get_groups() {
        return $this->groups;
    }

    /**
     * @return array an array of groups names (used in js and
     * simply returns values from a 'name' column)
     */
    public function get_groups_names() {
        return array_column($this->groups, 'name');
    }

    /**
     * @return array an array of params from all groups (used in js)
     */
    public function get_params() {
        return $this->params;
    }

    /**
     * @return array an array of coefs from all groups (used in js)
     */
    public function get_coefs() {
        return $this->coefs;
    }

    /**
     * Describes {@link qtype_digitalliteracy_settings_describer::describe_group()}
     * the groups for a specified $responseformat
     * and calls {@link qtype_digitalliteracy_settings::set_data()}.
     * @param string $responseformat empty string or a response format
     * from {@link qtype_digitalliteracy::response_formats()}
     */
    public function set_groups($responseformat = '') {
        $groups = array();
        foreach ($this->structures->get_structure($responseformat) as $index => $params) {
            $groups[] = qtype_digitalliteracy_settings_describer::describe_group($index + 1, $params);
        }
        $this->groups = $groups;
        $this->set_data();
    }

    /**
     * Updates $params and $coefs fields according to the $groups field.
     */
    private function set_data() {
        $params = array();
        $coefs = array();
        foreach ($this->groups as $group) {
            foreach ($group['items'] as $item => $type) {
                if ($type) {
                    $params[] = $item;
                } else {
                    $coefs[] = $item;
                }
            }
        }
        $this->coefs = $coefs;
        $this->params = $params;
    }

    /**
     * Maps coefs to groups (a group has only one coef).
     * @return array an array of coef => groupname values
     */
    public function get_coefs_map() {
        $result = array();
        foreach ($this->get_groups_names() as $name) {
            $result[$name. 'coef'] = $name;
        }
        return $result;
    }

    /**
     * Appends 'id_' to {@link qtype_digitalliteracy_settings::$coefs} (used in js).
     * @return array new array
     */
    public function get_coefs_ids() {
        return preg_replace('/^/', 'id_', $this->get_coefs());
    }

    public function get_paramscount_map() {
        $result = array();
        foreach ($this->groups as $group) {
            $result[$group['name']] = $group['paramscount'];
        }
        return $result;
    }
    
    /**
     * Maps params to groups (a group has at least one param).
     * @return array an array of group => [params] values (params is an array too)
     */
    public function get_params_map() {
        $result = array();
        foreach ($this->groups as $group) {
            $items = array();
            foreach ($group['items'] as $item => $type) {
                if ($type) {
                    $items[] = $item;
                }
            }
            $result[$group['name']] = $items;
        }
        return $result;
    }

    /**
     * @return array an array containing all setting (coefs, params and common_params)
     */
    public function get_all_options() {
        return array_merge($this->get_coefs(), $this->get_params(),
            array_keys($this->group_common_params()));
    }

    /**
     * @return array an array containing common params like binarygrading
     */
    public function group_common_params() {
        return array_fill_keys(array('binarygrading', 'showmistakes', 'checkbutton'), true);
    }

    /**
     * @return array an array - common group
     */
    public function group_common() {
        return array('name' => 'commonsettings', 'items' => $this->group_common_params());
    }
}

/**
 * Settings mapper (depending on the response format).
 * A structure (an array) determines the number of groups (coefs)
 * and the number of params in each group.
 */
class qtype_digitalliteracy_settings_structures {
    // array(4, 4, 4) - means 3 groups (and coefs) with 4 params in each
    private $structures = array('excel' => array(4, 4, 4), 'powerpoint' => array(2, 2), 'word' => array(4, 4, 3));
    private $placeholder = array();

    /**
     * Invokes the place holder array calculation.
     */
    public function __construct() {
        array_unshift($this->structures, array($this, 'find_placeholder'));
        call_user_func_array('array_map', $this->structures);
    }

    /**
     * @param string $responseformat empty string or a response format
     * from {@link qtype_digitalliteracy::response_formats()}
     * @return array an array: a placeholder or a structure for a given $responseformat
     */
    public function get_structure($responseformat) {
        return empty($responseformat) ? $this->placeholder : $this->structures[$responseformat];
    }

    /**
     * Callback to calculate the array containing the max values from all
     * {@link qtype_digitalliteracy_settings_structures::$structures}.
     */
    private function find_placeholder() {
        $this->placeholder[] = max(array_map(function ($val) {
            return $val ?? 0;
        }, func_get_args()));
    }
}

/**
 * Class to describe a group.
 */
class qtype_digitalliteracy_settings_describer {
    /**
     * Describes a group - gives group a name and names its coefs and params.
     * Groups, coefs and params are autonamed in numerical order.
     *
     * For example: a structure array(2, 1) will look like:
     * group1 | group1coef | group1param1, group1param2
     * group2 | group2coef | group2param1
     * @param int $groupindex index of a group from {@link qtype_digitalliteracy_settings::$groups} + 1
     * @param int $paramscount the number of params (a value from a structure array) in a group
     * @return array an array - a group description
     */
    public static function describe_group($groupindex, $paramscount) {
        $result = array();
        // A grading options group, there are only two element types:
        // 'text' [false] and 'advcheckbox' [true] <=> key - a unique name, value - the element type
        $result['group'. $groupindex. 'coef'] = false;
        for ($i = 1; $i <= $paramscount; $i++) {
            $result['group'. $groupindex. 'param'. $i] = true;
        }
        return array('name' => 'group'. $groupindex, 'items' => $result, 'paramscount' => $paramscount);
    }
}