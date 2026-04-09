<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
$report = $DB->get_record('local_coursequality_report', ['id' => $id], '*', MUST_EXIST);
$course = get_course($report->courseid);

require_login($course);
$context = context_course::instance($course->id);
$syscontext = context_system::instance();
$canmanage = has_capability('local/coursequality:manage', $syscontext);

if (!$canmanage && !local_coursequality_user_is_course_editing_teacher($context)) {
    throw new moodle_exception(
        'nopermissions',
        'error',
        '',
        get_string('error_not_editing_teacher', 'local_coursequality')
    );
}
if (!$canmanage) {
    require_capability('local/coursequality:view', $context);
}

$PAGE->set_url(new moodle_url('/local/coursequality/view.php', ['id' => $report->id]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('page_title_report', 'local_coursequality'));
$PAGE->set_heading(format_string($course->fullname, true));

$metrics = json_decode($report->metrics, true);
if (!is_array($metrics)) {
    $metrics = [];
}

$logstorenote = !empty($metrics['logstore'])
    ? get_string('logstore_ok', 'local_coursequality')
    : get_string('error_logstore', 'local_coursequality');

$period = '—';
if (!empty($metrics['window_since_ts']) && !empty($metrics['window_until_ts'])) {
    $period = gmdate('d.m.Y', (int) $metrics['window_since_ts']) . '-' . gmdate('d.m.Y', (int) $metrics['window_until_ts']);
} else if (!empty($metrics['window_since']) && !empty($metrics['window_until'])) {
    // Fallback to stored userdate strings if timestamps are missing.
    $period = (string) $metrics['window_since'] . '-' . (string) $metrics['window_until'];
}
$meta = implode(' · ', [
    get_string('window_days_label', 'local_coursequality') . ' ' . $period,
    get_string('student_cohort_label', 'local_coursequality') . ' ' . ($metrics['student_cohort'] ?? '—'),
    get_string('visible_modules_label', 'local_coursequality') . ' ' . ($metrics['visible_modules'] ?? '—'),
    $logstorenote,
]);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('page_title_report', 'local_coursequality'), 2);
echo html_writer::tag('p', userdate($report->timecreated), ['class' => 'text-muted']);

echo $OUTPUT->render_from_template('local_coursequality/report_summary', [
    'totalscore' => format_float($report->totalscore, 2),
    'structure' => format_float($metrics['structure']['score'] ?? 0, 2),
    'recommended' => format_float($metrics['recommended']['score'] ?? 0, 2),
    'content' => format_float($metrics['content']['score'] ?? 0, 2),
    'reach' => format_float($metrics['reach']['score'] ?? 0, 2),
    'stability' => format_float($metrics['stability']['score'] ?? 0, 2),
    'meta_line' => $meta,
]);

$lowbytype = [];
$rawbytype = $metrics['element_utilization']['low_touch_by_type'] ?? [];
foreach ($rawbytype as $modname => $items) {
    $lowbytype[] = [
        'modname' => $modname,
        'count' => is_array($items) ? count($items) : 0,
        'items' => is_array($items) ? $items : [],
    ];
}
usort($lowbytype, fn($a, $b) => strcmp($a['modname'], $b['modname']));

$threshold = (int) ($metrics['element_utilization']['low_touch_threshold_pct'] ?? 5);
$hint = get_string('element_utilization_hint', 'local_coursequality', $threshold);

echo $OUTPUT->render_from_template('local_coursequality/report_details', [
    'element_utilization_hint' => $hint,
    'has_low_touch' => !empty($lowbytype),
    'low_touch_by_type' => $lowbytype,
]);

$back = new moodle_url('/local/coursequality/index.php', ['id' => $course->id]);
echo html_writer::div(html_writer::link($back, get_string('back')), 'mt-3');

echo $OUTPUT->footer();
