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

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once("$CFG->libdir/formslib.php");

class account_linking_form extends moodleform {
    public function definition() {
        global $USER;

        $mform = $this->_form;

        $name = get_string('alexaskill_accountlinking_username', 'local_alexaskill');
        $options = array('required' => true);
        $mform->addElement('text', 'username', $name, $options);
        $mform->setType('username', PARAM_USERNAME);
        if (isloggedin()) {
            $mform->setDefault('username', $USER->username);
        }
        $mform->addHelpButton('username', 'alexaskill_accountlinking_username', 'local_alexaskill');

        $name = get_string('alexaskill_accountlinking_password', 'local_alexaskill');
        $options = array('required' => true);
        $mform->addElement('password', 'password', $name, $options);
        $mform->setType('password', PARAM_RAW);
        $mform->addHelpButton('password', 'alexaskill_accountlinking_password', 'local_alexaskill');
        
        $name = get_string('alexaskill_accountlinking_pin', 'local_alexaskill');
        $options = array('maxlength' => 4);
        $mform->addElement('password', 'pin', $name, $options);
        $mform->setType('pin', PARAM_INT);
        $mform->addHelpButton('pin', 'alexaskill_accountlinking_pin', 'local_alexaskill');

        $mform->addElement('hidden', 'service');
        $mform->setType('service', PARAM_TEXT);

        $mform->addElement('hidden', 'state');
        $mform->setType('state', PARAM_TEXT);

        $mform->addElement('hidden', 'response_type');
        $mform->setType('response_type', PARAM_TEXT);

        $mform->addElement('hidden', 'redirect_uri');
        $mform->setType('redirect_uri', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('alexaskill_accountlinking_submit', 'local_alexaskill'));
    }

    public function validation($data, $files) {
        global $CFG;
        $errors = array();
        $ch = curl_init();
        $values = array(
                'username' => $data['username'],
                'password' => $data['password'],
                'service' => $data['service']
        );

        $curlurl = $CFG->wwwroot . '/login/token.php/';
        $options = array(
                CURLOPT_URL => $curlurl,
                CURLOPT_POSTFIELDS => $values,
                CURLOPT_RETURNTRANSFER => 1
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);

        $obj = json_decode($result, true);
        
        // errors array displays errors on form field based on errorcode returned in JSON response.
        if (!key_exists('token', $obj)) {
            switch ($obj['errorcode']) {
                case 'enablewsdescription':
                case 'servicenotavailable':
                case 'sitemaintenance':
                case 'noguest':
                case 'usernotconfirmed':
                case 'invalidlogin':
                    $errors['username'] = $obj['error'];
                    break;
                case 'restoredaccountresetpassword':
                case 'passwordisexpired':
                    $errors['password'] = $obj['error'];
                    break;
            }
        }
        
        $pinlength = strlen($data['pin']);
        if (($data['pin'] != 0 && $pinlength < 4) || $pinlength > 4) {
            $errors['pin'] = 'PIN must be 4 digits.';
        }
        return $errors;
    }
}