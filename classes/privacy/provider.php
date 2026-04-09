<?php
// This file is part of Moodle - http://moodle.org/

namespace local_coursequality\privacy;

use context;
use context_course;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy API implementation for stored evaluation reports.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_coursequality_report', [
            'courseid' => 'privacy:metadata:local_coursequality_report:courseid',
            'userid' => 'privacy:metadata:local_coursequality_report:userid',
            'timecreated' => 'privacy:metadata:local_coursequality_report:timecreated',
            'totalscore' => 'privacy:metadata:local_coursequality_report:totalscore',
            'metrics' => 'privacy:metadata:local_coursequality_report:metrics',
        ], 'privacy:metadata:local_coursequality_report');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_coursequality_report} r ON r.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :courselevel
                   AND r.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'userid' => $userid,
            'courselevel' => CONTEXT_COURSE,
        ]);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        foreach ($contextlist->get_userids() as $userid) {
            foreach ($contextlist->get_contexts() as $context) {
                if ($context->contextlevel != CONTEXT_COURSE) {
                    continue;
                }
                $reports = $DB->get_records('local_coursequality_report', [
                    'userid' => $userid,
                    'courseid' => $context->instanceid,
                ], 'timecreated ASC');
                if (!$reports) {
                    continue;
                }
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_coursequality')],
                    (object) ['reports' => array_values($reports)]
                );
            }
        }
    }

    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $DB->delete_records('local_coursequality_report', ['courseid' => $context->instanceid]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_userids() as $userid) {
            foreach ($contextlist->get_contexts() as $context) {
                if ($context->contextlevel != CONTEXT_COURSE) {
                    continue;
                }
                $DB->delete_records('local_coursequality_report', [
                    'userid' => $userid,
                    'courseid' => $context->instanceid,
                ]);
            }
        }
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_course) {
            return;
        }
        $sql = "SELECT DISTINCT r.userid
                  FROM {local_coursequality_report} r
                 WHERE r.courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
    }

    public static function delete_data_for_users(userlist $userlist): void {
        global $DB;
        if (!$userlist->get_context() instanceof context_course) {
            return;
        }
        $courseid = $userlist->get_context()->instanceid;
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['courseid'] = $courseid;
        $DB->delete_records_select(
            'local_coursequality_report',
            "courseid = :courseid AND userid $insql",
            $params
        );
    }
}
