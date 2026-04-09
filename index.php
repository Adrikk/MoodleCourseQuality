<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
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
require_capability('local/coursequality:view', $context);

$PAGE->set_url(new moodle_url('/local/coursequality/index.php', ['id' => $course->id]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('page_title_course', 'local_coursequality', format_string($course->shortname, true)));
$PAGE->set_heading(format_string($course->fullname, true));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'local_coursequality'));

$canevaluate = local_coursequality_user_is_course_editing_teacher($context)
    && has_capability('local/coursequality:evaluate', $context);

$reports = $DB->get_records('local_coursequality_report', ['courseid' => $course->id], 'timecreated DESC');

echo $OUTPUT->heading(get_string('reports_heading', 'local_coursequality'), 3);

if (!$reports) {
    echo $OUTPUT->notification(get_string('noreports', 'local_coursequality'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('report_date', 'local_coursequality'),
        get_string('report_score', 'local_coursequality'),
        '',
    ];
    $table->attributes['class'] = 'generaltable';
    $syscontext = context_system::instance();
    $canmanage = has_capability('local/coursequality:manage', $syscontext);

    foreach ($reports as $r) {
        $viewurl = new moodle_url('/local/coursequality/view.php', ['id' => $r->id]);
        $delurl = new moodle_url('/local/coursequality/delete.php', ['id' => $r->id, 'sesskey' => sesskey()]);
        $eye = html_writer::tag('i', '', [
            'class' => 'icon fa-regular fa-eye fa-fw',
            'aria-hidden' => 'true',
        ]);
        $trash = html_writer::tag('i', '', [
            'class' => 'icon fa fa-trash-can fa-fw',
            'aria-hidden' => 'true',
        ]);

        $actions = html_writer::link($viewurl, $eye, ['title' => get_string('report_view', 'local_coursequality')]);
        $can_delete = $canmanage || ($canevaluate && $r->userid == $USER->id);
        if ($can_delete) {
            $actions .= ' ' . html_writer::link(
                $delurl,
                $trash,
                ['onclick' => 'return confirm("' . get_string('confirm_delete', 'local_coursequality') . '");']
            );
        }
        $table->data[] = [
            userdate($r->timecreated),
            format_float($r->totalscore, 2),
            $actions,
        ];
    }
    echo html_writer::table($table);
}

if ($canevaluate) {
    echo html_writer::div('', '', ['id' => 'local_coursequality_spinner', 'style' => 'display:none;margin:1rem 0;']);
    echo html_writer::tag(
        'div',
        get_string('evaluating', 'local_coursequality'),
        ['id' => 'local_coursequality_spinner_text', 'class' => 'alert alert-info', 'style' => 'display:none;']
    );

    $form = html_writer::tag(
        'form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $course->id]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-primary',
            'value' => get_string('evaluate_button', 'local_coursequality'),
        ]),
        [
            'method' => 'post',
            'action' => (new moodle_url('/local/coursequality/evaluate.php'))->out(false),
            'onsubmit' => 'document.getElementById("local_coursequality_spinner").style.display="block";' .
                'document.getElementById("local_coursequality_spinner_text").style.display="block";',
        ]
    );
    echo html_writer::div($form, 'mt-3');
}

echo $OUTPUT->footer();
