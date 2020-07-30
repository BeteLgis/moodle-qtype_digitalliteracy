<?php

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot.'/question/type/digitalliteracy/slider.php');
require_once($CFG->dirroot.'/question/type/digitalliteracy/question.php');
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
                    'qtype_digitalliteracy'), array('size' => 1, 'maxlength' => 3));
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
        foreach (array('sourcefiles', 'templatefiles') as $filearea) {
            $draftid_editor = 0;
            file_prepare_draft_area($draftid_editor, $question->contextid, 'qtype_digitalliteracy',
                $filearea, $question->id, array('subdirs' => false));
            $question->{$filearea . '_filemanager'} = $draftid_editor;
        }
        $this->load_formdata_into_question($question, false);
        // prepare files
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
        // coefs - coef [significance text area] => group association
        $coefs = array('firstcoef' => 'coef_value_group', 'secondcoef' => 'coef_format_group',
            'thirdcoef' => 'coef_enclosures_group');
        // groups - group => param (checkbox) association
        $groups = array('coef_value_group' => ['paramvalue', 'paramtype'], 'coef_format_group' =>
        ['parambold', 'paramfillcolor'], 'coef_enclosures_group' => ['paramcharts', 'paramimages']);

        $values = $this->validatecoefs($coefs, $fromform, $errors);
        $this->validateparams($coefs, $groups, $fromform, $values, $errors);
        if ($fromform['filetypeslist'] == '')
            $errors['filetypeslist'] = get_string('emptyfiletypelist', 'qtype_digitalliteracy');

        if (empty($errors))
            $this->validatedata($fromform, $errors);

        return $errors;
    }

    private function validatecoefs($coefs, $fromform, &$errors) {
        $options = array(
            'options' => array(
                'default' => -1,
                'min_range' => 0,
                'max_range' => 100
            )
        );
        $values = array();
        foreach ($coefs as $value => $group) { // validate significance value
            if (($res = filter_var($fromform[$value], FILTER_VALIDATE_INT, $options)) < 0) {
                $errors[$group] = get_string('validatecoef', 'qtype_digitalliteracy');
            } else
                $values[$value] = $res;
        }
        if (count($values) === 3 && array_sum($values) != 100) { // validate significances sum
            foreach ($coefs as $value => $group) {
                $errors[$group] = get_string('notahunred', 'qtype_digitalliteracy');
            }
        }
        return $values;
    }

    private function validateparams($coefs, $groups, $fromform, $values, &$errors) {
        foreach ($values as $key => $value) {
            if ($value != 0) { // for each coefficient with non-zero significance validate params - checkboxes
                $counter = false;
                foreach ($groups[$coefs[$key]] as $checkbox) {
                    if ($fromform[$checkbox] == 1) {
                        $counter = true;
                        break;
                    }
                }
                if (!$counter) { // concatenate error string for a group === $coefs[$key]
                    $prefix = empty($errors[$coefs[$key]]) ? '' : $errors[$coefs[$key]] . ' | ';
                    $errors[$coefs[$key]] = $prefix . get_string('tickacheckbox', 'qtype_digitalliteracy');
                }
            }
        }
    }

    private function validatedata($fromform, &$errors) {
        global $USER;
        $error_types = $this->error_interpreter();
        $question = $this->load_formdata_into_question($fromform, true);
        $question->contextid = context_user::instance($USER->id)->id;

        $response = array('attachments' => new question_file_saver($fromform['sourcefiles_filemanager'],
            'qtype_digitalliteracy', 'sourcefiles'), 'flag' => '1');
        $flag = false;
        if (($error = $question->validate_response($response)) !== '') {
            $errors[$error_types['sourcefiles']] = $error;
            $flag = true;
        }
        if ($question->hastemplatefile && ($error = $this->validate_files($fromform,
                'templatefiles_filemanager')) !== '') {
            $errors[$error_types['templatefiles']] = $error;
            $flag = true;
        }
        if ($flag)
            return;

        if ($question->hastemplatefile && $question->excludetemplate)
            $question->templatefiles = $fromform['templatefiles_filemanager'];
        $result = $question->grade_response($response);
        list($fraction, $state) = $result;
        if ($fraction != 1.0) {
            $errors[$error_types['']] = count($result) > 2 ? $result[2]['_error']:
                get_string('validationerror', 'qtype_digitalliteracy');
        }
    }

    private function error_interpreter() {
        return array('templatefiles' => 'templatefiles_filemanager',
            'sourcefiles' => 'sourcefiles_filemanager',
            '' => 'commonsettings'); // '' == NULL check https://www.php.net/manual/en/language.types.array.php
    }

    private function load_formdata_into_question(&$question, $newquestion) {
        $extraquestionfields = (new qtype_digitalliteracy())->extra_question_fields();
        if (is_array($extraquestionfields)) {
            array_shift($extraquestionfields);
            if ($newquestion) {
                $res = new qtype_digitalliteracy_question();
                foreach ($extraquestionfields as $field) {
                    $res->$field = $question[$field];
                }
                $res->filetypeslist = $question['filetypeslist'];
                return $res;
            } else {
                foreach ($extraquestionfields as $field) {
                    $question->$field = $question->options->$field;
                }
                $question->filetypeslist = $question->options->filetypeslist;
            }
        }
        return '';
    }

    private function validate_files($fromform, $element) {
        global $USER;
        $fs = get_file_storage();
        $contextid = context_user::instance($USER->id)->id;
        $files = $fs->get_area_files($contextid, 'user', 'draft',
            $fromform[$element], 'filename', false);
        $comparator = new qtype_digitalliteracy_comparator();
        return $comparator->validate_files($files, $fromform['responseformat'],
            $fromform['filetypeslist'], $fromform['attachmentsrequired']);
    }
    /** Override this in the subclass to question type name.
     * @Overrides question_edit_form::qtype */
    public function qtype() {
        return 'digitalliteracy';
    }
}