<?php

defined('MOODLE_INTERNAL') || die();

/** Question attempts renderer (displayed to the student) */
class qtype_digitalliteracy_renderer extends qtype_renderer {

    const component = 'qtype_digitalliteracy';
    /**
     * Generate the display of the formulation part of the question.
     * This is the area that contains the question text, files
     * and the controls for students to input their answers.
     * @Overrides {@link qtype_renderer::formulation_and_controls}
     * @param question_attempt $qa the question attempt to display
     * @param question_display_options $options controls what should and should not be displayed
     */
    public function formulation_and_controls(question_attempt $qa,
                                             question_display_options $options) {
        $question = $qa->get_question();

        $files = '';
        if ($question->attachmentsrequired) {
            if (empty($options->readonly)) {
                $files = $this->files_input($qa, $question->attachmentsrequired, $options);
            } else {
                $files = $this->files_read_only($qa, $options, 'attachments');
            }
        }

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
            array('class' => 'qtext'));
        $result .= html_writer::start_tag('div', array('class' => 'ablock'));

        $context = context::instance_by_id($question->contextid, MUST_EXIST);
        $access = has_capability('moodle/question:editall', $context);

        if (!empty($options->readonly) && $access) {
            $result .= html_writer::tag('h4', html_writer::tag('b',
                get_string('sourcefiles_heading', self::component)));
            $result .= html_writer::tag('div', $this->get_files_from_filearea($qa, $question->contextid,
                self::component,'sourcefiles', $question->id), array('class' => 'sourcefiles'));
        }

        if ($question->showtemplatefile) {
            $result .= html_writer::tag('h4', html_writer::tag('b',
                get_string('templatefiles_heading', self::component)));
            $result .= html_writer::tag('div', $this->get_files_from_filearea($qa, $question->contextid,
                self::component,'templatefiles', $question->id), array('class' => 'templatefiles'));
        }

        $result .= html_writer::tag('h4', html_writer::tag('b',
            get_string('answerfiles_heading', self::component)));

        $result .= !empty($options->readonly) && $files == '' ?
            html_writer::tag('div', get_string('notanswered', self::component)) :
            html_writer::tag('div', $files, array('class' => 'attachments'));

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div', $question->get_validation_error($qa->get_last_qt_data()),
                array('class' => 'validationerror'));
        }

        $mistakefiles = $this->get_files_from_filearea($qa, $options->context->id,
            'question','response_mistakes', $qa->get_last_step()->get_id());

        if (!empty($options->readonly) && ($question->showmistakes || $access)) {
            $result .= html_writer::tag('h4', html_writer::tag('b',
                get_string('mistakefiles_heading', self::component)));
            $result .= html_writer::tag('div', $mistakefiles !== '' ? $mistakefiles :
                get_string('nomistakes', self::component), array('class' => 'mistakefiles'));
        }

        $result .= html_writer::end_tag('div');

        return $result;
    }

    /**
     * Displays filearea files.
     * @param question_attempt $qa the question attempt to display
     * @param int $contextid context ID
     * @param string $component component
     * @param mixed $filearea file area (areas)
     * @param int $itemid item ID or all files if not specified
     */
    private function get_files_from_filearea(question_attempt $qa, $contextid, $component, $filearea, $itemid) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, $component,
            $filearea, $itemid, 'filename', false);
        return $this->file_linker($qa, $files, $component === 'question');
    }

    /**
     * @param question_attempt $qa the question attempt to display
     * @param stored_file[] $files
     * @param bool $response does the file belong to a response variable of the $qa
     * @return string return a link to download each file from a the $files array
     */
    private function file_linker(question_attempt $qa, $files, $response) {
        if (empty($files))
            return get_string('nofiles', self::component);
        $output = array();
        foreach ($files as $file) {
            $output[] = html_writer::tag('p', html_writer::link(
                $response ? $qa->get_response_file_url($file) : $this->get_url($file) ,
                $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file),
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename())));
        }
        return implode($output);
    }

    /**
     * Creates a file URL.
     * @param stored_file $file a file
     * @return moodle_url url to download the file
     */
    private function get_url($file) {
        return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
            $file->get_filearea(), $file->get_itemid(), $file->get_filepath(),
            $file->get_filename(), true);
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context
     */
    public function files_read_only(question_attempt $qa, question_display_options $options, $name) {
        $files = $qa->get_last_qt_files($name, $options->context->id);
        return $this->file_linker($qa, $files, true);
    }

    /**
     * Displays the input control for when the student should upload a single file.
     * @param question_attempt $qa the question attempt to display
     * @param int $numallowed the maximum number of attachments allowed
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context
     */
    public function files_input(question_attempt $qa, $numallowed,
                                question_display_options $options) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        $pickeroptions = new stdClass();
        $pickeroptions->mainfile = null;
        $pickeroptions->maxfiles = $numallowed;
        $pickeroptions->context = $options->context;
        $pickeroptions->return_types = FILE_INTERNAL | FILE_CONTROLLED_LINK;

        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid(
            'attachments', $options->context->id);
        $pickeroptions->accepted_types = $qa->get_question()->filetypeslist;

        $fm = new form_filemanager($pickeroptions);
        $filesrenderer = $this->page->get_renderer('core', 'files');

        $text = '';
        if (!empty($qa->get_question()->filetypeslist)) {
            $text = html_writer::tag('p', get_string('acceptedfiletypes', self::component));
            $filetypesutil = new \core_form\filetypes_util();
            $filetypes = $qa->get_question()->filetypeslist;
            $filetypedescriptions = $filetypesutil->describe_file_types($filetypes);
            $text .= $this->render_from_template('core_form/filetypes-descriptions', $filetypedescriptions);
        }
        return $filesrenderer->render($fm). html_writer::empty_tag(
                'input', array('type' => 'hidden', 'name' => $qa->get_qt_field_name('attachments'),
                'value' => $pickeroptions->itemid)) . $text;
    }

    /**
     * Displays feedback when an error occurred in {@link qtype_digitalliteracy_question::grade_response()}.
     * @param question_attempt $qa the question attempt to display
     * @param question_display_options $options controls what should and should not be displayed
     */
    public function feedback(question_attempt $qa, question_display_options $options) {
        $optionsclone = clone($options);

        if ($qa->get_last_step()->has_qt_var('_error'))
            $optionsclone->feedback = question_display_options::VISIBLE;

        return parent::feedback($qa, $optionsclone);
    }

    /**
     * Shows error message to a student.
     * See {@link qtype_digitalliteracy_question::get_validation_error()}.
     * @param question_attempt $qa the question attempt to display
     */
    public function specific_feedback(question_attempt $qa)
    {
        $error = $qa->get_last_step()->get_qt_var('_error');
        if (!isset($error))
            return '';

        $result = '';
        $result .= html_writer::start_tag('div', array('class' => 'response_error'));
        $result .= html_writer::tag('div', $error, array('class' => 'error_text'));
        $result .= html_writer::end_tag('div');
        return $result;
    }
}