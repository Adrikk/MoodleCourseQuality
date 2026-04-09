<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course quality insights';
$string['privacy:metadata'] = 'The Course quality insights plugin stores evaluation reports triggered by users.';

$string['privacy:metadata:local_coursequality_report'] = 'Stored course quality evaluation snapshots.';
$string['privacy:metadata:local_coursequality_report:courseid'] = 'Course that was evaluated.';
$string['privacy:metadata:local_coursequality_report:userid'] = 'User who generated the report.';
$string['privacy:metadata:local_coursequality_report:timecreated'] = 'When the report was generated.';
$string['privacy:metadata:local_coursequality_report:totalscore'] = 'Aggregate score.';
$string['privacy:metadata:local_coursequality_report:metrics'] = 'JSON details of subscores and counters.';

$string['admin_menu'] = 'Course quality evaluations';
$string['nav_course_quality'] = 'Evaluate course quality';

$string['coursequality:evaluate'] = 'Run course quality evaluation';
$string['coursequality:view'] = 'View course quality reports';
$string['coursequality:manage'] = 'Manage course quality evaluations (site)';

$string['page_title_course'] = 'Course quality: {$a}';
$string['page_title_report'] = 'Quality report';
$string['page_title_admin'] = 'Course quality evaluations';

$string['reports_heading'] = 'Saved reports';
$string['noreports'] = 'No reports yet. Run an evaluation to generate the first report.';
$string['report_date'] = 'Generated';
$string['report_score'] = 'Score';
$string['report_view'] = 'View';
$string['report_delete'] = 'Delete';

$string['evaluate_button'] = 'Evaluate course';
$string['evaluating'] = 'Evaluating…';
$string['evaluate_done'] = 'Report generated. You can open it from the list above.';
$string['evaluate_error'] = 'Evaluation failed.';

$string['confirm_delete'] = 'Delete this report permanently?';

$string['metric_total'] = 'Overall score';
$string['metric_structure'] = 'Structure diversity';
$string['metric_recommended'] = 'Recommended activity types';
$string['metric_content_use'] = 'Theory/content utilization';
$string['metric_reach'] = 'Student reach';
$string['metric_stability'] = 'Engagement stability';

$string['admin_reports'] = 'All reports';
$string['admin_bulk'] = 'Bulk evaluation';
$string['bulk_categories'] = 'Categories (hold Ctrl/Cmd to select multiple)';
$string['bulk_include_subcats'] = 'Include subcategories';
$string['bulk_submit'] = 'Evaluate courses in selected categories';
$string['bulk_done'] = 'Queued {$a} evaluations.';
$string['bulk_nothing_selected'] = 'Select at least one category or enter course IDs.';
$string['bulk_select_course'] = 'Or pick individual courses';
$string['bulk_courses'] = 'Courses';

$string['error_permission'] = 'You do not have permission to do this.';
$string['error_not_editing_teacher'] = 'This page is only available to editing teachers of this course (role editingteacher / id 3 in the course context).';
$string['error_logstore'] = 'Standard log store is not available; some metrics will be limited.';

$string['window_days_label'] = 'Analysis window (days):';
$string['student_cohort_label'] = 'Students (role student, enrolled):';
$string['visible_modules_label'] = 'Visible modules:';
$string['logstore_ok'] = 'Standard logs: available.';
