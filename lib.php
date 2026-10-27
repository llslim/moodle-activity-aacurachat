<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package   mod_aacurachat
 * @copyright 2025 Eduardo Kraus https://eduardokraus.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Checks if certificate activity supports a specific feature.
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_SHOW_DESCRIPTION
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_MODEDIT_DEFAULT_COMPLETION
 * @uses FEATURE_BACKUP_MOODLE2
 *
 * @param string $feature FEATURE_xx constant for requested feature
 *
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function aacurachat_supports(string $feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_aacurachat into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param stdClass $data                           An object from the form.
 * @param mod_aacurachat_mod_form $mform The form.
 *
 * @return int The id of the newly inserted record.
 * @throws dml_exception
 */
function aacurachat_add_instance(stdClass $data, $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $cmid = $data->coursemodule;

    $data->id = $DB->insert_record("aacurachat", $data);

    // We need to use context now, so we need to make sure all needed info is already in db.
    $DB->set_field("course_modules", "instance", $data->id, ["id" => $cmid]);

    // Handle saving uploaded custom JSON scenario file
    if ($mform && isset($data->scenariofile)) {
        $context = context_module::instance($cmid);
        file_save_draft_area_files(
            $data->scenariofile,
            $context->id,
            'mod_aacurachat',
            'scenariofile',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }

    // Register with Moodle Gradebook
    aacurachat_grade_item_update($data);

    return $data->id;
}

/**
 * Updates an instance of the mod_aacurachat in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param stdClass $data                           An object from the form in mod_form.php.
 * @param mod_aacurachat_mod_form $mform The form.
 *
 * @return bool True if successful, false otherwise.
 * @throws dml_exception
 */
function aacurachat_update_instance(stdClass $data, $mform = null): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $result = $DB->update_record("aacurachat", $data);

    if ($result && $mform && isset($data->scenariofile)) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files(
            $data->scenariofile,
            $context->id,
            'mod_aacurachat',
            'scenariofile',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
    }

    // Sync Gradebook properties
    aacurachat_grade_item_update($data);

    return $result;
}

/**
 * Removes an instance of the mod_aacurachat from the database.
 *
 * @param int $id Id of the module instance.
 *
 * @return bool True if successful, false on failure.
 * @throws coding_exception
 * @throws dml_exception
 */
function aacurachat_delete_instance(int $id): bool {
    global $DB;

    if (!$DB->record_exists("aacurachat", ["id" => $id])) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance("aacurachat", $id)) {
        return false;
    }
    $DB->delete_records("aacurachat", ["id" => $id]);

    return true;
}

/**
 * Update/create grade item for given geniai instance.
 *
 * @param stdClass $geniai
 * @param array|stdClass $grades
 * @return int
 */
function aacurachat_grade_item_update(stdClass $aacurachat, $grades = null): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = [
        'itemname' => $aacurachat->name,
        'idnumber' => $aacurachat->idnumber ?? '',
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => 10,
        'grademin' => 0,
    ];

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/aacurachat', $aacurachat->course, 'mod', 'aacurachat', $aacurachat->id, 0, $grades, $params);
}

/**
 * Update grades in the gradebook for a specific mod_aacurachat activity instance.
 * Queries sessions and evaluations by cmid and applies the activity's configured
 * multi-scenario grading_mode (single_highest, single_latest, or all_average).
 *
 * @param stdClass $aacurachat
 * @param int $userid
 * @param bool $nullifnone
 */
function aacurachat_update_grades(stdClass $aacurachat, int $userid = 0, bool $nullifnone = true): void {
    global $DB, $CFG;

    $cm = get_coursemodule_from_instance('aacurachat', $aacurachat->id, $aacurachat->course, false, IGNORE_MISSING);
    $cmid = $cm ? (int)$cm->id : 0;

    $libpath = $CFG->dirroot . '/local/aacuracore/classes/library/topic_library_manager.php';
    if (file_exists($libpath)) {
        require_once($libpath);
    }

    $grades = [];
    $userids = [];
    if ($userid > 0) {
        $userids = [$userid];
    } else if ($cmid > 0) {
        $sessions = $DB->get_records('local_aacuracore_sessions', ['cmid' => $cmid], '', ' DISTINCT userid');
        foreach ($sessions as $s) {
            $userids[] = (int)$s->userid;
        }
    }

    foreach ($userids as $uid) {
        $computedscore = null;
        if (class_exists('\\local_aacuracore\\library\\topic_library_manager')) {
            $computedscore = \local_aacuracore\library\topic_library_manager::calculate_activity_grade($aacurachat, $cmid, $uid);
        } else {
            $params = ['userid' => $uid];
            if ($cmid > 0) {
                $params['cmid'] = $cmid;
            } else {
                $params['scenariocode'] = $aacurachat->scenariocode;
            }
            $session = $DB->get_record('local_aacuracore_sessions', $params, '*', IGNORE_MULTIPLE);
            if ($session) {
                $totalscore = 10;
                $analytics = $DB->get_records('local_aacuracore_analytics', ['sessionid' => $session->id]);
                $missedcount = 0;
                foreach ($analytics as $analytic) {
                    if ($analytic->metric_value == 0.00) {
                        $missedcount++;
                    }
                }
                $computedscore = max(0, $totalscore - $missedcount);
            }
        }

        if ($computedscore !== null) {
            $grade = new \stdClass();
            $grade->userid = $uid;
            $grade->rawgrade = $computedscore;
            $grades[$uid] = $grade;
        } else if ($nullifnone && $userid > 0) {
            $grade = new \stdClass();
            $grade->userid = $uid;
            $grade->rawgrade = null;
            $grades[$uid] = $grade;
        }
    }

    aacurachat_grade_item_update($aacurachat, empty($grades) ? null : $grades);
}
