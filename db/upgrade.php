<?php

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

    if ($oldversion < 2020041430) {

        // Define table qtype_digitalliteracy_option to be created.
        $table = new xmldb_table('qtype_digitalliteracy_option');

        // Adding fields to table qtype_digitalliteracy_option.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('responseformat', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'excel');
        $table->add_field('attachments', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('attachmentsrequired', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('filetypeslist', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table qtype_digitalliteracy_option.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('questionid', XMLDB_KEY_FOREIGN_UNIQUE, array('questionid'), 'question', array('id'));

        // Conditionally launch create table for qtype_digitalliteracy_option.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Digitalliteracy savepoint reached.
        upgrade_plugin_savepoint(true, 2020041430, 'qtype', 'digitalliteracy');
    }


    // Automatically generated Moodle v3.5.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
