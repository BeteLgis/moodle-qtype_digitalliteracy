<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');

/**
 * File comparison wrapper (prepares data and executes validation/grading).
 * 1) Copies files to a request (temporary) directory for validation/grading;
 * 2) Runs validation/grading in a shell (using {@link exec()});
 * 3) Returns validation/grading result that is, afterwards, processed by moodle.
 * Important:
 * Validation means a run, where we make sure that current answer file with
 * current question setting (coefficients and params), returns fraction 1.
 * Before that, each file [same as each question setting field in {@link qtype_digitalliteracy_edit_form}]
 * is validated separately using {@link qtype_digitalliteracy_sandbox::validate_files()}.
 */
class qtype_digitalliteracy_sandbox {
    /** @var bool $isteacher the flag indicating user permissions (teacher or student) */
    private $isteacher = false;

    public function __construct($contextid) {
        $context = context::instance_by_id($contextid, MUST_EXIST);
        $this->isteacher = has_capability('moodle/question:editall', $context);
    }

    /**
     * 1) Create a request directory;
     * 2) Copy files (source, response, template) there;
     * 3) Run validation/grading in a shell;
     * 4) Read and return the result.
     * @param array $response as returned by {@link question_attempt_step::get_qt_data()}
     * @param stdClass $data contains all data needed for validation/grading,
     * see {@link qtype_digitalliteracy_sandbox::validate_files()} and
     * {@link qtype_digitalliteracy_question::grade_response()}
     * @return array an array containing an error message or {@link question_file_saver} and a fraction
     */
    public function grade_response(array $response, &$data) {
        $dir = make_request_directory(true);
        $data->validation ? $this->copy_files_validation($data, $response, $dir) :
            $this->copy_files_grading($data, $response, $dir);
        $data->requestdirectory = $dir;
        return $this->run_in_shell($data, true);
    }

    /**
     * Executes validation/grading in a shell using {@link exec()}.
     * Data is serialized, base64 encoded and passed as an argument.
     * Upon completion, either a data transmission error is issued or
     * result (serialized and base64 encoded) is read from the resulting file.
     * @param stdClass $data contains all data needed for validation/grading,
     * see {@link qtype_digitalliteracy_sandbox::validate_files()} and
     * {@link qtype_digitalliteracy_question::grade_response()}
     * @param bool $isgrading the flag to be passed to the shell
     * @return array array('error' => error message) or array('file_saver' => {@link question_file_saver},
     * 'fraction' => fraction [from 0 to 1 inclusively])
     * @throws qtype_digitalliteracy_exception|moodle_exception an unexpected error
     */
    private function run_in_shell($data, $isgrading) {
        global $CFG;

        $data->isgrading = $isgrading; // true means to grade, false means to validate
        $data->dirroot = $CFG->dirroot;
        $data->maxmemory = 20 * pow(2, 20); // 20 MB
        $path = $CFG->dirroot . '/question/type/digitalliteracy/classes/shell.php';
        if (!file_exists($path)) // shouldn't happen!
            throw new qtype_digitalliteracy_exception($this->isteacher, 'exception_noshell');

        exec("php $path ". base64_encode(serialize($data)), $output, $return_var);

        $shell_result = new qtype_digitalliteracy_shell_result($data->requestdirectory);
        list($success, $result) = $shell_result->read();
        if (!$success) {
            $output = array_filter($output, function ($value) { return !empty($value); });
            if (!empty($output) && ($this->isteacher | $msg = @unserialize($output[0]))) {
                return array('errors' => qtype_digitalliteracy_exception::conditional_throw
                    ($this->isteacher, empty($msg['code']) ? 'exception_shell' : $msg['code'],
                    null, implode(PHP_EOL, $output), true));
            }
            return array('errors' => qtype_digitalliteracy_exception::conditional_throw($this->isteacher,
                'exception_unknownshell', null, null, true));
        }
        return $this->process_result($result, $isgrading);
    }

    /**
     * Read and process result from the resulting file
     * @throws qtype_digitalliteracy_exception|moodle_exception an unexpected error
     */
    private function process_result($result, $isgrading) {
        if (!empty($result['exceptions'])) {
            $msg = $this->numerate_strings($result['exceptions'], 'exception_unexpected');
            return array('errors' => qtype_digitalliteracy_exception::conditional_throw
                ($this->isteacher, $msg, null, null, true));
        }
        return $this->validate_result($result, $isgrading);
    }

