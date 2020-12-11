<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/digitalliteracy/question.php');

/**
 * Class for building and displaying a digitalliteracy question
 * editing form for a teacher.
 * Performs input data (settings and files) validation!
 */
class qtype_digitalliteracy_edit_form extends question_edit_form {

    const component = 'qtype_digitalliteracy';
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
            get_string('responseformat', self::component), $responseformats);
        $mform->setDefault('responseformat', 'excel');

        $mform->addElement('header', 'responsefileoptions', get_string('responsefileoptions',
            self::component));
        $mform->setExpanded('responsefileoptions');

        $mform->addElement('select', 'attachmentsrequired',
            get_string('attachmentsrequired', self::component), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', 1);
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', self::component);

        $mform->addElement('filetypes', 'filetypeslist', get_string('acceptedfiletypes',
            self::component), $qtype->attachments_filetypes_option());
        $mform->setDefault('filetypeslist', '.xlsx');
        $mform->addHelpButton('filetypeslist', 'acceptedfiletypes', self::component);
//        $mform->addRule('filetypeslist', get_string('emptyfiletypelist', self::component),
//            'required', null, 'client'); // Error here fixed in newer version of moodle

        $mform->addElement('filemanager', 'sourcefiles_filemanager', get_string('sourcefiles',
            self::component), null, $options);

        $template_setting_group = array();
        $template_setting_group[] = &$mform->createElement('advcheckbox', 'showtemplatefile',
            get_string('showtemplatefile', self::component));
        $mform->setDefault('showtemplatefile', false);

        $template_setting_group[] = &$mform->createElement('advcheckbox', 'excludetemplate',
            get_string('excludetemplate', self::component));
        $mform->setDefault('excludetemplate', true);

        $mform->addGroup($template_setting_group, 'templatesettings', get_string('templatesettings',
            self::component), null, false);
        $mform->addHelpButton('templatesettings', 'templatesettings', self::component);

        $mform->addElement('filemanager', 'templatefiles_filemanager', get_string('templatefiles',
            self::component), null, $options);

        $mform->addElement('header', 'responsegradingoptions', get_string('responsegradingoptions',
            self::component));
        $mform->setExpanded('responsegradingoptions');

        foreach ($settings->get_groups() as $index => $group) {
            $this->add_group($mform, $group);
            if ($index === 1) {
                $options = array(
                    'multiple' => true,
                    'noselectionstring' => get_string('allfontparams', self::component),
                    'placeholder' => get_string('choosefontparams', self::component)
                );
                $params = array();
                foreach ($qtype->fontparams() as $param) {
                    $params[] = get_string($param, self::component);
                }
                $mform->addElement('autocomplete', 'fontparams', get_string('fontparams',
                    self::component), $params, $options);
            }
        }

        $this->add_group($mform, $settings->group_common(), true);

        $mform->setDefault($settings->get_coefs()[0], '100');
        $mform->setDefault('binarygrading', false);

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
                $content[] = &$mform->createElement('text', $name, get_string('significance',
                    self::component), array('size' => 1, 'maxlength' => 3));
                $mform->setType($name, PARAM_RAW);
                $mform->setDefault($name, '0');
            } else { // true == advcheckbox
                $content[] = &$mform->createElement('advcheckbox', $name, $commom ?
                    get_string($name, self::component) : $name);
                $mform->setDefault($name, true);
            }
        }
        $mform->addGroup($content, $groupname, $commom ? get_string($groupname,
            self::component) : $groupname, null, false);
