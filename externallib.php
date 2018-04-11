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
    
    const APPLICATIONID = 'amzn1.ask.skill.74b90d83-aa64-4fee-abfc-735d33619522';
    
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function alexa_parameters() {
        return new external_function_parameters(array(
                'request' => new external_value(PARAM_TEXT, 'JSON request as a string')
        ));
    }
    
    public static function alexa($request) {        
        $json = json_decode($request, true);
        // Verify request is intended for my service: 
        if (!self::verify_app_id($json["session"]["application"]["applicationId"])) {
            return http_response_code(404);
        }
        
        if ($json["request"]["type"] == 'LaunchRequest') {
            $text = self::launch_request();
        } elseif ($json["request"]["type"] == 'IntentRequest') {
            switch($json["request"]["intent"]["name"]) {
                case "GetSiteAnnouncementsIntent":
                    $text = self::get_site_announcements(1);
                    break;
                case "GetGradesIntent":
                    $text = self::get_grades();
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
    
    private static function verify_app_id($applicationId) {
        return $applicationId = APPLICATIONID;
    }
    
    private static function launch_request() {
        global $SITE;
        
        //return 'Welcome to ' . $SITE->fullname;
        return 'Welcome to As You Learn';
    }
    
    /**
     * Returns description of method return values
     * @return external_single_structure
     */
    public static function alexa_returns() {
        return new external_single_structure(array(
                'version' => new external_value(PARAM_TEXT, 'version number'),
                'response' => new external_single_structure(array(
                        'outputSpeech' => new external_single_structure(array(
                                'type' => new external_value(PARAM_TEXT, 'type of speech output'),
                                'text' => new external_value(PARAM_TEXT, 'text string to speak')
                        )),
                        'shouldEndSession' => new external_value(PARAM_BOOL,'true if responses ends session')
                ))
        ));
    }
    
    /**
     * Function to get front page site announcements
     * @return string site announcements
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
        
        $announcementrecords = $DB->get_records_sql($sql);
        $siteannouncements = '';
        foreach ($announcementrecords as $post) {
            $post->message = strip_tags($post->message);
            $siteannouncements .= $post->subject . '. ' . $post->message . '. ';
        }
        
        return $siteannouncements;
    }
    
    /**
     * Function to get a user's grades
     * @return string grades of courses user is currently taking
     */
    private static function get_grades() {
        global $DB, $USER;
        
        // Parameters validation
        //$params = self::validate_parameters(self::get_site_news_parameters(), array('id' => $id));
        
        // Note: don't forget to validate the context and check capabilities
        
        // Context validation
        // OPTIONAL but in most web service it should present
        $context = context_user::instance($USER->id);
        self::validate_context($context);
        
        // Capability checking
        // OPTIONAL but in most web service it should present
        if (!has_capability('moodle/grade:view', $context)) {
            throw new moodle_exception('nopermissiontoviewgrades');
        }
        
        // Make sure SQL query is best way to get grades - no internal function?
        $sql = 'select mdl_course.fullname, mdl_grade_grades.finalgrade
                from mdl_grade_grades
                inner join mdl_grade_items
                on mdl_grade_grades.itemid = mdl_grade_items.id
                inner join mdl_course
                on mdl_grade_items.courseid = mdl_course.id
                where mdl_grade_grades.userid = :userid
                AND mdl_grade_items.itemtype = "course"
                AND mdl_course.visible = 1
                AND mdl_course.showgrades = 1';
        $params = array('userid' => $USER->id);
        
        $gradesrecords = $DB->get_records_sql($sql, $params);
        $grades = '';
        foreach ($gradesrecords as $grade) {
            $grades .= $grade->fullname . '. ' . $grade->finalgrade . '. ';
        }
        
        return $grades;
    }
}