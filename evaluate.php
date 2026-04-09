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

$eval = new local_coursequality\evaluator();
$result = $eval->evaluate($course->id);

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
