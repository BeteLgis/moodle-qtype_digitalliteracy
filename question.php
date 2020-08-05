<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/interactive/behaviour.php');
require_once($CFG->dirroot . '/question/engine/questionattemptstep.php');
require_once($CFG->dirroot . '/question/type/digitalliteracy/questiontype.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/behaviour/interactive_for_digitalliteracy/behaviour.php');

/** Class for a response processing
 * @extends {@link question_graded_automatically}
 */
class qtype_digitalliteracy_question extends question_graded_automatically {

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        return new qbehaviour_interactive_for_digitalliteracy($qa, $preferredbehaviour);
    }

    public function get_expected_data() {
        return array('attachments' => question_attempt::PARAM_FILES);
    }

    public function get_correct_response() {
        return null;
    }

    /** Produce a plain text summary of a response.
     * @param array $response - a response, as might be passed to grade_response().
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
                return get_string('answered', 'qtype_digitalliteracy', $result);
            }
        }
        return get_string('notanswered', 'qtype_digitalliteracy');
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
     * Used to determine same responses
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

    /** Validates response files: checks filetypes and reads file with reader
     * (looks for loops, memory exhaustion problems [size of the file]
     * and other possible errors that may occur during grading itself)
     * @return string describing error, empty string if no errors were caught
     */
    public function validate_response(array $response) {
        if (array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files) {
            $files = $response['attachments']->get_files();
        } else
            return get_string('notanswered', 'qtype_digitalliteracy');

        $comparator = new qtype_digitalliteracy_comparator();
        return $comparator->validate_files($files, $this->responseformat,
            $this->filetypeslist, $this->attachmentsrequired);
    }

    public function get_validation_error(array $response) {
        $error = $this->validate_response($response);
        if ($error) {
            return $error;
        } else {
            return get_string('unknownerror', 'qtype_digitalliteracy');
        }
    }

    /** The data used in {@link qtype_digitalliteracy_compare_base::compare_files()} as a parameter */
    public static function response_data() {
        return array('contextid', 'id', 'firstcoef', 'secondcoef','thirdcoef',
                     'responseformat', 'hastemplatefile', 'excludetemplate',
                     'paramvalue', 'paramtype', 'parambold',
                     'paramfillcolor', 'paramcharts', 'paramimages');
    }

    /** Grade a response to the question, returning a fraction between get_min_fraction()
     * and get_max_fraction(), the corresponding question_state (right, partial or wrong)
     * and _mistakes {@link question_file_saver} containing created analysis files
     * @Implements {@link question_automatically_gradable::grade_response}
     * @param array $response - responses, as returned by {@link question_attempt_step::get_qt_data()}.
     */
    public function grade_response(array $response) {
        $data = new stdClass();
        $data->validation = false; // data->validation used to deteriorate
        // validation run from the real one (used in comparator.php)
        foreach (self::response_data() as $value) {
            $data->$value = $this->$value;
        }
        if (array_key_exists('validation', $response)) {
            $data->validation = true;
            if (isset($this->templatefiles_draftid))
                $data->templatefiles_draftid = $this->templatefiles_draftid;
        }
        $comparator = new qtype_digitalliteracy_comparator();
        $result = $comparator->grade_response($response, $data);
        if (!empty($result['error']))
            return array(0, question_state::$invalid, array('_error' => $result['error']));
        $fraction = $result['fraction'];
        if ($this->binarygrading) {
            $fraction = $fraction < 1 ? 0 : 1;
        }
        return $data->validation ? array($fraction, question_state::graded_state_for_fraction($fraction)) :
            array($fraction, question_state::graded_state_for_fraction($fraction),
            array('_mistakes' => $result['file_saver']));
    }

    /**
     * Checks whether the users is allow to be served a particular file.
     * @Overrides question_definition::check_file_access
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