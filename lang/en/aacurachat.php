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
 * Lang en file
 *
 * @package   mod_aacurachat
 * @copyright 2025 Eduardo Kraus https://eduardokraus.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aacurachat:addinstance'] = 'Add instance';
$string['aacurachat:view'] = 'Allow the user to view the LAFF Strategy Chat';
$string['modulename'] = 'LAFF Strategy Chat';
$string['modulenameplural'] = 'LAFF Strategy Chat';
$string['pluginadministration'] = 'Adminitrate Chats';
$string['pluginname'] = 'LAFF Strategy Chat';
$string['privacy:metadata'] = 'None store data';
$string['scenariocode'] = 'Active Roleplay Persona';
$string['scenariofile'] = 'Upload Custom JSON Scenario';
$string['scenariofile_help'] = 'Upload a custom, structured JSON directed graph scenario config matching the PRD specification schema.';
$string['activitysettings'] = 'Conversation Settings';
$string['min_turns'] = 'Minimum student turns before grading';
$string['min_turns_help'] = 'The minimum number of student exchanges the conversation must run before it is automatically evaluated and graded. The dialogue continues for at least this many turns even if a terminal state is reached early. Select "Use site default" to inherit the global setting (default 8).';
$string['turns'] = 'turns';
$string['min_turns_default'] = '0 — Use site default (8)';
$string['max_turns'] = 'Minimum student turns before grading';
$string['max_turns_help'] = 'The minimum number of student exchanges the conversation must run before it is automatically evaluated and graded. The dialogue continues for at least this many turns even if a terminal state is reached early. Select "Use site default" to inherit the global setting (default 8).';
$string['max_turns_default'] = '0 — Use site default (8)';
$string['parent_intensity'] = 'Parent assertiveness / aggressiveness';
$string['parent_intensity_help'] = 'Dial the simulated parent\'s assertiveness/aggressiveness up or down for this activity. Select "Default" to use the site-wide global setting (Medium).';
$string['parent_intensity_default'] = 'Default (site-wide)';
$string['parent_intensity_very_low'] = 'Very Low (very gentle, passive)';
$string['parent_intensity_low'] = 'Low (mildly assertive)';
$string['parent_intensity_medium'] = 'Medium (moderately assertive)';
$string['parent_intensity_high'] = 'High (assertive & firm)';
$string['parent_intensity_very_high'] = 'Very High (confrontational, aggressive)';

// v2.0 / Core v3.0 Per-Activity Topic & Scenario Library strings.
$string['topicscenarioheader'] = 'Topic & Scenario Selection';
$string['topiccode'] = 'Conversation Topic Category';
$string['topiccode_help'] = 'Select a communication training topic from the Topic Library. Choosing a topic filters the scenario checklist below and provides topic-level default settings.';
$string['topic_all'] = 'All Topics (Show all available scenarios)';
$string['scenariolist'] = 'Enabled Roleplay Scenarios for this Activity';
$string['scenariolist_help'] = 'Check one or more scenarios from the library to make them available in this activity. Each activity instance in a course can assign its own distinct topic and scenario set.';
$string['defaultscenario'] = 'Default Starting Scenario';
$string['defaultscenario_help'] = 'The scenario loaded automatically when a student first opens this activity.';
$string['allow_persona_switch'] = 'Student Persona Switching';
$string['allow_persona_switch_help'] = 'Control whether students can use the header dropdown to switch between the enabled scenarios in this activity, or if they are locked to the Default Starting Scenario.';
$string['allow_persona_switch_yes'] = 'Allow students to switch between enabled scenarios';
$string['allow_persona_switch_no'] = 'Lock students to the Default Starting Scenario only';
$string['grading_mode'] = 'Multi-Scenario Grading Mode';
$string['grading_mode_help'] = 'Determines how the Moodle gradebook score (out of 10) is calculated when multiple scenarios are enabled in this activity.';
$string['grading_mode_single_highest'] = 'Single Scenario — Highest Score Across Enabled Scenarios (Default)';
$string['grading_mode_single_latest'] = 'Single Scenario — Most Recent Completed Scenario';
$string['grading_mode_all_average'] = 'All Enabled Scenarios — Average Score Across All Assigned Scenarios';
$string['register_to_library'] = 'Register uploaded JSON in Course Topic Library';
$string['register_to_library_desc'] = 'Also save the uploaded .json scenario into this course\'s Topic Library (assigned to the selected Topic above) so it can be reused across other activities in this course.';
$string['generation_case'] = 'AI Generation Use Case Profile';
$string['generation_case_help'] = 'Override the site-wide AI generation temperature/Top_p profile for this specific activity.';
$string['generation_case_default'] = 'Default (use site-wide Use Case profile)';
$string['custom_prompt_template'] = 'Activity Persona System Prompt Override (Optional)';
$string['custom_prompt_template_help'] = 'Optional per-activity LLM system prompt template. Leave empty to inherit from the Scenario JSON or Site Default. Supports LAFF placeholders such as {{persona_name}}, {{role_display_label}}, {{backstory}}, {{pronoun}}, {{statekey}}, {{stateprompt}}, and {{parent_intensity}}.';
$string['custom_eval_template'] = 'Activity Rubric Evaluation Prompt Override (Optional)';
$string['custom_eval_template_help'] = 'Optional per-activity rubric evaluation system prompt. Leave empty to inherit the Site Default evaluation template. Use {{rubric}} to inject the scenario\'s per-state rubric criteria.';

