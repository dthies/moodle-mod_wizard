# Wizard #

The Activity wizard plugin is a utility that allows admins to define
presets for activities for teachers to use as templates. A teacher selects
the preset from the Activity chooser like a regular activity. The plugin
then copies one or more preconfigured activities into the course section.

## Usage ##

After installation 

1. An admin or manager creates a course with template activities.
2. The option 'Course presets' in the secondary navigation on the course
page will open a page to edit presets.
3. Clicking 'Add preset' opens a form to crate a new preset.
4. The admin or manager provides a name and descriptions, selects one or
more activity templates from the course, adds configuration instructions
and saves.
5. Users that should be allowed to use the template need to be given
capabilities 'mod/wizard:use' and 'moodle/backup:backuptargetimport' in
the course where the preset is located. They also need the capability
'mod/wizard:addinstance' in the target course. By default the Editing
teacher role should have these capabilities.
6. When teacher is editing a course opens the activity chooser, the
presets are displayed along with the standard activities.
7. When the teacher selects a preset, an editing form appears for the
teacher to confirm.
8. After saving, the plugin copies the templates to the teacher's course.
9. An extra activity is created that indicates success and displays
instructions for additional configuration of the activities.
10. After configuration is complete, the teacher deletes the activity with
the configuration steps.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/wizard

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2024 Daniel Thies <dethies@gmail.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
