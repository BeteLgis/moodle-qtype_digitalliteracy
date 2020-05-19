<?php
class qtype_digitalliteracy_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
                                             question_display_options $options) {

        $question = $qa->get_question();

        $files = '';
        if ($question->attachmentsrequired) {
            if (empty($options->readonly)) {
                $files = $this->files_input($qa, $question->attachmentsrequired, $options);

            } else {
                $files = $this->files_read_only($qa, $options);
            }
        }

        $templatefiles = '';
        if ($question->hastemplatefile) {
            $templatefiles = $this->get_template_files($question);
        }

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
            array('class' => 'qtext'));
        $result .= html_writer::tag('div', $templatefiles, array('class' => 'templatefiles'));
        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $files, array('class' => 'attachments'));
        $result .= html_writer::end_tag('div');

        return $result;
    }

    private function get_template_files($question) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($question->contextid, 'qtype_digitalliteracy',
            'templatefiles', $question->id, 'filename', false);
        $output = array();
        foreach ($files as $file) {
            $output[] = html_writer::tag('p', html_writer::link($this->get_url($file, $question),
                $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file),
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename())));
        }
        return implode($output);
    }

    private function get_url($file, $question) {
        return moodle_url::make_pluginfile_url($question->contextid, 'qtype_digitalliteracy',
            'templatefiles', $question->id, $file->get_filepath(), $file->get_filename(), true);
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $output = array();

        foreach ($files as $file) {
            $output[] = html_writer::tag('p', html_writer::link($qa->get_response_file_url($file),
                $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file),
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename())));
        }
        return implode($output);
    }

    /**
     * Displays the input control for when the student should upload a single file.
     * @param question_attempt $qa the question attempt to display.
     * @param int $numallowed the maximum number of attachments allowed. -1 = unlimited.
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
        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid(
            'attachments', $options->context->id);
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
}