//        $mform->addHelpButton($groupname, $identifier, self::component);
    }

    /**
     * Initializes the AMD JS script to change the default labels according to the specified responseformat.
     * @param $mform MoodleQuickForm
     * @param $responseformats {@link qtype_digitalliteracy::response_formats()}
     * @param $settings qtype_digitalliteracy_settings
     */
    private function responseformat_change_listeners(&$mform, $responseformats, $settings) {
        $labels = $this->createlabels($responseformats, clone $settings);
        $labels['filetype_description'] = get_string('filetype_description', self::component);

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
        $groups = $settings->get_paramscount_map();
        $data = array('params' => array_flip($params), 'groups' => $groups, 'types' => $types);
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
                $labels[$key] = get_string($key, self::component);
            }
            foreach ($groups as $group) {
//                foreach (array('_help_title', '_help_text') as $item) {
//                    $key = $group. $item. '_'. $responseformat;
//                    $value = get_string($key, self::component);
//                    $labels[$key] = get_string('pattern'. $item, self::component, $value);
//                    if (strlen($item) === 11) // $item === '_help_title'
//                        $labels[$group. '_'. $responseformat] = $value;
//                }
                $key = $group. '_'. $responseformat;
                $labels[$key] = get_string($key, self::component);
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
        $errors = array('validatecoef' => get_string('validatecoef', self::component),
            'notahundred' => get_string('notahundred', self::component),
            'tickacheckbox' => get_string('tickacheckbox', self::component));

        global $PAGE;
        $data = array('coefs_map' => $settings->get_coefs_map(), 'params_map' => $settings->get_params_map(),
            'errors' => $errors);
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
            file_prepare_draft_area($draftitemid, $question->contextid, self::component,
                $filearea, $question->id, array('subdirs' => false));
            $question->{$filearea . '_filemanager'} = $draftitemid;
        }
        $this->load_formdata_into_question($question, false);
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
                self::component);
        else
            $this->validatefiletypelist($qtype, $fromform,$errors);

        if (!in_array($fromform['attachmentsrequired'], $qtype->attachments_required_options()))
            $errors['attachmentsrequired'] = get_string('elementchanged',
                self::component);

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
            $errors['filetypeslist'] = get_string('emptyfiletypelist', self::component);
            return;
        }
        $accepted = call_user_func(array($qtype, $fromform['responseformat']. '_filetypes'));
        if (!empty($types = array_diff(explode(',', $fromform['filetypeslist']), $accepted))) {
            $errors['filetypeslist'] = get_string('incorrectfiletypes', self::component,
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
                $error_msg[$group][] = get_string('validatecoef', self::component);
            }
        }
        if (!in_array(-1, $values) && array_sum($values) != 100) { // validates sum
            foreach ($coefs_map as $group) {
                $error_msg[$group][] = get_string('notahundred', self::component);
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
                    $error_msg[$group][] = get_string('tickacheckbox', self::component);
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
            self::component, 'sourcefiles'));
        $erroroccured = false;
        if (!empty($error = $question->validate_response($response))) {
            $errors['sourcefiles_filemanager'] = $error;
            $erroroccured = true;
        }
        if ($question->excludetemplate && !empty($error = $this->validate_files($question->contextid,
                $fromform, 'templatefiles_filemanager'))) {
            $errors['templatefiles_filemanager'] = $error;
            $erroroccured = true;
        }
        if ($erroroccured)
            return;

        if ($question->excludetemplate)
            $question->templatefilesdraftid = $fromform['templatefiles_filemanager'];

        $question->validation = true;
        $result = $question->grade_response($response);
        if ($result[0] != 1.0) {
            $errors['commonsettings'] = count($result) > 2 ? $result[2]['_error'] :
                get_string('validationerror', self::component, $result[0]);
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
        $qtype = new qtype_digitalliteracy();
        $extraquestionfields = (new qtype_digitalliteracy())->extra_question_fields();
        if (is_array($extraquestionfields)) {
            array_shift($extraquestionfields);
            if ($newquestion) {
                $res = new qtype_digitalliteracy_question();
                foreach ($extraquestionfields as $field) {
                    $res->$field = $question[$field];
                }
                $res->filetypeslist = $question['filetypeslist'];
                $res->fontparams = $qtype->get_bitmask_string($question['fontparams'], 6);
                return $res;
            } else {
                foreach ($extraquestionfields as $field) {
                    $question->$field = $question->options->$field;
                }
                $question->filetypeslist = $question->options->filetypeslist;
                $question->fontparams = $qtype->unmask_bitmask($question->options->fontparams, 6);
            }
        }
        return '';
    }

    /**
     * Sends files for validation.
     * @param int $contextid
     * @param array $fromform
     * @param string $element a filemanager element
     * @return string an error message or an empty string
     */
    private function validate_files($contextid, $fromform, $element) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'user', 'draft',
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