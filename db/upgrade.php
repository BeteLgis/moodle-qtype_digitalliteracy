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

    if (true) {
        // Rename field paramvalue on table qtype_digitalliteracy_option to NEWNAMEGOESHERE.
        $table = new xmldb_table('qtype_digitalliteracy_option');

        $field_1 = new xmldb_field('grouponeparamthree', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'grouponeparamtwo');
        $field_2 = new xmldb_field('grouponeparamfour', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'grouponeparamthree');
        $field_3 = new xmldb_field('grouptwoparamthree', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'grouptwoparamtwo');
        $field_4 = new xmldb_field('grouptwoparamfour', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'grouptwoparamthree');
        $field_5 = new xmldb_field('groupthreeparamthree', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'groupthreeparamtwo');
        $field_6 = new xmldb_field('groupthreeparamfour', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'groupthreeparamthree');

        $dbman->add_field($table, $field_1);
        $dbman->add_field($table, $field_2);
        $dbman->add_field($table, $field_3);
        $dbman->add_field($table, $field_4);
        $dbman->add_field($table, $field_5);
        $dbman->add_field($table, $field_6);

        // Digitalliteracy savepoint reached.
        upgrade_plugin_savepoint(true, 2020041466, 'qtype', 'digitalliteracy');
    }


    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
