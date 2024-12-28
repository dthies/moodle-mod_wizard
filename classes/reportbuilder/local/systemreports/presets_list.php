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

namespace mod_wizard\reportbuilder\local\systemreports;

use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\column;
use mod_wizard\reportbuilder\local\entities\presets;
use core_reportbuilder\system_report;

/**
 * Activity presets list system report class implementation.
 *
 * @package    mod_wizard
 * @copyright  2024 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presets_list extends system_report {
    /** @var \stdClass the course to constrain the report to. */
    protected \stdClass $course;

    /** @var int the usage count for the tool represented in a row, and set by row_callback(). */
    protected int $perrowtoolusage = 0;

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        global $DB, $CFG;

        $this->course = get_course($this->get_context()->instanceid);

        // Our main entity, it contains all the column definitions that we need.
        $entitymain = new presets();
        $entitymainalias = $entitymain->get_table_alias('wizard_preset');

        $this->set_main_table('wizard_preset', $entitymainalias);
        $this->add_entity($entitymain);

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns($entitymain);
        $this->add_filters();
        $this->add_actions();

        // We need id and course in the actions column, without entity prefixes, so add these here.
        // We also need access to the tool usage count in a few places (the usage column as well as the actions column).
        $ti = database::generate_param_name(); // Tool instance param.
        $this->add_base_fields("{$entitymainalias}.id");

        // Scope the report to the course context and include only those tools available to the category.
        $paramprefix = database::generate_param_name();
        $categoryparam = database::generate_param_name();
        $toolstateparam = database::generate_param_name();
        [$insql, $params] = $DB->get_in_or_equal([get_site()->id, $this->course->id], SQL_PARAMS_NAMED, "{$paramprefix}_");
        $wheresql = "";
        $params = array_merge(
            $params,
            [
            ]
        );
        $this->add_base_condition_sql($wheresql, $params);

        $this->set_downloadable(false, get_string('pluginname', 'mod_wizard'));
        $this->set_default_per_page(10);
        $this->set_default_no_results_notice(null);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('mod/wizard:configure', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report.
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     * @param presets $tooltypesentity
     * @return void
     */
    protected function add_columns(presets $tooltypesentity): void {
        $entitymainalias = $tooltypesentity->get_table_alias('wizard_preset');

        $columns = [
            'presets:name',
            'presets:description',
            'presets:timecreated',
            'presets:course',
        ];

        $this->add_columns_from_entities($columns);

        // Attempt to create a dummy actions column, working around the limitations of the official actions feature.
        $this->add_column(new column(
            'actions',
            new \lang_string('actions'),
            $tooltypesentity->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_fields("{$entitymainalias}.id, {$entitymainalias}.name")
            ->add_callback(function ($field, $row) {
                global $COURSE, $OUTPUT;

                // Build and display an action menu.
                $menu = new \action_menu();
                $menu->set_menu_trigger(
                    $OUTPUT->pix_icon('i/moremenu', get_string('actions', 'core')),
                    'btn btn-icon d-flex align-items-center justify-content-center'
                );

                $menu->add(new \action_menu_link(
                    new \moodle_url('/mod/wizard/coursepresetedit.php', ['course' => $COURSE->id, 'typeid' => $row->id]),
                    null,
                    get_string('edit', 'core'),
                    null
                ));

                $menu->add(new \action_menu_link(
                    new \moodle_url('#'),
                    null,
                    get_string('delete', 'core'),
                    null,
                    [
                        'data-action' => 'course-tool-delete',
                        'data-course-tool-id' => $row->id,
                        'data-course-tool-name' => $row->name,
                        'data-course-tool-usage' => $this->perrowtoolusage,
                        'class' => 'text-danger',
                    ],
                ));

                return $OUTPUT->render($menu);
            });

        // Default sorting.
        $this->set_initial_sort_column('presets:name', SORT_ASC);
    }

    /**
     * Add any actions for this report.
     *
     * @return void
     */
    protected function add_actions(): void {
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {

        $this->add_filters_from_entities(['presets:name', 'presets:course']);
    }
}
