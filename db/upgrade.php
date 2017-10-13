<?php

function xmldb_block_bc_dashboard_upgrade($oldversion = 0)
{
    global $DB;
    
    $dbman = $DB->get_manager();

    if ($oldversion < 2013082000)
    {
        
        // Define table block_bcdb_reports to be created
        $table = new xmldb_table('block_bcdb_reports');

        // Adding fields to table block_bcdb_reports
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('updatedbyuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('lastrunuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timelastrun', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('runs', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_bcdb_reports
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('uidfk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('uuidfk', XMLDB_KEY_FOREIGN, array('updatedbyuserid'), 'user', array('id'));
        $table->add_key('lruidfk', XMLDB_KEY_FOREIGN, array('lastrunuserid'), 'user', array('id'));

        // Conditionally launch create table for block_bcdb_reports
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // bc_dashboard savepoint reached
        upgrade_block_savepoint(true, 2013082000, 'bc_dashboard');

        
    }
    
    if ($oldversion < 2013091600)
    {
        
        // Define field lastruntook to be added to block_bcdb_reports
        $table = new xmldb_table('block_bcdb_reports');
        $field = new xmldb_field('lastruntook', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'data');

        // Conditionally launch add field lastruntook
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // bc_dashboard savepoint reached
        upgrade_block_savepoint(true, 2013091600, 'bc_dashboard');
        
    }
    
    
    if ($oldversion < 2013093002)
    {
        
         // Define field del to be added to block_bcdb_reports
        $table = new xmldb_table('block_bcdb_reports');
        $field = new xmldb_field('del', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'lastruntook');

        // Conditionally launch add field del
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // bc_dashboard savepoint reached
        upgrade_block_savepoint(true, 2013093002, 'bc_dashboard');
        
    }
    
    if ($oldversion < 2014010200)
    {
        
        // Define field category to be added to block_bcdb_reports
        $table = new xmldb_table('block_bcdb_reports');
        $field = new xmldb_field('category', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'name');

        // Conditionally launch add field category
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // bc_dashboard savepoint reached
        upgrade_block_savepoint(true, 2014010200, 'bc_dashboard');
        
    }
    
    
    if ($oldversion < 2014012700)
    {
        
        // Define table bcdb_settings to be created
        $table = new xmldb_table('block_bcdb_settings');

        // Adding fields to table bcdb_settings
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('setting', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table bcdb_settings
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for bcdb_settings
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // bc_dashboard savepoint reached
        upgrade_block_savepoint(true, 2014012700, 'bc_dashboard');
        
    }
    
    
    if ($oldversion < 2014092300) {

        // Define table block_bcdb_schedule to be created.
        $table = new xmldb_table('block_bcdb_schedule');

        // Adding fields to table block_bcdb_schedule.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('period', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, null);
        $table->add_field('hour', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('minute', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contact', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table block_bcdb_schedule.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('rid_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'block_bcdb_reports', array('id'));

        // Conditionally launch create table for block_bcdb_schedule.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2014092300, 'bc_dashboard');
        
    }
    
    
    if ($oldversion < 2014092301) {

        // Define table block_bcdb_report_logs to be created.
        $table = new xmldb_table('block_bcdb_report_logs');

        // Adding fields to table block_bcdb_report_logs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_bcdb_report_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('rid_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'block_bcdb_reports', array('id'));

        // Conditionally launch create table for block_bcdb_report_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2014092301, 'bc_dashboard');
        
    }

    
    if ($oldversion < 2014092401) {

        // Define field contact to be added to block_bcdb_schedule.
         // Define field lastrun to be added to block_bcdb_schedule.
        $table = new xmldb_table('block_bcdb_schedule');
        $field = new xmldb_field('lastrun', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, 0, 'contact');

        // Conditionally launch add field contact.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2014092401, 'bc_dashboard');
        
    }

    
    // Delete old tables and create new tables for improved dashboard block
    if ($oldversion < 2017022000) {
        
        
        // DELETE OLD TABLES
        
        // Define table block_bcdb_reports to be dropped.
        $table = new xmldb_table('block_bcdb_report_logs');

        // Conditionally launch drop table for block_bcdb_reports.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        
        
        
        // Define table block_bcdb_reports to be dropped.
        $table = new xmldb_table('block_bcdb_schedule');

        // Conditionally launch drop table for block_bcdb_reports.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        
        
        
        // Define table block_bcdb_reports to be dropped.
        $table = new xmldb_table('block_bcdb_reports');

        // Conditionally launch drop table for block_bcdb_reports.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        
        
        
        // CREATE NEW TABLES
        
        // Define table block_bcdb_reports to be created.
        $table = new xmldb_table('block_bcdb_reports');

        // Adding fields to table block_bcdb_reports.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('category', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('query', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('params', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('options', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('createddate', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastrun', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('del', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_bcdb_reports.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_bcdb_reports.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017022000, 'bc_dashboard');
        
    }
    
    
    
    if ($oldversion < 2017022800) {

        // Define table block_bcdb_report_categories to be created.
        $table = new xmldb_table('block_bcdb_report_categories');

        // Adding fields to table block_bcdb_report_categories.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('parent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_bcdb_report_categories.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_bcdb_report_categories.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        
        // Changing type of field category on table block_bcdb_reports to int.
        $table = new xmldb_table('block_bcdb_reports');
        $field = new xmldb_field('category', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'type');

        // Launch change of type for field category.
        $dbman->change_field_type($table, $field);
        

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017022800, 'bc_dashboard');
        
    }

    
    if ($oldversion < 2017030200){
        
        // Changing nullability of field category on table block_bcdb_reports to null.
        $table = new xmldb_table('block_bcdb_reports');
        $field = new xmldb_field('category', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'type');

        // Launch change of nullability for field category.
        $dbman->change_field_notnull($table, $field);

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017030200, 'bc_dashboard');
        
    }
    
    
    if ($oldversion < 2017030300){
        
         // Define field createdby to be added to block_bcdb_reports.
        $table = new xmldb_table('block_bcdb_reports');
        
        $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'createddate');

        // Conditionally launch add field createdby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('lastrun');

        // Conditionally launch drop field createdby.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017030300, 'bc_dashboard');
        
    }
    
    
    if ($oldversion < 2017030301) {

        // Define table block_bcdb_logs to be created.
        $table = new xmldb_table('block_bcdb_logs');

        // Adding fields to table block_bcdb_logs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('reportid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('log', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_bcdb_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_bcdb_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017030301, 'bc_dashboard');
    }

    
    
    if ($oldversion < 2017030800) {

        // Define table block_bcdb_download_codes to be created.
        $table = new xmldb_table('block_bcdb_download_codes');

        // Adding fields to table block_bcdb_download_codes.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('path', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_bcdb_download_codes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_bcdb_download_codes.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017030800, 'bc_dashboard');
    }

    
    if ($oldversion < 2017030801) {

        // Changing type of field path on table block_bcdb_download_codes to char.
        $table = new xmldb_table('block_bcdb_download_codes');
        $field = new xmldb_field('path', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'code');

        // Launch change of type for field path.
        $dbman->change_field_type($table, $field);

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017030801, 'bc_dashboard');
    }

    
    if ($oldversion < 2017042500) {

        // Define table block_bcdb_report_elements to be created.
        $table = new xmldb_table('block_bcdb_report_elements');

        // Adding fields to table block_bcdb_report_elements.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('plugin', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filepath', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('classname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table block_bcdb_report_elements.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_bcdb_report_elements.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017042500, 'bc_dashboard');
        
    }

    
    
    if ($oldversion < 2017042600) {

        // Define field subplugin to be added to block_bcdb_report_elements.
        $table = new xmldb_table('block_bcdb_report_elements');
        $field = new xmldb_field('subplugin', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'plugin');

        // Conditionally launch add field subplugin.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017042600, 'bc_dashboard');
        
    }

    
    if ($oldversion < 2017050201) {

        // Changing nullability of field query on table block_bcdb_reports to null.
        $table = new xmldb_table('block_bcdb_reports');
        $field = new xmldb_field('query', XMLDB_TYPE_TEXT, null, null, null, null, null, 'description');

        // Launch change of nullability for field query.
        $dbman->change_field_notnull($table, $field);

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017050201, 'bc_dashboard');
        
    }

    if ($oldversion < 2017053000) {

        // Define table block_bcdb_schedule to be created.
        $table = new xmldb_table('block_bcdb_schedule');

        // Adding fields to table block_bcdb_schedule.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('reportid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scheduledtime', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, null);
        $table->add_field('repetitiontype', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('repetitionvalues', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('emailto', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('lastrun', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('nextrun', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table block_bcdb_schedule.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('rid_fk', XMLDB_KEY_FOREIGN, array('reportid'), 'block_bcdb_reports', array('id'));

        // Conditionally launch create table for block_bcdb_schedule.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017053000, 'bc_dashboard');
        
    }
    
    
    if ($oldversion < 2017053001) {

        // Define field createdbyuserid to be added to block_bcdb_schedule.
        $table = new xmldb_table('block_bcdb_schedule');
        $field = new xmldb_field('createdbyuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'nextrun');

        // Conditionally launch add field createdbyuserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Launch add key cbuid_fk.
        $key = new xmldb_key('cbuid_fk', XMLDB_KEY_FOREIGN, array('createdbyuserid'), 'user', array('id'));
        $dbman->add_key($table, $key);
        
        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017053001, 'bc_dashboard');
        
    }
    
    
    if ($oldversion < 2017060201) {

        // Define field params to be added to block_bcdb_schedule.
        $table = new xmldb_table('block_bcdb_schedule');
        $field = new xmldb_field('params', XMLDB_TYPE_TEXT, null, null, null, null, null, 'repetitionvalues');

        // Conditionally launch add field params.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017060201, 'bc_dashboard');
    }


    if ($oldversion < 2017060700) {

        // Define field filters to be added to block_bcdb_reports.
        $table = new xmldb_table('block_bcdb_reports');
        $field = new xmldb_field('filters', XMLDB_TYPE_TEXT, null, null, null, null, null, 'options');

        // Conditionally launch add field filters.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017060700, 'bc_dashboard');
    }
    
    
    if ($oldversion < 2017101201){
        
        // Changing nullability of field params on table block_bcdb_reports to null.
        $table = new xmldb_table('block_bcdb_reports');
        $field = new xmldb_field('params', XMLDB_TYPE_TEXT, null, null, null, null, null, 'query');

        // Launch change of nullability for field params.
        $dbman->change_field_notnull($table, $field);

        // Bc_dashboard savepoint reached.
        upgrade_block_savepoint(true, 2017101201, 'bc_dashboard');
        
    }

    
    
    return true;
    
    
}