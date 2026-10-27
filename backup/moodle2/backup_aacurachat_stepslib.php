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
 * Define the complete structure for backup, with file and id annotations.
 *
 * @package   mod_aacurachat
 * @copyright 2026 LL Slim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * The class defines the complete structure for backup, with file and id annotations.
 *
 * @package   mod_aacurachat
 * @copyright 2026 LL Slim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_aacurachat_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the structure of the resulting xml file.
     *
     * @return backup_nested_element The structure wrapped by the common 'activity' element.
     * @throws base_element_struct_exception
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function define_structure() {
        // Course certificate.
        $fields = [
            "name",
            "timecreated",
            "timemodified",
            "intro",
            "introformat",
            "scenariocode",
            "min_turns",
            "parent_intensity",
            "topiccode",
            "scenariolist",
            "allow_persona_switch",
            "grading_mode",
            "generation_case",
            "custom_prompt_template",
            "custom_eval_template",
        ];
        $aacurachat = new backup_nested_element("aacurachat", ["id"], $fields);

        // Define the source tables for the elements.
        $aacurachat->set_source_table("aacurachat", ["id" => backup::VAR_ACTIVITYID]);

        // Define file annotations.
        $aacurachat->annotate_files("mod_aacurachat", "intro", null); // This file area hasn't itemid.
        $aacurachat->annotate_files("mod_aacurachat", "scenariofile", null);

        return $this->prepare_activity_structure($aacurachat);
    }
}
