<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_coursequality_evaluate_course' => [
        'classname'   => 'local_coursequality\external\evaluate_course',
        'methodname'  => 'execute',
        'description' => 'Run course quality evaluation and store a report row',
        'type'        => 'write',
        'ajax'        => true,
        'loginrequired' => true,
        'capabilities' => 'local/coursequality:evaluate',
    ],
];
