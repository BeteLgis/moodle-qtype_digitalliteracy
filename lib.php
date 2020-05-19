<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Checks file access for digitalliteracy questions.
 *
 * @package  qtype_digitalliteracy
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function qtype_digitalliteracy_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

//    if ($context->contextlevel != CONTEXT_MODULE) {
//        return false;
//    }
//
    require_login($course, false, $cm); // TODO
//
//    if (!$quiz = $DB->get_record('quiz', array('id'=>$cm->instance))) {
//        return false;
//    }
//
//    // The 'intro' area is served by pluginfile.php.
//    $fileareas = array('feedback');
//    if (!in_array($filearea, $fileareas)) {
//        return false;
//    }
//
//    $feedbackid = (int)array_shift($args);
//    if (!$feedback = $DB->get_record('quiz_feedback', array('id'=>$feedbackid))) {
//        return false;
//    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/qtype_digitalliteracy/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true, $options);
}
