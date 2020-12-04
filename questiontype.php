<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/** Class representing question type */
class qtype_digitalliteracy extends question_type {

    const component = 'qtype_digitalliteracy';

    public function can_analyse_responses() {
        return false;
    }

    public function response_file_areas() {
        return array('attachments', '_mistakes'); // _mistakes as it is a qt_var
    }

    public function extra_question_fields() {
        return array_merge(array('qtype_digitalliteracy_option',
            'responseformat', 'attachmentsrequired',
            'showtemplatefile', 'excludetemplate'),
            (new qtype_digitalliteracy_settings())->get_all_options());
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

    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('qtype_digitalliteracy_option',
            array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    public function save_question_options($formdata) {
        global $DB, $USER;
        parent::save_question_options($formdata);

        $options = $DB->get_record('qtype_digitalliteracy_option', array('questionid' => $formdata->id));

        if (!isset($formdata->filetypeslist)) {
            $options->filetypeslist = "";
        } else {
            $options->filetypeslist = $formdata->filetypeslist;
        }

        $options->fontparams = $this->get_bitmask_int($formdata->fontparams, 6);

        $DB->update_record('qtype_digitalliteracy_option', $options);

        if ($USER->id) {
            // The id check is a hack to deal with phpunit initialisation, when no user exists.
            foreach (array('sourcefiles', 'templatefiles') as $filearea) {
                if (!empty($formdata->{$filearea. '_filemanager'})) {
                    file_save_draft_area_files($formdata->{$filearea. '_filemanager'}, $formdata->context->id,
                        self::component, $filearea, (int) $formdata->id, array('subdirs' => false));
                }
            }
        }
    }

    /**
     * @param array|int $val
     * @param int $size
     * @return string representation
     */
    public function get_bitmask_string($val, $size) {
        return strrev(sprintf( "%0". $size. "d", decbin(is_array($val) ?
            $this->get_bitmask_int($val, $size) : $val))) ;
    }

    /**
     * @param array $array
     * @param int $size
     * @return int representation
     */
    public function get_bitmask_int($array, $size) {
        if (empty($array))
            return (1 << $size) - 1;
        $bitmask = 0;
        foreach ($array as $elm) {
            $bitmask |= (1 << $elm);
        }
        return $bitmask;
    }

    /**
     * @param int $int_val bitmask representation
     * @param int $size
     * @return array of indexes
     */
    public function unmask_bitmask($int_val, $size) {
        $res = array();
        foreach (str_split($this->get_bitmask_string($int_val, $size)) as $index => $char) {
            if ($char) {
                $res[] = $index;
            }
        }
        return $res;
    }

    public function save_question($question, $form) {
        $form->isnew = empty($question->id);
        $question = parent::save_question($question, $form);
        return $question;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $filetypesutil = new \core_form\filetypes_util();
        $question->filetypeslist = $filetypesutil->normalize_file_types($questiondata->options->filetypeslist);
        $question->fontparams = $this->get_bitmask_string($questiondata->options->fontparams, 6);
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
            'excel' => get_string('excel', self::component),
            //'powerpoint' => get_string('powerpoint', self::component),
            'word' => get_string('word', self::component)
        );
    }

    /**
     * @return array the choices that should be offered for the number of required attachments.
     */
    public function attachments_required_options() {
        return array(
            1 => '1'
//            2 => '2',
//            3 => '3'
        );
    }

    /**
     * @return array filetypes allowed.
     */
    public function attachments_filetypes_option() {
        return array(
            'onlytypes' => array_merge($this->excel_filetypes(), $this->powerpoint_filetypes(),
                $this->word_filetypes())
        );
    }

    public function fontparams() {
        return array('fontname', 'fontsize', 'fontbold', 'fontitalic', 'fontunderline', 'fontcolor');
    }

    public function excel_filetypes() {
        return array('.xlsx', '.xls', '.ods');
    }

    public function powerpoint_filetypes() {
        return array('.pptx', '.ppt', '.odp');
    }

    public function word_filetypes() {
        return array('.docx');
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

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid,
            $newcontextid, self::component, 'sourcefiles', $questionid);
        $fs->move_area_files_to_new_context($oldcontextid,
            $newcontextid, self::component, 'templatefiles', $questionid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, self::component, 'sourcefiles', $questionid);
        $fs->delete_area_files($contextid, self::component, 'templatefiles', $questionid);
    }
}
