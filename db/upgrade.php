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
 * Book module upgrade code
 *
 * @package   mod_aacurachat
 * @copyright 2025 Eduardo Kraus https://eduardokraus.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Book module upgrade task
 *
 * @param int $oldversion the version we are upgrading from
 *
 * @return bool always true
 * @throws coding_exception
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws moodle_exception
 * @throws upgrade_exception
 */
function xmldb_aacurachat_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026052500) {
        $table = new xmldb_table('aacurachat');
        $field = new xmldb_field('scenariocode', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'anna', 'introformat');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026052500, 'mod', 'aacurachat');
    }

    if ($oldversion < 2026082100) {
        $table = new xmldb_table('aacurachat');

        $field = new xmldb_field('max_turns', XMLDB_TYPE_INTEGER, '6', null, null, null, '8', 'scenariocode');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('parent_intensity', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'max_turns');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026082100, 'mod', 'aacurachat');
    }

    if ($oldversion < 2026100100) {
        $table = new xmldb_table('aacurachat');

        $oldfield = new xmldb_field('max_turns', XMLDB_TYPE_INTEGER, '6', null, null, null, '8', 'scenariocode');
        if ($dbman->field_exists($table, $oldfield)) {
            $dbman->rename_field($table, $oldfield, 'min_turns');
        } else {
            $newfield = new xmldb_field('min_turns', XMLDB_TYPE_INTEGER, '6', null, null, null, '8', 'scenariocode');
            if (!$dbman->field_exists($table, $newfield)) {
                $dbman->add_field($table, $newfield);
            }
        }

        upgrade_plugin_savepoint(true, 2026100100, 'mod', 'aacurachat');
    }

    if ($oldversion < 2026110100) {
        $table = new xmldb_table('aacurachat');

        $fields = [
            new xmldb_field('topiccode', XMLDB_TYPE_CHAR, '100', null, null, null, 'parent_counseling', 'introformat'),
            new xmldb_field('scenariolist', XMLDB_TYPE_TEXT, null, null, null, null, null, 'topiccode'),
            new xmldb_field('allow_persona_switch', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'scenariocode'),
            new xmldb_field('grading_mode', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'single_highest', 'allow_persona_switch'),
            new xmldb_field('generation_case', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'parent_intensity'),
            new xmldb_field('custom_prompt_template', XMLDB_TYPE_TEXT, null, null, null, null, null, 'generation_case'),
            new xmldb_field('custom_eval_template', XMLDB_TYPE_TEXT, null, null, null, null, null, 'custom_prompt_template'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Populate scenariolist on existing activity instances so existing courses experience zero disruption.
        $libpath = $CFG->dirroot . '/local/aacuracore/classes/library/topic_library_manager.php';
        if (file_exists($libpath)) {
            require_once($libpath);
            \local_aacuracore\library\topic_library_manager::backfill_activity_scenariolists();
        }

        upgrade_plugin_savepoint(true, 2026110100, 'mod', 'aacurachat');
    }

    return true;
}
