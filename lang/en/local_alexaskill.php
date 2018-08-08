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
 * Language file.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $SITE, $CFG;

$string['pluginname'] = 'Alexa skill web service';
$string['alexaskill_settings'] = 'Alexa skill settings';
$string['alexaskill_applicationid_label'] = 'Alexa skill application ID';
$string['alexaskill_applicationid_desc'] = 'Enter the application ID for the skill from the Alexa Skills Kit Developer Console.';
$string['alexaskill_redirecturis_label'] = 'Alexa skill redirect URIs';
$string['alexaskill_redirecturis_desc'] = 'Enter the possible redirect URIs for account linking for the skill from the Alexa Skills Kit Developer Console.';
$string['alexaskill_development_label'] = 'Development server';
$string['alexaskill_development_desc'] = 'Select if this is a development server (signature certificate will not be validated).';
$string['alexaskill_coursenameregex_label'] = 'Course regular expression';
$string['alexaskill_coursenameregex_desc'] = 'Enter a regular expression to parse the course fullname for how Alexa should say the course name. The plugin will use the first capturing group from the regular expression.';
$string['alexaskill_courseslotvalues'] = 'Alexa skill course slot values';
$string['alexaskill_accountlinking_username'] = 'Username';
$string['alexaskill_accountlinking_username_help'] = 'Enter your ' . $SITE->fullname . ' username.';
$string['alexaskill_accountlinking_password'] = 'Password';
$string['alexaskill_accountlinking_password_help'] = 'Enter your ' . $SITE->fullname . ' password. <a href="' . $CFG->wwwroot . '/login/forgot_password.php">Forgotten your username or password?</a>';
$string['alexaskill_accountlinking_pin'] = 'Create PIN (optional)';
$string['alexaskill_accountlinking_pin_help'] = 'Create an optional 4-digit PIN if you want to secure your Amazon Alexa access.';
$string['alexaskill_accountlinking_pin_error'] = 'PIN must be 4-digits.';
$string['alexaskill_accountlinking_plugin_error'] = 'Alexa skill plugin has not been installed correctly. Contact the site administrator.';
$string['alexaskill_accountlinking_submit'] = 'Link account';