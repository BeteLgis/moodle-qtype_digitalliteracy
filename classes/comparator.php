<?php

defined('MOODLE_INTERNAL') || die();

/** File comparison wrapper (prepares data for comparison) */
class qtype_digitalliteracy_comparator {
    public static $fork = false; // use fork [only UNIX] or shell [UNIX and Windows]

    public function __construct() {
        self::$fork = false; // function_exists('pcntl_fork');
    }

    /** Create request directory, copy files (source, response and, optionally, template),
     * choose comparator (excel or powerpoint) and, finally compare files [i.e grade response]
     * @return array containing error or {@link question_file_saver} and fraction
     */
    public function grade_response(array $response, &$data) {
        try {
            $dir = make_request_directory(true);
            $data->validation ? $this->copy_files_validation($data, $response, $dir) :
                $this->copy_files($data, $response, $dir);
            $data->request_directory = $dir;
            $data->fork = self::$fork;
            $result = $this->run_in_shell($data, true);
        } catch (Exception $ex) {
            return array('error' => $ex->getMessage());
        }
        return $result;
    }

    private function run_in_child($data, $grade_response) { // TODO ONLY UNIX!
    }

    private function run_in_shell($data, $grade_response) {
        if ($grade_response)
            $data->grade_response = true; // grade_response flag (validate otherwise)

        global $CFG;
        $data->dirroot = $CFG->dirroot;
        $path = $CFG->dirroot . '/question/type/digitalliteracy/classes/tester_base.php';
        if (!file_exists($path)) // shouldn't happen!
            throw new coding_exception('File tester_base.php not found!');

        $data->errors = $this->shell_errors();

        $output = shell_exec("php $path ". base64_encode(serialize($data)));
        if (is_null($output) || $res = @unserialize($output)) {
            if (!empty($res['error']))
                return $res;
            throw new coding_exception('Unknown error has occurred in the shell.');
        }

        require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/tester_base.php');
        $result = (new Shell($data->request_directory))->read_result();
        if (!empty($result['files'])) {
            $result['file_saver'] = self::generate_question_file_saver($result['files']);
            unset($result['files']);
        }
        return $result;
    }

