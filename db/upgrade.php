<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the digitalliteracy question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_digitalliteracy_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020120100) {
        // Define field fontparams to be added to qtype_digitalliteracy_option.
        $table = new xmldb_table('qtype_digitalliteracy_option');

        $field = new xmldb_field('hastemplatefile', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'filetypeslist');
        // Launch rename field showtemplatefile.
        $dbman->rename_field($table, $field, 'showtemplatefile');

        $field = new xmldb_field('fontparams', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'checkbutton');
        // Conditionally launch add field fontparams.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('grouponecoef', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '100', 'fontparams');
        // Launch rename field group1coef.
        $dbman->rename_field($table, $field, 'group1coef');

        $field = new xmldb_field('grouptwocoef', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'group1coef');
        // Launch rename field group2coef.
        $dbman->rename_field($table, $field, 'group2coef');

        $field = new xmldb_field('groupthreecoef', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'group2coef');
        // Launch rename field group3coef.
        $dbman->rename_field($table, $field, 'group3coef');

        $field = new xmldb_field('grouponeparamone', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group3coef');
        // Launch rename field group1param1.
        $dbman->rename_field($table, $field, 'group1param1');

        $field = new xmldb_field('grouponeparamtwo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group1param1');
        // Launch rename field group1param2.
        $dbman->rename_field($table, $field, 'group1param2');

        $field = new xmldb_field('grouponeparamthree', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group1param2');
        // Launch rename field group1param3.
        $dbman->rename_field($table, $field, 'group1param3');

        $field = new xmldb_field('grouponeparamfour', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group1param3');
        // Launch rename field group1param4.
        $dbman->rename_field($table, $field, 'group1param4');

        $field = new xmldb_field('grouptwoparamone', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group1param4');
        // Launch rename field group2param1.
        $dbman->rename_field($table, $field, 'group2param1');

        $field = new xmldb_field('grouptwoparamtwo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group2param1');
        // Launch rename field group2param2.
        $dbman->rename_field($table, $field, 'group2param2');

        $field = new xmldb_field('grouptwoparamthree', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group2param2');
        // Launch rename field group2param3.
        $dbman->rename_field($table, $field, 'group2param3');

        $field = new xmldb_field('grouptwoparamfour', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group2param3');
        // Launch rename field group2param4.
        $dbman->rename_field($table, $field, 'group2param4');

        $field = new xmldb_field('groupthreeparamone', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group2param4');
        // Launch rename field group3param1.
        $dbman->rename_field($table, $field, 'group3param1');

        $field = new xmldb_field('groupthreeparamtwo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group3param1');
        // Launch rename field group3param2.
        $dbman->rename_field($table, $field, 'group3param2');

        $field = new xmldb_field('groupthreeparamthree', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group3param2');
        // Launch rename field group3param3.
        $dbman->rename_field($table, $field, 'group3param3');

        $field = new xmldb_field('groupthreeparamfour', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'group3param3');
        // Launch rename field group3param4.
        $dbman->rename_field($table, $field, 'group3param4');

        // Digitalliteracy savepoint reached.
        upgrade_plugin_savepoint(true, 2020120100, 'qtype', 'digitalliteracy');
    }

    return true;
}
