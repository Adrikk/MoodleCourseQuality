<?php
// This file is part of Moodle - http://moodle.org/
//
// Drops only this plugin's tables. Does not touch core or other plugins.

defined('MOODLE_INTERNAL') || die();

/**
 * Uninstall hook: remove local_coursequality database objects.
 *
 * @return bool
 */
function xmldb_local_coursequality_uninstall() {
    global $DB;

    $dbman = $DB->get_manager();
    $table = new xmldb_table('local_coursequality_report');

    if ($dbman->table_exists($table)) {
        $dbman->drop_table($table);
    }

    return true;
}
