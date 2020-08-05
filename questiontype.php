<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/** Class for processing question options (settings) */
class qtype_digitalliteracy extends question_type {
    public function can_analyse_responses() {
        return false;
    }

    /** If the question type uses files in responses, then this method should return
     * an array of all the response variables that might have corresponding files.
     * @Overrides question_type::response_file_areas */
    public function response_file_areas() {
        return array('attachments', '_mistakes');
    }

    public function extra_question_fields()
    {
        return array('qtype_digitalliteracy_option',
            'responseformat', 'attachmentsrequired',
            'hastemplatefile', 'firstcoef', 'secondcoef',
            'thirdcoef', 'binarygrading', 'showmistakes',
            'checkbutton', 'excludetemplate', 'paramvalue',
            'paramtype', 'parambold', 'paramfillcolor',
            'paramcharts', 'paramimages');
    }

    /**
     * Abstract function implemented by each question type. It runs all the code
     * required to set up and save a question of any type for testing purposes.
     * Alternate DB table prefix may be used to facilitate data deletion.
     */
    public function generate_test($name, $courseid=null) {
        // Closer inspection shows that this method isn't actually implemented
        // by even the standard question types and wouldn't be called for any
        // non-standard ones even if implemented. I'm leaving the stub in, in
        // case it's ever needed, but have set it to throw an exception, and
        // I've removed the actual test code.
        throw new coding_exception('Unexpected call to generate_test. Read code for details.');
    }

    /** Loads the question type specific options for the question.
     * This function loads any question type specific options for the question
     * from the database into the question object. This information is placed in the
     * $question->options field. A question type is free, however, to decide on a internal structure of the options field.
     * @Overrides question_type::get_question_options
     * @param $question object The question object for the question.
     * This object should be updated to include the question type specific information (it is passed by reference).
     */
    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('qtype_digitalliteracy_option',
            array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }
    /** Saves question-type specific options
     * This is called by save_question() to save the question-type specific data
     * @Overrides question_type::save_question_options
     * @param $formdata object This holds the information from the editing form, it is not a standard question object. */
    public function save_question_options($formdata) {
        global $DB, $USER;
        parent::save_question_options($formdata);

        $options = $DB->get_record('qtype_digitalliteracy_option', array('questionid' => $formdata->id));

        if (!isset($formdata->filetypeslist)) {
            $options->filetypeslist = "";
        } else {
            $options->filetypeslist = $formdata->filetypeslist;
        }

        $DB->update_record('qtype_digitalliteracy_option', $options);

        if ($USER->id) {
            // The id check is a hack to deal with phpunit initialisation, when no user exists.
            foreach (array('sourcefiles', 'templatefiles') as $filearea) {
                if (!empty($formdata->{$filearea. '_filemanager'})) {
                    file_save_draft_area_files($formdata->{$filearea. '_filemanager'}, $formdata->context->id,
                        'qtype_digitalliteracy', $filearea, (int) $formdata->id, array('subdirs' => false));
                }
            }
        }
    }
    /** Saves (creates or updates) a question.
     * @Overrides question_type::save_question
     * @param $question object the question object which should be updated. For a new question will be mostly empty.
     * @param $form object the object containing the information to save, as if from the question editing form. */
    public function save_question($question, $form) {
        $form->isnew = empty($question->id);
        $question = parent::save_question($question, $form);
        return $question;
    }
    /** Initialise the common question_definition fields.
     * @Overrides question_type::initialise_question_instance
     * @param question_definition $question the question_definition we are creating.
     * @param $questiondata object the question data loaded from the database. */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $filetypesutil = new \core_form\filetypes_util();
        $question->filetypeslist = $filetypesutil->normalize_file_types($questiondata->options->filetypeslist);
    }
    /** Deletes the question-type specific data when a question is deleted.
     * @Overrides question_type::delete_question
     * @param $questionid int the question being deleted.
     * @param $contextid int the context this question belongs to.*/
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
     * @return array the choices that should be offered for the number of required attachments.
     */
    public function attachments_required_options() {
        return array(
            1 => '1',
//            2 => '2',
//            3 => '3'
        );
    }
    /**
     * @return array filetypes allowed.
     */
    public function attachments_filetypes_option() {
        return array(
            'onlytypes' => ['ods', 'xlsx', 'xls', 'csv', 'pptx', 'odp']
        );
    }

    /**
     * @return array the options for maximum file size
     */
    public function attachment_filesize_max() {
        return array(
            102400 => '100 kB',
            1048576 => '1 MB',
            10485760 => '10 MB',
            104857600 => '100 MB'
        );
    }

    /** Move all the files belonging to this question from one context to another.
     * @Overrides question_type::move_files
     * @param $questionid int the question being moved.
     * @param $oldcontextid int the context it is moving from.
     * @param $newcontextid int the context it is moving to.*/
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid,
            $newcontextid, 'qtype_digitalliteracy', 'sourcefiles', $questionid);
        $fs->move_area_files_to_new_context($oldcontextid,
            $newcontextid, 'qtype_digitalliteracy', 'templatefiles', $questionid);
    }
    /** Delete all the files belonging to this question.
     * @Overrides question_type::delete_files
     * @param $questionid int the question being deleted.
     * @param $contextid int the context the question is in. */
    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, 'qtype_digitalliteracy', 'sourcefiles', $questionid);
        $fs->delete_area_files($contextid, 'qtype_digitalliteracy', 'templatefiles', $questionid);
    }
}
