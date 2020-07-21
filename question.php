<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/interactive/behaviour.php');
require_once($CFG->dirroot . '/question/engine/questionattemptstep.php');
require_once($CFG->dirroot . '/question/type/digitalliteracy/questiontype.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once($CFG->dirroot . '/question/behaviour/interactive_for_digitalliteracy/behaviour.php');

/** Class for a response processing
 * @extends  question_graded_automatically
 */
class qtype_digitalliteracy_question extends question_graded_automatically {

    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        return new qbehaviour_interactive_for_digitalliteracy($qa, $preferredbehaviour);
    }
    /** What data may be included in the form submission when a student submits this question in its current state?
     * This information is used in calls to optional_param.
     * The parameter name has question_attempt::get_field_prefix() automatically prepended.
     * @Overrides question_definition::get_expected_data
     */
    public function get_expected_data() {
        return array('attachments' => question_attempt::PARAM_FILES);
    }
    /** What data would need to be submitted to get this question correct.
     * If there is more than one correct answer, this method should just return one possibility.
     * If it is not possible to compute a correct response, this method should return null.
     * @Overrides question_definition::get_correct_response
     */
    public function get_correct_response() {
        return null;
    }

    /** Produce a plain text summary of a response.
     * @Implements question_manually_gradable::summarise_response
     * @param array $response - a response, as might be passed to grade_response().
     * @return string a plain text summary of that response, that could be used in reports
     */
    public function summarise_response(array $response)
    {
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

    /** Used by many of the behaviours, to work out whether the student's response to the question is complete.
     * That is, whether the question attempt should move to the COMPLETE or INCOMPLETE state.
     * @Implements question_manually_gradable::is_complete_response
     * @param array $response - a response, as might be passed to grade_response().
     */
    public function is_complete_response(array $response) {
        return $this->is_gradable_response($response);
    }
    /** Use by many of the behaviours to determine whether the student's response has changed.
     * This is normally used to determine that a new set of responses can safely be discarded.
     * @Implements question_manually_gradable::is_same_response
     * @param array $newresponse the new responses, in the same format.
     * @param array $prevresponse the responses previously recorded for this question,
     * as returned by question_attempt_step::get_qt_data()
     */
    public function is_same_response(array $prevresponse, array $newresponse)
    {
        return $this->attachmentsrequired == 0 ||
                question_utils::arrays_same_at_key_missing_is_blank(
                    $prevresponse, $newresponse, 'attachments');
    }
    /** Use by many of the behaviours to determine whether the student has provided enough of
     * an answer for the question to be graded automatically, or whether it must be considered aborted.
     * @Overrides question_with_responses::is_gradable_response
     * @Implements question_manually_gradable::is_gradable_response
     * @param array $response - responses, as returned by question_attempt_step::get_qt_data().
     */
    public function is_gradable_response(array $response) {
        return $this->validate_response($response) == '';
    }

    public function validate_response(array $response) {
        if (array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files) {
            $files = $response['attachments']->get_files();
        } else
            $files = array();

        $comparator = new qtype_digitalliteracy_comparator();
        return $comparator->validate_files($files, $this->responseformat,
            $this->filetypeslist, $this->attachmentsrequired);
    }
    /** In situations where is_gradable_response() returns false,
     * this method should generate a description of what the problem is.
     * @Implements question_automatically_gradable::get_validation_error.
     * @param array $response - current response
     */
    public function get_validation_error(array $response)
    {
        $error = $this->validate_response($response);
        if ($error) {
            return $error;
        } else {
            return get_string('unknownerror', 'qtype_digitalliteracy');
        }
    }

    public static function response_data() {
        return array('contextid', 'id', 'firstcoef', 'secondcoef','thirdcoef',
                     'responseformat', 'hastemplatefile', 'excludetemplate',
                     'paramvalue', 'paramtype', 'parambold',
                     'paramfillcolor', 'paramcharts', 'paramimages');
    }

    /** Grade a response to the question, returning a fraction between get_min_fraction()
     * and get_max_fraction(), and the corresponding question_state right, partial or wrong.
     * @Implements question_automatically_gradable::grade_response
     * @param array $response - responses, as returned by question_attempt_step::get_qt_data().
     */
    public function grade_response(array $response)
    {
        $data = new stdClass();
        $data->flag = false;
        foreach (self::response_data() as $value) {
            $data->$value = $this->$value;
        }
        if (array_key_exists('flag', $response)) {
            $data->flag = true;
            $data->templatefiles = $this->templatefiles;
        }
        $comparator = new qtype_digitalliteracy_comparator();
        $result = $comparator->grade_response($response, $data);
        if (!empty($result['error']))
            return array(0, question_state::$invalid, array('_error' => $result['error']));
        $fraction = $result['fraction'];
        if ($this->binarygrading) {
            $fraction = $fraction < 1 ? 0 : 1;
        }
        return $data->flag ? array($fraction, question_state::graded_state_for_fraction($fraction)) :
            array($fraction, question_state::graded_state_for_fraction($fraction),
            array('_mistakes' => $result['file_saver']));
    }

    /** Checks whether the users is allow to be served a particular file.
     * @Overrides question_definition::check_file_access
     * @param $qa question_attempt the question attempt being displayed.
     * @param $options question_display_options the options that control display of the question.
     * @param $component string name of the component we are serving files for.
     * @param $filearea string the name of the file area
     * @param array $args the remaining bits of the file path
     * @param $forcedownload bool whether the user must be forced to download the file.
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'response_attachments') {
            // Response attachments visible if the question has them.
            return $this->attachmentsrequired != 0;
        } else if ($component == 'question' && $filearea == 'response_mistakes') {
            return $this->attachmentsrequired != 0;
        } else {
            return parent::check_file_access($qa, $options, $component,
                $filearea, $args, $forcedownload);
        }
    }
}