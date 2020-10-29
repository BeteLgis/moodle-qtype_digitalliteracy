<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/digitalliteracy/question.php');

/**
 * Class for building and displaying a digitalliteracy question
 * editing form for a teacher.
 * Performs input data (settings and files) validation!
 */
class qtype_digitalliteracy_edit_form extends question_edit_form {

    /**
     * Add a digitalliteracy question type specific form fields.
     * Setting fields are declared in {@link qtype_digitalliteracy_settings}.
     * @Overrides {@link question_edit_form::definition_inner}
     * @param $mform MoodleQuickForm the form being built
     */
    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('digitalliteracy');
        $options = array();
        $options['subdirs'] = false;
        $options['maxfiles'] = 1;
        $settings = new qtype_digitalliteracy_settings();

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

        $mform->addElement('group', 'templatesettings', get_string('templatesettings',
            'qtype_digitalliteracy'), $template_setting_group, null, false);
        $mform->addHelpButton('templatesettings', 'templatesettings', 'qtype_digitalliteracy');

        $mform->addElement('filemanager', 'templatefiles_filemanager', get_string('templatefiles',
            'qtype_digitalliteracy'), null, $options);
        $mform->hideif('templatefiles_filemanager', 'hastemplatefile');

        $mform->addElement('header', 'responsegradingoptions', get_string('responsegradingoptions',
            'qtype_digitalliteracy'));
        $mform->setExpanded('responsegradingoptions');

        foreach ($settings->get_groups() as $group) {
            $this->add_group($mform, $group);
        }
        $this->add_group($mform, $settings->group_common(), true);

        $mform->setDefault($settings->get_coefs()[0], '100');
        $mform->setDefault('binarygrading', 0);

