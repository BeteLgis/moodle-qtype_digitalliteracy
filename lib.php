<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Checks file access for digitalliteracy questions.
 * @package  qtype_digitalliteracy
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function qtype_digitalliteracy_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    if ($context->contextlevel < CONTEXT_COURSE) {
        return false;
    }

    // Make sure the filearea is one of those used by the plugin.
    if (!in_array($filearea, array('templatefiles', 'sourcefiles'))) {
        return false;
    }

    require_login($course, false, $cm);
    if ($filearea === 'sourcefiles') {
        require_capability('moodle/question:editall', $context, null,
            true, 'downloadanswer', 'qtype_digitalliteracy');
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/qtype_digitalliteracy/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
