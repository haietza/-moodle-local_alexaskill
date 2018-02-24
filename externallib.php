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

require_once($CFG->libdir . "/externallib.php");

class local_alexaskill_external extends external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hello_world_parameters() {
        // FUNCTIONNAME_parameters() always return an external_function_parameters().
        // The external_function_parameters constructor expects an array of external_description.
        return new external_function_parameters(
                // a external_description can be: external_value, external_single_structure or external_multiple structure
                array('welcomemessage' => new external_value(PARAM_TEXT, 'the welcome message'))
                );
    }
    
    /**
     * The function itself
     * @return string welcome message
     */
    public static function hello_world($welcomemessage = 'Hello ') {
        global $USER;
        
        // Parameters validation
        $params = self::validate_parameters(self::hello_world_parameters(),
                array('welcomemessage' => $welcomemessage));
        
        // Note: don't forget to validate the context and check capabilities
        
        // Context validation
        // OPTIONAL but in most web service it should present
        $context = context_user::instance($USER->id);
        self::validate_context($context);
        
        // Capability checking
        // OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }
        
        return $params['welcomemessage'] . $USER->firstname ;
    }
    
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_word_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }
}