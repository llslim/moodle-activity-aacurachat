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

global $PAGE, $USER, $CFG;

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

$capability = has_capability("local/aacuracore:manage", $context);

$active_scenario = $aacurachat->scenariocode ?? 'anna';
if (class_exists('\\local_aacuracore\\api')) {
    $activesession = \local_aacuracore\api::get_latest_active_session((int)$USER->id, (int)$course->id, (int)$cm->id);
} else {
    $activesession = $DB->get_record('local_aacuracore_sessions', ['userid' => $USER->id, 'courseid' => $course->id, 'cmid' => $cm->id], '*', IGNORE_MULTIPLE);
}
if ($activesession && !empty($activesession->scenariocode) && $activesession->scenariocode !== '__builder__') {
    $active_scenario = $activesession->scenariocode;
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

// Build available personas options dynamically
$personasoptions = [];
$preloadednames = [
    'anna' => "Anna Charles (Sarah's Mother - Autism)",
    'brianna' => "Brianna Mitchell (Wesley's Mother - Apraxia)",
    'cathy' => "Cathy Fratner (Charlie's Mother - Down Syndrome)",
    'mary' => "Mary (Mother of Non-Verbal 6-Year-Old)",
];

// Role display labels for preloaded scenarios (from scenario JSON role metadata).
$preloadedroles = [
    'anna' => 'Parent',
    'brianna' => 'Parent',
    'cathy' => 'Parent',
    'mary' => 'Parent',
];

// 1. Static preloaded personas enabled in site config
$activesetting = get_config('local_aacuracore', 'active_scenarios');
$enabledscenarios = !empty($activesetting) ? explode(',', $activesetting) : ['anna', 'brianna', 'cathy', 'mary'];

foreach ($enabledscenarios as $code) {
    if (isset($preloadednames[$code])) {
        $personasoptions[] = [
            'code' => $code,
            'name' => $preloadednames[$code],
            'role_label' => $preloadedroles[$code] ?? 'Parent',
            'selected' => ($active_scenario === $code),
        ];
    }
}

// 2. Site-wide custom uploaded personas
$customrecords = $DB->get_records('local_aacuracore_custom_scenarios', null, 'name ASC');
foreach ($customrecords as $cr) {
    $rolelabel = 'Parent';
    $jsondata = json_decode($cr->json_data ?? '', true);
    if (is_array($jsondata) && isset($jsondata['persona']['role']['display_label'])) {
        $rolelabel = $jsondata['persona']['role']['display_label'];
    }
    $personasoptions[] = [
        'code' => $cr->scenariocode,
        'name' => $cr->name . ' (Custom Persona)',
        'role_label' => $rolelabel,
        'selected' => ($active_scenario === $cr->scenariocode),
    ];
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
