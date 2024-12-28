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
 * Course presets list management.
 *
 * @module      mod_wizard/presets_list
 * @copyright   2024 Daniel Thies <dethies@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

"use strict";

import ModalForm from 'core_form/modalform';
import {add as addToast} from 'core/toast';
import {getString} from 'core/str';
import {refreshTableContent} from 'core_table/dynamic';
import * as Selectors from 'core_table/local/dynamic/selectors';

/**
 * Initialise module.
 */
export const init = () => {
    document.addEventListener('click', event => {

        const courseToolDelete = event.target.closest('[data-action="course-tool-delete"]');
        if (courseToolDelete) {
            const triggerElement = courseToolDelete.closest('.dropdown').querySelector('.dropdown-toggle');
            const modalForm = new ModalForm({
                formClass: "mod_wizard\\form\\delete_preset",
                args: {id: courseToolDelete.dataset.courseToolId},
                modalConfig: {title: getString('confirm', 'core')},
                returnFocus: courseToolDelete.closest('.dropdown').querySelector('.dropdown-toggle')
            });
            event.preventDefault();
            modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, async() => {
                const tableRoot = triggerElement.closest(Selectors.main.region);
                await addToast(getString(
                    'coursetooldeleted', 'mod_lti', courseToolDelete.dataset.courseToolName),
                    {type: 'success'}
                );
                return await refreshTableContent(tableRoot);
            });
            modalForm.show();
        }
    });
};
