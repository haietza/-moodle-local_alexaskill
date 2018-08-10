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
 * Account linking form.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//@codingStandardsIgnoreLine
require_once('../../config.php');
require_once($CFG->dirroot . '/local/alexaskill/account_linking_form.php');
require_once($CFG->dirroot . '/user/externallib.php');
require_login();

$site = get_site();
$loginsite = get_string('loginsite');

$PAGE->set_url($CFG->wwwroot . '/local/alexaskill/account_linking.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->navbar->add($loginsite);
$PAGE->set_title($site->fullname);
$PAGE->set_heading($site->fullname);

$mform = new account_linking_form();

// Form processing and displaying is done here.
if ($fromform = $mform->get_data()) {
    // In this case you process validated data; $mform->get_data() returns data posted in form.
    global $USER, $DB;
    $serviceshortname = $fromform->service;
    
    // Copied from login/token.php
    $service = $DB->get_record('external_services', array('shortname' => $serviceshortname, 'enabled' => 1));
    
    // Get an existing token or create a new one.
    $token = external_generate_token_for_current_user($service);
    external_log_token_request($token);

    $fieldid = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
    
    $userinfodata = new stdClass();
    $userinfodata->userid = $USER->id;
    $userinfodata->fieldid = $fieldid->id;
    $userinfodata->data = $fromform->pin;
    
    if ($fromform->pin != 0) {
        // User has submitted PIN.
        if ($DB->record_exists('user_info_data', array('userid' => $USER->id, 'fieldid' => $fieldid->id))) {
            // User has submitted changed PIN.
            $userpinfieldid = $DB->get_record('user_info_data', array('userid' => $USER->id, 'fieldid' => $fieldid->id), 'id');
            $userinfodata->id = $userpinfieldid->id;
            $DB->update_record('user_info_data', $userinfodata);
        } else {
            // User has submitted new PIN.
            $DB->insert_record('user_info_data', $userinfodata);
        }
    } else {
        if ($DB->record_exists('user_info_data', array('userid' => $USER->id, 'fieldid' => $fieldid->id))) {
            // User has removed PIN.
            $DB->delete_records('user_info_data', array('id' => $userpinfieldid->id));
        }
    }

    $redirect = $fromform->redirect_uri . '#state=' . $fromform->state . '&access_token=' . $token->token . '&token_type=Bearer';
    require_logout();
    header ("Location: $redirect");
} else {
    // This branch is executed if the form is submitted but the data doesn't validate
    // and the form should be redisplayed or on the first display of the form.

    // Set default data (if any).
    $toform = new stdClass();
    if ($mform->is_submitted()) {
        // Form was submitted but data did not validate and form needs to be redisplayed (have to get url params from HTTP_REFERER).
        $urlquery = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
        $params = explode('&', $urlquery);
        $paramvalues = array();
        foreach ($params as $param) {
            $keyvalue = explode('=', $param);
            $paramvalues[$keyvalue[0]] = $keyvalue[1];
        }
        if (isset($keyvalue[2])) {
            $paramvalues['redirect_uri'] = $paramvalues['redirect_uri'] . '=' . $keyvalue[2];
        }

        $toform->state = $paramvalues['state'];
        $toform->service = $paramvalues['client_id'];
        $toform->response_type = $paramvalues['response_type'];
        $toform->redirect_uri = $paramvalues['redirect_uri'];

        unset($urlquery);
        unset($params);
        unset($param);
        unset($keyvalue);
        unset($paramvalues);
    } else {
        // First display of the form, can get params from $_GET.
        $toform->state = $_GET['state'];
        $toform->service = $_GET['client_id'];
        $toform->response_type = $_GET['response_type'];
        $toform->redirect_uri = $_GET['redirect_uri'];
    }

    $mform->set_data($toform);
    echo $OUTPUT->header();
    echo $OUTPUT->box_start('col-xl-6 push-xl-3 m-2-md col-sm-8 push-sm-2');
    echo $OUTPUT->box_start('card');
    echo $OUTPUT->box_start('card-block');
    echo $OUTPUT->box_start('card-title text-xs-center');
    echo $OUTPUT->heading($PAGE->title);
    echo '<hr>';
    echo $OUTPUT->box_end();
    $mform->display();
    echo $OUTPUT->box_end();
    echo $OUTPUT->box_end();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
}