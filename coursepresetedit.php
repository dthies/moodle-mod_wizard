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
 * Page allowing admins to create activity presets
 *
 * @package    mod_wizard
 * @copyright  2024 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;

require_once('../../config.php');

if ($typeid = optional_param('typeid', null, PARAM_INT)) {
    $preset = $DB->get_record('wizard_preset', ['id' => $typeid]);
    $courseid = $preset->course;
} else {
    $courseid = required_param('course', PARAM_INT);
}

// Permissions etc.
require_login($courseid, false);
require_capability('mod/wizard:configure', context_system::instance());

// Page setup.
$url = new moodle_url('/mod/wizard/coursepresetedit.php', ['courseid' => $courseid]);
$pageheading = !empty($typeid) ? get_string('coursepresetedit', 'mod_wizard', $preset->name) :
    get_string('coursepresetadd', 'mod_wizard');

$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($pageheading);
$PAGE->set_secondary_active_tab('coursetools');
$PAGE->add_body_class('limitedwidth');

$form = new \mod_wizard\form\preset($url, (object)['id' => $typeid, 'iscoursetool' => true, 'course' => $courseid]);
$options = [
    'trusttext' => 1,
    'subdirs' => 1,
    'maxfiles' => 20,
    'maxbytes' => 1000000000,
    'context' => context_course::instance($courseid),
];
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/wizard/coursepresets.php', ['id' => $courseid]));
} else if ($data = $form->get_data()) {
    $data->modules = implode(',', array_filter($data->module));
    require_sesskey();
    $context = context_course::instance($data->course);
    $data->timemodified = time();

    if (empty($data->id)) {
        $data->timecreated = $data->timemodified;
        $data->id = $DB->insert_record('wizard_preset', $data);
    }
    $data = file_postupdate_standard_editor($data, 'postscript', $options, $context, 'mod_wizard', 'postscript', $data->id);
    $DB->update_record('wizard_preset', $data);
    $redirecturl = new moodle_url('/mod/wizard/coursepresets.php', ['id' => $courseid]);
    $notice = get_string('coursepresetaddsuccess', 'mod_wizard', $data->name);

    redirect($redirecturl, $notice, 0, notification::NOTIFY_SUCCESS);
}

// Display the form.
echo $OUTPUT->header();
echo $OUTPUT->heading($pageheading);

if (!empty($typeid)) {
    $preset->module = explode(',', $preset->modules);

    $preset = file_prepare_standard_editor(
        $preset,
        'postscript',
        $options,
        context_course::instance($preset->course),
        'mod_wizard',
        'postscript',
        $typeid
    );

    $form->set_data($preset);
}
$form->display();

echo $OUTPUT->footer();
