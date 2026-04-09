<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
require_sesskey();

$report = $DB->get_record('local_coursequality_report', ['id' => $id], '*', MUST_EXIST);
$course = get_course($report->courseid);
require_login($course);

$context = context_course::instance($course->id);
$syscontext = context_system::instance();

$canmanage = has_capability('local/coursequality:manage', $syscontext);
$iseditingteacher = local_coursequality_user_is_course_editing_teacher($context);

if ($canmanage) {
    // Site-level managers: full delete access.
} else if (
    $iseditingteacher
    && $report->userid == $USER->id
    && has_capability('local/coursequality:evaluate', $context)
) {
    // Course editing teacher may delete only own reports.
} else {
    throw new moodle_exception('error_permission', 'local_coursequality');
}

$DB->delete_records('local_coursequality_report', ['id' => $id]);

redirect(
    new moodle_url('/local/coursequality/index.php', ['id' => $course->id]),
    get_string('report_delete', 'local_coursequality'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
