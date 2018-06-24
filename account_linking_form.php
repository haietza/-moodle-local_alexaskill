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

require_once("$CFG->libdir/formslib.php");
 
class account_linking_form extends moodleform {
    public function definition() {
        global $CFG, $SITE, $USER;
 
        $mform = $this->_form;
        
        $mform->addElement('text', 'username', get_string('alexaskill_accountlinking_username', 'local_alexaskill'));
        $mform->setType('username', PARAM_USERNAME);
        
        $mform->addElement('password', 'password', get_string('alexaskill_accountlinking_password', 'local_alexaskill'));
        $mform->setType('password', PARAM_RAW);
        
        if (isloggedin()) {
            $mform->setDefault('username', $USER->username);
            $mform->setDefault('password', $USER->password);
        }
        
        $mform->addElement('hidden', 'service');
        $mform->setType('service', PARAM_TEXT);
        $mform->setDefault('service', 'alexa_skill_service');
        
        $mform->addElement('hidden', 'state');
        $mform->setType('state', PARAM_TEXT);
        $mform->setDefault('state', $this->_customdata['state']);
        
        $mform->addElement('hidden', 'client_id');
        $mform->setType('client_id', PARAM_TEXT);
        $mform->setDefault('client_id', $this->_customdata['client_id']);
        
        $mform->addElement('hidden', 'response_type');
        $mform->setType('response_type', PARAM_TEXT);
        $mform->setDefault('response_type', $this->_customdata['response_type']);
        
        $mform->addElement('hidden', 'redirect_uri');
        $mform->setType('redirect_uri', PARAM_URL);
        $mform->setDefault('redirect_uri', $this->_customdata['redirect_uri']);
        
        $this->add_action_buttons(false, get_string('alexaskill_accountlinking_submit', 'local_alexaskill'));
    }
    
    function validation($data, $files) {
        return array();
    }
}