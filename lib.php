<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Whether the current user is an editing teacher of this course (role shortname editingteacher
 * or role id 3 in standard installs), i.e. holds that role assignment in the course context.
 *
 * Site admins are NOT treated as editing teachers here (they use the admin UI if needed).
 *
 * @param context $context Course context.
 * @return bool
 */
function local_coursequality_user_is_course_editing_teacher(context $context): bool {
    global $DB, $USER;

    if ($context->contextlevel !== CONTEXT_COURSE || isguestuser()) {
        return false;
    }

    // Role id 3 = editingteacher in default Moodle; shortname check matches renamed/custom DBs.
    $sql = "SELECT 1
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
             WHERE ra.userid = :userid
               AND ra.contextid = :ctxid
               AND (r.shortname = :shortname OR r.id = :roleid)";

    return $DB->record_exists_sql($sql, [
        'userid' => $USER->id,
        'ctxid' => $context->id,
        'shortname' => 'editingteacher',
        'roleid' => 3,
    ]);
}

/**
 * Add link under course administration (gear / "More" / course settings).
 * Only users with editing teacher role in this course (see above).
 */
function local_coursequality_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return;
    }
    if (!local_coursequality_user_is_course_editing_teacher($context)) {
        return;
    }

    $courseid = $context->instanceid;
    $url = new moodle_url('/local/coursequality/index.php', ['id' => $courseid]);

    $node = navigation_node::create(
        get_string('nav_course_quality', 'local_coursequality'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_coursequality',
        new pix_icon('i/report', '')
    );

    // Prefer course administration branch; fall back to root of settings nav.
    if ($coursenode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $coursenode->add_node($node);
    } else if ($coursenode = $settingsnav->find('courseadmin', navigation_node::TYPE_SETTING)) {
        $coursenode->add_node($node);
    } else {
        $settingsnav->add_node($node);
    }
}
