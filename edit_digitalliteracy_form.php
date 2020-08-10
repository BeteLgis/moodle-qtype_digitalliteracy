<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/digitalliteracy/question.php');

/** Class for rendering (and outputting) question edit form */
class qtype_digitalliteracy_edit_form extends question_edit_form {

    /** Add any question-type specific form fields.
     * @Overrides question_edit_form::definition_inner
     * @param $mform MoodleQuickForm the form being built.
     */
    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('digitalliteracy');
        $options['subdirs'] = false;
        $options['maxfiles'] = 1;

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

        $mform->addElement('advcheckbox', 'hastemplatefile', get_string('hastemplatefile',
            'qtype_digitalliteracy'));
        $mform->setDefault('hastemplatefile', 0);
        $mform->addElement('filemanager', 'templatefiles_filemanager', get_string('responsefiletemplate',
            'qtype_digitalliteracy'), null, $options);
        $mform->hideif('templatefiles_filemanager', 'hastemplatefile');

        $mform->addElement('header', 'responsegradingoptions', get_string('responsegradingoptions',
            'qtype_digitalliteracy'));
        $mform->setExpanded('responsegradingoptions');

        $this->add_group($mform, $this->group_1);
        $this->add_group($mform, $this->group_2);
        $this->add_group($mform, $this->group_3);
        $this->add_group($mform, $this->common_settings_group, true);

        $mform->setDefault('firstcoef', '100');
        $mform->setDefault('secondcoef', '0');
        $mform->setDefault('thirdcoef', '0');
        $mform->setDefault('binarygrading', '0');
        $mform->disabledIf('excludetemplate', 'hastemplatefile');

