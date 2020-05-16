<?php

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

        $mform->addElement('select', 'attachments',
            get_string('allowattachments', 'qtype_digitalliteracy'), $qtype->attachment_options());
        $mform->setDefault('attachments', 1);

        $mform->addElement('select', 'attachmentsrequired',
            get_string('attachmentsrequired', 'qtype_digitalliteracy'), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', 1);
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_digitalliteracy');
        //$mform->disabledIf('attachmentsrequired', 'attachments', 'eq', 0);

        $mform->addElement('filetypes', 'filetypeslist', get_string('acceptedfiletypes', 'qtype_digitalliteracy'));
        //$mform->setDefault('filetypeslist', array('.xlsx'));
        $mform->addHelpButton('filetypeslist', 'acceptedfiletypes', 'qtype_digitalliteracy');
        //$mform->disabledIf('filetypeslist', 'attachments', 'eq', 0);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->responseformat = $question->options->responseformat;
        $question->attachments = $question->options->attachments;
        $question->attachmentsrequired = $question->options->attachmentsrequired;
        $question->filetypeslist = $question->options->filetypeslist;
        //$question->attachmentoptions = $question->options->attachmentoptions;

        // prepare files
        $filecontext = context::instance_by_id($question->contextid, IGNORE_MISSING);
        $question = file_prepare_standard_filemanager($question, 'sourcefiles',
            array('subdirs' => false), $filecontext, 'qtype_digitalliteracy', 'sourcefiles', $question->id);
        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        // Don't allow the teacher to require more attachments than they allow; as this would
        // create a condition that it's impossible for the student to meet.
        if ($fromform['attachments'] != -1 && $fromform['attachments'] < $fromform['attachmentsrequired'] ) {
            $errors['attachmentsrequired']  = get_string('mustrequirefewer', 'qtype_digitalliteracy');
        }

        return $errors;
    }

    public function qtype() {
        return 'digitalliteracy';
    }
}