<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_coursequality_admin');
$syscontext = context_system::instance();
require_capability('local/coursequality:manage', $syscontext);

$PAGE->set_title(get_string('page_title_admin', 'local_coursequality'));
$PAGE->set_heading(get_string('page_title_admin', 'local_coursequality'));

echo $OUTPUT->header();

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/coursequality/admin/bulk.php'),
        get_string('admin_bulk', 'local_coursequality'),
        ['class' => 'btn btn-secondary mb-3']
    )
);

echo $OUTPUT->heading(get_string('admin_reports', 'local_coursequality'), 3);

$reports = $DB->get_records_sql(
    "SELECT r.*, c.shortname, c.fullname, u.firstname, u.lastname, u.email
       FROM {local_coursequality_report} r
       JOIN {course} c ON c.id = r.courseid
       JOIN {user} u ON u.id = r.userid
   ORDER BY r.timecreated DESC
      LIMIT 500"
);

if (!$reports) {
    echo $OUTPUT->notification(get_string('noreports', 'local_coursequality'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('report_date', 'local_coursequality'),
    get_string('course'),
    get_string('user'),
    get_string('report_score', 'local_coursequality'),
    '',
];
$table->attributes['class'] = 'generaltable';

foreach ($reports as $r) {
    $courseurl = new moodle_url('/course/view.php', ['id' => $r->courseid]);
    $viewurl = new moodle_url('/local/coursequality/view.php', ['id' => $r->id]);
    $delurl = new moodle_url('/local/coursequality/delete.php', ['id' => $r->id, 'sesskey' => sesskey()]);
    $courselink = html_writer::link($courseurl, format_string($r->shortname, true));
    $userfull = fullname($r);
    $viewtitle = get_string('report_view', 'local_coursequality');
    $deltitle = get_string('report_delete', 'local_coursequality');

    $eye = html_writer::tag('i', '', [
        'class' => 'icon fa fa-eye fa-fw iconsmall',
        'title' => $viewtitle,
        'role' => 'img',
        'aria-label' => $viewtitle,
    ]);
    $trash = html_writer::tag('i', '', [
        'class' => 'icon fa fa-trash fa-fw iconsmall',
        'title' => $deltitle,
        'role' => 'img',
        'aria-label' => $deltitle,
    ]);

    $actions = html_writer::link($viewurl, $eye, [
        'class' => 'action-icon',
        'title' => $viewtitle,
    ]);
    $actions .= ' ' . html_writer::link(
        $delurl,
        $trash,
        [
            'class' => 'action-icon',
            'title' => $deltitle,
            'onclick' => 'return confirm("' . get_string('confirm_delete', 'local_coursequality') . '");',
        ]
    );
    $table->data[] = [
        userdate($r->timecreated),
        $courselink,
        $userfull,
        format_float($r->totalscore, 2),
        $actions,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
