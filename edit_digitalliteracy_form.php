<?php

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot.'/question/type/digitalliteracy/slider.php');

//MoodleQuickForm::registerElementType('slider', "$CFG->dirroot/question/type/digitalliteracy/slider.php",
//    'MoodleQuickForm_slider');
/** class for rendering (and outputting) question edit form */
class qtype_digitalliteracy_edit_form extends question_edit_form {
    /** Add any question-type specific form fields.
     * @Overrides question_edit_form::definition_inner
     * @param $mform object the form being built.*/
    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('digitalliteracy');

        $options['subdirs'] = false;

        $mform->addElement('header', 'responsefileoptions', get_string('responsefileoptions',
            'qtype_digitalliteracy'));
        $mform->setExpanded('responsefileoptions');

        $mform->addElement('filemanager', 'sourcefiles_filemanager', get_string('sourcefiles',
            'qtype_digitalliteracy'), null, $options);

        $mform->addElement('select', 'responseformat',
            get_string('responseformat', 'qtype_digitalliteracy'), $qtype->response_formats());
        $mform->setDefault('responseformat', 'excel');

        $mform->addElement('select', 'attachmentsrequired',
            get_string('attachmentsrequired', 'qtype_digitalliteracy'), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', 1);
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_digitalliteracy');

        $mform->addElement('filetypes', 'filetypeslist', get_string('acceptedfiletypes',
            'qtype_digitalliteracy'), $qtype->attachments_filetypes_option());
        $mform->setDefault('filetypeslist', 'xlsx');
        $mform->addHelpButton('filetypeslist', 'acceptedfiletypes', 'qtype_digitalliteracy');
//        $mform->addRule('filetypeslist', null, 'required', null, 'client');

//        $mform->addElement('slider', 'firstslider', '[Feature is in development]',
//            array('min' => 0, 'max' => 10, 'value' => 5));

        $mform->addElement('advcheckbox', 'hastemplatefile', get_string('hastemplatefile',
            'qtype_digitalliteracy'));
        $mform->setDefault('hastemplatefile', 0);
        $mform->addElement('filemanager', 'templatefiles_filemanager', get_string('responsefiletemplate',
            'qtype_digitalliteracy'), null, $options);
        $mform->hideif('templatefiles_filemanager', 'hastemplatefile');

        $mform->addElement('header', 'responsegradingoptions', get_string('responsegradingoptions',
            'qtype_digitalliteracy'));
        $mform->setExpanded('responsegradingoptions');

        $this->add_group($mform, array('firstcoef' => 'text', 'paramvalue' => 'advcheckbox',
            'paramtype' => 'advcheckbox'), 'coef_value_group');

        $this->add_group($mform, array('secondcoef' => 'text', 'parambold' => 'advcheckbox',
            'paramfillcolor' => 'advcheckbox'), 'coef_format_group');

        $this->add_group($mform, array('thirdcoef' => 'text', 'paramcharts' => 'advcheckbox',
            'paramimages' => 'advcheckbox'), 'coef_enclosures_group');

        $this->add_group($mform, array_fill_keys(array('excludetemplate', 'binarygrading', 'showmistakes',
            'checkbutton'), 'advcheckbox'), 'commonsettings');

        $mform->setDefault('firstcoef', '100');
        $mform->setDefault('secondcoef', '0');
        $mform->setDefault('thirdcoef', '0');
        $mform->setDefault('binarygrading', '0');
        $mform->disabledIf('excludetemplate', 'hastemplatefile');
    }

    private function add_group(&$mform, array $names, $groupname) {
        $group = array();
        foreach ($names as $name => $type) {
            if ($type === 'text') {
                $significance = $name;
                $group[] = $mform->createElement($type, $name, get_string('significance',
                    'qtype_digitalliteracy'));
                $mform->setType($name, PARAM_RAW);
            } else {
                $group[] = $mform->createElement($type, $name, get_string($name,'qtype_digitalliteracy'));
                $mform->setDefault($name, '1');
            }
        }
        $mform->addElement('group', $groupname,
            get_string($groupname, 'qtype_digitalliteracy'), $group, null, false);
        $mform->addHelpButton($groupname, $groupname, 'qtype_digitalliteracy');
        if (isset($significance))
            $mform->disabledIf($groupname, $significance, 'eq', 0);
    }


