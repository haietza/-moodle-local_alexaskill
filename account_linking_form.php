<?php
use Symfony\Component\DomCrawler\Form;

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
//require_login();

class account_linking_form extends moodleform {
    /**
     * Define account linking form.
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
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
    
    /**
     * Validate account linking form data.
     * Some code taken from login/token.php
     * 
     * @param $data data submitted
     * @param $files files submitted
     * @return $errors array of error message to display on form
     */
     public function validation($data, $files) {
        global $DB;
        $errors = array();
        
        // Some content cop from login/token.php
        // Check if the service exists and is enabled.
        $serviceshortname = $data['service'];
        $service = $DB->get_record('external_services', array('shortname' => $serviceshortname, 'enabled' => 1));
        if (empty($service)) {
            // No external service found, display error message on form. 
            $errors['pin'] = get_string('servicenotavailable', 'webservice');
            return $errors;
        }
        
        // Redirect URI is not valid, display error message and log.
        if (stripos(get_config('local_alexaskill', 'alexaskill_redirecturis'), $data['redirect_uri']) === false) {
            $errors['pin'] = get_string('alexaskill_accountlinking_plugin_error', 'local_alexaskill');
            debugging('Amazon Alexa skill redirect URI does not match configured settings.', NO_DEBUG_DISPLAY);
            return $errors;
        }
        
        // Response type is not valid, display error message and log.
        if ($data['response_type'] != 'token') {
            $errors['pin'] = get_string('alexaskill_accountlinking_plugin_error', 'local_alexaskill');
            debugging('The response_type argument should always be token for implicit grant.', NO_DEBUG_DISPLAY);
            return $errors;
        }
       
        // Make sure token exists or a new one can be created.
        try {
            $token = external_generate_token_for_current_user($service);
            external_log_token_request($token);
        } catch (moodle_exception $e) {
            // If exception is thrown, display error message on form.
            $errors['pin'] = get_string($e->errorcode, $e->module);
            return $errors;
        }
        
        // If user enters PIN (!= 0), make sure it is 4-digits in length.
        $pinlength = strlen($data['pin']);
        if ($data['pin'] != 0 && ($pinlength < 4 || $pinlength > 4 || !is_numeric($data['pin']))) {
            $errors['pin'] = get_string('alexaskill_accountlinking_pin_error', 'local_alexaskill');
            return $errors;
        }
        
        // Make sure user profile field exists.
        $fieldid = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        if (empty($fieldid)) {
            // PIN field has not been configured, display error message and log.
            $errors['pin'] = get_string('alexaskill_accountlinking_plugin_error', 'local_alexaskill');
            debugging('Amazon Alexa skill PIN user profiled field has not been configured. See local/alexaskill/db/install.php.', NO_DEBUG_DISPLAY);
            return $errors;
        }
        
        // Make sure state, reponse_type and redirect_uri were included as query strings.
        if (empty($data['state']) || empty($data['response_type']) || empty($data['redirect_uri'])) {
            $errors['pin'] = '';
            return $errors;
        }
        
        return $errors;
    }
}