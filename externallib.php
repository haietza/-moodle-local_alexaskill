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
require_once($CFG->dirroot . '/calendar/externallib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/grade/report/overview/classes/external.php');

class local_alexaskill_external extends external_api {
    
    static $response;
        
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function alexa_parameters() {
        return new external_function_parameters(array(
                'request' => new external_value(PARAM_TEXT, 'JSON request as a string'),
                'token' => new external_value(PARAM_TEXT, 'Invalid token status', VALUE_OPTIONAL)
        ));
    }
    
    public static function alexa($request, $token = '') {  
        self::$response = array(
                'version' => '1.0',
                'response' => array (
                        'outputSpeech' => array(
                                'type' => 'PlainText',
                                'text' => 'Hello'
                        ),
                        'shouldEndSession' => true
                )
        );
        
        $json = json_decode($request, true);
   /*
        // Check the signature of the request
        if (!self::validate_signature($_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE'], $request)) {
            error_log('invalid signature');
            return http_response_code(400);
        }
        
        // Check the request timestamp.
        if (!self::verify_timestamp($json['request']['timestamp'])) {
            error_log('invalid timestamp');
            return http_response_code(400);
        }*/
        
        // Verify request is intended for my service.
        if (!self::verify_app_id($json['session']['application']['applicationId'])) {
            error_log('invalid app id');
            return http_response_code(400);
        }
        
        // Process request.
        if ($json['request']['type'] == 'LaunchRequest') {
            self::launch_request();
        } elseif ($json['request']['type'] == 'IntentRequest') {
            switch($json['request']['intent']['name']) {
                case "GetSiteAnnouncementsIntent":
                    self::get_site_announcements(1);
                    break;
                case "GetGradesIntent":
                    // accessToken may exist even if invalid. Need to see
                    if ($token !== 'valid') {
                        self::verify_account_linking('get grades');
                        return self::$response;
                    }
                    self::get_grades();
                    break;
                case "GetDueDatesIntent":
                    if ($token !== 'valid') {
                        self::verify_account_linking('get due dates');
                        return self::$response;
                    }
                    self::get_due_dates();
                    break;
                case "AMAZON.CancelIntent":
                case "AMAZON.StopIntent":
                    self::$response['response']['outputSpeech']['text'] = 'Your session has ended. Good bye!';
                    break;
                case "AMAZON.HelpIntent":
                    self::$response['response']['outputSpeech']['text'] = 'You can get site announcements, grades, or due dates. Which would you like?';
                    self::$response['response']['shouldEndSession'] = false;
                    break;
            }
        } elseif ($json['request']['type'] == 'SessionEndedRequest') {
            self::session_ended_request($json['request']['error']['message']);
        }
        
        return self::$response;
    }
    
    /**
     * Returns description of method return values.
     *
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
                        'shouldEndSession' => new external_value(PARAM_BOOL,'true if responses ends session'),
                        'card' => new external_single_structure(array(
                                'type' => new external_value(PARAM_TEXT, 'type of card')
                        ), 'card for app', VALUE_OPTIONAL)
                ))
        ));
    }
    
    /**
     * Function to verify appliation ID.
     * 
     * @param string $applicationId
     * @return true if valid
     */
    private static function verify_app_id($applicationId) {
        return $applicationId == get_config('local_alexaskill', 'alexaskill_applicationid');
    }
    
    /**
     * Function to parse ISO 8601 formatted string to verify within 150 seconds.
     * 
     * @param string $timestamp
     * @return boolean timestamp is valid
     */
    private static function verify_timestamp($timestamp) {
        return (time() - strtotime($timestamp)) < 150;
    }
    
