<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/digitalliteracy/question.php');

/** Class for rendering (and outputting) question edit form */
class qtype_digitalliteracy_edit_form extends question_edit_form {

    /** Add any question-type specific form fields.
     * @Overrides question_edit_form::definition_inner
     * @param $mform MoodleQuickForm the form being built.
     * groups are declared in {@link qtype_digitalliteracy_test_settings}
     */
    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('digitalliteracy');
        $options['subdirs'] = false;
        $options['maxfiles'] = 1;
        $grading_options = new qtype_digitalliteracy_test_settings();

        $responseformats = $qtype->response_formats();
        $mform->addElement('select', 'responseformat',
            get_string('responseformat', 'qtype_digitalliteracy'), $responseformats);
        $mform->setDefault('responseformat', 'excel');

        $mform->addElement('header', 'responsefileoptions', get_string('responsefileoptions',
            'qtype_digitalliteracy'));
        $mform->setExpanded('responsefileoptions');

        $mform->addElement('select', 'attachmentsrequired',
            get_string('attachmentsrequired', 'qtype_digitalliteracy'), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', 1);
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_digitalliteracy');

        $mform->addElement('filetypes', 'filetypeslist', get_string('acceptedfiletypes',
            'qtype_digitalliteracy'), $qtype->attachments_filetypes_option());
        $mform->setDefault('filetypeslist', '.xlsx');
        $mform->addHelpButton('filetypeslist', 'acceptedfiletypes', 'qtype_digitalliteracy');
//        $mform->addRule('filetypeslist', get_string('emptyfiletypelist', 'qtype_digitalliteracy'),
//            'required', null, 'client'); // Error here fixed in newer version of moodle

        $mform->addElement('filemanager', 'sourcefiles_filemanager', get_string('sourcefiles',
            'qtype_digitalliteracy'), null, $options);

        $template_setting_group = array();
        $template_setting_group[] = $mform->createElement('advcheckbox', 'hastemplatefile', get_string('hastemplatefile',
            'qtype_digitalliteracy'));
        $mform->setDefault('hastemplatefile', 0);

        $template_setting_group[] = $mform->createElement('advcheckbox', 'excludetemplate', get_string('excludetemplate',
        'qtype_digitalliteracy'));
        $mform->disabledIf('excludetemplate', 'hastemplatefile');
        $mform->setDefault('excludetemplate', 0);

        $mform->addElement('group', 'template_settings', get_string('template_settings',
            'qtype_digitalliteracy'), $template_setting_group, null, false);
        $mform->addHelpButton('template_settings', 'template_settings', 'qtype_digitalliteracy');

        $mform->addElement('filemanager', 'templatefiles_filemanager', get_string('responsefiletemplate',
            'qtype_digitalliteracy'), null, $options);
        $mform->hideif('templatefiles_filemanager', 'hastemplatefile');

        $mform->addElement('header', 'responsegradingoptions', get_string('responsegradingoptions',
            'qtype_digitalliteracy'));
        $mform->setExpanded('responsegradingoptions');

        foreach ($grading_options->get_groups() as $group) {
            $this->add_group($mform, $group);
        }
        $this->add_group($mform, $grading_options->group_common(), true);

        $mform->setDefault($grading_options->get_coefs()[0], '100');
        $mform->setDefault('binarygrading', 0);

        $this->responseformat_change_listeners($mform, $responseformats, $grading_options);
        $this->validation_listeners($grading_options);
    }

    /**
     * Adds group [listed above] to the form
     * @param $mform MoodleQuickForm
     * @param bool $commom is the flag indicating common group
     * @link HTML_QuickForm_element
     * @link MoodleQuickForm_group
     */
    private function add_group(&$mform, $group, $commom = false) {
        $groupname = $group['name'];
        $items = $group['items'];

        $content = array();
        foreach ($items as $name => $type) {
            if (!$type) { // false == text
                $content[] = $mform->createElement('text', $name, get_string('significance',
                    'qtype_digitalliteracy'), array('size' => 1, 'maxlength' => 3));
                $mform->setType($name, PARAM_RAW);
                $mform->setDefault($name, '0');
            } else { // true == advcheckbox
                $identifier = $commom ? $name : $name. '_excel';
                $content[] = $mform->createElement('advcheckbox', $name, get_string($identifier,
                    'qtype_digitalliteracy'));
                $mform->setDefault($name, 1);
            }
        }
        $identifier = $commom ? $groupname : 'groupplaceholder';
        $mform->addElement('group', $groupname, get_string($identifier,
            'qtype_digitalliteracy'), $content, null, false);
        $mform->addHelpButton($groupname, $identifier, 'qtype_digitalliteracy');
    }

