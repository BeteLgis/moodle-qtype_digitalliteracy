<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the essay question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_digitalliteracy_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Automatically generated Moodle v3.2.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.3.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.4.0 release upgrade line.
    // Put any upgrade step following this.

    if (false) {

        // Rename field showmistakes on table qtype_digitalliteracy_option to NEWNAMEGOESHERE.
        $table = new xmldb_table('qtype_digitalliteracy_option');

        $field = new xmldb_field('randomization', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'binarygrading');

        $field_2 = new xmldb_field('checkbutton', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'showmistakes');

        // Launch rename field showmistakes.
        $dbman->rename_field($table, $field, 'showmistakes');

        // Conditionally launch add field checkbutton.
        if (!$dbman->field_exists($table, $field_2)) {
            $dbman->add_field($table, $field_2);
        }

        // Digitalliteracy savepoint reached.
        upgrade_plugin_savepoint(true, 2020041454, 'qtype', 'digitalliteracy');
    }


    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
