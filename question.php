<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/interactive/behaviour.php');
require_once($CFG->dirroot . '/question/behaviour/interactive_for_digitalliteracy/behaviour.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');

/** Class for a response processing
 * @extends {@link question_graded_automatically}
 */
class qtype_digitalliteracy_question extends question_graded_automatically {

    const component = 'qtype_digitalliteracy';

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        return new qbehaviour_interactive_for_digitalliteracy($qa, $preferredbehaviour);
    }

    public function get_expected_data() {
        return array('attachments' => question_attempt::PARAM_FILES);
    }

    public function get_correct_response() {
        return null;
    }

    /**
     * Produce a plain text summary of a response.
     * @param array $response as returned by {@link question_attempt_step::get_qt_data()}
     * @return string containing all uploaded file's names (or no files uploaded message)
     */
    public function summarise_response(array $response) {
        if (array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files) {
            $files = $response['attachments']->get_files();
            if (count($files) > 0) {
                $file_list = array();
                foreach ($files as $file)
                    $file_list[] = $file->get_filename();
                $result = implode(', ', $file_list);
                return get_string('answered', self::component, $result);
            }
        }
        return get_string('notanswered', self::component);
    }

    public function is_complete_response(array $response) {
        return $this->is_gradable_response($response);
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        $prevattachments = $this->get_attached_files($prevresponse);
        $newattachments = $this->get_attached_files($newresponse);
        return $prevattachments == $newattachments; // == cause https://www.php.net/manual/en/language.operators.array.php
                                                    // as we don't care about order
    }

    /**
     * Used to determine same responses.
     * @param array $response as returned by {@link question_attempt_step::get_qt_data()}
     * @return array filename => content
     */
    private function get_attached_files(array $response) {
        $attachments = array();
        if (array_key_exists('attachments', $response) && $response['attachments']) {
            $files = $response['attachments']->get_files();
            foreach ($files as $file) {
                $attachments[$file->get_filename()] = $file->get_content();
            }
        }
        return $attachments;
    }

    public function is_gradable_response(array $response) {
        return $this->validate_response($response) == '';
    }

    /**
     * Validates response files: checks filetypes, filename length and reads every file within a shell
     * (looks for infinite loops, memory exhaustion problems etc).
     * See {@link qtype_digitalliteracy_sandbox::validate_file()}.
     * @param array $response as returned by {@link question_attempt_step::get_qt_data()}
     * @return string a string describing error or an empty string if no errors were caught
     */
    public function validate_response(array $response) {
        if (array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files) {
            $files = $response['attachments']->get_files();
        } else
            return get_string('notanswered', self::component);

        return (new qtype_digitalliteracy_sandbox($this->contextid))
            ->validate_files($files, $this->responseformat,
            $this->filetypeslist, $this->attachmentsrequired);
    }

    public function get_validation_error(array $response) {
        $error = $this->validate_response($response);
        if ($error) {
            return $error;
        } else {
            // error is stored in qt_data and will be displayed in the specific feedback
            return get_string('unknownerror', self::component);
        }
    }

    /** The data passed to {@link qtype_digitalliteracy_base_tester::compare_files()} as a parameter. */
    public static function response_data($responseformat) {
        $settings = new qtype_digitalliteracy_settings($responseformat);
        return array_merge(array('contextid', 'id','responseformat', 'showtemplatefile',
            'excludetemplate'), $settings->get_coefs(), $settings->get_params());
    }

    /**
     * Grade a response to the question.
     * @param array $response as returned by {@link question_attempt_step::get_qt_data()}
     * @return array a fraction between {@link get_min_fraction()}
     * and {@link get_max_fraction()}, the corresponding {@link question_state} (right, partial or wrong)
     * and, optionally, _error string or _mistakes {@link question_file_saver} containing created mistakes files.
     */
    public function grade_response(array $response) {
        $data = new stdClass();
        foreach (self::response_data($this->responseformat) as $value) {
            $data->$value = $this->$value;
        }
        if (isset($this->templatefilesdraftid))
            $data->templatefilesdraftid = $this->templatefilesdraftid;
        $data->validation = isset($this->validation); // flag to identify a validation run
        $data->fontparams = $this->fontparams;

        $result = (new qtype_digitalliteracy_sandbox($this->contextid))
            ->grade_response($response, $data);
        if (!empty($result['errors']))
            return array(0, question_state::$invalid, array('_error' => $result['errors']));

        $fraction = $result['fraction'] ?? 0;
        if ($this->binarygrading) {
            $fraction = $fraction < 1 ? 0 : 1;
        }

        return $data->validation || empty($result['file_saver']) ?
            array($fraction, question_state::graded_state_for_fraction($fraction)) :
            array($fraction, question_state::graded_state_for_fraction($fraction),
            array('_mistakes' => $result['file_saver']));
    }

    /**
     * Checks whether the users is allowed to be served a particular file.
     * @Overrides {@link question_definition::check_file_access}
     * @param $qa question_attempt the question attempt being displayed.
     * @param $options question_display_options the options that control display of the question.
     * @param $component string name of the component we are serving files for.
     * @param $filearea string the name of the file area
     * @param array $args the remaining bits of the file path
     * @param $forcedownload bool whether the user must be forced to download the file.
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && ($filearea == 'response_attachments' ||
                $filearea == 'response_mistakes')) {
            return true;
        } else {
            return parent::check_file_access($qa, $options, $component,
                $filearea, $args, $forcedownload);
        }
    }
}