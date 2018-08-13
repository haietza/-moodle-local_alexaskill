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
$string['alexaskill_coursenameregex_desc'] = 'Enter a regular expression to parse the course fullname for how Alexa should say the course name. The plugin will use the first capturing group from the regular expression if there is a match, otherwise it will use the course fullname.';
$string['alexaskill_courseslotvalues'] = 'Alexa skill course slot values';
$string['alexaskill_courseslotvalues_desc'] = '<p>Copy the list of course names below, formatted according to the course regular expression in the skill settings, and paste into the Alexa developer console COURSE slot configuration:</p>';
$string['alexaskill_accountlinking_username'] = 'Username';
$string['alexaskill_accountlinking_username_help'] = 'Enter your ' . $SITE->fullname . ' username.';
$string['alexaskill_accountlinking_password'] = 'Password';
$string['alexaskill_accountlinking_password_help'] = 'Enter your ' . $SITE->fullname . ' password. <a href="' . $CFG->wwwroot . '/login/forgot_password.php">Forgotten your username or password?</a>';
$string['alexaskill_accountlinking_pin'] = 'Create PIN (optional)';
$string['alexaskill_accountlinking_pin_help'] = 'Create an optional 4-digit PIN if you want to secure your Amazon Alexa access.';
$string['alexaskill_accountlinking_pin_error'] = 'PIN must be 4-digits.';
$string['alexaskill_accountlinking_plugin_error'] = 'Alexa skill plugin has not been installed correctly. Contact the site administrator.';
$string['alexaskill_accountlinking_submit'] = 'Link account';
$string['privacy:metadata:alexaskill_alexa:userinfodata'] = 'The Alexa PIN for the user is stored in the user info data table.';
$string['privacy:metadata:alexaskill_alexa'] = 'In order to integrate with the Amazon Alexa skill front-end, user data needs to be exchanged with that service.';
$string['privacy:metadata:alexaskill_alexa:token'] = 'The Alexa skill web service token for the user is sent from Moodle to allow users to access their data from Alexa.';
$string['privacy:metadata:alexaskill_alexa:firstname'] = 'User first names are sent to Alexa in the welcome response.';
$string['privacy:metadata:alexaskill_alexa:coursefullname'] = 'Fullnames of courses in which users are enrolled are sent to Alexa in the course announcements and grades responses.';
$string['privacy:metadata:alexaskill_alexa:forumdiscussionsubject'] = 'Forum discussion subjects from courses in which users are enrolled are sent to Alexa in the course announcement responses.';
$string['privacy:metadata:alexaskill_alexa:forumdiscussionmessage'] = 'Forum discussion messages from courses in which users are enrolled are sent to Alexa in the course announcement responses.';
$string['privacy:metadata:alexaskill_alexa:gradereportoverview'] = 'Overview grade reports for courses in which users are enrolled are sent to Alexa in grade responses.';
$string['privacy:metadata:alexaskill_alexa:usereventsname'] = 'Names of user events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:usereventstimestart'] = 'Start times of user events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:groupeventsname'] = 'Names of group events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:groupeventstimestart'] = 'Start times of group events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:courseeventsname'] = 'Names of course events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:courseeventstimestart'] = 'Start times of course events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:categoryeventsname'] = 'Names of category events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:categoryeventstimestart'] = 'Start times of category events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:siteeventsname'] = 'Names of site events for users are sent to Alexa in due date responses.';
$string['privacy:metadata:alexaskill_alexa:siteeventstimestart'] = 'Start times of site events for users are sent to Alexa in due date responses.';