        $this->responseformat_change_listeners($mform, $responseformats, $settings);
        $this->validation_listeners($settings);
    }

    /**
     * Adds a setting group from {@link qtype_digitalliteracy_settings::get_groups()} to the form.
     * For more details, also see {@link HTML_QuickForm_element} and {@link MoodleQuickForm_group}.
     * @param $mform MoodleQuickForm
     * @param array $group an element from {@link qtype_digitalliteracy_settings::get_groups()}
     * @param bool $commom a flag indicating common group
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
                $identifier = $commom ? $name : 'paramplaceholder';
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
     * Initializes the AMD JS script to change the default labels according to the specified responseformat.
     * @param $mform MoodleQuickForm
     * @param $responseformats {@link qtype_digitalliteracy::response_formats()}
     * @param $settings qtype_digitalliteracy_settings
     */
    private function responseformat_change_listeners(&$mform, $responseformats, $settings) {
        $labels = $this->createlabels($responseformats, clone $settings);
        $labels['filetype_description'] = get_string('filetype_description', 'qtype_digitalliteracy');

        // a little trick to avoid error "more than 1024 symbols were passed to an AMD JS script"
        $src = html_writer::tag('div', '', array('class' => 'data_container', 'id' =>
            'id_labels_container', 'data-serialized' => serialize($labels))); // set serialized content
        $mform->addElement('html', $src);

        // used for renaming filetypelist
        $types = array('matches' => ['excel' => 'spreadsheet', 'powerpoint' => 'presentation', 'word' => 'document'],
            'defaults' => ['excel' => ['value' => '.xlsx', 'description' =>
                get_string('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','mimetypes')],
                'powerpoint' => ['value' => '.pptx', 'description' =>
                    get_string('application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'mimetypes')],
                'word' => ['value' => '.docx', 'description' =>
                    get_string('application/vnd.openxmlformats-officedocument.wordprocessingml.document','mimetypes')]]);

        global $PAGE;
        $params = $settings->get_params();
        $groups = $settings->get_groups_names();
        $data = array('params' => $params, 'groups' => $groups, 'types' => $types);
        $PAGE->requires->js_call_amd('qtype_digitalliteracy/labelchange', 'process',
            array($data));
        $coefs = $settings->get_coefs_ids();
        $PAGE->requires->js_call_amd('qtype_digitalliteracy/coefficientchange', 'process',
            array($coefs));
    }

    /**
     * Creates the labels array that will be send to the JS script.
     * @param $responseformats {@link qtype_digitalliteracy::response_formats()}
     * @param $settings qtype_digitalliteracy_settings
     * @return array an array containing the labels for all of the $responseformats
     */
    private function createlabels($responseformats, $settings) {
        $responseformats = array_keys($responseformats);
        $labels = array();
        foreach ($responseformats as $responseformat) {
            $settings->set_groups($responseformat);
            $params = $settings->get_params();
            $groups = $settings->get_groups_names();
            foreach ($params as $param) {
                $key = $param. '_'. $responseformat;
                $labels[$key] = get_string($key, 'qtype_digitalliteracy');
            }
            foreach ($groups as $group) {
                foreach (array('_help_title', '_help_text') as $item) {
                    $key = $group. $item. '_'. $responseformat;
                    $value = get_string($key, 'qtype_digitalliteracy');
                    $labels[$key] = get_string('pattern'. $item, 'qtype_digitalliteracy', $value);
                    if (strlen($item) === 11) // $item === '_help_title'
                        $labels[$group. '_'. $responseformat] = $value;
                }
            }
        }
        return $labels;
    }

    /**
     * Adds validation checks to the HTML document [using AMD JS]
     * and triggers them on corresponding values change.
     * @param $settings qtype_digitalliteracy_settings
     */
    private function validation_listeners($settings) {
        $errors = array('validatecoef' => get_string('validatecoef', 'qtype_digitalliteracy'),
            'notahundred' => get_string('notahundred', 'qtype_digitalliteracy'),
            'tickacheckbox' => get_string('tickacheckbox', 'qtype_digitalliteracy'));

        global $PAGE;
        $data = array('coefs_map' => $settings->get_coefs_map(),
            'params_map' => $settings->get_params_map(), 'errors' => $errors);
        $PAGE->requires->js_call_amd('qtype_digitalliteracy/validation', 'process',
            array($data));
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }
        foreach (array('sourcefiles', 'templatefiles') as $filearea) {
            $draftitemid = 0;
            file_prepare_draft_area($draftitemid, $question->contextid, 'qtype_digitalliteracy',
                $filearea, $question->id, array('subdirs' => false));
            $question->{$filearea . '_filemanager'} = $draftitemid;
        }
        $this->load_formdata_into_question($question, false);
        // prepare files
        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);
        if ($this->validatefields($fromform, $errors))
            return $errors;

        $settings = new qtype_digitalliteracy_settings($fromform['responseformat']);
        $error_msg = array();

        // coefs_map - coef [significance text area] => group association
        $coefs_map = $settings->get_coefs_map();
        $values = $this->validatecoefs($coefs_map, $fromform, $error_msg);

        // params_map - group => params (checkboxes) association
        $params_map = $settings->get_params_map();
        $this->validateparams($params_map, $fromform, $values, $error_msg);

        foreach ($error_msg as $group => $value) {
            if (!empty($value))
                $errors[$group] = implode(' | ', $value);
        }

        if (empty($errors))
            $this->validatedata($fromform, $errors);

        return $errors;
    }

    /**
     * Validates input fields. Checks that their html code wasn't changed by the user.
     * Validates the file type list.
     * @param array $fromform
     * @param array $errors
     */
    private function validatefields($fromform, &$errors) {
        $qtype = question_bank::get_qtype('digitalliteracy');

        if (!in_array($fromform['responseformat'], array_flip($qtype->response_formats())))
            $errors['responseformat'] = get_string('elementchanged',
                'qtype_digitalliteracy');
        else
            $this->validatefiletypelist($qtype, $fromform,$errors);

        if (!in_array($fromform['attachmentsrequired'], $qtype->attachments_required_options()))
            $errors['attachmentsrequired'] = get_string('elementchanged',
                'qtype_digitalliteracy');

        return !empty($errors);
    }

    /**
     * File type list validation.
     * @param question_type $qtype
     * @param array $fromform
     * @param array $errors
     */
    private function validatefiletypelist($qtype, $fromform, &$errors) {
        if ($fromform['filetypeslist'] === '') {
            $errors['filetypeslist'] = get_string('emptyfiletypelist', 'qtype_digitalliteracy');
            return;
        }
        $accepted = call_user_func(array($qtype, $fromform['responseformat']. '_filetypes'));
        if (!empty($types = array_diff(explode(',', $fromform['filetypeslist']), $accepted))) {
            $errors['filetypeslist'] = get_string('incorrectfiletypes', 'qtype_digitalliteracy',
                implode(', ', $types));
        }
    }

    /**
     * Validates coefficients (each is an integer in range [0,100] and sum of all is a 100).
     * @param array $coefs_map {@link qtype_digitalliteracy_settings::get_coefs_map()}
     * @param array $fromform
     * @param array $error_msg
     */
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

    /**
     * Validates params (at least one is checked).
     * @param array $params_map {@link qtype_digitalliteracy_settings::get_params_map()}
     * @param array $fromform
     * @param array $values
     * @param array $error_msg
     */
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

    /**
     * Validates uploaded files (test run of {@link qtype_digitalliteracy_question::grade_response()}).
     * @param array $fromform
     * @param array $errors
     */
    private function validatedata($fromform, &$errors) {
        global $USER;

        $question = $this->load_formdata_into_question($fromform, true);
        $question->contextid = context_user::instance($USER->id)->id;

        $response = array('attachments' => new question_file_saver($fromform['sourcefiles_filemanager'],
            'qtype_digitalliteracy', 'sourcefiles'));
        $erroroccured = false;
        if (!empty($error = $question->validate_response($response))) {
            $errors['sourcefiles_filemanager'] = $error;
            $erroroccured = true;
        }
        if ($question->hastemplatefile && !empty($error = $this->validate_files($fromform,
                'templatefiles_filemanager'))) {
            $errors['templatefiles_filemanager'] = $error;
            $erroroccured = true;
        }
        if ($erroroccured)
            return;

        if ($question->hastemplatefile && $question->excludetemplate)
            $question->templatefilesdraftid = $fromform['templatefiles_filemanager'];

        $question->validation = true;
        $result = $question->grade_response($response);
        list($fraction, $state) = $result;
        if ($fraction != 1.0) {
            $errors['commonsettings'] = count($result) > 2 ? $result[2]['_error'] :
                get_string('validationerror', 'qtype_digitalliteracy');
        }
    }

    /**
     * Load formdata into question.
     * Separate function is created as this logic is used in two places
     * {@link qtype_digitalliteracy_edit_form::data_preprocessing()}
     * and {@link qtype_digitalliteracy_edit_form::validatedata()}.
     * @param array|object $question
     * @param bool $newquestion return new object or not
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

    /**
     * Sends files for validation.
     * @param array $fromform
     * @param string $element a filemanager element
     * @return string an error message or an empty string
     */
    private function validate_files($fromform, $element) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'user', 'draft',
            $fromform[$element], 'filename', false);

        return (new qtype_digitalliteracy_sandbox($this->context->id))
            ->validate_files($files, $fromform['responseformat'],
            $fromform['filetypeslist'], $fromform['attachmentsrequired']);
    }

    /**
     * Override this in the subclass to question type name.
     * @Overrides {@link question_edit_form::qtype}
     */
    public function qtype() {
        return 'digitalliteracy';
    }
}