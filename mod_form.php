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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_wizard
 * @copyright   2024 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wizard_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('wizardname', 'mod_wizard'), ['size' => '64']);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'wizardname', 'mod_wizard');

        $mform->addElement('hidden', 'beforemod');
        $mform->setType('beforemod', PARAM_INT);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_INT);

        $this->standard_intro_elements();

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Prepares the form before data are set
     *
     * @param  array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $DB, $USER;

        $defaultvalues['visible'] = 0;
        $defaultvalues['showdescription'] = 1;
        if ($type = optional_param('type', 0, PARAM_INT)) {
            $preset = $DB->get_record('wizard_preset', ['id' => $type]);
            require_capability('mod/wizard:use', context_course::instance($preset->course));
            $defaultvalues['name'] = $preset->name;

            // Manually load editor files for preset file storage.
            $draftid = file_get_unused_draft_itemid();
            $fs = get_file_storage();
            $files = $fs->get_area_files(context_course::instance($preset->course)->id, 'mod_wizard', 'postscript', $type);
            foreach ($files as $file) {
                if (!$file->is_directory()) {
                    $fs->create_file_from_storedfile([
                        'component' => 'user',
                        'contextid' => \context_user::instance($USER->id)->id,
                        'filearea' => 'draft',
                        'filename' => $file->get_filename(),
                        'filepath' => $file->get_filepath(),
                        'itemid' => $draftid,
                    ], $file);
                }
            }
            $preset->postscript = file_rewrite_pluginfile_urls(
                $preset->postscript,
                'draftfile.php',
                \context_user::instance($USER->id)->id,
                'user',
                'draft',
                $draftid
            );
            $defaultvalues['introeditor'] = [
                'text' => $preset->postscript,
                'format' => $preset->postscriptformat,
                'itemid' => $draftid,
            ];
        }
    }
}
