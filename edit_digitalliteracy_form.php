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

        $options = $this->fileoptions;
        $options['subdirs'] = false;

        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_digitalliteracy'));
        $mform->setExpanded('responseoptions');

        $mform->addElement('filemanager', 'sourcefiles_filemanager', get_string('sourcefiles', 'qtype_digitalliteracy'), null,
            $options);

        $mform->addElement('select', 'responseformat',
            get_string('responseformat', 'qtype_digitalliteracy'), $qtype->response_formats());
        $mform->setDefault('responseformat', 'excel');

        $mform->addElement('select', 'attachmentsrequired',
            get_string('attachmentsrequired', 'qtype_digitalliteracy'), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', 1);
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_digitalliteracy');

        $mform->addElement('filetypes', 'filetypeslist', get_string('acceptedfiletypes',
            'qtype_digitalliteracy'), $qtype->attachments_filetypes_option());
//        $mform->setDefault('filetypeslist', 'xlsx');

        $mform->addHelpButton('filetypeslist', 'acceptedfiletypes', 'qtype_digitalliteracy');

//        $mform->addElement('slider', 'firstslider', '[Feature is in development]',
//            array('min' => 0, 'max' => 10, 'value' => 5));

        $mform->addElement('advcheckbox', 'hastemplatefile', get_string('hastemplatefile', 'qtype_digitalliteracy'),
            null, null, array(0, 1));

        $mform->addElement('filemanager', 'templatefiles_filemanager', get_string('responsefiletemplate', 'qtype_digitalliteracy'), null,
           $options);
        $mform->disabledIF('templatefiles_filemanager', 'hastemplatefile');

        $mform->addElement('header', 'responsetemplateheader', get_string('responsetemplateheader', 'qtype_digitalliteracy'));

        $mform->addElement('text', 'firstcoef', get_string('firstcoef', 'qtype_digitalliteracy'));
        $mform->addElement('text', 'secondcoef', get_string('secondcoef', 'qtype_digitalliteracy'));
        $mform->addElement('text', 'thirdcoef', get_string('thirdcoef', 'qtype_digitalliteracy'));
        $mform->setType('firstcoef', PARAM_TEXT);
        $mform->setType('secondcoef', PARAM_TEXT);
        $mform->setType('thirdcoef', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'binarygrading', get_string('binarygrading', 'qtype_digitalliteracy'),
            null, null, array(0, 1));
        $mform->addElement('advcheckbox', 'showmistakes', get_string('showmistakes', 'qtype_digitalliteracy'),
            null, null, array(0, 1));
        $mform->addElement('advcheckbox', 'checkbutton', get_string('checkbutton', 'qtype_digitalliteracy'),
            null, null, array(0, 1));
    }

    /** Perform an preprocessing needed on the data passed to set_data() before it is used to initialise the form.
     * @Overrides question_edit_form::data_preprocessing
     * @param $question object the data being passed to the form. */
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->responseformat = $question->options->responseformat;
        $question->attachmentsrequired = $question->options->attachmentsrequired;
        $question->filetypeslist = $question->options->filetypeslist;
        $question->hastemplatefile = $question->options->hastemplatefile;

        $question->firstcoef = $question->options->firstcoef;
        $question->secondcoef = $question->options->secondcoef;
        $question->thirdcoef = $question->options->thirdcoef;

        $question->binarygrading = $question->options->binarygrading;
        $question->showmistakes = $question->options->showmistakes;
        $question->checkbutton = $question->options->checkbutton;
//        $this->fileoptions TODO
        // prepare files
        $filecontext = context::instance_by_id($question->contextid, MUST_EXIST);
        $question = file_prepare_standard_filemanager($question, 'sourcefiles',
            array('subdirs' => false), $filecontext, 'qtype_digitalliteracy',
            'sourcefiles', $question->id);
        $question = file_prepare_standard_filemanager($question, 'templatefiles',
            array('subdirs' => false), $filecontext, 'qtype_digitalliteracy',
            'templatefiles', $question->id);
        return $question;
    }
    /** Validation assistant method */
    private function coefval($fromform, $coef) {
        if (!is_numeric($fromform[$coef]) || ($value = $fromform[$coef]) < 0 || $value > 100)
            return -1;
        return $value;
    }
    /** Dummy stub method - override if you needed to perform some extra validation.
     * If there are errors return array of errors (“fieldname”=>“error message”), otherwise true if ok.
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     * @Overrides question_edit_form::validation
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param array $fromform array of ("fieldname"=>value) of submitted data*/
    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);
        if (($value1 = $this->coefval($fromform, 'firstcoef')) == -1)
            $errors['firstcoef'] = get_string('validatecoef', 'qtype_digitalliteracy');
        if (($value2 = $this->coefval($fromform, 'secondcoef')) == -1)
            $errors['secondcoef'] = get_string('validatecoef', 'qtype_digitalliteracy');
        if (($value3 = $this->coefval($fromform, 'thirdcoef')) == -1)
            $errors['thirdcoef'] = get_string('validatecoef', 'qtype_digitalliteracy');

        if ($value1 != -1 && $value2 != -1 && $value3 != -1 && ($value1 + $value2 + $value3 != 100)) {
            $errors['firstcoef'] = get_string('notahunred', 'qtype_digitalliteracy');
            $errors['secondcoef'] = get_string('notahunred', 'qtype_digitalliteracy');
            $errors['thirdcoef'] = get_string('notahunred', 'qtype_digitalliteracy');
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