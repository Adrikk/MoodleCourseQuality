<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_coursequality_bulk');
require_capability('local/coursequality:manage', context_system::instance());

$PAGE->set_title(get_string('admin_bulk', 'local_coursequality'));
$PAGE->set_heading(get_string('admin_bulk', 'local_coursequality'));

$maxcourses = 40;

if (($data = data_submitted()) && confirm_sesskey()) {
    $categoryids = optional_param_array('categoryids', [], PARAM_INT);
    $recursive = optional_param('recursive', 0, PARAM_BOOL);
    $manual = optional_param('courseidsmanual', '', PARAM_RAW_TRIMMED);

    $coursestoeval = [];

    if (!$categoryids && $manual === '') {
        redirect(
            new moodle_url('/local/coursequality/admin/bulk.php'),
            get_string('bulk_nothing_selected', 'local_coursequality'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    if ($categoryids) {
        foreach ($categoryids as $catid) {
            try {
                $cat = \core_course_category::get($catid, IGNORE_MISSING, true);
            } catch (\Exception $e) {
                continue;
            }
            if (!$cat) {
                continue;
            }
            foreach ($cat->get_courses(['recursive' => $recursive, 'hidden' => 0]) as $c) {
                $coursestoeval[$c->id] = $c;
            }
        }
    }

    if ($manual !== '') {
        foreach (preg_split('/[\s,;]+/', $manual, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $cid = clean_param($part, PARAM_INT);
            if ($cid <= 0) {
                continue;
            }
            try {
                $coursestoeval[$cid] = get_course($cid);
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    $done = 0;
    $eval = new local_coursequality\evaluator();
    foreach ($coursestoeval as $course) {
        if ($done >= $maxcourses) {
            break;
        }
        $result = $eval->evaluate($course->id);
        $record = new stdClass();
        $record->courseid = $course->id;
        $record->userid = $USER->id;
        $record->timecreated = time();
        $record->status = 'complete';
        $record->totalscore = $result['total'];
        $record->metrics = json_encode($result['metrics'], JSON_UNESCAPED_UNICODE);
        $DB->insert_record('local_coursequality_report', $record);
        $done++;
    }

    redirect(
        new moodle_url('/local/coursequality/admin/index.php'),
        get_string('bulk_done', 'local_coursequality', $done),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url('/local/coursequality/admin/index.php'),
    get_string('admin_reports', 'local_coursequality')
);

echo $OUTPUT->heading(get_string('admin_bulk', 'local_coursequality'), 3);

$cats = $DB->get_records('course_categories', null, 'sortorder', 'id, name');

$form = html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/local/coursequality/admin/bulk.php'))->out(false)]);
$form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$form .= html_writer::tag('p', get_string('bulk_categories', 'local_coursequality'));
$form .= html_writer::start_tag('select', ['name' => 'categoryids[]', 'multiple' => 'multiple', 'size' => min(15, max(5, count($cats))), 'class' => 'form-control mb-2']);
foreach ($cats as $cat) {
    $form .= html_writer::tag('option', format_string($cat->name, true), ['value' => $cat->id]);
}
$form .= html_writer::end_tag('select');

$form .= html_writer::tag(
    'label',
    html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'recursive', 'value' => '1']) . ' ' .
        get_string('bulk_include_subcats', 'local_coursequality'),
    ['class' => 'form-check mb-3']
);

$form .= html_writer::tag('p', get_string('bulk_select_course', 'local_coursequality'));
$form .= html_writer::tag('p', get_string('bulk_courses', 'local_coursequality') . ' (id через запятую, опционально):');
$form .= html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'courseidsmanual',
    'class' => 'form-control mb-2',
    'placeholder' => '11099, 2837',
]);

$form .= html_writer::empty_tag('input', [
    'type' => 'submit',
    'name' => 'submit',
    'value' => get_string('bulk_submit', 'local_coursequality'),
    'class' => 'btn btn-primary',
]);

$form .= html_writer::end_tag('form');

echo $form;

echo $OUTPUT->footer();
