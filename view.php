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
 * Prints an instance of mod_aacurachat.
 *
 * @package   mod_aacurachat
 * @copyright 2025 Eduardo Kraus https://eduardokraus.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_aacuracore\local\util\release;

if (file_exists(__DIR__ . '/../../config.php')) {
    require_once(__DIR__ . '/../../config.php');
} else if (file_exists('/var/www/html/config.php')) {
    require_once('/var/www/html/config.php');
} else {
    require_once("../../config.php");
}

global $PAGE, $USER, $CFG, $DB;

$id = required_param("id", PARAM_INT);

$cm = get_coursemodule_from_id("aacurachat", $id, 0, false, MUST_EXIST);
$course = $DB->get_record("course", ["id" => $cm->course], "*", MUST_EXIST);

$context = context_module::instance($cm->id);

/** @var \mod_aacurachat\vo\aacurachat $aacurachat */
$aacurachat = $DB->get_record("aacurachat", ["id" => $cm->instance], "*", MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url("/mod/aacurachat/view.php", ["id" => $id]);
$PAGE->set_title($course->shortname . ": " . $aacurachat->name);
$PAGE->set_heading(format_string($course->fullname));

require_course_login($course, true, $cm);
require_capability("mod/aacurachat:view", $context);

$event = \mod_aacurachat\event\course_module_viewed::create([
    "objectid" => $PAGE->cm->instance,
    "context" => $PAGE->context,
]);
$event->add_record_snapshot("course", $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $aacurachat);
$event->trigger();

// Update "viewed" state if required by completion system.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

$libpath = $CFG->dirroot . '/local/aacuracore/classes/library/topic_library_manager.php';
if (file_exists($libpath)) {
    require_once($libpath);
}

$capability = has_capability("local/aacuracore:manage", $context)
    || has_capability("mod/aacurachat:addinstance", $context)
    || has_capability("moodle/course:manageactivities", $context);

$allowpersonaswitch = !isset($aacurachat->allow_persona_switch) || (int)$aacurachat->allow_persona_switch === 1;

// Resolve the scenarios enabled for this specific activity instance (cmid).
if (class_exists('\\local_aacuracore\\library\\topic_library_manager')) {
    \local_aacuracore\library\topic_library_manager::ensure_seeded();
    $enabledscenarios = \local_aacuracore\library\topic_library_manager::resolve_activity_scenariolist($aacurachat);
} else {
    $activesetting = get_config('local_aacuracore', 'active_scenarios');
    $enabledscenarios = !empty($activesetting) ? explode(',', $activesetting) : ['anna', 'brianna', 'cathy', 'mary'];
}

$active_scenario = $aacurachat->scenariocode ?? 'anna';
if ($allowpersonaswitch) {
    if (class_exists('\\local_aacuracore\\api')) {
        $activesession = \local_aacuracore\api::get_latest_active_session((int)$USER->id, (int)$course->id, (int)$cm->id);
    } else {
        $activesession = $DB->get_record(
            'local_aacuracore_sessions',
            ['userid' => $USER->id, 'courseid' => $course->id, 'cmid' => $cm->id],
            '*',
            IGNORE_MULTIPLE
        );
    }
    if ($activesession && !empty($activesession->scenariocode) && $activesession->scenariocode !== '__builder__') {
        if (in_array($activesession->scenariocode, $enabledscenarios, true)) {
            $active_scenario = $activesession->scenariocode;
        }
    }
}

if (!in_array($active_scenario, $enabledscenarios, true) && !empty($enabledscenarios)) {
    $active_scenario = $enabledscenarios[0];
}

// Derive the active role display label from the selected scenario (default Parent).
$active_role_label = 'Parent';
try {
    if (class_exists('\\local_aacuracore\\scenario\\scenario_loader')) {
        $active_sc = \local_aacuracore\scenario\scenario_loader::load($active_scenario, $course->id, $cm->id);
        $activerole = $active_sc->get_role();
        if ($activerole && !empty($activerole['display_label'])) {
            $active_role_label = $activerole['display_label'];
        }
    }
} catch (\Throwable $e) {
    // Fall back to Parent.
}

// Build available personas options dynamically for this activity's scenariolist.
$personasoptions = [];
$preloadednames = [
    'anna' => "Anna Charles (Sarah's Mother - Autism)",
    'brianna' => "Brianna Mitchell (Wesley's Mother - Apraxia)",
    'cathy' => "Cathy Fratner (Charlie's Mother - Down Syndrome)",
    'mary' => "Mary (Mother of Non-Verbal 6-Year-Old)",
];

$libraryscenarios = [];
if (class_exists('\\local_aacuracore\\library\\topic_library_manager')) {
    $libraryscenarios = \local_aacuracore\library\topic_library_manager::get_scenarios_for_topic(null, (int)$course->id, true);
}

$customrecords = $DB->get_records('local_aacuracore_custom_scenarios', null, 'name ASC');

foreach ($enabledscenarios as $code) {
    if (isset($preloadednames[$code])) {
        $personasoptions[] = [
            'code' => $code,
            'name' => $preloadednames[$code],
            'role_label' => 'Parent',
            'selected' => ($active_scenario === $code),
        ];
    } else if (isset($libraryscenarios[$code])) {
        $ls = $libraryscenarios[$code];
        $rlabel = !empty($ls->role_label) ? $ls->role_label : 'Parent';
        $personasoptions[] = [
            'code' => $ls->scenariocode,
            'name' => $ls->name . " ({$rlabel})",
            'role_label' => $rlabel,
            'selected' => ($active_scenario === $ls->scenariocode),
        ];
    } else if (isset($customrecords[$code])) {
        $cr = $customrecords[$code];
        $rolelabel = 'Parent';
        $jsondata = json_decode($cr->json_data ?? '', true);
        if (is_array($jsondata) && isset($jsondata['persona']['role']['display_label'])) {
            $rolelabel = $jsondata['persona']['role']['display_label'];
        }
        $personasoptions[] = [
            'code' => $cr->scenariocode,
            'name' => $cr->name . " ({$rolelabel})",
            'role_label' => $rolelabel,
            'selected' => ($active_scenario === $cr->scenariocode),
        ];
    } else {
        try {
            $loaded = \local_aacuracore\scenario\scenario_loader::load($code, (int)$course->id, (int)$cm->id);
            $r = $loaded->get_role();
            $rlabel = $r['display_label'] ?? 'Parent';
            $personasoptions[] = [
                'code' => $code,
                'name' => $loaded->get_persona_name() . " ({$rlabel})",
                'role_label' => $rlabel,
                'selected' => ($active_scenario === $code),
            ];
        } catch (\Throwable $e) {
            // Skip unresolvable scenario code.
        }
    }
}

$topiccode = !empty($aacurachat->topiccode) ? $aacurachat->topiccode : 'parent_counseling';
$topicname = 'Parent & Family AAC Counseling';
if (class_exists('\\local_aacuracore\\library\\topic_library_manager')) {
    $topics = \local_aacuracore\library\topic_library_manager::get_topics((int)$course->id);
    if (isset($topics[$topiccode])) {
        $topicname = $topics[$topiccode]->name;
    }
}

$data = [
    "message_01" => get_string("message_01", "local_aacuracore", fullname($USER)),
    "manage_capability" => $capability,
    "editing" => $PAGE->user_is_editing(),
    "geniainame" => get_config("local_aacuracore", "geniainame"),
    "mode" => get_config("local_aacuracore", "mode"),
    "talk_geniai" => get_string("talk_geniai", "local_aacuracore", get_config("local_aacuracore", "geniainame")),
    "active_scenario" => $active_scenario,
    "active_role_label" => $active_role_label,
    "allow_persona_switch" => $allowpersonaswitch,
    "topiccode" => $topiccode,
    "topic_name" => $topicname,
    "grading_mode" => $aacurachat->grading_mode ?? 'single_highest',
    "cmid" => (int)$cm->id,
    "courseid" => (int)$course->id,
    "personas_options" => $personasoptions,
    "student_name" => fullname($USER),
    "course_name" => format_string($course->fullname),
];

$geniainame = get_config("local_aacuracore", "geniainame");
$course = $DB->get_record("course", ["id" => $COURSE->id]);
$data["message_02"] = get_string(
    "message_02_course",
    "local_aacuracore",
    ["geniainame" => $geniainame, "moodlename" => $SITE->fullname, "coursename" => $course->fullname]
);

echo $OUTPUT->render_from_template("mod_aacurachat/chat", $data);
$PAGE->requires->js_call_amd("local_aacuracore/chat", "init", [$COURSE->id, $cm->id, release::version()]);

echo $OUTPUT->footer();
