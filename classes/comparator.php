<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/digitalliteracy/vendor/autoload.php');
/** Common interface for file comparison */
class qtype_digitalliteracy_comparator
{
//    public function validate_filearea($question, $filearea, $dir); // file validation (check whether it is correctly loaded)
//                                // number of pages (slides) not 0!!!!
    /** @returns array */
    public final function grade_response(array $response, array $data) {
        $request_directory = $this->create_directory();
        if (!$request_directory)
            return array('error' => $request_directory);
//        try {
            $data = $this->copy_files($data, $response, $request_directory);
            $data['request_directory'] = $request_directory;
            $result = $this->process_comparison($data);
//        } catch (Exception $ex) {
//            return array('error' => $ex->getMessage());
//        }
        return $result;
    }

    private function create_directory() {
        try {
            $request_directory = make_request_directory();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return $request_directory;
    }

    private function copy_files(array $data, array $response, $dir) {
        $data['source_path'] = $this->get_filearea_files('source', $data['contextid'],
            $data['itemid'], 'sourcefiles', $dir)[0];
//        $data['template_path'] = $this->get_filearea_files('template', $data['contextid'],
//            $data['itemid'], 'templatefiles', $dir)[0];
        $files = $response['attachments']->get_files();
        $data['response_path'] = $this->get_paths_from_files('response',
            $files, $dir)[0];
        $data['mistakes_name'] = array_values($files)[0]->get_filename();
        return $data;
    }

    public function get_filearea_files($name, $contextid, $itemid, $filearea, $dir)
    {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'qtype_digitalliteracy',
            $filearea, $itemid, 'filename', false);
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
        }
        return $result;
    }

    public function process_comparison(array $data) {
        switch ($data['responseformat'])
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
        return $comparator->compare_files($data);
    }

    public static function generate_question_file_saver($name, $path) {
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
        $filerecord->filename = $name;
        $fs->create_file_from_pathname($filerecord, $path);
//        var_dump($fs->get_area_files($filerecord->contextid,$filerecord->component,
//            $filerecord->filearea, $filerecord->itemid));
        return new question_file_saver($draftitemid, 'question', 'response_mistakes');
    }
}

interface qtype_digitalliteracy_compare_interface {
    public function compare_files(array $data);
}