    /**
     * Function to validate the signature.
     * Thanks to https://github.com/craigh411/alexa-request-validator
     * 
     * @param string $certurl
     * @param array $json
     * @return boolean signature is valid
     */
    private static function validate_signature($certurl, $signature, $request) {
        global $CFG;
        
        // The protocol is equal to https (case insensitive).
        $protocol = strtolower(parse_url($certurl, PHP_URL_SCHEME));
        
        // The hostname is equal to s3.amazonaws.com (case insensitive).
        $hostname = strtolower(parse_url($certurl, PHP_URL_HOST));
        
        // The path starts with /echo.api/ (case sensitive).
        $path = substr(parse_url($certurl, PHP_URL_PATH), 0, 10);
        
        //If a port is defined in the URL, the port is equal to 443.
        $port = parse_url($certurl, PHP_URL_PORT);
        
        // Verify signature URL.
        if ($protocol != 'https'
                || $hostname != 's3.amazonaws.com'
                || $path != '/echo.api/'
                || ($port != 443 && $port != NULL)) {
                    return false;
        }
        
        // Determine if we need to download a new Signature Certificate Chain from Amazon
        //$md5pem = '/var/cache/amazon_echo/' . md5($certurl) . '.pem';
        // If we haven't received a certificate with this URL before,
        // store it as a cached copy
        //if (!file_exists($md5pem)) {
        //file_put_contents($md5pem, file_get_contents($certurl));
        //}
        
        // Download PEM-enoded X.509 certificate chain.
        $cert = file_get_contents($certurl);
        
        // Parse certificate.
        $parsedcert = openssl_x509_parse($cert);
        if (!$parsedcert) {
            return false;
        }
        
        // Check that signing certificate has not expired.
        $validFrom = $parsedcert['validFrom_time_t'];
        $validTo = $parsedcert['validTo_time_t'];
        $time = time();
        if (!($validFrom <= $time && $time <= $validTo)) {
            return false;
        }
        
        // Check SAN.
        if (strpos($parsedcert['extensions']['subjectAltName'], 'echo-api.amazon.com') === false) {
            return false;
        }
        
        // Check all certs combine to trusted root CA.
        $decodedsignature = base64_decode($signature);
        $verifysig = openssl_verify($request, $decodedsignature, $cert, OPENSSL_ALGO_SHA1);
        if ($verifysig != 1) {
            return false;
        }
        
        // Extract public key from signing certificate.
        $publickey = openssl_pkey_get_public($cert);
        
        // Base64-decode the Signature header on request to obtain encrypted signature.
        openssl_public_decrypt($decodedsignature, $decryptedsignature, $publickey);
        
        // Generate SHA-1 hash from full HTTPS request body to produce derived hash value.
        $responsehash = sha1($request);
        $decryptedsignature = bin2hex($decryptedsignature);
        
        // Compare asserted and derived hashes for matching.
        if (substr($decryptedsignature, 30) !== $responsehash) {
            return false;
        }

        return true;
    }
    
    /**
     * Function to get welcome message.
     *
     * @return string welcome
     */
    private static function launch_request() {
        global $SITE;
        
        self::$response['response']['outputSpeech']['text'] = 'Welcome to ' . $SITE->fullname . '. You can get site announcements, grades, or due dates. Which would you like?';
        self::$response['response']['shouldEndSession'] = false;
    }
    
    private static function session_ended_request($error) {
        if ($error) {
            self::$response['response']['outputSpeech']['text'] = 'Your session has ended because ' . $error;
        } else {
            self::$response['response']['outputSpeech']['text'] = 'Your session has ended. Good bye!';
        }
    }
    
    private static function verify_account_linking($task) {
        global $SITE;
        self::$response['response']['card']['type'] = 'LinkAccount';
        self::$response['response']['outputSpeech']['text'] = 'You must have an account on ' . $SITE->fullname . ' to '
                . $task . '. Please use the Alexa app to link your Amazon account with your ' . $SITE->fullname . ' account.';
        return;
    }
    
