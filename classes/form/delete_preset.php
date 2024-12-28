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

namespace mod_wizard\form;

use context;
use context_course;
use context_system;
use core_form\dynamic_form;
use core_user;
use mod_wizard\motion;
use moodle_exception;
use moodle_url;

/**
 * Form to delete preset
 *
 * @package     mod_wizard
 * @copyright   2024 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_preset extends dynamic_form {
    /**
     * Form definition
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {

        return context_system::instance();
    }

    /**
     * Checks if current user has access to this form, otherwise throws exception
     *
     */
    protected function check_access_for_dynamic_submission(): void {
        global $USER;

        $data = (object)$this->_ajaxformdata;
        $context = $this->get_context_for_dynamic_submission();
        require_capability('mod/wizard:configure', $context);
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * @return mixed
     */
    public function process_dynamic_submission() {
        global $DB;

        $id = $this->_ajaxformdata['id'];

        $fs = get_file_storage();
        $preset = $DB->get_record('wizard_preset', ['id' => $id]);
        $files = $fs->get_area_files(
            context_course::instance($preset->course)->id,
            'mod_wizard',
            'postscript',
            $preset->id,
            'id ASC'
        );
        foreach ($files as $file) {
            $file->delete();
        }

        $DB->delete_records('wizard_preset', ['id' => $id]);

        return '';
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $mform = $this->_form;

        $context = $this->get_context_for_dynamic_submission();
        $id = $this->_ajaxformdata['id'];
        $mform->setDefault('id', $id);

        $preset = $DB->get_record('wizard_preset', ['id' => $id]);

        $mform->addElement('html', get_string('confirmdelete', 'mod_wizard', $preset->name));
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $context = $this->get_context_for_dynamic_submission();

        $url = new moodle_url('/mod/wizard/coursepresets.php', ['id' => $context->instanceid]);

        return $url;
    }
}
