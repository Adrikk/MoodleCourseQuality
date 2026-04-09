<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$ADMIN->add(
    'courses',
    new admin_externalpage(
        'local_coursequality_admin',
        get_string('admin_menu', 'local_coursequality'),
        new moodle_url('/local/coursequality/admin/index.php'),
        'local/coursequality:manage'
    )
);
$ADMIN->add(
    'courses',
    new admin_externalpage(
        'local_coursequality_bulk',
        get_string('admin_bulk', 'local_coursequality'),
        new moodle_url('/local/coursequality/admin/bulk.php'),
        'local/coursequality:manage'
    )
);
