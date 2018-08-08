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
//require_login();

class account_linking_form extends moodleform {
    public function definition() {
        global $USER;

        $mform = $this->_form;

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
        global $CFG, $USER, $DB;
        $errors = array();
        $serviceshortname = $data['service'];
        
        // Copied from login/token.php/
        // Check if the service exists and is enabled.
        $service = $DB->get_record('external_services', array('shortname' => $serviceshortname, 'enabled' => 1));
        if (empty($service)) {
            // Will throw exception if no token found.
            //throw new moodle_exception('servicenotavailable', 'webservice');
            $errors['pin'] = 'Service not available.';
        }
        
        // Get an existing token or create a new one.
        try {
            $token = external_generate_token_for_current_user($service);
            external_log_token_request($token);
        } catch (moodle_exception $e) {
            // If exception is thrown, log in $errors.
        }

        $pinlength = strlen($data['pin']);
        if (($data['pin'] != 0 && $pinlength < 4) || $pinlength > 4) {
            $errors['pin'] = 'PIN must be 4 digits.';
        }
        return $errors;
    }
}