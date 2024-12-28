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
 * Library of interface functions and constants.
 *
 * @package     mod_wizard
 * @copyright   2024 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function wizard_supports($feature) {
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_OTHER;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_wizard into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_wizard_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function wizard_add_instance($moduleinstance, $mform = null) {
    global $COURSE, $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->visible = 0;
    $type = $DB->get_record('wizard_preset', ['id' => $moduleinstance->type]);
    $course = get_course($moduleinstance->course);
    require_capability('mod/wizard:use', context_course::instance($type->course));
    if ($section = $DB->get_record('course_sections', ['course' => $COURSE->id, 'section' => $moduleinstance->section])) {
        $modules = [];
        foreach (explode(',', $type->modules) as $cmid) {
            if ($cm = get_coursemodule_from_id(null, $cmid)) {
                $newcm = wizard_duplicate_module($COURSE, $cm, $section->id, false);
                $modules[] = $newcm->id;
                moveto_module($newcm, $section, $moduleinstance->beforemod);
            }
        }
        $moduleinstance->modules = implode(',', $modules);
    }

    $id = $DB->insert_record('wizard', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_wizard in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_wizard_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function wizard_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('wizard', $moduleinstance);
}

/**
 * Removes an instance of the mod_wizard from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function wizard_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('wizard', ['id' => $id]);
    if (!$exists) {
        return false;
    }

    $DB->delete_records('wizard', ['id' => $id]);

    return true;
}

/**
 * Extends the global navigation tree by adding mod_wizard nodes if there is a relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $wizardnode An object representing the navigation tree node.
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function wizard_extend_navigation($wizardnode, $course, $module, $cm) {
}

/**
 * Extends the settings navigation with the mod_wizard settings.
 *
 * This function is called when the context for the page is a mod_wizard module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@see settings_navigation}
 * @param navigation_node $wizardnode {@see navigation_node}
 */
function wizard_extend_settings_navigation($settingsnav, $wizardnode = null) {
}

/**
 * Extend the course navigation with an "Activity presets" link which redirects to a list of all presets available for course use.
 *
 * @param settings_navigation $navigation The settings navigation object
 * @param stdClass $course The course
 * @param stdclass $context Course context
 * @return void
 */
function mod_wizard_extend_navigation_course($navigation, $course, $context): void {
    if (has_capability('mod/wizard:configure', context_system::instance())) {
        $url = new moodle_url('/mod/wizard/coursepresets.php', ['id' => $course->id]);
        $settingsnode = navigation_node::create(
            get_string('coursepresets', 'mod_wizard'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'coursepresets',
            new pix_icon('i/settings', '')
        );
        $navigation->add_node($settingsnode);
    }
}

/**
 * Return all content items which can be added to any course.
 *
 * @param \core_course\local\entity\content_item $defaultmodulecontentitem
 * @return array the array of content items.
 */
function mod_wizard_get_all_content_items(\core_course\local\entity\content_item $defaultmodulecontentitem): array {
    global $DB, $OUTPUT, $CFG;

    $types = [];

    foreach ($DB->get_records('wizard_preset') as $preset) {
        $type           = new stdClass();
        $type->id       = $preset->id;
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->name     = 'wizard_preset_' . $preset->id;
        // Clean the name. We don't want tags here.
        $type->title    = clean_param($preset->name, PARAM_NOTAGS);
        $trimmeddescription = trim($preset->description ?? '');
        $type->help = '';
        if ($trimmeddescription != '') {
            // Clean the description. We don't want tags here.
            $type->help     = clean_param($trimmeddescription, PARAM_NOTAGS);
            $type->helplink = get_string('modulename_link', 'mod_wizard');
        }
        if (empty($preset->icon)) {
            $type->icon = $OUTPUT->pix_icon('monologo', '', 'wizard', ['class' => 'icon']);
        } else {
            $type->icon = html_writer::empty_tag('img', ['src' => $preset->icon, 'alt' => $preset->name, 'class' => 'icon']);
        }
        $type->link = new moodle_url('/course/modedit.php', ['add' => 'wizard', 'return' => 0, 'type' => $preset->id]);

        $types[] = new \core_course\local\entity\content_item(
            $type->id,
            $type->name,
            new \core_course\local\entity\string_title($type->title),
            $type->link,
            $type->icon,
            $type->help,
            $defaultmodulecontentitem->get_archetype(),
            $defaultmodulecontentitem->get_component_name(),
            $defaultmodulecontentitem->get_purpose()
        );
    }

    return $types;
}

/**
 * Return the preconfigured tools which are configured for inclusion in the activity picker.
 *
 * @param \core_course\local\entity\content_item $defaultmodulecontentitem reference to the content item for the wizard
 * @param \stdClass $user the user object, to use for cap checks if desired.
 * @param stdClass $course the course to scope items to.
 * @return array the array of content items.
 */
function wizard_get_course_content_items(
    \core_course\local\entity\content_item $defaultmodulecontentitem,
    \stdClass $user,
    \stdClass $course
) {
    global $DB, $OUTPUT, $CFG;

    $types = [];

    foreach ($DB->get_records('wizard_preset') as $preset) {
        $context = context_course::instance($preset->course);
        if (
            !has_capability('mod/wizard:use', $context)
            || !has_capability('moodle/backup:backuptargetimport', $context)
        ) {
            continue;
        }
        $type           = new stdClass();
        $type->id       = $preset->id;
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->name     = 'wizard_preset_' . $preset->id;
        // Clean the name. We don't want tags here.
        $type->title    = clean_param($preset->name, PARAM_NOTAGS);
        $trimmeddescription = trim($preset->description ?? '');
        $type->help = '';
        if ($trimmeddescription != '') {
            // Clean the description. We don't want tags here.
            $type->help     = clean_param($trimmeddescription, PARAM_NOTAGS);
            $type->helplink = get_string('modulename_link', 'mod_wizard');
        }
        if (empty($preset->icon)) {
            $type->icon = $OUTPUT->pix_icon('monologo', '', 'wizard', ['class' => 'icon']);
        } else {
            $type->icon = html_writer::empty_tag('img', ['src' => $preset->icon, 'alt' => $preset->name, 'class' => 'icon']);
        }
        $type->link = new moodle_url('/course/modedit.php', [
            'add' => 'wizard',
            'return' => 0,
            'type' => $preset->id,
            'course' => $course->id,
        ]);

        $types[] = new \core_course\local\entity\content_item(
            $type->id,
            $type->name,
            new \core_course\local\entity\string_title($type->title),
            $type->link,
            $type->icon,
            $type->help,
            $defaultmodulecontentitem->get_archetype(),
            $defaultmodulecontentitem->get_component_name(),
            $defaultmodulecontentitem->get_purpose()
        );
    }

    return $types;
}

/**
 * Api to duplicate a module.
 *
 * This is duplicated from core duplicate_module which does not work copying
 * between courses.
 *
 * @param object $course course object.
 * @param object $cm course module object to be duplicated.
 * @param int $sectionid section ID new course module will be placed in.
 * @param bool $changename updates module name with text from duplicatedmodule lang string.
 * @since Moodle 2.8
 *
 * @throws Exception
 * @throws coding_exception
 * @throws moodle_exception
 * @throws restore_controller_exception
 *
 * @return cm_info|null cminfo object if we sucessfully duplicated the mod and found the new cm.
 */
function wizard_duplicate_module($course, $cm, ?int $sectionid = null, bool $changename = true): ?cm_info {
    global $CFG, $DB, $USER;
    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->libdir . '/filelib.php');

    $a          = new stdClass();
    $a->modtype = get_string('modulename', $cm->modname);
    $a->modname = format_string($cm->name);

    if (!plugin_supports('mod', $cm->modname, FEATURE_BACKUP_MOODLE2)) {
        throw new moodle_exception('duplicatenosupport', 'error', '', $a);
    }

    // Backup the activity.

    $bc = new backup_controller(
        backup::TYPE_1ACTIVITY,
        $cm->id,
        backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO,
        backup::MODE_IMPORT,
        $USER->id
    );

    $backupid       = $bc->get_backupid();
    $backupbasepath = $bc->get_plan()->get_basepath();

    $bc->execute_plan();

    $bc->destroy();

    // Restore the backup immediately.

    $rc = new restore_controller(
        $backupid,
        $course->id,
        backup::INTERACTIVE_NO,
        backup::MODE_IMPORT,
        $USER->id,
        backup::TARGET_CURRENT_ADDING
    );

    // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
    $plan = $rc->get_plan();
    $groupsetting = $plan->get_setting('groups');
    if (empty($groupsetting->get_value())) {
        $groupsetting->set_value(true);
    }

    $cmcontext = context_module::instance($cm->id);
    if (!$rc->execute_precheck()) {
        $precheckresults = $rc->get_precheck_results();
        if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($backupbasepath);
            }
        }
    }

    $rc->execute_plan();

    // Now a bit hacky part follows - we try to get the cmid of the newly
    // restored copy of the module.
    $newcmid = null;
    $tasks = $rc->get_plan()->get_tasks();
    foreach ($tasks as $task) {
        if (is_subclass_of($task, 'restore_activity_task')) {
            if ($task->get_old_contextid() == $cmcontext->id) {
                $newcmid = $task->get_moduleid();
                break;
            }
        }
    }

    $rc->destroy();

    if (empty($CFG->keeptempdirectoriesonbackup)) {
        fulldelete($backupbasepath);
    }

    // If we know the cmid of the new course module, let us move it
    // right below the original one. otherwise it will stay at the
    // end of the section.
    if ($newcmid) {
        // Proceed with activity renaming before everything else. We don't use APIs here to avoid
        // triggering a lot of create/update duplicated events.
        $newcm = get_coursemodule_from_id($cm->modname, $newcmid, $course->id);
        if ($changename) {
            // Add ' (copy)' language string postfix to duplicated module.
            $newname = get_string('duplicatedmodule', 'moodle', $newcm->name);
            set_coursemodule_name($newcm->id, $newname);
        }

        $section = $DB->get_record('course_sections', ['id' => $sectionid ?? $cm->section, 'course' => $course->id]);
        if (isset($sectionid)) {
            moveto_module($newcm, $section);
        } else {
            $modarray = explode(",", trim($section->sequence));
            $cmindex = array_search($cm->id, $modarray);
            if ($cmindex !== false && $cmindex < count($modarray) - 1) {
                moveto_module($newcm, $section, $modarray[$cmindex + 1]);
            }
        }

        // Update calendar events with the duplicated module.
        // The following line is to be removed in MDL-58906.
        course_module_update_calendar_events($newcm->modname, null, $newcm);

        // Copy permission overrides to new course module.
        $newcmcontext = context_module::instance($newcm->id);
        $overrides = $DB->get_records('role_capabilities', ['contextid' => $cmcontext->id]);
        foreach ($overrides as $override) {
            $override->contextid = $newcmcontext->id;
            unset($override->id);
            $DB->insert_record('role_capabilities', $override);
        }

        // Copy locally assigned roles to new course module.
        $overrides = $DB->get_records('role_assignments', ['contextid' => $cmcontext->id]);
        foreach ($overrides as $override) {
            $override->contextid = $newcmcontext->id;
            unset($override->id);
            $DB->insert_record('role_assignments', $override);
        }

        // Trigger course module created event. We can trigger the event only if we know the newcmid.
        $newcm = get_fast_modinfo($course->id)->get_cm($newcmid);
        $event = \core\event\course_module_created::create_from_cm($newcm);
        $event->trigger();
    }

    return isset($newcm) ? $newcm : null;
}

/**
 * Pre-delete course hook to cleanup any records with references to the deleted course.
 *
 * @param stdClass $course The deleted course
 */
function mod_wizard_pre_course_delete($course) {
    global $DB;

    $context = context_course::instance($course->id);
    $fs = get_file_storage();
    foreach ($fs->get_area_files($context->id, 'mod_wizard', 'postscript', false, 'id ASC') as $file) {
        $file->delete();
    }

    $DB->delete_records('wizard_preset', ['course' => $course->id]);
}