    /**
     * Creates necessary data structure for response format change action [select element]
     * and passes that data to JS (AMD)
     * @param $grading_options qtype_digitalliteracy_test_settings
     */
    private function responseformat_change_listeners(&$mform, $responseformats, $grading_options) {
        $labels = $this->createlabels($responseformats, clone $grading_options);
        $labels['filetype_description'] = get_string('filetype_description', 'qtype_digitalliteracy');

        // a little trick to avoid error "more than 1024 symbols were passed to an AMD JS script"
        $src = html_writer::tag('div', '', array('class' => 'data_container', 'id' =>
            'id_labels_container', 'data-serialized' => serialize($labels))); // set serialized content
        $mform->addElement('html', $src);

        // used for renaming filetypelist
        $types = array('matches' => ['excel' => 'spreadsheet', 'powerpoint' => 'presentation'],
            'defaults' => ['excel' => ['value' => '.xlsx', 'description' =>
                get_string('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','mimetypes')],
                'powerpoint' => ['value' => '.pptx', 'description' =>
                    get_string('application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'mimetypes')]]);

        global $PAGE;
        $params = $grading_options->get_params();
        $groups = $grading_options->get_groups_names();
        $data = array('params' => $params, 'groups' => $groups, 'types' => $types);
        $PAGE->requires->js_call_amd('qtype_digitalliteracy/labelchange', 'process',
            array($data));
        $coefs = $grading_options->get_coefs_ids();
        $PAGE->requires->js_call_amd('qtype_digitalliteracy/coefficientchange', 'process',
            array($coefs));
    }

    /** @return array of the labels for various response options */
    private function createlabels($responseformats, $grading_options) {
        $responseformats = array_keys($responseformats);
        $labels = array();
        foreach ($responseformats as $responseformat) {
            $grading_options->set_groups($responseformat);
            $params = $grading_options->get_params();
            $groups = $grading_options->get_groups_names();
            foreach ($params as $param) {
                $key = $param. '_'. $responseformat;
                $labels[$key] = get_string($key, 'qtype_digitalliteracy');
            }
            foreach ($groups as $group) {
                foreach (array('_help_title', '_help_text') as $item) {
                    $key = $group. $item. '_'. $responseformat;
                    $labels[$key] = get_string('pattern'. $item, 'qtype_digitalliteracy',
                        get_string($key, 'qtype_digitalliteracy'));
                    if (strlen($item) === 11)
                        $labels[$group. '_'. $responseformat] = get_string($key, 'qtype_digitalliteracy');
                }
            }
        }
        return $labels;
    }

    /** Add validation messages to the HTML document [using AMD JS]
     * and trigger them on corresponding events
     * @param $grading_options qtype_digitalliteracy_test_settings
     */
    private function validation_listeners($grading_options) {
        $errors = array('validatecoef' => get_string('validatecoef', 'qtype_digitalliteracy'),
            'notahundred' => get_string('notahundred', 'qtype_digitalliteracy'),
            'tickacheckbox' => get_string('tickacheckbox', 'qtype_digitalliteracy'));

        global $PAGE;
        $data = array('coefs_map' => $grading_options->get_coefs_map(),
            'params_map' => $grading_options->get_params_map(), 'errors' => $errors);
        $PAGE->requires->js_call_amd('qtype_digitalliteracy/validation', 'process',
            array($data));
    }

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

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);
        $grading_options = new qtype_digitalliteracy_test_settings();
        $grading_options->set_groups($fromform['responseformat']);
        $error_msg = array();

        // coefs_map - coef [significance text area] => group association
        $coefs_map = $grading_options->get_coefs_map();
        $values = $this->validatecoefs($coefs_map, $fromform, $error_msg);

        // params_map - group => params (checkboxes) association
        $params_map = $grading_options->get_params_map();
        $this->validateparams($params_map, $fromform, $values, $error_msg);

        foreach ($error_msg as $group => $value) {
            if (!empty($value))
                $errors[$group] = implode(' | ', $value);
        }

        $this->validatefiletypelist($fromform,$errors);

        if (empty($errors))
            $this->validatedata($fromform, $errors);

        return $errors;
    }

    /** Validates coefficients (int int range [0,100] and sum of them is 100) */
    private function validatecoefs($coefs_map, $fromform, &$error_msg) {
        $options = array(
            'options' => array(
                'default' => -1,
                'min_range' => 0,
                'max_range' => 100
            )
        );
        $values = array();
        foreach ($coefs_map as $coef => $group) { // validates value
            $error_msg[$group] = array();
            $res = filter_var($fromform[$coef], FILTER_VALIDATE_INT, $options);
            $values[$group] = $res;
            if ($res < 0) {
                $error_msg[$group][] = get_string('validatecoef', 'qtype_digitalliteracy');
            }
        }
        if (!in_array(-1, $values) && array_sum($values) != 100) { // validates sum
            foreach ($coefs_map as $group) {
                $error_msg[$group][] = get_string('notahundred', 'qtype_digitalliteracy');
            }
        }
        return $values;
    }

    /** Validates params (Value, Calculated Value, Bold and so on) */
    private function validateparams($params_map, $fromform, $values, &$error_msg) {
        foreach ($params_map as $group => $params) {
            if ($values[$group] !== 0) { // for each coefficient with non-zero significance validate params - checkboxes
                $counter = false;
                foreach ($params as $param) {
                    if ($fromform[$param] == 1) {
                        $counter = true;
                        break;
                    }
                }
                if (!$counter) { // concatenate error string for a group === $coefs[$key]
                    $error_msg[$group][] = get_string('tickacheckbox', 'qtype_digitalliteracy');
                }
            }
        }
    }

    private function validatefiletypelist($fromform, &$errors) {
        if ($fromform['filetypeslist'] === '') {
            $errors['filetypeslist'] = get_string('emptyfiletypelist', 'qtype_digitalliteracy');
            return;
        }
        $qtype = question_bank::get_qtype('digitalliteracy');
        $accepted = call_user_func(array($qtype, $fromform['responseformat']. '_filetypes'));
        if (!empty($types = array_diff(explode(',', $fromform['filetypeslist']), $accepted))) {
            $errors['filetypeslist'] = get_string('incorrectfiletypes', 'qtype_digitalliteracy',
                implode(', ', $types));
        }
    }

    /** Validates uploaded files (test run of {@link qtype_digitalliteracy_question::grade_response()}) */
    private function validatedata($fromform, &$errors) {
        global $USER;
        $error_types = $this->error_interpreter();
        $question = $this->load_formdata_into_question($fromform, true);
        $question->contextid = context_user::instance($USER->id)->id;

        $response = array('attachments' => new question_file_saver($fromform['sourcefiles_filemanager'],
            'qtype_digitalliteracy', 'sourcefiles'), 'validation' => true);
        $flag = false;
        if (!empty($error = $question->validate_response($response))) {
            $errors[$error_types['sourcefiles']] = $error;
            $flag = true;
        }
        if ($question->hastemplatefile && !empty($error = $this->validate_files($fromform,
                'templatefiles_filemanager'))) {
            $errors[$error_types['templatefiles']] = $error;
            $flag = true;
        }
        if ($flag)
            return;

        if ($question->hastemplatefile && $question->excludetemplate)
            $question->templatefiles_draftid = $fromform['templatefiles_filemanager'];

        $result = $question->grade_response($response);
        list($fraction, $state) = $result;
        if ($fraction != 1.0) {
            $errors[$error_types['']] = count($result) > 2 ? $result[2]['_error'] :
                get_string('validationerror', 'qtype_digitalliteracy');
        }
    }

    /** Used just for convenience [more handy element matching] */
    private function error_interpreter() {
        return array('templatefiles' => 'templatefiles_filemanager',
            'sourcefiles' => 'sourcefiles_filemanager',
            '' => 'commonsettings'); // '' == NULL check https://www.php.net/manual/en/language.types.array.php
    }

    /** Load formdata into question
     * Separate function created as it is used in two places
     * {@link qtype_digitalliteracy_edit_form::data_preprocessing()}
     * and {@link qtype_digitalliteracy_edit_form::validatedata()}
     */
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

    /** Send files for validation */
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