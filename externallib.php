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
require_once($CFG->dirroot . '/mod/forum/externallib.php');

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
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello"', VALUE_DEFAULT, 'Hello '))
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
        //if (!has_capability('moodle/user:viewdetails', $context)) {
        //    throw new moodle_exception('cannotviewprofile');
        //}
        
        return $params['welcomemessage'] . $USER->firstname;
    }
    
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function hello_world_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }
    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_site_news_parameters() {
        // FUNCTIONNAME_parameters() always return an external_function_parameters().
        // The external_function_parameters constructor expects an array of external_description.
        return new external_function_parameters(
                // a external_description can be: external_value, external_single_structure or external_multiple structure
                array('id' => new external_value(PARAM_INT, 1, VALUE_DEFAULT, 1))
                );
    }
    
    /**
     * The function itself
     * @return string welcome message
     */
    public static function get_site_news($id = 1) {        
        global $DB;
        
        // Parameters validation
        $params = self::validate_parameters(self::get_site_news_parameters(),
                array('id' => $id));
        
        // Note: don't forget to validate the context and check capabilities
        
        // Context validation
        // OPTIONAL but in most web service it should present
        $context = context_module::instance(1);
        self::validate_context($context);
        
        // Capability checking
        // OPTIONAL but in most web service it should present
        if (!has_capability('mod/forum:viewdiscussion', $context)) {
            throw new moodle_exception('nopermissiontoviewpage');
        }
        
        $sql = 'SELECT mdl_forum_posts.id, mdl_forum_posts.subject, mdl_forum_posts.message
                FROM mdl_forum_posts
                WHERE mdl_forum_posts.discussion IN 
                    (SELECT mdl_forum_discussions.id 
                    FROM mdl_forum_discussions 
                    WHERE mdl_forum_discussions.forum = 1) 
                ORDER BY mdl_forum_posts.id DESC';
        
        $sitenews = $DB->get_records_sql($sql);
        foreach ($sitenews as $post) {
            $post->message = strip_tags($post->message);
        }
        return $sitenews;
    }
    
    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_site_news_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                        array(
                                'id' => new external_value(PARAM_INT, 'forum post id'),
                                'subject' => new external_value(PARAM_TEXT, 'forum post subject'),
                                'message' => new external_value(PARAM_TEXT, 'forum post message'),
                        )
                        )
                );
    }
}