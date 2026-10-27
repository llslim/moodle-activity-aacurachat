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
 * Activity configuration form for mod_aacurachat.
 *
 * Supports per-activity Topic & Scenario Library selection, multi-scenario
 * checklist filtering, student persona switching control, grading mode selection,
 * and per-activity AI simulation overrides.
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
     * Defines form elements.
     *
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function definition(): void {
        global $CFG, $DB, $COURSE;

        $mform = $this->_form;
        $courseid = isset($COURSE->id) ? (int)$COURSE->id : 0;

        $libpath = $CFG->dirroot . '/local/aacuracore/classes/library/topic_library_manager.php';
        if (file_exists($libpath)) {
            require_once($libpath);
        }

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

        // =========================================================================
        // 1. TOPIC & SCENARIO SELECTION SECTION
        // =========================================================================
        $mform->addElement('header', 'topicscenarioheader', get_string('topicscenarioheader', 'mod_aacurachat'));
        $mform->setExpanded('topicscenarioheader', true);

        $topicoptions = [
            'all' => get_string('topic_all', 'mod_aacurachat'),
        ];
        $scenarios = [];
        $scenariosmeta = [];

        if (class_exists('\\local_aacuracore\\library\\topic_library_manager')) {
            \local_aacuracore\library\topic_library_manager::ensure_seeded();
            $topicrecords = \local_aacuracore\library\topic_library_manager::get_topics($courseid);
            foreach ($topicrecords as $tr) {
                $scopebadge = ($tr->scope === 'course') ? ' [Course Topic]' : '';
                $topicoptions[$tr->topiccode] = $tr->name . $scopebadge;
            }

            $scenariorecords = \local_aacuracore\library\topic_library_manager::get_scenarios_for_topic(null, $courseid, true);
            foreach ($scenariorecords as $sr) {
                $rolebadge = !empty($sr->role_label) ? $sr->role_label : 'Parent';
                $scopelabel = ($sr->scope === 'course') ? 'Course' : 'System';
                $scenarios[$sr->scenariocode] = "{$sr->name} ({$rolebadge})";
                $scenariosmeta[] = [
                    'code' => $sr->scenariocode,
                    'topiccode' => $sr->topiccode ?: 'parent_counseling',
                    'name' => $sr->name,
                    'role_label' => $rolebadge,
                    'scope' => $scopelabel,
                    'description' => shorten_text($sr->description ?? '', 110),
                ];
            }
        } else {
            $topicoptions['parent_counseling'] = 'Parent & Family AAC Counseling';
            $scenarios = [
                'anna' => 'Anna Charles (Autism pre-K concern)',
                'brianna' => 'Brianna Mitchell (Apraxia / social isolation)',
                'cathy' => 'Cathy Fratner (Down Syndrome / app concern)',
                'mary' => 'Mary (Mother of Non-Verbal 6-Year-Old)',
            ];
            $customrecords = $DB->get_records('local_aacuracore_custom_scenarios', null, 'name ASC');
            foreach ($customrecords as $cr) {
                $scenarios[$cr->scenariocode] = $cr->name . ' (Custom Persona)';
            }
            foreach ($scenarios as $code => $label) {
                $scenariosmeta[] = [
                    'code' => $code,
                    'topiccode' => 'parent_counseling',
                    'name' => $label,
                    'role_label' => 'Parent',
                    'scope' => 'System',
                    'description' => '',
                ];
            }
        }

        // Topic Dropdown.
        $mform->addElement('select', 'topiccode', get_string('topiccode', 'mod_aacurachat'), $topicoptions);
        $mform->setDefault('topiccode', 'parent_counseling');
        $mform->setType('topiccode', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('topiccode', 'topiccode', 'mod_aacurachat');

        // Hidden JSON array backing the interactive scenario checklist.
        $mform->addElement('hidden', 'scenariolist', '["anna"]');
        $mform->setType('scenariolist', PARAM_RAW);

        // Interactive Scenario Checklist filtered by Topic.
        $metajson = htmlspecialchars(json_encode($scenariosmeta), ENT_QUOTES, 'UTF-8');
        $checklisthtml = '
        <div class="form-group row fitem" id="fitem_id_scenariolist_ui">
            <div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">
                <label class="d-inline word-break">' . get_string('scenariolist', 'mod_aacurachat') . '</label>
            </div>
            <div class="col-md-9 form-inline align-items-start felement">
                <div class="w-100" id="aacura-scenario-checklist-root" data-scenarios="' . $metajson . '"
                     style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                        <span style="font-size: 0.85rem; color: #475569; font-weight: 600;" id="aacura-checklist-summary">
                            Check one or more scenarios to enable in this activity:
                        </span>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="aacura-select-topic-all" style="padding: 2px 8px; font-size: 0.78rem;">
                                ✓ Select All in Topic
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="aacura-select-topic-First" style="padding: 2px 8px; font-size: 0.78rem;">
                                Single Only
                            </button>
                        </div>
                    </div>
                    <div id="aacura-scenario-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 8px; max-height: 280px; overflow-y: auto; padding: 2px;">
                    </div>
                </div>
            </div>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var root = document.getElementById("aacura-scenario-checklist-root");
            if (!root) return;
            var scenarios = [];
            try {
                scenarios = JSON.parse(root.getAttribute("data-scenarios") || "[]");
            } catch (e) {}

            var hiddenInput = document.querySelector("input[name=\'scenariolist\']");
            var topicSelect = document.querySelector("select[name=\'topiccode\']");
            var defaultSelect = document.querySelector("select[name=\'scenariocode\']");
            var cardsContainer = document.getElementById("aacura-scenario-cards");
            var btnAll = document.getElementById("aacura-select-topic-all");
            var btnSingle = document.getElementById("aacura-select-topic-First");

            function getSelectedCodes() {
                if (!hiddenInput || !hiddenInput.value) return ["anna"];
                try {
                    var parsed = JSON.parse(hiddenInput.value);
                    if (Array.isArray(parsed) && parsed.length > 0) return parsed;
                } catch (e) {}
                return defaultSelect && defaultSelect.value ? [defaultSelect.value] : ["anna"];
            }

            function setSelectedCodes(codes) {
                if (!Array.isArray(codes) || codes.length === 0) {
                    codes = defaultSelect && defaultSelect.value ? [defaultSelect.value] : ["anna"];
                }
                if (hiddenInput) {
                    hiddenInput.value = JSON.stringify(codes);
                }
                if (defaultSelect && defaultSelect.value !== "custom" && codes.indexOf(defaultSelect.value) === -1) {
                    defaultSelect.value = codes[0];
                }
            }

            function renderChecklist() {
                if (!cardsContainer) return;
                var activeTopic = topicSelect ? topicSelect.value : "all";
                var selected = getSelectedCodes();
                cardsContainer.innerHTML = "";

                scenarios.forEach(function(sc) {
                    if (activeTopic !== "all" && sc.topiccode !== activeTopic && selected.indexOf(sc.code) === -1) {
                        return;
                    }
                    var isChecked = selected.indexOf(sc.code) !== -1;
                    var card = document.createElement("label");
                    card.style.cssText = "display:flex;align-items:flex-start;gap:8px;padding:8px 10px;border-radius:6px;cursor:pointer;margin:0;border:1px solid " + (isChecked ? "#4f46e5" : "#e2e8f0") + ";background:" + (isChecked ? "#eef2ff" : "#ffffff") + ";";

                    var cb = document.createElement("input");
                    cb.type = "checkbox";
                    cb.value = sc.code;
                    cb.checked = isChecked;
                    cb.style.marginTop = "4px";
                    cb.addEventListener("change", function() {
                        var curr = getSelectedCodes();
                        if (cb.checked) {
                            if (curr.indexOf(sc.code) === -1) curr.push(sc.code);
                        } else {
                            curr = curr.filter(function(c) { return c !== sc.code; });
                        }
                        setSelectedCodes(curr);
                        renderChecklist();
                    });

                    var body = document.createElement("div");
                    body.style.cssText = "flex:1;min-width:0;line-height:1.3;";
                    var titleRow = document.createElement("div");
                    titleRow.style.cssText = "font-weight:600;font-size:0.86rem;color:#1e293b;display:flex;justify-content:space-between;gap:4px;";
                    var nameSpan = document.createElement("span");
                    nameSpan.textContent = sc.name;
                    var badgeSpan = document.createElement("span");
                    badgeSpan.textContent = sc.role_label + " · " + sc.scope;
                    badgeSpan.style.cssText = "font-size:0.7rem;padding:1px 6px;border-radius:4px;background:#e2e8f0;color:#334155;white-space:nowrap;";
                    titleRow.appendChild(nameSpan);
                    titleRow.appendChild(badgeSpan);

                    body.appendChild(titleRow);
                    if (sc.description) {
                        var desc = document.createElement("div");
                        desc.textContent = sc.description;
                        desc.style.cssText = "font-size:0.76rem;color:#64748b;margin-top:3px;";
                        body.appendChild(desc);
                    }

                    card.appendChild(cb);
                    card.appendChild(body);
                    cardsContainer.appendChild(card);
                });
            }

            if (topicSelect) {
                topicSelect.addEventListener("change", function() {
                    var activeTopic = topicSelect.value;
                    if (activeTopic !== "all") {
                        var topicCodes = scenarios
                            .filter(function(s) { return s.topiccode === activeTopic; })
                            .map(function(s) { return s.code; });
                        if (topicCodes.length > 0) {
                            setSelectedCodes(topicCodes);
                        }
                    }
                    renderChecklist();
                });
            }

            if (btnAll) {
                btnAll.addEventListener("click", function() {
                    var activeTopic = topicSelect ? topicSelect.value : "all";
                    var codes = scenarios
                        .filter(function(s) { return activeTopic === "all" || s.topiccode === activeTopic; })
                        .map(function(s) { return s.code; });
                    setSelectedCodes(codes);
                    renderChecklist();
                });
            }

            if (btnSingle) {
                btnSingle.addEventListener("click", function() {
                    var activeTopic = topicSelect ? topicSelect.value : "all";
                    var matching = scenarios.filter(function(s) { return activeTopic === "all" || s.topiccode === activeTopic; });
                    if (matching.length > 0) {
                        setSelectedCodes([matching[0].code]);
                        renderChecklist();
                    }
                });
            }

            if (defaultSelect) {
                defaultSelect.addEventListener("change", function() {
                    var val = defaultSelect.value;
                    if (val && val !== "custom") {
                        var curr = getSelectedCodes();
                        if (curr.indexOf(val) === -1) {
                            curr.push(val);
                            setSelectedCodes(curr);
                            renderChecklist();
                        }
                    }
                });
            }

            renderChecklist();
        });
        </script>';
        $mform->addElement('html', $checklisthtml);

        // Default Starting Scenario.
        $scenarios['custom'] = 'Activity File Upload (Upload single scenario .json below)';
        $mform->addElement('select', 'scenariocode', get_string('defaultscenario', 'mod_aacurachat'), $scenarios);
        $mform->setDefault('scenariocode', 'anna');
        $mform->setType('scenariocode', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('scenariocode', 'defaultscenario', 'mod_aacurachat');

        // Student Persona Switching Toggle.
        $switchoptions = [
            1 => get_string('allow_persona_switch_yes', 'mod_aacurachat'),
            0 => get_string('allow_persona_switch_no', 'mod_aacurachat'),
        ];
        $mform->addElement('select', 'allow_persona_switch', get_string('allow_persona_switch', 'mod_aacurachat'), $switchoptions);
        $mform->setDefault('allow_persona_switch', 1);
        $mform->setType('allow_persona_switch', PARAM_INT);
        $mform->addHelpButton('allow_persona_switch', 'allow_persona_switch', 'mod_aacurachat');

        // Multi-Scenario Grading Mode.
        $gradingoptions = [
            'single_highest' => get_string('grading_mode_single_highest', 'mod_aacurachat'),
            'single_latest' => get_string('grading_mode_single_latest', 'mod_aacurachat'),
            'all_average' => get_string('grading_mode_all_average', 'mod_aacurachat'),
        ];
        $mform->addElement('select', 'grading_mode', get_string('grading_mode', 'mod_aacurachat'), $gradingoptions);
        $mform->setDefault('grading_mode', 'single_highest');
        $mform->setType('grading_mode', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('grading_mode', 'grading_mode', 'mod_aacurachat');

        // Direct link to Topic & Scenario Library Manager.
        $builderurl = new moodle_url('/local/aacuracore/scenario_builder.php', ['courseid' => $courseid]);
        $buttonhtml = '<div class="form-group row fitem">' .
            '<div class="col-md-3 text-sm-right"><label class="col-form-label"></label></div>' .
            '<div class="col-md-9 form-inline felement">' .
            '<a href="' . $builderurl->out(false) . '" target="_blank" class="btn btn-primary" style="background-color: #4F46E5; border-color: #4F46E5; color: white;">' .
            '🛠️ Open Topic & Scenario Library / Builder' .
            '</a>' .
            '<span class="form-text text-muted ml-2">Manage course topics, build custom scenarios, or upload a scenario JSON file below.</span>' .
            '</div></div>';
        $mform->addElement('html', $buttonhtml);

        $mform->addElement(
            'filepicker',
            'scenariofile',
            get_string('scenariofile', 'mod_aacurachat'),
            null,
            ['maxbytes' => 1024 * 1024, 'accepted_types' => ['.json']]
        );
        $mform->addHelpButton('scenariofile', 'scenariofile', 'mod_aacurachat');

        $mform->addElement(
            'advcheckbox',
            'register_to_library',
            get_string('register_to_library', 'mod_aacurachat'),
            get_string('register_to_library_desc', 'mod_aacurachat')
        );
        $mform->setDefault('register_to_library', 1);

        // =========================================================================
        // 2. PER-ACTIVITY CONVERSATION & AI SETTINGS
        // =========================================================================
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

        $caseoptions = [
            '' => get_string('generation_case_default', 'mod_aacurachat'),
            'chatbot' => get_string('caseuse_chatbot', 'local_aacuracore'),
            'creative' => get_string('caseuse_creative', 'local_aacuracore'),
            'balanced' => get_string('caseuse_balanced', 'local_aacuracore'),
            'precise' => get_string('caseuse_precise', 'local_aacuracore'),
            'exploration' => get_string('caseuse_exploration', 'local_aacuracore'),
            'formal' => get_string('caseuse_formal', 'local_aacuracore'),
            'informal' => get_string('caseuse_informal', 'local_aacuracore'),
        ];
        $mform->addElement('select', 'generation_case', get_string('generation_case', 'mod_aacurachat'), $caseoptions);
        $mform->setDefault('generation_case', '');
        $mform->setType('generation_case', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('generation_case', 'generation_case', 'mod_aacurachat');

        $mform->addElement(
            'textarea',
            'custom_prompt_template',
            get_string('custom_prompt_template', 'mod_aacurachat'),
            ['rows' => 6, 'cols' => 80]
        );
        $mform->setType('custom_prompt_template', PARAM_RAW);
        $mform->addHelpButton('custom_prompt_template', 'custom_prompt_template', 'mod_aacurachat');

        $mform->addElement(
            'textarea',
            'custom_eval_template',
            get_string('custom_eval_template', 'mod_aacurachat'),
            ['rows' => 6, 'cols' => 80]
        );
        $mform->setType('custom_eval_template', PARAM_RAW);
        $mform->addHelpButton('custom_eval_template', 'custom_eval_template', 'mod_aacurachat');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Preprocess data before populating the form.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if (isset($defaultvalues['max_turns']) && !isset($defaultvalues['min_turns'])) {
            $defaultvalues['min_turns'] = $defaultvalues['max_turns'];
        }

        if (empty($defaultvalues['scenariolist'])) {
            $defaultcode = !empty($defaultvalues['scenariocode']) ? $defaultvalues['scenariocode'] : 'anna';
            $defaultvalues['scenariolist'] = json_encode([$defaultcode]);
        }

        if ($this->current && !empty($this->current->coursemodule)) {
            $draftitemid = file_get_submitted_draft_itemid('scenariofile');
            file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_aacurachat',
                'scenariofile',
                0,
                ['subdirs' => 0, 'maxfiles' => 1]
            );
            $defaultvalues['scenariofile'] = $draftitemid;
        }
    }

    /**
     * Enforce validation rules here.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
