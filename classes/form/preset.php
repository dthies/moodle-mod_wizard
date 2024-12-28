<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The main mod_wizard configuration form.
 *
 * @package     mod_wizard
 * @copyright   2024 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_wizard\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use context_course;
use moodleform;

/**
 * Module instance settings form.
 *
 * @package     mod_wizard
 * @copyright   2024 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preset extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id', $this->customdata->id ?? 0);
        $mform->setType('id', PARAM_INT);

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('presetname', 'mod_wizard'), ['size' => '64']);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'wizardname', 'mod_wizard');

        $mform->addElement('textarea', 'description', get_string('description', 'mod_wizard'), ['size' => '12']);
        $mform->addHelpButton('description', 'description', 'mod_wizard');
        $mform->addElement('hidden', 'course', $this->_customdata->course);
        $mform->setType('course', PARAM_INT);

        // Section to configure activities.
        $mform->addElement('header', 'activitiesfieldset', get_string('activitiesfieldset', 'mod_wizard'));
        $modules = get_fast_modinfo($this->_customdata->course)->cms;
        $modules = ['' => get_string('choose')] + array_column($modules, 'name', 'id');

        $modulearray = [
             $mform->createElement('select', 'module', get_string('module', 'mod_wizard'), $modules),
        ];
        if (!empty($this->_customdata->id)) {
            $preset = $DB->get_record('wizard_preset', ['id' => $this->_customdata->id]);
            $repeatno = count(explode(',', $preset->modules)) + 2;
        } else {
            $repeatno = 5;
        }

        $moduleoptions = [];
        $this->repeat_elements(
            $modulearray,
            $repeatno,
            $moduleoptions,
            'option_repeats',
            'option_add_fields',
            2,
            null,
            true,
            'delete'
        );

        $mform->addElement('header', 'finalfieldset', get_string('finalfieldset', 'mod_wizard'));
        $mform->addElement('editor', 'postscript_editor', get_string('postscript', 'mod_wizard'), null, [
            'subdirs' => 1,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => -1,
            'context' => context_course::instance($this->_customdata->course),
            'noclean' => 1,
            'trusttext' => 0,
        ]);
        $mform->setType('postscript_editor', PARAM_RAW);
        $mform->addHelpButton('postscript_editor', 'postscript', 'mod_wizard');

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
