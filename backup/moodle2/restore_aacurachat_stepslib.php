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
 * All the steps to restore mod_aacurachat are defined here.
 *
 * @package   mod_aacurachat
 * @copyright 2026 LL Slim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Class defines the structure step to restore one mod_aacurachat activity.
 *
 * @package   mod_aacurachat
 * @copyright 2026 LL Slim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_aacurachat_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines the structure to be restored.
     *
     * @return restore_path_element[].
     */
    protected function define_structure(): array {
        $paths = [];
        $paths[] = new restore_path_element("aacurachat", "/activity/aacurachat");

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Processes the element restore data.
     *
     * @param array $data Parsed element data.
     *
     * @throws dml_exception
     */
    protected function process_aacurachat(array $data): void {
        global $DB;
        $data = (object) $data;
        $data->course = $this->get_courseid();

        // Defaults for restoring backups from previous major versions.
        if (empty($data->scenariocode)) {
            $data->scenariocode = 'anna';
        }
        if (empty($data->topiccode)) {
            $data->topiccode = 'parent_counseling';
        }
        if (empty($data->scenariolist)) {
            $data->scenariolist = json_encode([$data->scenariocode]);
        }
        if (!isset($data->allow_persona_switch)) {
            $data->allow_persona_switch = 1;
        }
        if (empty($data->grading_mode)) {
            $data->grading_mode = 'single_highest';
        }
        if (!isset($data->generation_case)) {
            $data->generation_case = '';
        }

        // Insert the record.
        $newitemid = $DB->insert_record("aacurachat", $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Defines post-execution actions.
     */
    protected function after_execute(): void {
        $this->add_related_files("mod_aacurachat", "intro", null);
        $this->add_related_files("mod_aacurachat", "scenariofile", null);
    }
}
