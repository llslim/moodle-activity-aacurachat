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
 * mod_form file
 *
 * @package   mod_aacurachat
 * @copyright 2025 Eduardo Kraus https://eduardokraus.com/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->dirroot}/course/moodleform_mod.php");

/**
 * Class mod_aacurachat_mod_form
 */
class mod_aacurachat_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function definition(): void {
        global $CFG, $DB;

        $mform = $this->_form;
        $mform->addElement("header", "general", get_string("general", "form"));

        $mform->addElement("text", "name", get_string("name"), ["size" => "64"]);
        $mform->addRule("name", null, "required", null, "client");
        $mform->addRule("name", get_string("maximumchars", "", 255), "maxlength", 255, "client");
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType("name", PARAM_TEXT);
        } else {
            $mform->setType("name", PARAM_CLEANHTML);
        }

        $this->standard_intro_elements();

        $scenarios = [
            'anna' => 'Anna Charles (Autism pre-K concern)',
            'brianna' => 'Brianna Mitchell (Apraxia / social isolation)',
            'cathy' => 'Cathy Fratner (Down Syndrome / app concern)',
            'mary' => 'Mary (Mother of Non-Verbal 6-Year-Old)',
        ];

        // Fetch custom registered personas from local_aacuracore_custom_scenarios DB table
        $customrecords = $DB->get_records('local_aacuracore_custom_scenarios', null, 'name ASC');
        foreach ($customrecords as $cr) {
            $scenarios[$cr->scenariocode] = $cr->name . ' (Custom Persona)';
        }

        $scenarios['custom'] = 'Activity File Upload (Upload single scenario .json below)';

        $mform->addElement('select', 'scenariocode', get_string('scenariocode', 'mod_aacurachat'), $scenarios);
        $mform->setDefault('scenariocode', 'anna');
        $mform->setType('scenariocode', PARAM_ALPHANUMEXT);

        // Add direct Scenario Builder link button in settings
        $builderurl = new moodle_url('/local/aacuracore/scenario_builder.php');
        $buttonhtml = '<div class="form-group row fitem">' .
            '<div class="col-md-3 text-sm-right"><label class="col-form-label"></label></div>' .
            '<div class="col-md-9 form-inline felement">' .
            '<a href="' . $builderurl->out() . '" target="_blank" class="btn btn-primary" style="background-color: #4F46E5; border-color: #4F46E5; color: white;">' .
            '🛠️ Open Custom Scenario Builder Tool' .
            '</a>' .
            '<span class="form-text text-muted ml-2">Build a custom scenario JSON file to upload below.</span>' .
            '</div></div>';
        $mform->addElement('html', $buttonhtml);

        $mform->addElement(
            'filepicker',
            'scenariofile',
            get_string('scenariofile', 'mod_aacurachat'),
            null,
            ['maxbytes' => 1024 * 1024, 'accepted_types' => ['.json']]
        );

        // Per-activity conversation settings (override site-wide global defaults).
        $mform->addElement('header', 'aacuraactivitysettings', get_string('activitysettings', 'mod_aacurachat'));

        $minoptions = [0 => get_string('min_turns_default', 'mod_aacurachat')];
        for ($i = 4; $i <= 20; $i++) {
            $minoptions[$i] = $i . ' ' . get_string('turns', 'mod_aacurachat');
        }
        $mform->addElement('select', 'min_turns', get_string('min_turns', 'mod_aacurachat'), $minoptions);
        $mform->setDefault('min_turns', 0);
        $mform->setType('min_turns', PARAM_INT);
        $mform->addHelpButton('min_turns', 'min_turns', 'mod_aacurachat');

        $intensitylevels = [
            '' => get_string('parent_intensity_default', 'mod_aacurachat'),
            'very_low' => get_string('parent_intensity_very_low', 'mod_aacurachat'),
            'low' => get_string('parent_intensity_low', 'mod_aacurachat'),
            'medium' => get_string('parent_intensity_medium', 'mod_aacurachat'),
            'high' => get_string('parent_intensity_high', 'mod_aacurachat'),
            'very_high' => get_string('parent_intensity_very_high', 'mod_aacurachat'),
        ];
        $mform->addElement('select', 'parent_intensity', get_string('parent_intensity', 'mod_aacurachat'), $intensitylevels);
        $mform->setDefault('parent_intensity', '');
        $mform->setType('parent_intensity', PARAM_ALPHA);
        $mform->addHelpButton('parent_intensity', 'parent_intensity', 'mod_aacurachat');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Preprocess data before populating the form.
     * Maps legacy max_turns database values to min_turns if min_turns is empty.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if (isset($defaultvalues['max_turns']) && !isset($defaultvalues['min_turns'])) {
            $defaultvalues['min_turns'] = $defaultvalues['max_turns'];
        }
    }

    /**
     * Enforce validation rules here
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array
     **/
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
