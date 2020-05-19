<?php
require_once($CFG->dirroot.'/question/type/digitalliteracy/slider.php');
MoodleQuickForm::registerElementType('slider', "$CFG->dirroot/question/type/digitalliteracy/slider.php",
    'MoodleQuickForm_slider');

class qtype_digitalliteracy_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('digitalliteracy');

        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_digitalliteracy'));
        $mform->setExpanded('responseoptions');

        $mform->addElement('filemanager', 'sourcefiles_filemanager', get_string('sourcefiles', 'qtype_digitalliteracy'), null,
            array('subdirs' => false));

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

        $mform->addElement('text', 'firstcoef', get_string('firstcoef', 'qtype_digitalliteracy'));
        $mform->addElement('text', 'secondcoef', get_string('secondcoef', 'qtype_digitalliteracy'));
        $mform->addElement('text', 'thirdcoef', get_string('thirdcoef', 'qtype_digitalliteracy'));
        $mform->setType('firstcoef', PARAM_TEXT);
        $mform->setType('secondcoef', PARAM_TEXT);
        $mform->setType('thirdcoef', PARAM_TEXT);

        $mform->addElement('header', 'responsetemplateheader', get_string('responsetemplateheader', 'qtype_digitalliteracy'));

        $mform->addElement('advcheckbox', 'hastemplatefile', get_string('hastemplatefile', 'qtype_digitalliteracy'),
            null, null, array(0, 1));

        $mform->addElement('filemanager', 'templatefiles_filemanager', get_string('responsefiletemplate', 'qtype_digitalliteracy'), null,
            array('subdirs' => false));
        $mform->disabledIF('templatefiles_filemanager', 'hastemplatefile');
    }

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

    private function coefval($fromform, $coef) {
        if (!is_numeric($fromform[$coef]) || ($value = $fromform[$coef]) < 0 || $value > 100)
            return -1;
        return $value;
    }

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

        return $errors;
    }

    public function qtype() {
        return 'digitalliteracy';
    }
}