        $this->responseformat_change_listeners($mform, $responseformats);
        $this->validation_listeners();
    }

    // Grading options group, only two element types supported for now
    // 'text' [false] and 'advcheckbox' [true]
    private $group_1 = array('name' => 'coef_value_group', 'items' => ['firstcoef' => false,
        'paramvalue' => true, 'paramtype' => true]);
    private $group_2 = array('name' => 'coef_format_group', 'items' => ['secondcoef' => false,
        'parambold' => true, 'paramfillcolor' => true]);
    private $group_3 = array('name' => 'coef_enclosures_group', 'items' => ['thirdcoef' => false,
        'paramcharts' => true, 'paramimages' => true]);
    // Common settings
    private $common_settings_group = array('name' => 'commonsettings', 'items' =>
        ['excludetemplate' => true, 'binarygrading' => true,
            'showmistakes' => true, 'checkbutton' => true]);
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
                $significance = $name;
                $content[] = $mform->createElement('text', $name, get_string('significance',
                    'qtype_digitalliteracy'), array('size' => 1, 'maxlength' => 3));
                $mform->setType($name, PARAM_RAW);
            } else { // true == advcheckbox
                $identifier = $commom ? $name : $name. '_excel';
                $content[] = $mform->createElement('advcheckbox', $name, get_string($identifier,
                    'qtype_digitalliteracy'));
                $mform->setDefault($name, '1');
            }
        }
        $mform->addElement('group', $groupname, get_string($groupname,
            'qtype_digitalliteracy'), $content, null, false);
        $mform->addHelpButton($groupname, $groupname, 'qtype_digitalliteracy');
        if (isset($significance))
            $mform->disabledIf($groupname, $significance, 'eq', 0);
    }

    /**
     * Creates necessary data structure for response format change action [select element]
     * and passes that data to JS (AMD)
     */
    private function responseformat_change_listeners(&$mform, $responseformats) {
        $labels = array();

        $params = array('paramvalue', 'paramtype', 'parambold', 'paramfillcolor', 'paramcharts', 'paramimages');
        $groups = array('coef_value_group', 'coef_format_group', 'coef_enclosures_group');
        $responseformats = array_keys($responseformats);
        // saving all labels for various response options into $labels array
        foreach ($params as $param) {
            foreach ($responseformats as $responseformat) {
                $key = $param. '_'. $responseformat;
                $labels[$key] = get_string($key, 'qtype_digitalliteracy');
            }
        }
        foreach ($groups as $group) {
            foreach (array('_help_title', '_help_text') as $item) {
                foreach ($responseformats as $responseformat) {
                    $key = $group. $item. '_'. $responseformat;
                    $labels[$key] = get_string('pattern'. $item, 'qtype_digitalliteracy',
                        get_string($key, 'qtype_digitalliteracy'));
                    if (strlen($item) === 11)
                        $labels[$group. '_'. $responseformat] = get_string($key, 'qtype_digitalliteracy');
                }
            }
        }
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
        $coefs = array('id_firstcoef', 'id_secondcoef', 'id_thirdcoef');
        $PAGE->requires->js_call_amd('qtype_digitalliteracy/coefficientchange', 'process',
            array($coefs));
        $data = array('params' => $params, 'groups' => $groups, 'types' => $types);
        $PAGE->requires->js_call_amd('qtype_digitalliteracy/labelchange', 'process',
            array($data));
    }

    /** Add validation messages to the HTML document [using AMD JS]
     * and trigger them on corresponding events
     */
    private function validation_listeners() {
        $items = array();
        foreach($this->group_1['items'] as $item => $type) {
            $items[$item] = ['type' => $type, 'group' => $this->group_1['name']];
        }
        foreach($this->group_2['items'] as $item => $type) {
            $items[$item] = ['type' => $type, 'group' => $this->group_2['name']];
        }
        foreach($this->group_3['items'] as $item => $type) {
            $items[$item] = ['type' => $type, 'group' => $this->group_3['name']];
        }

        $errors = array('validatecoef' => get_string('validatecoef', 'qtype_digitalliteracy'),
            'notahunred' => get_string('notahunred', 'qtype_digitalliteracy'),
            'tickacheckbox' => get_string('tickacheckbox', 'qtype_digitalliteracy'));

        global $PAGE;
        $data = array('items' => $items, 'errors' => $errors);
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
        // coefs - coef [significance text area] => group association
        $coefs = array('firstcoef' => 'coef_value_group', 'secondcoef' => 'coef_format_group',
            'thirdcoef' => 'coef_enclosures_group');
        // groups - group => param (checkbox) association
        $groups = array('coef_value_group' => ['paramvalue', 'paramtype'], 'coef_format_group' =>
        ['parambold', 'paramfillcolor'], 'coef_enclosures_group' => ['paramcharts', 'paramimages']);

        $error_msg = array();
        $values = $this->validatecoefs($coefs, $fromform, $error_msg);
        $this->validateparams($coefs, $groups, $fromform, $values, $error_msg);

        foreach ($error_msg as $group => $value) {
            $errors[$group] = implode(' | ', $value);
        }

        $this->validatefiletypelis($fromform,$errors);

        if (empty($errors))
            $this->validatedata($fromform, $errors);

        return $errors;
    }

    /** Validates coefficients (int int range [0,100] and sum of them is 100) */
    private function validatecoefs($coefs, $fromform, &$error_msg) {
        $options = array(
            'options' => array(
                'default' => -1,
                'min_range' => 0,
                'max_range' => 100
            )
        );
        $values = array();
        foreach ($coefs as $value => $group) { // validates value
            $error_msg[$group] = array();
            if (($res = filter_var($fromform[$value], FILTER_VALIDATE_INT, $options)) < 0) {
                $error_msg[$group][] = get_string('validatecoef', 'qtype_digitalliteracy');
                $values[$value] = -1;
            } else
                $values[$value] = $res;
        }
        if (!in_array(-1, $values) && array_sum($values) != 100) { // validates sum
            foreach ($coefs as $value => $group) {
                $error_msg[$group][] = get_string('notahunred', 'qtype_digitalliteracy');
            }
        }
        return $values;
    }

    /** Validates params (Value, Calculated Value, Bold and so on) */
    private function validateparams($coefs, $groups, $fromform, $values, &$error_msg) {
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
                    $error_msg[$coefs[$key]][] = get_string('tickacheckbox', 'qtype_digitalliteracy');
                }
            }
        }
    }

    private function validatefiletypelis($fromform, &$errors) {
        if ($fromform['filetypeslist'] === '') {
            $errors['filetypeslist'] = get_string('emptyfiletypelist', 'qtype_digitalliteracy');
            return;
        }
        $qtype = question_bank::get_qtype('digitalliteracy');
        $accepted = call_user_func(array($qtype, $fromform['responseformat']. '_filetypes'));
        if (count($types = array_diff(explode(',', $fromform['filetypeslist']), $accepted)) > 0) {
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
            $question->templatefiles_draftid = $fromform['templatefiles_filemanager'];

        $result = $question->grade_response($response);
        list($fraction, $state) = $result;
        if ($fraction != 1.0) {
            $errors[$error_types['']] = count($result) > 2 ? $result[2]['_error']:
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