    private function numerate_strings($strings, $unexpected = 'exception_unexpected') {
        $result = array();
        $index = 0;
        $strmanager = get_string_manager();
        foreach ($strings as $string) {
            $code = $string['code'];
            $a = !empty($string['a']) ? $string['a'] : null;
            $message = strval($index + 1).'. '. ($strmanager->string_exists($code, 'qtype_digitalliteracy') ?
                    get_string($code, 'qtype_digitalliteracy', $a) :
                    get_string($unexpected, 'qtype_digitalliteracy', $code));
            array_push($result, $message);
            $index++;
        }
        return implode(PHP_EOL, $result);
    }

    /**
     * Validates the result (filters keys and validates their values).
     * @param array $result the array to be validated
     * @param bool $isgrading the flag to be passed to the shell
     * @throws qtype_digitalliteracy_exception|moodle_exception an unexpected error
     */
    private function validate_result($result, $isgrading) {
        if (!is_array($result))
            throw new qtype_digitalliteracy_exception($this->isteacher, 'exception_resultnotarray');

        // filter the keys
        $filteredresult = array();
        $key = 'errors';
        if (!empty($result[$key])) {
            $msg = $this->numerate_strings($result[$key], 'error_unexpected');
            $filteredresult[$key] = $msg;
        }

        // return array() or array('errors' => 'message')
        if (!$isgrading)
            return $filteredresult;

        $key = 'fraction';
        if (!empty($result[$key]) && is_numeric($result[$key])) {
            $value = floatval($result[$key]);
            if ($value >= 1)
                $filteredresult[$key] = 1;
            elseif ($value <= 0)
                $filteredresult[$key] = 0;
            else
                $filteredresult[$key] = $value;
        }

        if (empty($filteredresult))
            throw new qtype_digitalliteracy_exception($this->isteacher, 'exception_emptyresultshell');

        $key = 'files';
        if (!empty($result[$key]) && is_array($result[$key]))
            $filteredresult['file_saver'] = $this->generate_question_file_saver($result[$key]);

        return $filteredresult;
    }

    /**
     * Copy files for grading.
     * @param stdClass $data contains all data needed for grading,
     * see {@link qtype_digitalliteracy_question::grade_response()}
     * @param array $response as returned by {@link question_attempt_step::get_qt_data()}
     * @param string $dir the request directory
     */
    private function copy_files_grading(&$data, array $response, $dir) {
        $source_files = $this->copy_area_files('source', $data->contextid,
            'qtype_digitalliteracy', 'sourcefiles', $data->id, $dir);
        $data->sourcepath = array_shift($source_files);
        if ($data->hastemplatefile && $data->excludetemplate) {
            $template_files = $this->copy_area_files('template', $data->contextid,
                'qtype_digitalliteracy', 'templatefiles', $data->id, $dir);
            $data->templatepath = array_shift($template_files);
        }
        $files = $response['attachments']->get_files();
        $response_files = $this->copy_files('response', $files, $dir);
        $data->responsepath = array_shift($response_files);
        $data->mistakesname = array_shift($files)->get_filename();
    }

    /**
     * Copy files for validation.
     * @param stdClass $data contains all data needed for validation,
     * see {@link qtype_digitalliteracy_sandbox::validate_files()}
     * @param array $response as returned by {@link question_attempt_step::get_qt_data()}
     * @param string $dir the request directory
     */
    private function copy_files_validation(&$data, array $response, $dir) {
        $files = $response['attachments']->get_files();
        $source_files = $this->copy_files('source', $files, $dir);
        $data->sourcepath = array_shift($source_files);
        if (isset($data->templatefilesdraftid)) {
            $template_files = $this->copy_area_files('template', $data->contextid,
                'user', 'draft', $data->templatefilesdraftid, $dir);
            $data->templatepath = array_shift($template_files);
        }
        $response_files = $this->copy_files('response', $files, $dir);
        $data->responsepath = array_shift($response_files);
    }

