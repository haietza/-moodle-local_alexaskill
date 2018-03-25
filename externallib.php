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
    public static function alexa_parameters() {
        return new external_function_parameters(array(
                'version' => new external_value(PARAM_TEXT),
                'session' => new external_single_structure(array(
                        'new' => new external_value(PARAM_BOOL),
                        'sessionId' => new external_value(PARAM_TEXT),
                        'application' => new external_single_structure(array(
                                'applicationId' => new external_value(PARAM_TEXT)
                        )),
                        'user' => new external_single_structure(array(
                                'userId' => new external_value(PARAM_TEXT)
                        ))
                )),
                'context' => new external_single_structure(array(
                        'AudioPlayer' => new external_single_structure(array(
                                'playerActivity' => new external_value(PARAM_TEXT)
                        )),
                        'Display' => new external_single_structure(array()),
                        'System' => new external_single_structure(array(
                                'application' => new external_single_structure(array(
                                        'applicationId' => new external_value(PARAM_TEXT)
                                )),
                                'user' => new external_single_structure(array(
                                        'userId' => new external_value(PARAM_TEXT)
                                )),
                                'device' => new external_single_structure(array(
                                        'deviceId' => new external_value(PARAM_TEXT),
                                        'supportedInterfaces' => new external_single_structure(array(
                                                'AudioPlayer' => new external_single_structure(array()),
                                                'Display' => new external_single_structure(array(
                                                        'templateVersion' => new external_value(PARAM_TEXT),
                                                        'markupVersion' => new external_value(PARAM_TEXT)
                                                ))
                                        ))
                                )),
                                'apiEndpoint' => new external_value(PARAM_TEXT),
                                'apiAccessToken' => new external_value(PARAM_TEXT)
                        ))
                )),
                'request' => new external_single_structure(array(
                        'type' => new external_value(PARAM_TEXT),
                        'requestId' => new external_value(PARAM_TEXT),
                        'timestamp' => new external_value(PARAM_TEXT),
                        'locale' => new external_value(PARAM_TEXT),
                        'intent' => new external_single_structure(array(
                                'name' => new external_value(PARAM_TEXT),
                                'confirmationStatus' => new external_value(PARAM_TEXT)
                        ), '', VALUE_OPTIONAL)
                ))
        ));
    }
    
    public static function alexa($version, $session, $context, $request) {
        if ($request[type] == 'LaunchRequest') {
            $text = 'Welcome to As You Learn';
        }
        return array(
                'version' => '1.0',
                'response' => array (
                        'outputSpeech' => array(
                                'type' => 'PlainText',
                                'text' => $text
                        ),
                        'shouldEndSession' => true
                )
        );
    }
    
    public static function alexa_returns() {
        //return new external_value(PARAM_TEXT, 'The request type');
        return new external_single_structure(array(
                'version' => new external_value(PARAM_TEXT),
                'response' => new external_single_structure(array(
                        'outputSpeech' => new external_single_structure(array(
                                'type' => new external_value(PARAM_TEXT),
                                'text' => new external_value(PARAM_TEXT)
                        )),
                        'shouldEndSession' => new external_value(PARAM_BOOL)
                ))
        ));
    }
    
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