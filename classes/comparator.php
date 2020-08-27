<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');
/** File comparison wrapper (prepares data for comparison) */
class qtype_digitalliteracy_comparator {
    /** Create request directory, copy files (source, response and, optionally, template),
     * choose comparator (excel or powerpoint) and, finally compare files [i.e grade response]
     * @return array containing error or {@link question_file_saver} and fraction
     */
    public function grade_response(array $response, &$data) {
        try {
            $request_directory = make_request_directory();
            $data->validation ? $this->copy_files_validation($data, $response, $request_directory) :
                $this->copy_files($data, $response, $request_directory);
            $data->request_directory = $request_directory;
            $comparator = self::switch($data->responseformat);
            $result = $comparator->compare_files($data);
        } catch (Throwable $th) {
            return array('error' => $th->getMessage());
        } catch (Exception $ex) {
            return array('error' => $ex->getMessage());
        }
        return $result;
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

    /** Switches comparator type */
    public static function switch($responseformat) {
        switch ($responseformat)
        {
            case 'excel':
                $comparator = new qtype_digitalliteracy_excel_tester();
                break;
            case 'powerpoint':
                $comparator = new qtype_digitalliteracy_powerpoint_tester();
                break;
        }
        if (!isset($comparator))
            throw new dml_read_exception('Unexpected error');
        return $comparator;
    }

    /**
     * Validates file
     * @param $filetypesutil \core_form\filetypes_util
     * @param $comparator qtype_digitalliteracy_tester_base
     * @param $file stored_file
     */
    public function validate_file($file, $filetypesutil, $comparator, $whitelist, $dir) {
        $filename = $file->get_filename();
        if (!$filetypesutil->is_allowed_file_type($filename, $whitelist))
            return get_string('error_incorrectextension', 'qtype_digitalliteracy', $filename);

        $fullpath = $dir.'\\'. $filename;
        if (!$file->copy_content_to($fullpath))
            return get_string('error_filecopy', 'qtype_digitalliteracy', $filename);

        if (strlen($filename) < strlen(pathinfo($fullpath, PATHINFO_EXTENSION)) + 4)
            return get_string('error_tooshortfilename', 'qtype_digitalliteracy', $filename);

        return $comparator->validate_file($fullpath, $filename);
    }

    /** Validate several files, it counts their amount */
    public function validate_files($files, $responseformat, $filetypeslist, $attachmentsrequired) {
        try {
            $comparator = self::switch($responseformat);
            $dir = make_request_directory();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }

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
                $result[] = $this->validate_file($file, $filetypesutil,
                    $comparator, $whitelist, $dir);
            }
            if (count($result) > 0) {
                return implode(' | ', $result);
            }
        }

        return '';
    }
}