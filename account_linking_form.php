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

require_once("$CFG->libdir/formslib.php");
 
class account_linking_form extends moodleform {
    public function definition() {
        global $USER;
 
        $mform = $this->_form;
        
        $mform->addElement('text', 'username', get_string('alexaskill_accountlinking_username', 'local_alexaskill'), array('required' => true));
        $mform->setType('username', PARAM_USERNAME);
        if (isloggedin()) {
            $mform->setDefault('username', $USER->username);
        }
        $mform->addHelpButton('username', 'alexaskill_accountlinking_username', 'local_alexaskill');
        
        $mform->addElement('password', 'password', get_string('alexaskill_accountlinking_password', 'local_alexaskill'), array('required' => true));
        $mform->setType('password', PARAM_RAW);
        $mform->addHelpButton('password', 'alexaskill_accountlinking_password', 'local_alexaskill');
        
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
    
    function validation($data, $files) {  
        $errors = array();
        $ch = curl_init();
        $values = array(
                'username' => $data['username'],
                'password' => $data['password'],
                'service' => $data['service']
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
        return $errors;
    }
}