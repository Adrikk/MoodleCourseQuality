<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
require_sesskey();

$course = get_course($id);
require_login($course);

$context = context_course::instance($course->id);

if (!local_coursequality_user_is_course_editing_teacher($context)) {
    throw new moodle_exception(
        'nopermissions',
        'error',
        '',
        get_string('error_not_editing_teacher', 'local_coursequality')
    );
}
require_capability('local/coursequality:evaluate', $context);

$startdate = optional_param('startdate', '', PARAM_RAW_TRIMMED); // YYYY-MM-DD.
$enddate = optional_param('enddate', '', PARAM_RAW_TRIMMED);     // YYYY-MM-DD.

// Default UI values are today/today. If unchanged, require explicit selection.
$today = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d');
if ($startdate === '' && $enddate === '') {
    $startdate = $today;
    $enddate = $today;
}

if ($startdate === $today && $enddate === $today) {
    redirect(
        new moodle_url('/local/coursequality/index.php', ['id' => $course->id]),
        get_string('error_choose_dates', 'local_coursequality'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Validate format (basic) and order.
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startdate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $enddate)) {
    redirect(
        new moodle_url('/local/coursequality/index.php', ['id' => $course->id]),
        get_string('error_invalid_dates', 'local_coursequality'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Convert to timestamps (UTC day bounds).
$since = (new DateTimeImmutable($startdate . ' 00:00:00', new DateTimeZone('UTC')))->getTimestamp();
$until = (new DateTimeImmutable($enddate . ' 23:59:59', new DateTimeZone('UTC')))->getTimestamp();
$todayend = (new DateTimeImmutable($today . ' 23:59:59', new DateTimeZone('UTC')))->getTimestamp();

if ($until > $todayend) {
    redirect(
        new moodle_url('/local/coursequality/index.php', ['id' => $course->id]),
        get_string('error_end_in_future', 'local_coursequality'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

if ($since > $until) {
    redirect(
        new moodle_url('/local/coursequality/index.php', ['id' => $course->id]),
        get_string('error_start_after_end', 'local_coursequality'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$eval = new local_coursequality\evaluator();
$result = $eval->evaluate($course->id, $since, $until);

$record = new stdClass();
$record->courseid = $course->id;
$record->userid = $USER->id;
$record->timecreated = time();
$record->status = 'complete';
$record->totalscore = $result['total'];
$record->metrics = json_encode($result['metrics'], JSON_UNESCAPED_UNICODE);

$rid = $DB->insert_record('local_coursequality_report', $record);

redirect(
    new moodle_url('/local/coursequality/view.php', ['id' => $rid]),
    get_string('evaluate_done', 'local_coursequality'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
