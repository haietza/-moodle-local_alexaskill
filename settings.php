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
 * External web service template.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

//require_once(__DIR__ . '/classes/task/gradecleanup.php');

// Verify moodle/site:config capability for system context - user can configure site settings.
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_alexaskill', get_string('pluginname', 'local_alexaskill'));
    $settings->add(new admin_setting_configtext('local_alexaskill/alexaskill_applicationid', get_string('alexaskill_applicationid_label', 'local_alexaskill'), get_string('alexaskill_applicationid_desc', 'local_alexaskill'), ''));
    $settings->add(new admin_setting_configtext('local_alexaskill/alexaskill_coursenameregex', get_string('alexaskill_coursenameregex_label', 'local_alexaskill'), get_string('alexaskill_coursenameregex_desc', 'local_alexaskill'), ''));
    $ADMIN->add('localplugins', $settings);
}