    /**
     * Function to get front page site announcements.
     * 
     * @return string site announcements
     */
    private static function get_site_announcements($id = 1) {        
        global $DB;
        
        $discussions = $DB->get_records('forum_discussions', array('course' => 1), 'id DESC', 'id');
        $forumposts = array();
        foreach ($discussions as $discussion) {
            $forumposts[] = mod_forum_external::get_forum_discussion_posts($discussion->id);
        }
        
        $siteannouncements = '';
        $count = 0;
        
        // Get course setting for number of announcements.
        // If over 5, limit to 5 initially for usability.
        $limit = $DB->get_field('course', 'newsitems', array('id' => $id));
        if ($limit > 5) {
            $limit = 5;
        }
        
        foreach ($forumposts as $forumpost) {
            foreach ($forumpost['posts'] as $post) {
                // Only return $limit number of original posts (not replies).
                if ($post->parent == 0 && $count <= $limit) {
                    $message = strip_tags($post->message);
                    $siteannouncements .= $post->subject . '. ' . $message . '. ';
                    $count++;
                }
            }
        }
        
        if ($siteannouncements == '') {
            $siteannouncements = 'There are no site announcements.';
        }
        
        self::$response['response']['outputSpeech']['text'] = $siteannouncements;
        
        return;
    }
    
    /**
     * Function to get a user's grades.
     * 
     * @return string grades of courses user is currently taking
     */
    private static function get_grades() {
        global $DB, $USER;
        
        $gradereport = gradereport_overview_external::get_course_grades($USER->id);
        $coursenames = array();
        $grades = '';
        foreach($gradereport['grades'] as $grade) {
            $course = $DB->get_record('course', array('id' => $grade['courseid']), 'fullname');
            $coursename = array();
            if (preg_match('/([A-Z][A-Z|\s][A-Z][0-9]{4}-[0-9]{3})_([^(]*)/', $course->fullname, $coursename) == 1) {
                // [0] will be course number and name, [1] will be course number, [2] will be course name.
                $coursenames[$grade['courseid']] = $coursename[0];
            } else {
                $coursenames[$grade['courseid']] = $course->fullname;
            }
            
            $grades .= 'Your grade in ' . $coursenames[$grade['courseid']] . ' is ' . $grade['grade'] . '. ';
        }
        
        if ($grades == '') {
            $grades = 'You have no course grades.';
        }
        
        self::$response['response']['outputSpeech']['text'] = $grades;
        return;
    }
    
    /**
     * Function to get a user's due dates.
     * 
     * @return string calendar event dates
     */
    private static function get_due_dates() {
        global $DB, $CFG, $USER;
        
        $courses = enrol_get_my_courses('id');
        $courses = array_keys($courses);
        $groups = groups_get_my_groups();
        $groups = array_keys($groups);
        $eventparams = array('eventids' => array(), 'courseids' => $courses, 'groupids' => $groups, 'categoryids' => array());
        $options = array('userevents' => true, 'siteevents' => true, 'timestart' => time(), 'timeend' => null, 'ignorehidden' => null);
        $events = core_calendar_external::get_calendar_events($eventparams, $options);
        
        $duedates = '';
        $count = 0;
        
        // Get site calendar setting for number of upcoming events.
        // If over 5, limit to 5 initially for usability.
        $limit = $CFG->calendar_maxevents;
        if ($limit > 5) {
            $limit = 5;
        }
        
        // Get site calendar setting for days to look ahead.
        $lookahead = $CFG->calendar_lookahead;
        $lookahead = strtotime($lookahead . ' days');
        
        foreach($events['events'] as $event) {
            if ($count <= $limit && $event['timestart'] < $lookahead) {
                $duedates .= $event['name'] . ' on ' . date('l F j Y g:i a', $event['timestart']) . '. ';
            }
        }
        
        if ($duedates == '') {
            $duedates = 'You have no upcoming due dates.';
        }
        
        self::$response['response']['outputSpeech']['text'] = $duedates;
        return;
    }
}