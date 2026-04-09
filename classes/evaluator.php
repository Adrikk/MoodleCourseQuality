<?php
// This file is part of Moodle - http://moodle.org/

namespace local_coursequality;

use context_course;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Computes heuristic course-quality metrics from course structure + standard logs.
 */
class evaluator {

    /** @var int Fallback analysis window in seconds (default 120 days). */
    protected int $window;

    /** @var string[] */
    protected array $recommendedmods;

    /** @var string[] */
    protected array $contentmods;

    public function __construct(?int $windowdays = null) {
        $this->window = ($windowdays ?? 120) * DAYSECS;
        $this->recommendedmods = ['lesson', 'quiz', 'assign', 'hvp', 'h5pactivity', 'game'];
        $this->contentmods = ['page', 'book', 'resource', 'folder', 'url'];
    }

    /**
     * Returns student user ids enrolled with role student.
     *
     * @param int $courseid
     * @return int[]
     */
    public function get_student_userids(int $courseid): array {
        global $DB;

        $ctx = context_course::instance($courseid, MUST_EXIST);
        $role = $DB->get_record('role', ['shortname' => 'student'], 'id');
        if (!$role) {
            return [];
        }

        $sql = "SELECT DISTINCT u.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid AND e.status = 0
                  JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
                  JOIN {role_assignments} ra ON ra.userid = u.id
                       AND ra.contextid = :ctxid AND ra.roleid = :roleid
                 WHERE ue.status = 0";

        return $DB->get_fieldset_sql($sql, [
            'courseid' => $courseid,
            'ctxid' => $ctx->id,
            'roleid' => $role->id,
        ]);
    }

    /**
     * Visible course modules with module name.
     *
     * @return array<int,stdClass> cmid => {cmid, modname}
     */
    public function get_visible_modules(int $courseid): array {
        global $DB;

        $sql = "SELECT cm.id AS cmid, m.name AS modname
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.course = :courseid
                   AND cm.deletioninprogress = 0
                   AND cm.visible = 1";

        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
    }

    public function logstore_available(): bool {
        global $DB;
        return $DB->get_manager()->table_exists('logstore_standard_log');
    }

