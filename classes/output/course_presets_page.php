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

namespace mod_wizard\output;

use core_reportbuilder\system_report_factory;
use mod_wizard\reportbuilder\local\systemreports\presets_list;
use renderable;
use templatable;

/**
 * The course presets page renderable, containing a page header renderable and a course presets system report.
 *
 * @package    mod_wizard
 * @copyright  2024 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_presets_page implements renderable, templatable {
    /** @var course_external_tools_list the course tools system report instance. */
    protected presets_list $coursetoolsreport;

    /** @var canadd Whether user can add preset */
    protected bool $canadd;

    /**
     * Renderable constructor.
     */
    public function __construct(
        /** @var int $courseid the id of the course. */
        protected int $courseid
    ) {
        global $DB;

        $context = \context_course::instance($courseid);
        $this->courseid = $courseid;

        // Page intro, zero state and 'add new' button.
        $this->canadd = has_capability('mod/wizard:configure', $context);
        $sql = 'SELECT COUNT(1)
                  FROM {wizard_preset}';
        $toolcount = $DB->count_records_sql($sql, ['siteid' => get_site()->id, 'courseid' => $courseid, 'coursevisible' => 0]);

        // Course tools report itself.
        $this->coursetoolsreport = system_report_factory::create(presets_list::class, $context);
    }

    /**
     * Get the course tools list system report.
     *
     * @return course_external_tools_list the course tools list report.
     */
    public function get_table(): presets_list {
        return $this->coursetoolsreport;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template($output): array {
        $context = [
            'table' => $this->get_table()->output(),
        ];
        if ($this->canadd) {
            $context['addlink'] = (new \moodle_url('/mod/wizard/coursepresetedit.php', ['course' => $this->courseid]))->out();
        }
        return $context;
    }
}