    /** Perform an preprocessing needed on the data passed to set_data() before it is used to initialise the form.
     * @Overrides question_edit_form::data_preprocessing
     * @param $question object the data being passed to the form. */
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $extraquestionfields = (new qtype_digitalliteracy())->extra_question_fields();
        if (is_array($extraquestionfields)) {
            array_shift($extraquestionfields);
            foreach ($extraquestionfields as $field) {
                $question->$field = $question->options->$field;
            }
        }
        $question->filetypeslist = $question->options->filetypeslist;

//        $this->fileoptions; TODO
        // prepare files
        $filecontext = context::instance_by_id($question->contextid, MUST_EXIST);
        foreach (array('sourcefiles', 'templatefiles') as $filearea) {
            file_prepare_standard_filemanager($question, $filearea,
                array('subdirs' => false), $filecontext, 'qtype_digitalliteracy',
                $filearea, $question->id);
        }
        return $question;
    }

    /** Dummy stub method - override if you needed to perform some extra validation.
     * If there are errors return array of errors (“fieldname”=>“error message”), otherwise true if ok.
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     * @Overrides question_edit_form::validation
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param array $fromform array of ("fieldname"=>value) of submitted data*/
    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);
        $coefs = array('firstcoef' => 'coef_value_group', 'secondcoef' => 'coef_format_group',
            'thirdcoef' => 'coef_enclosures_group');
        $groups = array('coef_value_group' => ['paramvalue', 'paramtype'], 'coef_format_group' =>
        ['parambold', 'paramfillcolor'], 'coef_enclosures_group' => ['paramcharts', 'paramimages']);

        $options = array(
            'options' => array(
                'default' => -1,
                'min_range' => 0,
                'max_range' => 100
            )
        );
        $values = array();
        foreach ($coefs as $value => $group) {
            if (($res = filter_var($fromform[$value], FILTER_VALIDATE_INT, $options)) < 0) {
                $errors[$group] = get_string('validatecoef', 'qtype_digitalliteracy');
            } else
                $values[$value] = $res;
        }
        if (count($values) === 3 && array_sum($values) != 100) {
            foreach ($coefs as $value => $group) {
               $errors[$group] = get_string('notahunred', 'qtype_digitalliteracy');
            }
        }
        foreach ($values as $key => $value) {
            if ($value != 0) {
                $counter = false;
                foreach ($groups[$coefs[$key]] as $checkbox) {
                    if ($fromform[$checkbox] == 1) {
                        $counter = true;
                        break;
                    }
                }
                if (!$counter) {
                    $prefix = empty($errors[$coefs[$key]]) ? '' : $errors[$coefs[$key]] . ' | ';
                    $errors[$coefs[$key]] = $prefix . get_string('tickacheckbox', 'qtype_digitalliteracy');
                }
            }
        }

//        $this->validateFiles($fromform, $errors); TODO

        return $errors;
    }

    private function validateFiles($fromform, $errors) {
        switch ($this->question->options->responseformat)
        {
            case 'excel':
                $comparator = new qtype_digitalliteracy_excel_tester();
                break;
            case 'powerpoint':
                $comparator = new qtype_digitalliteracy_powerpoint_tester();
                break;
            default :
//                throw new dml_read_exception('qtype_digitalliteracy_option');
                return array(0, question_state::$needsgrading);
        }
        $dir = make_request_directory();
        $sources = $comparator->validate_filearea($this->question, 'sourcefiles', $dir);
        $templates = $comparator->validate_filearea($this->question, 'templatefiles', $dir);

        throw new Exception("a");
    }
    /** Override this in the subclass to question type name.
     * @Overrides question_edit_form::qtype */
    public function qtype() {
        return 'digitalliteracy';
    }
}