<?php
// This file is part of Moodle - http://moodle.org/

namespace local_coursequality\external;

use context_course;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_coursequality\evaluator;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class evaluate_course extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
        ]);
    }

    public static function execute(int $courseid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $courseid = $params['courseid'];

        $context = context_course::instance($courseid, MUST_EXIST);
        self::validate_context($context);

        if (!\local_coursequality_user_is_course_editing_teacher($context)) {
            throw new \moodle_exception(
                'nopermissions',
                'error',
                '',
                get_string('error_not_editing_teacher', 'local_coursequality')
            );
        }
        require_capability('local/coursequality:evaluate', $context);

        $eval = new evaluator();
        $result = $eval->evaluate($courseid);

        $record = new stdClass();
        $record->courseid = $courseid;
        $record->userid = $USER->id;
        $record->timecreated = time();
        $record->status = 'complete';
        $record->totalscore = $result['total'];
        $record->metrics = json_encode($result['metrics'], JSON_UNESCAPED_UNICODE);

        $id = $DB->insert_record('local_coursequality_report', $record);

        return [
            'reportid' => $id,
            'totalscore' => $result['total'],
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'reportid' => new external_value(PARAM_INT, 'New report id'),
            'totalscore' => new external_value(PARAM_FLOAT, 'Aggregate score'),
        ]);
    }
}