    /**
     * Copy all area files to a given (the request) directory and return their new paths.
     * @param string $name filename prefix
     * @param int $contextid context ID
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID or all files if not specified
     * @param string $dir the request directory
     * @return array array of file paths indexed by file names ($name_1, $name_2 etc)
     */
    private function copy_area_files($name, $contextid, $component,
                                       $filearea, $itemid, $dir) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, $component, $filearea,
            $itemid, 'filename', false);
        return $this->copy_files($name, $files, $dir);
    }

    /**
     * Copy files to a given (the request) directory and return their new paths.
     * Sorts files by their names!
     * @param string $name filename prefix
     * @param stored_file[] $files array of {@link stored_file}
     * @param string $dir a directory to copy files to (the request directory)
     * @return array array of file paths indexed by file names ($name_1, $name_2 etc)
     * @throws qtype_digitalliteracy_exception
     */
    private function copy_files($name, $files, $dir) {
        if (empty($files))
            throw new qtype_digitalliteracy_exception('exception_nofilespassed');

        usort($files, function (stored_file $file1, stored_file $file2) {
            strcmp(core_text::strtolower(trim($file1->get_filename())),
                core_text::strtolower(trim($file2->get_filename())));
        });
        $result = array();
        $index = 0;
        foreach ($files as $file) {
            $index++;
            $filename = core_text::strtolower(trim($file->get_filename()));
            $ext = substr($filename, strrpos($filename, '.') + 1);
            $fullpath = $dir.'\\'.$name.'.'.$ext;
            if ($file->copy_content_to($fullpath))
                $result[$name. '_'. $index] = $fullpath;
            else
                throw new qtype_digitalliteracy_exception('exception_filecopy', $filename);
        }
        return $result;
    }

    /**
     * Generates {@link question_file_saver} from $files passed in the argument.
     * @param array $files array of file paths indexed by file names
     * @return question_file_saver|string an empty string is returned when $files array is empty
     * @throws qtype_digitalliteracy_exception|moodle_exception
     */
    private function generate_question_file_saver(array $files) {
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
        foreach ($files as $name => $path) {
            $filerecord->filename = $name;
            if (file_exists($path))
                $fs->create_file_from_pathname($filerecord, $path);
            else {
                $file = new stdClass();
                $file->name = $name;
                $file->path = $path;
                throw new qtype_digitalliteracy_exception('exception_filenotexist', $file);
            }
        }
        return new question_file_saver($draftitemid, 'question', 'response_mistakes');
    }

    /**
     * Validates a file: checks file type [extension], filename length [excluding extension]
     * and runs {@link qtype_digitalliteracy_base_tester::validate_file()} in a shell.
     * @param stored_file $file
     * @param stdClass $data contains all data needed for validation,
     * see {@link qtype_digitalliteracy_sandbox::validate_files()}
     * @param \core_form\filetypes_util $filetypesutil
     * @param array|string[] $whitelist
     * @throws qtype_digitalliteracy_exception|moodle_exception
     */
    private function validate_file($file, $data, $filetypesutil, $whitelist) {
        $filename = $file->get_filename();
        if (!$filetypesutil->is_allowed_file_type($filename, $whitelist))
            return get_string('error_disallowedfiletype', 'qtype_digitalliteracy', $filename);

        $fullpath = $data->requestdirectory.'/'. $filename;
        if (!$file->copy_content_to($fullpath))
            return qtype_digitalliteracy_exception::conditional_throw($this->isteacher,
                'exception_filecopy', $filename);

        if (strlen($filename) < strlen(pathinfo($fullpath, PATHINFO_EXTENSION)) + 4)
            return get_string('error_tooshortfilename', 'qtype_digitalliteracy', $filename);

        $data->fullpath = $fullpath;
        $data->filename = $filename;
        $result = $this->run_in_shell($data, false);
        return !empty($result['errors']) ? $result['errors'] : '';
    }

    /**
     * Validates several files using {@link qtype_digitalliteracy_sandbox::validate_file()}.
     * Verifies that the amount of files uploaded satisfy the required amount.
     * @param stored_file[] $files
     * @param string $responseformat
     * @param array $filetypeslist
     * @param int $attachmentsrequired
     * @return string an error message or an empty string
     * @throws qtype_digitalliteracy_exception|coding_exception|moodle_exception
     */
    public function validate_files($files, $responseformat, $filetypeslist, $attachmentsrequired) {
        $data = new stdClass();
        $data->isteacher = $this->isteacher;
        $data->responseformat = $responseformat;
        $data->requestdirectory = make_request_directory(true);

        $attachcount = count($files);
        if ($attachcount != $attachmentsrequired) {
            return get_string('error_insufficientattachments',
                'qtype_digitalliteracy', $attachmentsrequired);
        }

        if ($attachcount > 0) { // designed to process more than 1 file (just in case)
            $result = array();
            $filetypesutil = new \core_form\filetypes_util();
            $whitelist = $filetypesutil->normalize_file_types($filetypeslist);
            foreach ($files as $file) {
                $result[] = $this->validate_file($file, $data, $filetypesutil, $whitelist);
            }
            return implode(PHP_EOL, $result);
        }
        return '';
    }
}
