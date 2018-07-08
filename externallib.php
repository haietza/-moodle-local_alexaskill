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
                                'type' => 'PlainText'
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
        }
        */
        
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
                    self::get_site_announcements();
                    break;
                case "GetCourseAnnouncementsIntent":
                    self::get_course_announcements($json);
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
                    $responses = array(
                        'Okay, have a nice day!',
                        'Great. Take care!',
                        'Thanks. Good bye!',
                        'Sure. Until next time!'
                    );
                    self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
                    break;
                case "AMAZON.HelpIntent":
                    $responses = array(
                        '<speak>You can get site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>',
                        '<speak>I can get you site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>'
                    );
                    self::$response['response']['outputSpeech']['type'] = "SSML";
                    self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, sizeof($responses) - 1)];
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
                                'text' => new external_value(PARAM_TEXT, 'text string to speak', VALUE_OPTIONAL),
                                'ssml' => new external_value(PARAM_RAW, 'the ssml string to speak', VALUE_OPTIONAL)
                        )),
                        'shouldEndSession' => new external_value(PARAM_BOOL,'true if responses ends session'),
                        'card' => new external_single_structure(array(
                                'type' => new external_value(PARAM_TEXT, 'type of card')
                        ), 'card for app', VALUE_OPTIONAL),
                        'directives' => new external_multiple_structure(new external_single_structure(array(
                                'type' => new external_value(PARAM_TEXT, 'type of directive'),
                                'slotToElicit' => new external_value(PARAM_TEXT, 'slot to elicit')
                        )), 'directives', VALUE_OPTIONAL)
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
        
        // Create the Signature Certificate Chain directory if it does not exist.
        $certdir = $CFG->dataroot . '/local_alexaskill';
        if (!file_exists($certdir)) {
            mkdir($certdir);
        }
        
        // Make sure directory is writeable.
        if (!is_writable($certdir)) {
            chmod($certdir, 0777);
        }
        
        // Download a new Signature Certificate Chain if we need to.
        $certfile = $certdir . '/' . md5($certurl) . '.pem';
        if (!file_exists($certfile)) {
            file_put_contents($certfile, file_get_contents($certurl));
        }
        
        // Download PEM-enoded X.509 certificate chain.
        $cert = file_get_contents($certfile);
        
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
        
        $responses = array(
                '<speak>Welcome to ' . $SITE->fullname . '. You can get site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>',
                '<speak>Hello. I can get you site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>'
        );
        self::$response['response']['outputSpeech']['type'] = 'SSML';
        self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, sizeof($responses) - 1)];
        self::$response['response']['shouldEndSession'] = false;
        return;
    }
    
    /**
     * Function to handle the session ended request.
     *
     * @param string $error
     */
    private static function session_ended_request($error) {
        if ($error) {
            $responses = array(
                    'Sorry, there was a problem because ' . $error,
                    'Whoops! I had a bit of a problem due to ' . $error
            );
            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];            
        } else {
            $responses = array(
                    'Okay, have a nice day!',
                    'Great. Take care!',
                    'Thanks. Good bye!',
                    'Sure. Until next time!'
            );
            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
        }
        return;
    }
    
    /**
     * Function to return the LinkAccount card.
     *
     * @param string $task
     */
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
    private static function get_site_announcements() {        
        global $DB;
        $courseid = 1;
        
        $discussions = $DB->get_records('forum_discussions', array('course' => $courseid), 'id DESC', 'id');
        $forumposts = array();
        foreach ($discussions as $discussion) {
            $forumposts[] = mod_forum_external::get_forum_discussion_posts($discussion->id);
        }

        // Get course setting for number of announcements.
        // If over 5, limit to 5 initially for usability.
        $limit = $DB->get_field('course', 'newsitems', array('id' => $courseid));
        if ($limit > 5) {
            $limit = 5;
        }
       
        $siteannouncements = '';
        $response = '';
        $count = 0;
        foreach ($forumposts as $forumpost) {
            foreach ($forumpost['posts'] as $post) {
                // Only return $limit number of original posts (not replies).
                if ($post->parent == 0 && $count <= $limit) {
                    $message = strip_tags($post->message);
                    $siteannouncements .= '<p>' . $post->subject . '. ' . $message . '</p> ';
                    $count++;
                }
            }
        }
        
        if ($siteannouncements == '') {
            $responses = array(
                    'Sorry, there are no site announcements right now.',
                    'I apologize, but there are no announcements for the site.'
            );
            
            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
            return;
        } else {
            $responses = array(
                    '<speak>Okay. Here are the ' . $count . ' most recent site announcements: ',
                    '<speak>Sure. The ' . $count . ' latest site announcements are: '
            );
            
            self::$response['response']['outputSpeech']['type'] = 'SSML';
            self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, sizeof($responses) - 1)] . $siteannouncements . '</speak>';
            return;
        }   
    }
    
    /**
     * Function to get course announcements.
     *
     * @param string $json
     */
    private static function get_course_announcements($json) {
        global $DB;
        $usercourses = enrol_get_my_courses(array('id', 'fullname'));
        foreach ($usercourses as $usercourse) {
            $usercourse->preferredname = self::get_course_name($usercourse->fullname);
        }
        $numcourses = sizeof($usercourses);
        
        // User has no courses, and therefore no announcements.
        if ($numcourses == 0) {
            $responses = array(
                    'Sorry, you are not enrolled in any courses.',
                    'I apologize, but there are no active courses listed for you.'
            );
            
            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
            return;
        }
        
        // User only has one course, no need to prompt.
        if ($numcourses == 1) {
            $usercourse = reset($usercourses);
            $coursename = self::get_course_name($usercourse->fullname);
                
            $discussions = $DB->get_records('forum_discussions', array('course' => $usercourse->id), 'id DESC', 'id');
            $forumposts = array();
            foreach ($discussions as $discussion) {
                $forumposts[] = mod_forum_external::get_forum_discussion_posts($discussion->id);
            }
            
            $courseannouncements = '';
            $count = 0;
                
            // Get course setting for number of announcements.
            // If over 5, limit to 5 initially for usability.
            $limit = $DB->get_field('course', 'newsitems', array('id' => $usercourse->id));
            if ($limit > 5) {
                $limit = 5;
            }
                
            foreach ($forumposts as $forumpost) {
                foreach ($forumpost['posts'] as $post) {
                    // Only return $limit number of original posts (not replies).
                    if ($post->parent == 0 && $count <= $limit) {
                        $message = strip_tags($post->message);
                        $courseannouncements .= '<p>' . $post->subject . '. ' . $message . '</p> ';
                        $count++;
                    }
                }
            }
            
            if ($courseannouncements == '') {
                $responses = array(
                        'Sorry, there are no announcements for ' . $coursename . '.',
                        'I apologize, but ' . $coursename . ' does not have any announcements.'
                );
                
                self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
                return;
            } else {
                $responses = array(
                        '<speak>Okay. Here are the ' . $count . ' most recent course announcements for ' . $coursename . ': ',
                        '<speak>Sure. The ' . $count . ' latest ' . $coursename . ' course announcements are: '
                );
                
                self::$response['response']['outputSpeech']['type'] = 'SSML';
                self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, sizeof($responses) - 1)] . $courseannouncements . '</speak>';
                return;
            }
        }
        
        if ($json['request']['dialogState'] == 'STARTED') {
            // We don't know the course, prompt for it.
            $responses = array(
                    '<speak>Thanks. You can get announcements for the following courses: ',
                    '<speak>Great. I can get announcements from the following courses for you: '
            );
            
            //$prompt = 'You can get announcements for the following courses: ';
            $prompt = '';
            $count = 0;
            foreach($usercourses as $usercourse) {
                if ($count < $numcourses - 1) {
                    $prompt .= $usercourse->preferredname . '<break time = "350ms"/> ';
                    $count++;
                }
            }
            $prompt .= 'or ' . $usercourse->preferredname . '. Which would you like?';

            self::$response['response']['outputSpeech']['type'] = 'SSML';
            self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, sizeof($responses) - 1)] . $prompt . '</speak>';
            
            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'course'
                    ) 
            );
            return;
        } elseif ($json['request']['dialogState'] == 'IN_PROGRESS' || $json['request']['dialogState'] == 'COMPLETED') {
            $coursevalue = $json['request']['intent']['slots']['course']['value'];
            $courseid = -1;
            
            foreach ($usercourses as $usercourse) {
                // Check if they say the exact preferred name first.
                if ($coursevalue == $usercourse->preferredname) {
                    $courseid = $usercourse->id;
                    break;
                } elseif (strpos($usercourse->preferredname, strtolower($coursevalue)) !== false) {
                    // Otherwise check if they said part of the preferred name.
                    $courseid = $usercourse->id;
                    break;
                }
            }

            if ($courseid != -1) {
                $discussions = $DB->get_records('forum_discussions', array('course' => $courseid), 'id DESC', 'id');
                $forumposts = array();
                foreach ($discussions as $discussion) {
                    $forumposts[] = mod_forum_external::get_forum_discussion_posts($discussion->id);
                }
                
                $courseannouncements = '';
                $count = 0;
                
                // Get course setting for number of announcements.
                // If over 5, limit to 5 initially for usability.
                $limit = $DB->get_field('course', 'newsitems', array('id' => $courseid));
                if ($limit > 5) {
                    $limit = 5;
                }
                
                foreach ($forumposts as $forumpost) {
                    foreach ($forumpost['posts'] as $post) {
                        // Only return $limit number of original posts (not replies).
                        if ($post->parent == 0 && $count <= $limit) {
                            $message = strip_tags($post->message);
                            $courseannouncements .= '<p>' . $post->subject . '. ' . $message . '</p> ';
                            $count++;
                        }
                    }
                }
                
                if ($courseannouncements == '') {
                    $responses = array(
                            'Sorry, there are no announcements for ' . $coursename . '.',
                            'I apologize, but ' . $coursename . ' does not have any announcements.'
                    );
                    
                    self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
                    return;
                } else {
                    $responses = array(
                            '<speak>Okay. Here are the ' . $count . ' most recent course announcements for ' . $coursename . ': ',
                            '<speak>Sure. The ' . $count . ' latest ' . $coursename . ' course announcements are: '
                    );
                    
                    self::$response['response']['outputSpeech']['type'] = 'SSML';
                    self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, sizeof($responses) - 1)] . $courseannouncements . '</speak>';
                    return;
                }
            } else {
                // We did not find course in list of user's courses.
                self::$response['response']['outputSpeech']['text'] = "I don't have any records for that course.";
                return;
                
                $responses = array(
                        'Sorry, there are no records for ' . $coursevalue . '.',
                        'I apologize, but ' . $coursevalue . ' does not have any records.'
                );
                
                self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
                return;
            } 
        }
    }
    
    /**
     * Function to get a user's grades.
     */
    private static function get_grades() {
        global $DB, $USER;
        
        $gradereport = gradereport_overview_external::get_course_grades($USER->id);
        $coursenames = array();
        $grades = '';
        foreach($gradereport['grades'] as $grade) {
            $course = $DB->get_record('course', array('id' => $grade['courseid']), 'fullname');
            $coursename = self::get_course_name($course->fullname);
            $grades .= '<p>Your grade in ' . $coursename . ' is ' . $grade['grade'] . '.</p> ';
        }
        
        if ($grades == '') {
            $responses = array(
                    'Sorry, you have no overall grades posted.',
                    'I apologize, but there are no overall grades posted for your courses.'
            );
            
            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
            return;
        } else {
            $responses = array(
                    '<speak>Got it. Here are your overall course grades: ',
                    '<speak>Okay. Your overall course grades are: '
            );
            
            self::$response['response']['outputSpeech']['type'] = 'SSML';
            self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, sizeof($responses) - 1)] . $grades . '</speak>';
            return;
        }
    }
    
    /**
     * Function to get a user's due dates.
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
                $duedates .= '<p>' . $event['name'] . ' on ' . date('l F j Y g:i a', $event['timestart']) . '.</p> ';
            }
        }
        
        if ($duedates == '') {
            $responses = array(
                    'Sorry, you have no upcoming events.',
                    'I apologize, but there are no upcoming events on your calendar.'
            );
            
            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, sizeof($responses) - 1)];
            return;
        } else {
            $responses = array(
                    '<speak>Got it. Here are the next ' . $count . ' upcoming events: ',
                    '<speak>Okay. The next' . $count . ' important dates are: '
            );
            
            self::$response['response']['outputSpeech']['type'] = 'SSML';
            self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, sizeof($responses) - 1)] . $duedates . '</speak>';
            return;
        }
    }
    
    /**
     * Function to parse a course name from a regular expression.
     * Allows user to customize how Alexa says course names.
     *
     * @param string $coursefullname
     * @return string parsed course name
     */
    private static function get_course_name($coursefullname) {
        $coursename = $coursefullname;
        $pattern = get_config('local_alexaskill', 'alexaskill_coursenameregex');
        if (preg_match($pattern, $coursefullname, $coursenamearray)) {
            // Strip course number off front of fullname and ' (TERM YEAR)' from end.
            $coursename = substr($coursenamearray[1], 0, -1);
        }
        return strtolower($coursename);
    }
}
