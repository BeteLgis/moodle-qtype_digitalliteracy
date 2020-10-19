<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');

/**
 * File comparison wrapper (prepares data and executes validation/grading).
 * 1) Copies files to a request (temporary) directory for validation/grading;
 * 2) Runs validation/grading in a shell (using {@link exec()}) or in a child (using {@link pcntl_fork()};
 * 3) Returns validation/grading result that is, afterwards, processed by moodle.
 * Important:
 * Validation means a run, where we make sure that current answer file with
 * current question setting (coefficients and params), returns fraction 1.
 * Before that, each file [same as each question setting field in {@link qtype_digitalliteracy_edit_form}]
 * is validated separately using {@link qtype_digitalliteracy_sandbox::validate_files()}.
 */
class qtype_digitalliteracy_sandbox {
    private $fork = false; // true means use fork [only UNIX], false means use shell [UNIX and Windows]

    public function __construct() {
        $this->fork = false; // function_exists('pcntl_fork');
    }

    /**
     * 1) Create a request directory;
     * 2) Copy files (source, response, template) there;
     * 3) Run validation/grading in a shell or in a child;
     * 4) Read and return the result.
     * @param array $response as returned by {@link question_attempt_step::get_qt_data()}
     * @param stdClass $data contains all data needed for validation/grading,
     * see {@link qtype_digitalliteracy_sandbox::validate_files()} and
     * {@link qtype_digitalliteracy_question::grade_response()}
     * @return array an array containing an error message or {@link question_file_saver} and a fraction
     */
    public function grade_response(array $response, &$data) {
        try {
            $dir = make_request_directory(true);
            $data->validation ? $this->copy_files_validation($data, $response, $dir) :
                $this->copy_files_grading($data, $response, $dir);
            $data->requestdirectory = $dir;
            $data->fork = $this->fork;
            $result = $this->run_in_shell($data, true);
        } catch (Exception $ex) {
            return array('error' => $ex->getMessage());
        }
        return $result;
    }

    /** TODO It's possible to execute validation/grading using {@link pcntl_fork()} (only for UNIX) */
    private function run_in_child($data, $isgrading) {
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
     * @throws Exception an unexpected error
     */
    private function run_in_shell($data, $isgrading) {
        global $CFG;

        $data->isgrading = $isgrading; // true means grading, false means validate
        $data->dirroot = $CFG->dirroot;
        $path = $CFG->dirroot . '/question/type/digitalliteracy/classes/shell.php';
        if (!file_exists($path)) // shouldn't happen!
            throw new qtype_digitalliteracy_exception(get_string('error_notesterbase', 'qtype_digitalliteracy'));

        $data->errors = $this->shell_errors();
        $data->maxmemory = 20 * pow(2, 20); // 20 MB
        exec("php $path ". base64_encode(serialize($data)), $output, $return_var);

        // data transmission error or unexpected error
        if ($return_var === 1) {
            if (!empty($output))
                return array('error' => $output[0]); // implode ?
            throw new qtype_digitalliteracy_exception(get_string('error_unknownshell', 'qtype_digitalliteracy'));
        }

        // reading result from the resulting file
        $result = (new qtype_digitalliteracy_shell($data->requestdirectory))->read_result();
        if (!empty($result['files'])) {
            $result['file_saver'] = self::generate_question_file_saver($result['files']);
            unset($result['files']);
        }
        return $result;
    }

    /**
     * Prepare error messages for data transmission.
     * Needed as {@link get_string()} can't be called from within shell.
     * @return array array of key => message pairs
     */
    private function shell_errors() {
        $errors = array();
        $strings = array_merge(qtype_digitalliteracy_base_tester::get_strings(),
            qtype_digitalliteracy_excel_tester::get_strings(),
            qtype_digitalliteracy_powerpoint_tester::get_strings());

        foreach ($strings as $key => $string) {
            $errors[$key] = preg_replace('/{@\S+}/', '%s', $string);
        }
        return $errors;
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
    public function copy_area_files($name, $contextid, $component,
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
     * @throws Exception
     */
    public function copy_files($name, $files, $dir) {
        if (empty($files))
            throw new qtype_digitalliteracy_exception(get_string('error_nofilespassed', 'qtype_digitalliteracy'));

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
                throw new qtype_digitalliteracy_exception(get_string('error_filecopy',
                    'qtype_digitalliteracy', $filename));
        }
        return $result;
    }

    /**
     * Generates {@link question_file_saver} from $files passed in the argument.
     * @param array $files array of file paths indexed by file names
     * @return question_file_saver|string an empty string is returned when $files array is empty
     * @throws Exception
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
        foreach ($files as $name => $path) {
            $filerecord->filename = $name;
            if (file_exists($path))
                $fs->create_file_from_pathname($filerecord, $path);
            else {
                $file = new stdClass();
                $file->name = $name;
                $file->path = $path;
                throw new qtype_digitalliteracy_exception(get_string('error_filenotexist',
                    'qtype_digitalliteracy', $file));
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
     */
    public function validate_file($file, $data, $filetypesutil, $whitelist) {
        $filename = $file->get_filename();
        if (!$filetypesutil->is_allowed_file_type($filename, $whitelist))
            return get_string('error_disallowedfiletype', 'qtype_digitalliteracy', $filename);

        $fullpath = $data->requestdirectory.'/'. $filename;
        if (!$file->copy_content_to($fullpath))
            return get_string('error_unexpected', 'qtype_digitalliteracy',
                get_string('error_filecopy', 'qtype_digitalliteracy', $filename));

        if (strlen($filename) < strlen(pathinfo($fullpath, PATHINFO_EXTENSION)) + 4)
            return get_string('error_tooshortfilename', 'qtype_digitalliteracy', $filename);

        $data->fullpath = $fullpath;
        $data->filename = $filename;
        $result = $this->run_in_shell($data, false);
        return !empty($result['error']) ? $result['error'] : '';
    }

    /**
     * Validates several files using {@link qtype_digitalliteracy_sandbox::validate_file()}.
     * Verifies that the amount of files uploaded satisfy the required amount.
     * @param stored_file[] $files
     * @param string $responseformat
     * @param array $filetypeslist
     * @param int $attachmentsrequired
     * @return string an error message or an empty string
     */
    public function validate_files($files, $responseformat, $filetypeslist, $attachmentsrequired) {
        try {
            $data = new stdClass();
            $data->requestdirectory = make_request_directory(true);
            $data->responseformat = $responseformat;
            $data->fork = $this->fork;

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
                return implode(' | ', $result);
            }
        } catch (Exception $ex) {
            return $ex->getMessage();
        }

        return '';
    }
}
