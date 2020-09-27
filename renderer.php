<?php

defined('MOODLE_INTERNAL') || die();

/** Question attempts renderer (displayed to the student) */
class qtype_digitalliteracy_renderer extends qtype_renderer {

    /** Generate the display of the formulation part of the question.
     * This is the area that contains the question text, files
     * and the controls for students to input their answers.
     * @Overrides qtype_renderer::formulation_and_controls
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed. */
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

        $student_or_no_role = $this->only_a_student_or_empty();

        if (!empty($options->readonly) && !$student_or_no_role) {
            $result .= html_writer::tag('h4', html_writer::tag('b',
                get_string('sourcefiles', 'qtype_digitalliteracy')));
            $result .= html_writer::tag('div', $this->get_files_from_filearea($qa, $question->contextid,
                'qtype_digitalliteracy','sourcefiles', $question->id), array('class' => 'sourcefiles'));
        }

        if ($question->hastemplatefile) {
            $result .= html_writer::tag('h4', html_writer::tag('b',
                get_string('templatefiles', 'qtype_digitalliteracy')));
            $result .= html_writer::tag('div', $this->get_files_from_filearea($qa, $question->contextid,
                'qtype_digitalliteracy','templatefiles', $question->id), array('class' => 'templatefiles'));
        }

        $result .= html_writer::tag('h4', html_writer::tag('b',
            get_string('answerfiles', 'qtype_digitalliteracy')));

        $result .= !empty($options->readonly) && $files == '' ?
            html_writer::tag('div', get_string('notanswered', 'qtype_digitalliteracy')) :
            html_writer::tag('div', $files, array('class' => 'attachments'));

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div', $question->get_validation_error($qa->get_last_qt_data()),
                array('class' => 'validationerror'));
        }

        $mistakefiles = $this->get_files_from_filearea($qa, $options->context->id,
            'question','response_mistakes', $qa->get_last_step()->get_id());

        if (!empty($options->readonly) && ($question->showmistakes || !$student_or_no_role)) {
            $result .= html_writer::tag('h4', html_writer::tag('b',
                get_string('mistakefiles', 'qtype_digitalliteracy')));
            $result .= html_writer::tag('div', $mistakefiles !== '' ? $mistakefiles :
                get_string('nomistakes', 'qtype_digitalliteracy'), array('class' => 'mistakefiles'));
        }

        $result .= html_writer::end_tag('div');

        return $result;
    }

    /** @return bool check a user's roles in the course, used to determine when to show source files */
    private function only_a_student_or_empty() {
        global $COURSE, $USER;
        $rolesarr = array();
        $context = context_course::instance($COURSE->id);
        $roles = get_user_roles($context, $USER->id, true);
        foreach ($roles as $role) {
            $rolesarr[] = role_get_name($role, $context);
        }
        $amount = count($rolesarr);
        return $amount === 0 || ($amount === 1 && in_array("Student", $rolesarr));
    }

    /** Displays filearea files */
    private function get_files_from_filearea(question_attempt $qa, $contextid, $component, $filearea, $itemid) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, $component,
            $filearea, $itemid, 'filename', false);
        return $this->file_linker($qa, $files, $component === 'question');
    }

    /** @return string creates and returns links to download each file from filearea */
    private function file_linker(question_attempt $qa, array $files, bool $response) {
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
     * Creates URLs for template files for students to download them
     * @param $file stored_file file itself (already stored in moodle filestorage)
     * @return moodle_url url to download the file
     */
    private function get_url($file) {
        return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
            $file->get_filearea(), $file->get_itemid(), $file->get_filepath(),
            $file->get_filename(), true);
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options, $name) {
        $files = $qa->get_last_qt_files($name, $options->context->id);
        return $this->file_linker($qa, $files, true);
    }

    /**
     * Displays the input control for when the student should upload a single file.
     * @param question_attempt $qa the question attempt to display.
     * @param int $numallowed the maximum number of attachments allowed.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
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
            $text = html_writer::tag('p', get_string('acceptedfiletypes', 'qtype_digitalliteracy'));
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
     * Displays feedback when error occurred in {@link qtype_digitalliteracy_question::grade_response()}
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     */
    public function feedback(question_attempt $qa, question_display_options $options) {
        $optionsclone = clone($options);

        if ($qa->get_last_step()->has_qt_var('_error'))
            $optionsclone->feedback = question_display_options::VISIBLE;

        return parent::feedback($qa, $optionsclone);
    }

    /**
     * Shows error message to a student
     * @param question_attempt $qa the question attempt to display.
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