    private function shell_errors() {
        global $CFG;
        require_once($CFG->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');
        require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/tester_base.php');
        require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/excel_tester.php');
        require_once($CFG->dirroot . '/question/type/digitalliteracy/classes/powerpoint_tester.php');
        $errors = array();
        $strings = array_merge(qtype_digitalliteracy_tester_base::get_strings(),
            qtype_digitalliteracy_excel_tester::get_strings(),
            qtype_digitalliteracy_powerpoint_tester::get_strings());

        foreach ($strings as $key => $string) {
            $errors[$key] = preg_replace('/{@\S+}/', '%s', $string);
        }
        return $errors;
    }

    /** File copy when not validation mode */
    private function copy_files(&$data, array $response, $dir) {
        $data->source_path = $this->get_filearea_files('source', $data->contextid,
            'qtype_digitalliteracy', 'sourcefiles', $data->id, $dir)[0];
        if ($data->hastemplatefile && $data->excludetemplate) {
            $data->template_path = $this->get_filearea_files('template', $data->contextid,
                'qtype_digitalliteracy', 'templatefiles', $data->id, $dir)[0];
        }
        $files = $response['attachments']->get_files();
        $data->response_path = $this->get_paths_from_files('response',
            $files, $dir)[0];
        $data->mistakes_name = array_values($files)[0]->get_filename();
    }

    /** File copy when in validation mode */
    private function copy_files_validation(&$data, array $response, $dir) {
        $files = $response['attachments']->get_files();
        $data->source_path = $this->get_paths_from_files('source', $files, $dir)[0];
        if (isset($data->templatefiles_draftid)) {
            $data->template_path = $this->get_filearea_files('template', $data->contextid,
                'user', 'draft', $data->templatefiles_draftid, $dir)[0];
        }
        $data->response_path = $this->get_paths_from_files('response', $files, $dir)[0];
    }

    public function get_filearea_files($name, $contextid, $component,
                                       $filearea, $itemid, $dir) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, $component, $filearea,
            $itemid, 'filename', false);
        return $this->get_paths_from_files($name, $files, $dir);
    }

    /** Processes files (copies them to a temp directory for the comparison)
     * @param $name string new filename
     * @param $files array of {@link stored_file}
     * @param $dir string one request (temporary) directory path
     * @return array filepaths
     */
    public function get_paths_from_files($name, $files, $dir)
    {
        $result = array();
        foreach ($files as $file) {
            $filename = trim(core_text::strtolower($file->get_filename()));
            $ext = substr($filename, strrpos($filename, '.') + 1);
            $fullpath = $dir.'\\'.$name.'.'.$ext;
            if ($file->copy_content_to($fullpath))
                $result[] = $fullpath;
            else
                throw new Exception(get_string('error_filecopy', 'qtype_digitalliteracy', $filename));
        }
        return $result;
    }

    /** Saves mistakes files or any other files, created after grading is completed
     * and send into this method
     * @param array $files file $name => file $path [in request directory] pairs
     */
    public static function generate_question_file_saver(array $files) {
        if (empty($files))
            return '';

        global $USER;
        $draftitemid = 0;
        file_prepare_draft_area($draftitemid, null, null, null, null);
        $fs = get_file_storage();
        $filerecord = new stdClass();
        $filerecord->contextid = context_user::instance($USER->id)->id;
        $filerecord->component = 'user';
        $filerecord->filearea = 'draft';
        $filerecord->itemid = $draftitemid;
        $filerecord->filepath = '/';
        foreach ($files as $name => $path)
        {
            $filerecord->filename = $name;
            if (file_exists($path))
                $fs->create_file_from_pathname($filerecord, $path);
            else {
                $file = new stdClass();
                $file->name = $name;
                $file->path = $path;
                throw new Exception(get_string('error_filenotexist',
                    'qtype_digitalliteracy', $file));
            }
        }
        return new question_file_saver($draftitemid, 'question', 'response_mistakes');
    }

    /**
     * Validates file
     * @param $filetypesutil \core_form\filetypes_util
     * @param $file stored_file
     */
    public function validate_file($file, $data, $filetypesutil, $whitelist) {
        $filename = $file->get_filename();
        if (!$filetypesutil->is_allowed_file_type($filename, $whitelist))
            return get_string('error_incorrectextension', 'qtype_digitalliteracy', $filename);

        $fullpath = $data->request_directory.'/'. $filename;
        if (!$file->copy_content_to($fullpath))
            return get_string('error_filecopy', 'qtype_digitalliteracy', $filename);

        if (strlen($filename) < strlen(pathinfo($fullpath, PATHINFO_EXTENSION)) + 4)
            return get_string('error_tooshortfilename', 'qtype_digitalliteracy', $filename);

        $data->fullpath = $fullpath;
        $data->filename = $filename;
        $result = $this->run_in_shell($data, false);
        return !empty($result['error']) ? $result['error'] : '';
    }

    /** Validate several files, it counts their amount */
    public function validate_files($files, $responseformat, $filetypeslist, $attachmentsrequired) {
        try {
            $data = new stdClass();
            $data->request_directory = make_request_directory(true);
            $data->responseformat = $responseformat;

            $attachcount = count($files);

            if ($attachcount != $attachmentsrequired) {
                return get_string('insufficientattachments',
                    'qtype_digitalliteracy', $attachmentsrequired);
            }

            if ($attachcount > 0) { // designed to process more than 1 file (just in case)
                $result = array();
                $filetypesutil = new \core_form\filetypes_util();
                $whitelist = $filetypesutil->normalize_file_types($filetypeslist);
                foreach ($files as $file) {
                    $result[] = $this->validate_file($file, $data, $filetypesutil, $whitelist);
                }
                return implode(' | ', $result);
            }
        } catch (Exception $ex) {
            return $ex->getMessage();
        }

        return '';
    }
}