    /**
     * Stream log rows to count distinct students per cmid within period.
     *
     * Returns map: cmid => distinct students who had crud='r' events for that module.
     *
     * @param int[] $studentids
     * @return array<int,int>
     */
    public function get_module_touch_counts(int $courseid, array $studentids, int $since, int $until): array {
        global $DB;

        if (!$this->logstore_available() || empty($studentids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'stu');
        $params = array_merge([
            'courseid' => $courseid,
            'since' => $since,
            'until' => $until,
            'ctxmodule' => CONTEXT_MODULE,
        ], $inparams);

        $sql = "SELECT userid, contextinstanceid AS cmid
                  FROM {logstore_standard_log}
                 WHERE courseid = :courseid
                   AND contextlevel = :ctxmodule
                   AND timecreated >= :since
                   AND timecreated <= :until
                   AND crud = 'r'
                   AND userid $insql";

        $rs = $DB->get_recordset_sql($sql, $params);
        $seen = [];
        $counts = [];
        foreach ($rs as $row) {
            $cmid = (int) $row->cmid;
            $uid = (int) $row->userid;
            if (!isset($seen[$cmid])) {
                $seen[$cmid] = [];
            }
            if (!isset($seen[$cmid][$uid])) {
                $seen[$cmid][$uid] = true;
                $counts[$cmid] = ($counts[$cmid] ?? 0) + 1;
            }
        }
        $rs->close();

        return $counts;
    }

    /**
     * Distinct students with any log row in course in window.
     *
     * @param int[] $studentids
     */
    public function count_active_students(int $courseid, array $studentids, int $since, int $until): int {
        global $DB;

        if (!$this->logstore_available() || empty($studentids)) {
            return 0;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'stu');
        $params = array_merge(['courseid' => $courseid, 'since' => $since, 'until' => $until], $inparams);

        $sql = "SELECT COUNT(DISTINCT userid)
                  FROM {logstore_standard_log}
                 WHERE courseid = :courseid
                   AND timecreated >= :since
                   AND timecreated <= :until
                   AND userid $insql";

        return (int) $DB->get_field_sql($sql, $params);
    }

    /**
     * Distinct students who generated view/read-like events for a cmid.
     *
     * @param int[] $studentids
     */
    public function count_students_touching_cmid(int $courseid, int $cmid, array $studentids, int $since, int $until): int {
        global $DB;

        if (!$this->logstore_available() || empty($studentids)) {
            return 0;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'stu');
        $params = array_merge([
            'courseid' => $courseid,
            'cmid' => $cmid,
            'since' => $since,
            'until' => $until,
            'ctxmodule' => CONTEXT_MODULE,
        ], $inparams);

        // Standard log: module context, read actions, linked to course.
        $sql = "SELECT COUNT(DISTINCT userid)
                  FROM {logstore_standard_log}
                 WHERE courseid = :courseid
                   AND contextlevel = :ctxmodule
                   AND contextinstanceid = :cmid
                   AND timecreated >= :since
                   AND timecreated <= :until
                   AND crud = 'r'
                   AND userid $insql";

        return (int) $DB->get_field_sql($sql, $params);
    }

    /**
     * Weekly active students (distinct per week) for stability metric.
     *
     * @param int[] $studentids
     * @return float[] values 0..1 fraction of cohort per ISO week index
     */
    public function weekly_active_fractions(int $courseid, array $studentids, int $since, int $until): array {
        global $DB;

        if (!$this->logstore_available() || empty($studentids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'stu');
        $params = array_merge(['courseid' => $courseid, 'since' => $since, 'until' => $until], $inparams);

        $sql = "SELECT userid, timecreated
                  FROM {logstore_standard_log}
                 WHERE courseid = :courseid
                   AND timecreated >= :since
                   AND timecreated <= :until
                   AND userid $insql";

        $rows = $DB->get_recordset_sql($sql, $params);
        $weekusers = [];
        $cohort = count($studentids);
        foreach ($rows as $row) {
            $w = (int) gmdate('oW', $row->timecreated); // Year + week approximation bucket.
            if (!isset($weekusers[$w])) {
                $weekusers[$w] = [];
            }
            $weekusers[$w][$row->userid] = true;
        }
        $rows->close();

        if (!$weekusers) {
            return [];
        }

        ksort($weekusers);
        $fractions = [];
        foreach ($weekusers as $userset) {
            $fractions[] = $cohort > 0 ? count($userset) / $cohort : 0.0;
        }
        return $fractions;
    }

    /**
     * Shannon entropy of module type distribution (visible modules).
     *
     * @param array<int,stdClass> $modules
     */
    protected function structure_entropy(array $modules): array {
        $counts = [];
        foreach ($modules as $m) {
            $name = $m->modname;
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }
        $total = array_sum($counts);
        if ($total === 0) {
            return ['entropy' => 0.0, 'richness' => 0, 'types' => 0];
        }
        $entropy = 0.0;
        foreach ($counts as $c) {
            $p = $c / $total;
            $entropy -= $p * log($p, 2);
        }
        return [
            'entropy' => $entropy,
            'richness' => $total,
            'types' => count($counts),
        ];
    }

    /**
     * Map entropy to 0..100 (cap at "enough" diversity).
     */
    protected function score_structure(float $entropy, int $types): float {
        // Heuristic: 3+ types and entropy ~1.5+ already "good".
        $etype = min(1.0, $entropy / 2.5);
        $tscore = min(1.0, $types / 6.0);
        return round(100.0 * (0.6 * $etype + 0.4 * $tscore), 2);
    }

    /**
     * Fraction of recommended mod types present among visible modules.
     */
    protected function score_recommended(array $modules): array {
        $present = [];
        foreach ($this->recommendedmods as $rm) {
            $present[$rm] = false;
        }
        foreach ($modules as $m) {
            if (isset($present[$m->modname])) {
                $present[$m->modname] = true;
            }
        }
        $hit = count(array_filter($present));
        $score = count($this->recommendedmods) > 0
            ? round(100.0 * $hit / count($this->recommendedmods), 2)
            : 0.0;
        return ['score' => $score, 'present' => $present];
    }

    /**
     * Mean student touch-rate on content-like modules.
     *
     * @param array<int,stdClass> $modules
     * @param int[] $studentids
     */
    protected function score_content_utilization(array $modules, int $courseid, array $studentids, int $since, int $until): array {
        $contentcms = [];
        foreach ($modules as $cm) {
            if (in_array($cm->modname, $this->contentmods, true)) {
                $contentcms[] = $cm;
            }
        }
        if (!$contentcms || !$studentids) {
            return [
                'score' => 0.0,
                'modules' => 0,
                'dead' => 0,
                'mean_touch_rate' => null,
            ];
        }

        $rates = [];
        $dead = 0;
        $cohort = count($studentids);
        foreach ($contentcms as $cm) {
            $touched = $this->count_students_touching_cmid($courseid, (int) $cm->cmid, $studentids, $since, $until);
            $r = $cohort > 0 ? $touched / $cohort : 0.0;
            $rates[] = $r;
            if ($r < 0.05) {
                $dead++;
            }
        }
        $mean = array_sum($rates) / count($rates);
        $score = round(min(1.0, $mean / 0.6) * 100, 2); // 60% cohort touching on average => 100.

        return [
            'score' => $score,
            'modules' => count($contentcms),
            'dead' => $dead,
            'mean_touch_rate' => round($mean, 4),
        ];
    }

    /**
     * Ever-active share + stability from weekly fractions.
     *
     * @param int[] $studentids
     */
    protected function score_reach_and_stability(int $courseid, array $studentids, int $since, int $until): array {
        $cohort = count($studentids);
        if ($cohort === 0) {
            return [
                'reach_score' => 0.0,
                'ever_active' => 0,
                'ever_active_share' => 0.0,
                'stability_score' => 0.0,
                'weekly_cv' => null,
            ];
        }

        $active = $this->count_active_students($courseid, $studentids, $since, $until);
        $share = $active / $cohort;
        $reachscore = round(min(1.0, $share / 0.85) * 100, 2); // 85% ever active in window ~ excellent.

        $fracs = $this->weekly_active_fractions($courseid, $studentids, $since, $until);
        if (count($fracs) < 3) {
            $stab = 50.0; // Not enough weeks.
            $cv = null;
        } else {
            $meanf = array_sum($fracs) / count($fracs);
            if ($meanf <= 0.00001) {
                $cv = 0.0;
            } else {
                $var = 0.0;
                foreach ($fracs as $f) {
                    $var += ($f - $meanf) ** 2;
                }
                $var /= count($fracs);
                $cv = sqrt($var) / $meanf;
            }
            // Lower CV is better; map CV 0->100, CV>=1 -> 0.
            $stab = round(max(0, min(1, 1 - min(1, $cv))) * 100, 2);
        }

        return [
            'reach_score' => $reachscore,
            'ever_active' => $active,
            'ever_active_share' => round($share, 4),
            'stability_score' => $stab,
            'weekly_cv' => $cv === null ? null : round($cv, 4),
            'weeks_observed' => count($fracs),
        ];
    }

    /**
     * Run full evaluation for a course.
     *
     * @return array{total: float, metrics: array}
     */
    public function evaluate(int $courseid, ?int $since = null, ?int $until = null): array {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $now = time();
        $since = $since ?? ($now - $this->window);
        $until = $until ?? $now;

        $modules = $this->get_visible_modules($courseid);
        $struct = $this->structure_entropy($modules);
        $structscore = $this->score_structure($struct['entropy'], $struct['types']);

        $rec = $this->score_recommended($modules);

        $studentids = $this->get_student_userids($courseid);
        $content = $this->score_content_utilization($modules, $courseid, $studentids, $since, $until);
        $rs = $this->score_reach_and_stability($courseid, $studentids, $since, $until);

        // Element-level utilization snapshot (visible modules) based on student touch-rate.
        $touchcounts = $this->get_module_touch_counts($courseid, $studentids, $since, $until);
        $cohort = max(1, count($studentids));
        $lowthreshold = 0.05;
        $bytype = [];

        // Use modinfo to get human names + URLs.
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modules as $m) {
            $cmid = (int) $m->cmid;
            if (empty($modinfo->cms[$cmid])) {
                continue;
            }
            $cm = $modinfo->cms[$cmid];
            // Labels are UI-only; exclude them from utilization analysis and "problem elements".
            if ($cm->modname === 'label') {
                continue;
            }
            if (!$cm->uservisible) {
                continue;
            }
            $touched = (int) ($touchcounts[$cmid] ?? 0);
            $rate = $touched / $cohort;
            if ($rate > $lowthreshold) {
                continue;
            }
            $type = (string) $cm->modname;
            if (!isset($bytype[$type])) {
                $bytype[$type] = [];
            }
            $bytype[$type][] = [
                'cmid' => $cmid,
                'name' => $cm->get_formatted_name(),
                'url' => $cm->url ? $cm->url->out(false) : '',
                'touched' => $touched,
                'touchrate' => round($rate * 100, 2),
            ];
        }
        // Sort within each type by touchrate asc.
        foreach ($bytype as $type => &$items) {
            usort($items, fn($a, $b) => ($a['touchrate'] <=> $b['touchrate']) ?: ($a['cmid'] <=> $b['cmid']));
        }
        unset($items);

        $weights = [
            'structure' => 0.20,
            'recommended' => 0.20,
            'content' => 0.25,
            'reach' => 0.20,
            'stability' => 0.15,
        ];

        $total = $weights['structure'] * $structscore
            + $weights['recommended'] * $rec['score']
            + $weights['content'] * $content['score']
            + $weights['reach'] * $rs['reach_score']
            + $weights['stability'] * $rs['stability_score'];

        $metrics = [
            'window_days' => (int) round(($until - $since) / DAYSECS),
            'window_since' => userdate($since),
            'window_until' => userdate($until),
            'window_since_ts' => $since,
            'window_until_ts' => $until,
            'logstore' => $this->logstore_available(),
            'visible_modules' => count($modules),
            'student_cohort' => count($studentids),
            'structure' => [
                'score' => $structscore,
                'entropy' => round($struct['entropy'], 4),
                'types' => $struct['types'],
            ],
            'recommended' => [
                'score' => $rec['score'],
                'present' => $rec['present'],
            ],
            'content' => $content,
            'reach' => [
                'score' => $rs['reach_score'],
                'ever_active' => $rs['ever_active'],
                'ever_active_share' => $rs['ever_active_share'],
            ],
            'stability' => [
                'score' => $rs['stability_score'],
                'weekly_cv' => $rs['weekly_cv'],
                'weeks_observed' => $rs['weeks_observed'],
            ],
            'element_utilization' => [
                'low_touch_threshold_pct' => (int) round($lowthreshold * 100),
                'low_touch_by_type' => $bytype,
            ],
            'weights' => $weights,
            'moodle_release' => $CFG->release ?? '',
        ];

        return [
            'total' => round($total, 2),
            'metrics' => $metrics,
        ];
    }
}
