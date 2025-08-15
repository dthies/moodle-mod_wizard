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

namespace mod_wizard\event;

use core\event\base;

/**
 * The preset_created event class.
 *
 * @package     mod_wizard
 * @category    event
 * @copyright   2024 Daniel Thies <dethies@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preset_created extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = 'wizard_preset';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventpresetcreated', 'mod_wizard');
    }

    /**
     * Creates an instance from the record from db table wizard_preset
     *
     * @param stdClass $record
     * @return self
     */
    public static function create_from_record($record) {
        $event = self::create([
            'objectid' => $record->id,
            'context' => \context_course::instance($record->course),
        ]);
        $event->add_record_snapshot('wizard_preset', $record);

        return $event;
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' created a course preset with "
                . "id '$this->objectid'.";
    }

    /**
     * Returns relevant URL based on the anonymous mode of the response.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/wizard/coursepresetedit.php', [
            'typeid' => $this->objectid,
            'course' => $this->contextinstanceid,
        ]);
    }

    /**
     * Get object mapping for backup restore
     */
    public static function get_objectid_mapping() {
        return [
            'db' => 'wizard_preset',
            'restore' => 'wizard_preset',
        ];
    }
}
