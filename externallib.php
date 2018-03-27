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
                'request' => new external_value(PARAM_TEXT)
        ));
    }
    
    public static function alexa($request) {        
        $json = json_decode($request, true);
        if ($json["request"]["type"] == 'LaunchRequest') {
            $text = self::launch_request();
        } elseif ($json["request"]["type"] == 'IntentRequest') {
            switch($json["request"]["intent"]["name"]) {
                case "GetSiteAnnouncementsIntent":
                    $text = self::get_site_announcements();
                    break;
            }
        } else {
            $text = 'Working on it';
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
    
    private static function launch_request() {
        global $SITE;
        
        //return 'Welcome to ' . $SITE->fullname;
        return 'Welcome to AsULearn';
    }
    
    public static function alexa_returns() {
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
     * The function itself
     * @return string welcome message
     */
    private static function get_site_announcements($id = 1) {        
        global $DB;
        
        // Parameters validation
        //$params = self::validate_parameters(self::get_site_news_parameters(), array('id' => $id));
        
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
        
        $siteannouncements = $DB->get_records_sql($sql);
        foreach ($siteannouncements as $post) {
            $post->message = strip_tags($post->message);
        }
        return $siteannouncements;
    }
}