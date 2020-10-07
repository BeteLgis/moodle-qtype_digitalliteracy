<?php
/** Stores test settings to display to the teacher in {@link qtype_digitalliteracy_edit_form} */
class qtype_digitalliteracy_test_settings {
    private $structures;
    private $groups;
    private $coefs;
    private $params;

    public function __construct() {
        $this->structures = new qtype_digitalliteracy_test_structures();
        $this->set_groups();
    }

    public function get_groups() {
        return $this->groups;
    }

    public function get_groups_names() {
        return array_column($this->groups, 'name');
    }

    public function get_params() {
        return $this->params;
    }

    public function get_coefs() {
        return $this->coefs;
    }

    public function set_groups($responseformat = '') {
        $groups = array();
        foreach ($this->structures->get_structure($responseformat) as $index => $params) {
            $groups[] = qtype_digitalliteracy_test_describer::describe_group($index + 1, $params);
        }
        $this->groups = $groups;
        $this->set_data();
    }

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

    public function get_coefs_map() {
        $result = array();
        foreach ($this->get_groups_names() as $name) {
            $result[$name. 'coef'] = $name;
        }
        return $result;
    }

    public function get_coefs_ids() {
        return preg_replace('/^/', 'id_', $this->get_coefs());
    }

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

    public function get_all_options() {
        return array_merge($this->get_coefs(), $this->get_params(),
            array_keys($this->group_common_params()));
    }

    // Common settings
    public function group_common_params() {
        return array_fill_keys(array('binarygrading', 'showmistakes', 'checkbutton'), true);
    }

    public function group_common() {
        return array('name' => 'commonsettings', 'items' => $this->group_common_params());
    }
}

class qtype_digitalliteracy_test_structures {
    // templates (structures)
    private $excel_structure = array(4, 4, 4);
    private $powerpoint_structure = array(2, 2);

    public function get_structure($responseformat) {
        return strlen($responseformat) === 0 ? $this->biggest_structure() :
            $this->{$responseformat. '_structure'};
    }

    private function biggest_structure() {
        $qtype = question_bank::get_qtype('digitalliteracy');
        $formats = array_keys($qtype->response_formats());
        $max = 0;
        $result = array();
        foreach ($formats as $format) {
            $structure = $this->{$format. '_structure'};
            $sum = array_sum($structure);
            if ($sum > $max) {
                $max = $sum;
                $result = $structure;
            }
        }
        return $result;
    }
}

class qtype_digitalliteracy_test_describer {
    private static $numbermapper = array(1 => 'one', 2 => 'two',
        3 => 'three', 4 => 'four');

    public static function describe_group($group, $param_count) {
        if ($param_count <= 0 || $param_count > count(self::$numbermapper))
            throw new coding_exception('Can\'t describe group, check mapper!');

        $result = array();
        // A grading options group, only two element types supported for now
        // 'text' [false] and 'advcheckbox' [true] <=> key - unique name, value - type
        $result['group'. self::$numbermapper[$group]. 'coef'] = false;
        for ($i = 1; $i <= $param_count; $i++) {
            $result['group'. self::$numbermapper[$group].
            'param'. self::$numbermapper[$i]] = true;
        }
        return array('name' => 'group'. self::$numbermapper[$group], 'items' => $result);
    }
}