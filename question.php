<?php
require_once($CFG->dirroot . '/question/type/questionbase.php');


class qtype_digitalliteracy_question extends question_graded_automatically {

    public $responseformat;

//    public $attachments;
    /** @var int The number of attachments required for a response to be complete. */
    public $attachmentsrequired;

    /** @var array The string array of file types accepted upon file submission. */
    public $filetypeslist;

    public $firstcoef;
    public $secondcoef;
    public $thirdcoef;

    public $hastemplatefile;

//    public $attachmentoptions;

    public function get_expected_data()
    {
        return array('attachments' => question_attempt::PARAM_FILES);
    }

    public function get_correct_response()
    {
        return null; // TODO add correct file?
    }

    public function summarise_response(array $response)
    {
        if (array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files) {
            return get_string('answered', 'qtype_digitalliteracy');
        } else {
            return get_string('notanswered', 'qtype_digitalliteracy');
        }
    }

    public function classify_response(array $response) {
        if (!$this->is_gradable_response($response)) {
            return array($this->id => question_classified_response::no_response());
        }
        list($fraction) = $this->grade_response($response);
        return array($this->id => new question_classified_response(0,
            get_string('graded', 'qtype_digitalliteracy'), $fraction));
    }

    public function is_complete_response(array $response)
    {
        // Determine if the given response has online text and attachments.
        $hasattachments = array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files;

        // Determine the number of attachments present.
        if ($hasattachments) {
            // Check the filetypes.
            $filetypesutil = new \core_form\filetypes_util();
            $whitelist = $filetypesutil->normalize_file_types($this->filetypeslist);
            $wrongfiles = array();
            foreach ($response['attachments']->get_files() as $file) {
                if (!$filetypesutil->is_allowed_file_type($file->get_filename(), $whitelist)) {
                    $wrongfiles[] = $file->get_filename();
                }
            }
            if ($wrongfiles) { // At least one filetype is wrong.
                return false;
            }
            $attachcount = count($response['attachments']->get_files());
        } else {
            $attachcount = 0;
        }

        // The response is complete iff all of our requirements are met.
        return $attachcount >= $this->attachmentsrequired;
    }

    public function is_same_response(array $prevresponse, array $newresponse)
    {
        return ($this->attachmentsrequired == 0 ||
                question_utils::arrays_same_at_key_missing_is_blank(
                    $prevresponse, $newresponse, 'attachments'));
    }

    public function is_gradable_response(array $response) {
        if (array_key_exists('attachments', $response)
            && $response['attachments'] instanceof question_response_files
            && count($response['attachments']->get_files()) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function get_validation_error(array $response)
    {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('notgradableanswer', 'qtype_digitalliteracy');
    }

    public function grade_response(array $response)
    {
        // TODO validation
        $dir = make_request_directory();
        switch ($this->responseformat)
        {
            case 'excel':
                $comparator =  new qtype_digitalliteracy_excel_tester();
                break;
            case 'powerpoint':
                $comparator = new qtype_digitalliteracy_powerpoint_tester();
                break;
            default :
//                throw new dml_read_exception('qtype_digitalliteracy_option');
                return array(0, question_state::$needsgrading);
        }
        $fraction = ($comparator)->compareFiles($this->get_sorcefiles($dir)[0],
            $this->get_responsefiles($response, $dir)[0]);
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    private function get_sorcefiles($dir)
    {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->contextid, 'qtype_digitalliteracy',
            'sourcefiles', $this->id, 'filename', false);
        return $this->get_paths_from_files($files, $dir);
    }

    private function get_responsefiles(array $response, $dir)
    {
        $files = $response['attachments']->get_files();
        return $this->get_paths_from_files($files, $dir);
    }

    public function get_paths_from_files($files, $dir)
    {
        $result = array();
        foreach ($files as $file) {
            $hash = $file->get_contenthash();
            $filename = $file->get_filename();
            $ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
            $fullpath = $dir.'\\'.$hash.'.'.$ext;
            if ($file->copy_content_to($fullpath))
                $result[] = $fullpath;
        }
        return $result;
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'response_attachments') {
            // Response attachments visible if the question has them.
            return $this->attachmentsrequired != 0;

        } else {
            return parent::check_file_access($qa, $options, $component,
                $filearea, $args, $forcedownload);
        }
    }
}