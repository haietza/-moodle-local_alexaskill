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

require_once('../../config.php');
require_once($CFG->dirroot . '/local/alexaskill/account_linking_form.php');

$site = get_site();
$loginsite = get_string('loginsite');

$PAGE->set_url($CFG->wwwroot . '/local/alexaskill/account_linking.php');
$PAGE->set_context(context_system::instance());
$PAGE->navbar->add($loginsite);
$PAGE->set_title("$site->fullname: $loginsite");
$PAGE->set_heading($site->fullname);
$PAGE->set_pagelayout('login');

$mform = new account_linking_form();
$formdata = new stdClass();

// Form processing and displaying is done here
if ($formdata = $mform->get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    $ch = curl_init();
    $values = array(
            'username' => $formdata->username,
            'password' => $formdata->password,
            'service' => $formdata->service
    );
    $options = array(
            //CURLOPT_URL => $CFG->wwwroot . '/login/token.php/',
            CURLOPT_URL => 'https://alexa.haietza.com/login/token.php/',
            CURLOPT_POSTFIELDS => $values,
            CURLOPT_RETURNTRANSFER => 1
    );
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);
    curl_close($ch);
    
    $obj = json_decode($data, true);
    $redirect = $formdata->redirect_uri . '&state=' . $formdata->state . '&access_token=' . $obj['token'] . '&token_type=Bearer';
    header ("Location: $redirect");
} else {
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
    
    // Set default data (if any)
    $mform->set_data($formdata);
    // Displays the form
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}