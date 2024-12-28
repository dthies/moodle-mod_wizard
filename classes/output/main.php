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

use context_module;
use moodle_url;
use stdClass;
use renderable;
use templatable;

/**
 * Main view page for Wizard
 *
 * @package    mod_wizard
 * @copyright  2024 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {
    /** @var array $modules Course modules added */
    protected array $modules;

    /**
     * Renderable constructor.
     */
    public function __construct(
        /** @var context_module $context Module contract */
        protected context_module $context,
        /** @var stdClass $instance Module instance */
        protected stdClass $instance
    ) {
        global $COURSE, $OUTPUT;

        $modules = [];
        $modinfo = get_fast_modinfo($COURSE->id);
        if (!empty($instance->modules)) {
            foreach (explode(',', $instance->modules) as $id) {
                if (!empty($modinfo->cms[$id])) {
                    $cm = $modinfo->get_cm($id);
                    $modules[] = [
                        'name' => $cm->name,
                        'icon' => $OUTPUT->pix_icon('monologo', '', $cm->modname, ['class' => 'icon']),
                        'id' => $cm->id,
                        'editurl' => (new moodle_url('/course/modedit.php', ['update' => $cm->id]))->out(),
                    ];
                }
            }
        }
        $this->modules = array_filter($modules);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template($output): array {
        $context = [
            'modules' => $this->modules,
        ];

        return $context;
    }
}
