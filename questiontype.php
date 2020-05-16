<?php

require_once($CFG->libdir . '/questionlib.php');

class qtype_digitalliteracy extends question_type {
    public function response_file_areas() {
        return array('attachments');
    }

    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('qtype_digitalliteracy_option',
            array('questionid' => $question->id), '*', MUST_EXIST);

        parent::get_question_options($question);
    }

    public function save_question_options($formdata) {
        global $DB;
        $context = $formdata->context;

        $options = $DB->get_record('qtype_digitalliteracy_option', array('questionid' => $formdata->id));
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $formdata->id;
            $options->id = $DB->insert_record('qtype_digitalliteracy_option', $options);
        }

        $options->responseformat = $formdata->responseformat;
        $options->attachments = $formdata->attachments;
        $options->attachmentsrequired = $formdata->attachmentsrequired;
        if (!isset($formdata->filetypeslist)) {
            $options->filetypeslist = "";
        } else {
            $options->filetypeslist = $formdata->filetypeslist;
        }
        $DB->update_record('qtype_digitalliteracy_option', $options);
    }

    public function save_question($question, $form)
    {
        $question = parent::save_question($question, $form);
        // save files
        $filecontext = context::instance_by_id($question->contextid, IGNORE_MISSING);
        if ($form) {
            $question = file_postupdate_standard_filemanager($form, 'sourcefiles', array('subdirs' => false),
                $filecontext, 'qtype_digitalliteracy', 'sourcefiles', $question->id);
        }
        return $question;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->responseformat = $questiondata->options->responseformat;
        $question->attachments = $questiondata->options->attachments;
        $question->attachmentsrequired = $questiondata->options->attachmentsrequired;
        $filetypesutil = new \core_form\filetypes_util();
        $question->filetypeslist = $filetypesutil->normalize_file_types($questiondata->options->filetypeslist);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;

        $DB->delete_records('qtype_digitalliteracy_option', array('questionid' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    /**
     * @return array the different response formats that the question type supports.
     * internal name => human-readable name.
     */
    public function response_formats() {
        return array(
            'excel' => get_string('excel', 'qtype_digitalliteracy'),
            'powerpoint' => get_string('powerpoint', 'qtype_digitalliteracy')
        );
    }

    /**
     * @return array the choices that should be offered for the number of attachments.
     */
    public function attachment_options() {
        return array(
            1 => '1',
            2 => '2',
            3 => '3'
        );
    }

    /**
     * @return array the choices that should be offered for the number of required attachments.
     */
    public function attachments_required_options() {
        return array(
            1 => '1',
            2 => '2',
            3 => '3'
        );
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid,
            $newcontextid, 'qtype_digitalliteracy', 'sourcefiles', $questionid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, 'qtype_digitalliteracy', 'sourcefiles', $questionid);
    }
}
