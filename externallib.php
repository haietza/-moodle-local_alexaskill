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
 * Amazon Alexa skill web service.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/forum/externallib.php');
require_once($CFG->dirroot . '/calendar/externallib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/grade/report/overview/classes/external.php');

class local_alexaskill_external extends external_api {
    // Static variable for web service request JSON.
    protected static $json;

    // Static variable for web service response.
    protected static $response;

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function alexa_parameters() {
        return new external_function_parameters(array(
                'request' => new external_value(PARAM_TEXT, 'JSON request as a string'),
                'token' => new external_value(PARAM_TEXT, 'Valid token status')
        ));
    }

    public static function alexa($request, $token = '') {
        self::$json = json_decode($request, true);

        // Only check the signature and timestamp if not on local server.
        // Cannot simulate signature since that is encrypted based on request.
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
            // Check the URL of the signature certificate.
            if (!self::verify_signature_certificate_url($_SERVER['HTTP_SIGNATURECERTCHAINURL'])) {
                debugging('Invalid signature certificate URL', NO_DEBUG_DISPLAY);
                return http_response_code(400);
            }

            // Check the signature of the request.
            if (!self::validate_signature($_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE'], $request)) {
                debugging('Invalid signature', NO_DEBUG_DISPLAY);
                return http_response_code(400);
            }

            // Check the request timestamp.
            if (!self::verify_timestamp()) {
                debugging('Invalid timestamp', NO_DEBUG_DISPLAY);
                return http_response_code(400);
            }
        }

        // Verify request is intended for my service.
        if (!self::verify_application_id()) {
            debugging('Invalid application id', NO_DEBUG_DISPLAY);
            return http_response_code(400);
        }

        // Process request.
        if (self::$json['request']['type'] == 'LaunchRequest') {
            return self::launch_request();
        } else if (self::$json['request']['type'] == 'IntentRequest') {
            switch(self::$json['request']['intent']['name']) {
                case "GetSiteAnnouncementsIntent":
                    return self::get_site_announcements();
                    break;
                case "GetCourseAnnouncementsIntent":
                    return self::get_course_announcements($token);
                    break;
                case "GetGradesIntent":
                    return self::get_grades($token);
                    break;
                case "GetDueDatesIntent":
                    return self::get_due_dates($token);
                    break;
                case "AMAZON.CancelIntent":
                case "AMAZON.StopIntent":
                    return self::say_good_bye();
                    break;
                case "AMAZON.HelpIntent":
                default:
                    return self::get_help();
                    break;
            }
        } else if (self::$json['request']['type'] == 'SessionEndedRequest') {
            self::session_ended_request();
        }
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
                        'shouldEndSession' => new external_value(PARAM_BOOL, 'true if responses ends session'),
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
     * Function to verify signature certificate URL.
     *
     * @param string $certurl
     * @return true if valid
     */
    private static function verify_signature_certificate_url($certurl) {
        // The protocol is equal to https (case insensitive).
        $protocol = strtolower(parse_url($certurl, PHP_URL_SCHEME));

        // The hostname is equal to s3.amazonaws.com (case insensitive).
        $hostname = strtolower(parse_url($certurl, PHP_URL_HOST));

        // The path starts with /echo.api/ (case sensitive).
        $path = substr(parse_url($certurl, PHP_URL_PATH), 0, 10);

        // If a port is defined in the URL, the port is equal to 443.
        $port = parse_url($certurl, PHP_URL_PORT);

        // Verify signature URL.
        return $protocol == 'https'
                && $hostname == 's3.amazonaws.com'
                && $path == '/echo.api/'
                && ($port == 443 || $port == null);
    }

    /**
     * Function to validate the signature.
     * Thanks to https://github.com/craigh411/alexa-request-validator
     *
     * @param string $certurl
     * @param string $signature
     * @param string $request
     * @return boolean signature is valid
     */
    private static function validate_signature($certurl, $signature, $request) {
        global $CFG;

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
        $validfrom = $parsedcert['validFrom_time_t'];
        $validto = $parsedcert['validTo_time_t'];
        $time = time();
        if (!($validfrom <= $time && $time <= $validto)) {
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
     * Function to parse ISO 8601 formatted string to verify within 150 seconds.
     *
     * @return boolean timestamp is valid
     */
    private static function verify_timestamp() {
        return (time() - strtotime(self::$json['request']['timestamp'])) < 150;
    }

    /**
     * Function to verify appliation ID.
     *
     * @return true if valid
     */
    private static function verify_application_id() {
        return self::$json['session']['application']['applicationId'] == get_config('local_alexaskill', 'alexaskill_applicationid');
    }

    /**
     * Function to initialize the JSON response.
     */
    private static function initialize_response() {
        self::$response = array(
                'version' => '1.0',
                'response' => array (
                        'outputSpeech' => array(
                                'type' => 'PlainText'
                        ),
                        'shouldEndSession' => true
                )
        );
    }

    /**
     * Function to get welcome message.
     *
     * @return string welcome
     */
    private static function launch_request() {
        global $SITE;
        self::initialize_response();

        $responses = array(
                '<speak>Welcome to ' . $SITE->fullname . '. You can get site announcements <break time = "350ms"/>'
                . 'course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>',
                '<speak>Hello. I can get you site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>'
                . 'grades <break time = "350ms"/>or due dates. Which would you like?</speak>'
        );
        self::$response['response']['outputSpeech']['type'] = 'SSML';
        self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, count($responses) - 1)];
        self::$response['response']['shouldEndSession'] = false;
        return self::$response;
    }

    /**
     * Function to log error for session ended request.
     */
    private static function session_ended_request() {
        debugging('SessionEndedRequest reason: ' . self::$json['request']['reason'], NO_DEBUG_DISPLAY);
        debugging('SessionEndedRequest error type: ' . self::$json['request']['error']['type'], NO_DEBUG_DISPLAY);
        debugging('SessionEndedRequest error message: ' . self::$json['request']['error']['message'], NO_DEBUG_DISPLAY);
    }

    /**
     * Function to return the LinkAccount card.
     *
     * @param string $task
     */
    private static function verify_account_linking($task) {
        global $SITE;
        self::initialize_response();

        self::$response['response']['card']['type'] = 'LinkAccount';
        self::$response['response']['outputSpeech']['text'] = 'You must have an account on ' . $SITE->fullname . ' to '
                . $task . '. Please use the Alexa app to link your Amazon account with your ' . $SITE->fullname . ' account.';
        return self::$response;
    }

    /**
     * Function to get front page site announcements.
     *
     * @return string site announcements
     */
    private static function get_site_announcements() {
        // Handle dialog directive response to "Would you like anything else?"
        if (self::$json['request']['dialogState'] == 'IN_PROGRESS') {
            return self::in_progress();
        }

        self::initialize_response();
        return self::get_announcements(1, 'the site');
    }

    /**
     * Function to get course announcements.
     *
     * @param string $token
     */
    private static function get_course_announcements($token) {
        global $DB;

        if ($token !== 'valid') {
            return self::verify_account_linking('get course announcements');
        }

        // Handle dialog directive response to "Would you like anything else?"
        if (self::$json['request']['dialogState'] == 'IN_PROGRESS' && self::$json['request']['intent']['slots']['else']['value']) {
            return self::in_progress();
        }

        self::initialize_response();
        $usercourses = enrol_get_my_courses(array('id', 'fullname'));
        foreach ($usercourses as $usercourse) {
            $usercourse->preferredname = self::get_preferred_course_name($usercourse->fullname);
        }
        $numcourses = count($usercourses);

        // User has no courses, and therefore no announcements.
        if ($numcourses == 0) {
            $responses = array(
                    'Sorry, you are not enrolled in any courses. Would you like anything else?',
                    'I apologize, but there are no active courses listed for you. Can I get you anything else?'
            );

            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, count($responses) - 1)];
            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'else'
                    )
            );
            return self::$response;
        }

        // User only has one course, no need to prompt.
        if ($numcourses == 1) {
            $usercourse = reset($usercourses);
            $coursename = self::get_preferred_course_name($usercourse->fullname);
            return self::get_announcements($usercourse->id, $coursename);
        }

        if (self::$json['request']['dialogState'] == 'STARTED') {
            // We don't know the course, prompt for it.
            $responses = array(
                    '<speak>Thanks. You can get announcements for the following courses: ',
                    '<speak>Great. I can get announcements from the following courses for you: '
            );

            $prompt = '';
            $count = 0;
            foreach ($usercourses as $usercourse) {
                if ($count < $numcourses - 1) {
                    $prompt .= $usercourse->preferredname . '<break time = "350ms"/> ';
                    $count++;
                }
            }
            $prompt .= 'or ' . $usercourse->preferredname . '. Which would you like?';

            self::$response['response']['outputSpeech']['type'] = 'SSML';
            self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, count($responses) - 1)] . $prompt . '</speak>';

            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'course'
                    )
            );
            return self::$response;
        } else if (self::$json['request']['dialogState'] == 'IN_PROGRESS'
                && ($coursevalue = self::$json['request']['intent']['slots']['course']['value'])) {
            // User has requested announcements for a specific course.
            $courseid = -1;
            $coursename = $coursevalue;

            foreach ($usercourses as $usercourse) {
                // Check if they say the exact preferred name first.
                if ($coursevalue == $usercourse->preferredname) {
                    $courseid = $usercourse->id;
                    break;
                } else if (stripos($usercourse->preferredname, $coursevalue) !== false) {
                    // Otherwise check if they said part of the preferred name.
                    $courseid = $usercourse->id;
                    $coursename = $usercourse->preferredname;
                    break;
                }
            }

            if ($courseid != -1) {
                // We found a valid course.
                return self::get_announcements($courseid, $coursename);
            } else {
                // We did not find course in list of user's courses.
                $responses = array(
                        'Sorry, there are no records for ' . $coursevalue . '. Would you like anything else?',
                        'I apologize, but ' . $coursevalue . ' does not have any records. Can I get you any other information?'
                );

                self::$response['response']['outputSpeech']['text'] = $responses[rand(0, count($responses) - 1)];
                self::$response['response']['shouldEndSession'] = false;
                self::$response['response']['directives'] = array(
                        array(
                                'type' => 'Dialog.ElicitSlot',
                                'slotToElicit' => 'else'
                        )
                );
                return self::$response;
            }
        }
    }

    /**
     * Function to get announcements for the site or a course.
     */
    private static function get_announcements($courseid, $coursename) {
        global $DB;

        $discussions = $DB->get_records('forum_discussions', array('course' => $courseid), 'id DESC', 'id');
        $forumposts = array();
        foreach ($discussions as $discussion) {
            $forumposts[] = mod_forum_external::get_forum_discussion_posts($discussion->id);
        }

        // Get course setting for number of announcements.
        // If over 5, limit to 5 initially for usability.
        $limit = $DB->get_field('course', 'newsitems', array('id' => $courseid));
        if ($limit > 5 || $limit === false) {
            $limit = 5;
        }

        $announcements = '';
        $count = 0;
        foreach ($forumposts as $forumpost) {
            foreach ($forumpost['posts'] as $post) {
                // Only return $limit number of original posts (not replies).
                if ($post->parent == 0 && $count <= $limit) {
                    $message = strip_tags($post->message);
                    $announcements .= '<p>' . $post->subject . '. ' . $message . '</p> ';
                    $count++;
                }
            }
        }

        if ($announcements == '') {
            $responses = array(
                    'Sorry, there are no announcements for ' . $coursename . '. Would you like anything else?',
                    'I apologize, but ' . $coursename . ' does not have any announcements. Can I get you any other information?'
            );

            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, count($responses) - 1)];
            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'else'
                    )
            );
            return self::$response;
        } else {
            $responses = array(
                    '<speak>Okay. Here are the ' . $count . ' most recent announcements for ' . $coursename . ': ',
                    '<speak>Sure. The ' . $count . ' latest announcements for ' . $coursename . ' are: '
            );

            self::$response['response']['outputSpeech']['type'] = 'SSML';
            self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, count($responses) - 1)] . $announcements
                . ' Would you like anything else?</speak>';
            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'else'
                    )
            );
            return self::$response;
        }
    }

    /**
     * Function to get a user's grades.
     */
    private static function get_grades($token) {
        global $DB, $USER;

        if ($token !== 'valid') {
            return self::verify_account_linking('get grades');
        }

        // Handle dialog directive response to "Would you like anything else?"
        if (self::$json['request']['dialogState'] == 'IN_PROGRESS') {
            return self::in_progress();
        }

        self::initialize_response();
        $gradereport = gradereport_overview_external::get_course_grades($USER->id);
        $coursenames = array();
        $grades = '';
        foreach ($gradereport['grades'] as $grade) {
            $course = $DB->get_record('course', array('id' => $grade['courseid']), 'fullname');
            $coursename = self::get_preferred_course_name($course->fullname);
            $grades .= '<p>Your grade in ' . $coursename . ' is ' . $grade['grade'] . '.</p> ';
        }

        if ($grades == '') {
            $responses = array(
                    'Sorry, you have no overall grades posted. Would you like anything else?',
                    'I apologize, but there are no overall grades posted for your courses. Can I get you any other information?'
            );

            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, count($responses) - 1)];
            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'else'
                    )
            );
            return self::$response;
        } else {
            $responses = array(
                    '<speak>Got it. Here are your overall course grades: ',
                    '<speak>Okay. These are your course grades overall: '
            );

            self::$response['response']['outputSpeech']['type'] = 'SSML';
            self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, count($responses) - 1)] . $grades
                . ' Would you like anything else?</speak>';
            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'else'
                    )
            );
            return self::$response;
        }
    }

    /**
     * Function to get a user's due dates.
     */
    private static function get_due_dates($token) {
        global $DB, $CFG, $USER;

        if ($token !== 'valid') {
            return self::verify_account_linking('get due dates');
        }

        // Handle dialog directive response to "Would you like anything else?"
        if (self::$json['request']['dialogState'] == 'IN_PROGRESS') {
            return self::in_progress();
        }

        self::initialize_response();
        $courses = enrol_get_my_courses('id');
        $courses = array_keys($courses);
        $groups = groups_get_my_groups();
        $groups = array_keys($groups);
        $eventparams = array('eventids' => array(), 'courseids' => $courses, 'groupids' => $groups, 'categoryids' => array());
        $options = array('userevents' => true, 'siteevents' => true, 'timestart' => time(), 'timeend' => null, 'ignorehidden' => null);
        $events = core_calendar_external::get_calendar_events($eventparams, $options);

        // Get site calendar setting for number of upcoming events.
        // If over 5, limit to 5 initially for usability.
        $limit = $CFG->calendar_maxevents;
        if ($limit > 5 || !is_number($limit)) {
            $limit = 5;
        }

        // Get site calendar setting for days to look ahead.
        $lookahead = $CFG->calendar_lookahead;
        if (!is_number($lookahead)) {
            $lookahead = 21;
        }
        $lookahead = strtotime($lookahead . ' days');

        $duedates = '';
        $count = 0;
        foreach ($events['events'] as $event) {
            if ($count <= $limit && $event['timestart'] < $lookahead) {
                $duedates .= '<p>' . $event['name'] . ' on ' . date('l F j Y g:i a', $event['timestart']) . '.</p> ';
            }
        }

        if ($duedates == '') {
            $responses = array(
                    'Sorry, you have no upcoming events. Would you like anything else?',
                    'I apologize, but there are no upcoming events on your calendar. Do you need any other information?'
            );

            self::$response['response']['outputSpeech']['text'] = $responses[rand(0, count($responses) - 1)];
            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'else'
                    )
            );
            return self::$response;
        } else {
            $responses = array(
                    '<speak>Got it. Here are the next ' . $count . ' upcoming events: ',
                    '<speak>Okay. The next' . $count . ' important dates are: '
            );

            self::$response['response']['outputSpeech']['type'] = 'SSML';
            self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, count($responses) - 1)] . $duedates
                . ' Would you like anything else? </speak>';
            self::$response['response']['shouldEndSession'] = false;
            self::$response['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                            'slotToElicit' => 'else'
                    )
            );
            return self::$response;
        }
    }

    /**
     * Function to parse a course name from a regular expression.
     * Allows user to customize how Alexa says course names.
     *
     * @param string $coursefullname
     * @return string parsed course name
     */
    private static function get_preferred_course_name($coursefullname) {
        $coursename = $coursefullname;
        $pattern = get_config('local_alexaskill', 'alexaskill_coursenameregex');
        if (preg_match($pattern, $coursefullname, $coursenamearray)) {
            // Strip course number off front of fullname and ' (TERM YEAR)' from end.
            $coursename = substr($coursenamearray[1], 0, -1);
        }
        return strtolower($coursename);
    }

    /**
     * Function to handle in progress response.
     *
     * @return array response
     */
    private static function in_progress() {
        if (self::$json['request']['intent']['slots']['else']['resolutions']['resolutionsPerAuthority'][0]['values'][0]['value']['name'] == 'no') {
            return self::say_good_bye();
        } else {
            return self::get_help();
        }
    }

    /**
     * Function to return help response.
     *
     * @return array response
     */
    private static function get_help() {
        self::initialize_response();

        $responses = array(
                '<speak>You can get site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>'
                    . 'grades <break time = "350ms"/>or due dates. Which would you like?</speak>',
                '<speak>I can get you site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>'
                    . 'grades <break time = "350ms"/>or due dates. Which would you like?</speak>'
        );
        self::$response['response']['outputSpeech']['type'] = 'SSML';
        self::$response['response']['outputSpeech']['ssml'] = $responses[rand(0, count($responses) - 1)];
        self::$response['response']['shouldEndSession'] = false;

        return self::$response;
    }

    /**
     * Function to return good by response.
     *
     * @return array response
     */
    private static function say_good_bye() {
        self::initialize_response();

        $responses = array(
                'Okay, have a nice day!',
                'Great. Take care!',
                'Thanks. Good bye!',
                'Sure. Until next time!'
        );
        self::$response['response']['outputSpeech']['text'] = $responses[rand(0, count($responses) - 1)];

        return self::$response;
    }
}