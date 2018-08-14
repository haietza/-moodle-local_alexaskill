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
global $CFG;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/forum/externallib.php');
require_once($CFG->dirroot . '/calendar/externallib.php');
require_once($CFG->dirroot . '/lib/grouplib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/grade/report/overview/classes/external.php');

class local_alexaskill_external extends external_api {
    // Static variable for web service request JSON.
    static $requestjson;

    // Static variable for web service response.
    static $responsejson;

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function alexa_parameters() {
        return new external_function_parameters(array(
                'request' => new external_value(PARAM_TEXT, 'Request JSON as a string'),
                'token' => new external_value(PARAM_TEXT, 'Valid token status')
        ));
    }

    /**
     * Main function to process web service request.
     *
     * @param string $request
     * @param string $token
     * @return mixed|string|array|string[]|boolean[][]|string[][][]|array
     */
    public static function alexa($request, $token = '') {
        self::$requestjson = json_decode($request, true);
        self::initialize_response();

        // Check the URL of the signature certificate.
        if (!self::signature_certificate_url_is_valid($_SERVER['HTTP_SIGNATURECERTCHAINURL'])) {
            debugging('Invalid signature certificate URL', DEBUG_DEVELOPER);
            return http_response_code(400);
        }

        // Only perform signature validation on live, internet accessible server that can receive requests directly from Alexa.
        // Signature is encrypted version of request, no way to simulate.
        if (self::is_development_site()) {
            // Check the signature of the request.
            if (!self::signature_is_valid($_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE'], $request)) {
                debugging('Invalid signature', DEBUG_DEVELOPER);
                return http_response_code(400);
            }
        }

        // Check the request timestamp.
        if (!self::timestamp_is_valid()) {
            debugging('Invalid timestamp', DEBUG_DEVELOPER);
            return http_response_code(400);
        }

        // Verify request is intended for my service.
        if (!self::applicationid_is_valid()) {
            debugging('Invalid application id', DEBUG_DEVELOPER);
            return http_response_code(400);
        }

        // Process request.
        if (self::$requestjson['request']['type'] == 'LaunchRequest') {
            return self::launch_request($token);
        } else if (self::$requestjson['request']['type'] == 'IntentRequest') {
            switch(self::$requestjson['request']['intent']['name']) {
                case "GetSiteAnnouncementsIntent":
                    return self::get_announcements(1, 'the site');
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
                case "AMAZON.FallbackIntent":
                    return self::get_help(true);
                    break;
                case "AMAZON.HelpIntent":
                default:
                    return self::get_help();
                    break;
            }
        } else if (self::$requestjson['request']['type'] == 'SessionEndedRequest') {
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
                'sessionAttributes' => new external_single_structure(array(
                        'pin' => new external_value(PARAM_TEXT, 'pin', 'alexa pin', VALUE_OPTIONAL)
                ), 'session attributes', VALUE_OPTIONAL),
                'response' => new external_single_structure(array(
                        'outputSpeech' => new external_single_structure(array(
                                'type' => new external_value(PARAM_TEXT, 'type of speech output'),
                                'text' => new external_value(PARAM_TEXT, 'text string to speak', VALUE_OPTIONAL),
                                'ssml' => new external_value(PARAM_RAW, 'the ssml string to speak', VALUE_OPTIONAL)
                        )),
                        'reprompt' => new external_single_structure(array(
                                'outputSpeech' => new external_single_structure(array(
                                        'type' => new external_value(PARAM_TEXT, 'type of speech output'),
                                        'text' => new external_value(PARAM_TEXT, 'text string to speak', VALUE_OPTIONAL),
                                        'ssml' => new external_value(PARAM_RAW, 'the ssml string to speak', VALUE_OPTIONAL)
                                ))
                        ), 'reprompt', VALUE_OPTIONAL),
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
     * Verify signature certificate URL.
     *
     * @param string $certurl
     * @return boolean cert URL is avlid
     */
    private static function signature_certificate_url_is_valid($certurl) {
        // The protocol is equal to https (case insensitive).
        $protocol = strtolower(parse_url($certurl, PHP_URL_SCHEME));

        // The hostname is equal to s3.amazonaws.com (case insensitive).
        $hostname = strtolower(parse_url($certurl, PHP_URL_HOST));

        // The path starts with /echo.api/ (case sensitive).
        $path = substr(parse_url($certurl, PHP_URL_PATH), 0, 10);

        // If a port is defined in the URL, the port is equal to 443.
        $port = parse_url($certurl, PHP_URL_PORT);

        // Verify signature URL.
        return $protocol == 'https' && $hostname == 's3.amazonaws.com'
                && $path == '/echo.api/' && ($port == 443 || $port == null);
    }

    /**
     * Determine if Moodle instance is non-internet accessible development site.
     *
     * @return mixed|string|boolean|unknown|StdClass|NULL
     */
    private static function is_development_site() {
        return get_config('local_alexaskill', 'alexaskill_development');
    }

    /**
     * Validate the signature.
     * Thanks to https://github.com/craigh411/alexa-request-validator
     *
     * @param string $certurl
     * @param string $signature
     * @param string $request
     * @return boolean signature is valid
     */
    private static function signature_is_valid($certurl, $signature, $request) {
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
     * Parse ISO 8601 formatted string to verify within 150 seconds.
     *
     * @return boolean timestamp is valid
     */
    private static function timestamp_is_valid() {
        return (time() - strtotime(self::$requestjson['request']['timestamp'])) < 150;
    }

    /**
     * Verify appliation ID.
     *
     * @return boolean application ID is valid
     */
    private static function applicationid_is_valid() {
        return self::$requestjson['session']['application']['applicationId'] == get_config('local_alexaskill', 'alexaskill_applicationid');
    }

    /**
     * Ask for PIN or validate PIN.
     *
     * @return void|string[]|boolean[][]|string[][][]|string[]|array|][[]
     */
    private static function process_pin() {
        global $SITE;

        if (isset(self::$requestjson['request']['intent']['slots']['pin']['value'])) {
            // User has responded with PIN for verification. Verify PIN.
            if (!self::pin_is_valid()) {
                self::$responsejson['response']['outputSpeech']['text'] = "I'm sorry, that PIN is invalid. "
                        . "You can use the Alexa app to relink your account and reset your PIN.";
                return self::$responsejson;
            }

            // PIN is valid; return and finish processing request.
            return;
        } else {
            // Ask user for PIN.
            return self::request_pin();
        }
    }

    /**
     * Check if PIN has been set for user.
     *
     * @return boolean
     */
    private static function pin_exists() {
        global $DB, $USER;
        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        if (!$field) {
            return false;
        }

        $pin = $DB->get_record('user_info_data', array('userid' => $USER->id, 'fieldid' => $field->id), 'data');
        if (!$pin) {
            return false;
        }

        return strlen($pin->data) == 4 && is_numeric($pin->data);
    }

    /**
     * Return response asking for PIN.
     *
     * @return string[]|boolean[][]|string[][][]
     */
    private static function request_pin() {
        $outputspeech = 'Please say your Amazon Alexa PIN.';
        return self::complete_response($outputspeech, false, 'pin');
    }

    /**
     * Verify PIN; set session attribute if valid.
     *
     * @return boolean
     */
    private static function pin_is_valid() {
        global $DB, $USER;

        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        if (!$field) {
            return false;
        }

        $pin = $DB->get_record('user_info_data', array('userid' => $USER->id, 'fieldid' => $field->id), 'data');
        if (!$pin) {
            return false;
        }

        if ($pin->data != self::$requestjson['request']['intent']['slots']['pin']['value']) {
            // PIN is not valid.
            return false;
        }

        // PIN is valid; set session attribute for future checks.
        self::$responsejson['sessionAttributes']['pin'] = 'valid';
        return true;
    }

    /**
     * Initialize the JSON response.
     */
    private static function initialize_response() {
        self::$responsejson = array(
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
     * Complete and send JSON response.
     *
     * @param string $outputspeech
     * @param boolean $endsession
     * @param string $slot
     * @return string[]|boolean[][]|string[][][]
     */
    private static function complete_response($outputspeech, $endsession = true, $slot = '') {
        if (stripos($outputspeech, '<speak') !== false) {
            self::$responsejson['response']['outputSpeech']['type'] = 'SSML';
            self::$responsejson['response']['outputSpeech']['ssml'] = $outputspeech;
        } else {
            self::$responsejson['response']['outputSpeech']['text'] = $outputspeech;
        }

        if (!$endsession) {
            self::$responsejson['response']['reprompt']['outputSpeech'] = self::get_reprompt();
            self::$responsejson['response']['shouldEndSession'] = false;
        }

        if ($slot != '') {
            self::$responsejson['response']['directives'] = array(
                    array(
                            'type' => 'Dialog.ElicitSlot',
                                    'slotToElicit' => $slot
                            )
                    );
        }

        return self::$responsejson;
    }

    /**
     * Get welcome message.
     *
     * @return array JSON response welcome
     */
    private static function launch_request($token) {
        global $SITE, $USER;

        if ($token == 'valid') {
            // User account is linked, include first name in welcome response.
            $responses = array(
                    '<speak>Welcome to ' . $SITE->fullname . ', ' . $USER->firstname . '. You can get site announcements, <break time = "350ms"/>'
                    . 'course announcements, <break time = "350ms"/>grades, <break time = "350ms"/>or due dates. Which would you like?</speak>',
                    '<speak>Hello ' . $USER->firstname . '. I can get you site announcements, <break time = "350ms"/>course announcements, <break time = "350ms"/>'
                    . 'grades, <break time = "350ms"/>or due dates. Which would you like?</speak>'
            );
        } else {
            $responses = array(
                    '<speak>Welcome to ' . $SITE->fullname . '. You can get site announcements, <break time = "350ms"/>'
                    . 'course announcements, <break time = "350ms"/>grades, <break time = "350ms"/>or due dates. Which would you like?</speak>',
                    '<speak>Hello. I can get you site announcements, <break time = "350ms"/>course announcements, <break time = "350ms"/>'
                    . 'grades, <break time = "350ms"/>or due dates. Which would you like?</speak>'
            );
        }

        $outputspeech = $responses[rand(0, count($responses) - 1)];
        return self::complete_response($outputspeech, false);
    }

    /**
     * Log error for session ended request.
     */
    private static function session_ended_request() {
        debugging('SessionEndedRequest reason: ' . self::$requestjson['request']['reason'], DEBUG_DEVELOPER);
        debugging('SessionEndedRequest error type: ' . self::$requestjson['request']['error']['type'], DEBUG_DEVELOPER);
        debugging('SessionEndedRequest error message: ' . self::$requestjson['request']['error']['message'], DEBUG_DEVELOPER);
    }

    /**
     * Return a LinkAccount card for unlinked accounts.
     *
     * @param string $intent
     * @return array JSON response with LinkAccount card
     */
    private static function request_account_linking($intent = 'access that information') {
        global $SITE;

        self::$responsejson['response']['card']['type'] = 'LinkAccount';
        self::$responsejson['response']['outputSpeech']['text'] = 'You must have an account on ' . $SITE->fullname . ' to '
                . $intent . '. Please use the Alexa app to link your Amazon account with your ' . $SITE->fullname . ' account.';
        return self::$responsejson;
    }

    /**
     * Get course announcements.
     *
     * @param string $token
     */
    private static function get_course_announcements($token) {
        global $DB, $SITE;

        // Access token is either for general web service user or invalid.
        // Send account linking card.
        if ($token !== 'valid') {
            return self::request_account_linking('get course announcements');
        }

        // User has set PIN access, but it has not been verified in this session.
        if (self::pin_exists() && self::$requestjson['session']['attributes']['pin'] != 'valid') {
            self::process_pin();
            if (stripos(self::$responsejson['response']['outputSpeech']['text'], 'PIN') !== false) {
                return self::$responsejson;
            }
            // PIN exists and is valid; continue with request.
        }

        $usercourses = enrol_get_my_courses(array('id', 'fullname'));
        foreach ($usercourses as $usercourse) {
            $usercourse->preferredname = self::get_preferred_course_name($usercourse->fullname);
        }
        $numcourses = count($usercourses);

        // User has no courses, and therefore no announcements.
        if ($numcourses == 0) {
            $responses = array(
                    'Sorry, you are not enrolled in any courses.',
                    'I apologize, but there are no active courses listed for you.'
            );

            $outputspeech = $responses[rand(0, count($responses) - 1)];
            return self::complete_response($outputspeech);
        }

        // User only has one course, no need to prompt.
        if ($numcourses == 1) {
            $usercourse = reset($usercourses);
            $coursename = self::get_preferred_course_name($usercourse->fullname);
            return self::get_announcements($usercourse->id, $coursename);
        }

        if (!isset(self::$requestjson['request']['intent']['slots']['course']['value'])) {
            // We don't know the course, prompt for it.
            $responses = array(
                    '<speak>Thanks. You can get announcements for the following courses: ',
                    '<speak>Great. I can get announcements from the following courses for you: '
            );

            $prompt = '';
            $count = 0;
            foreach ($usercourses as $usercourse) {
                if ($count < $numcourses - 1) {
                    $prompt .= $usercourse->preferredname . ', <break time = "350ms"/> ';
                    $count++;
                }
            }
            $prompt .= 'or ' . $usercourse->preferredname . '. Which would you like?';

            $outputspeech = $responses[rand(0, count($responses) - 1)] . $prompt . '</speak>';
            return self::complete_response($outputspeech, false, 'course');
        } else if ($coursevalue = self::$requestjson['request']['intent']['slots']['course']['value']) {
            // User has requested announcements for a specific course.
            $courseid = -1;
            $coursename = $coursevalue;

            foreach ($usercourses as $usercourse) {
                if ($coursevalue == $usercourse->preferredname) {
                    // Check if they say the exact preferred name first.
                    $courseid = $usercourse->id;
                    break;
                } else if (stripos($usercourse->preferredname, $coursevalue) !== false) {
                    // Otherwise check if they said part of the preferred name.
                    $courseid = $usercourse->id;
                    $coursename = $usercourse->preferredname;
                    break;
                }
            }

            if ($courseid == -1) {
                // We did not find course in list of user's courses.
                $responses = array(
                        'Sorry, there are no records for ' . $coursevalue . '.',
                        'I apologize, but ' . $coursevalue . ' does not have any records.'
                );

                $outputspeech = $responses[rand(0, count($responses) - 1)];
                return self::complete_response($outputspeech);
            } else {
                // We found a valid course.
                return self::get_announcements($courseid, $coursename);
            }
        }
    }

    /**
     * Get announcements for the site or a course.
     *
     * @return array JSON response with site or course announcements
     */
    private static function get_announcements($courseid, $coursename) {
        global $DB;
        $forumposts = array();

        try {
            $discussions = $DB->get_records('forum_discussions', array('course' => $courseid), 'id DESC', 'id');
            foreach ($discussions as $discussion) {
                $forumposts[] = mod_forum_external::get_forum_discussion_posts($discussion->id);
            }
        } catch (moodle_exception $e) {
            // Exceptions may be thrown if user does not have capability to view forum.
            // In this case, just don't return those posts (do nothing).
            unset($e);
        }

        // Get course setting for number of announcements.
        // Field for newsitems has to be numeric in database.
        // Won't return false because course record has to exist to get to this point.
        $limit = $DB->get_field('course', 'newsitems', array('id' => $courseid));

        // If over 5, limit to 5 initially for usability.
        if ($limit > 5) {
            $limit = 5;
        }

        $announcements = '';
        $count = 0;
        foreach ($forumposts as $forumpost) {
            foreach ($forumpost['posts'] as $post) {
                // Only return $limit number of original posts (not replies).
                if ($post->parent == 0 && $count < $limit) {
                    $message = strip_tags($post->message);
                    $announcements .= '<p>Subject: ' . $post->subject . '. Message: ' . $message . '</p>';
                    $count++;
                }
            }
        }

        if ($announcements == '') {
            $responses = array(
                    'Sorry, there are no announcements for ' . $coursename . '.',
                    'I apologize, but ' . $coursename . ' does not have any announcements.'
            );

            $outputspeech = $responses[rand(0, count($responses) - 1)];
            return self::complete_response($outputspeech);
        } else {
            $responses = array(
                    '<speak>Okay. Here are the ' . $count . ' most recent announcements for ' . $coursename . ': ',
                    '<speak>Sure. The ' . $count . ' latest announcements for ' . $coursename . ' are: '
            );

            $outputspeech = $responses[rand(0, count($responses) - 1)] . $announcements . '</speak>';
            return self::complete_response($outputspeech);
        }
    }

    /**
     * Get a user's grades.
     *
     * @return array JSON response with grades
     */
    private static function get_grades($token) {
        global $DB, $USER;

        if ($token !== 'valid') {
            return self::request_account_linking('get grades');
        }

        // User has set PIN access, but it has not been verified in this session.
        if (self::pin_exists() && self::$requestjson['session']['attributes']['pin'] != 'valid') {
            self::process_pin();
            if (stripos(self::$responsejson['response']['outputSpeech']['text'], 'PIN') !== false) {
                return self::$responsejson;
            }
            // PIN exists and is valid; continue with request.
        }

        $gradereport = gradereport_overview_external::get_course_grades($USER->id);
        $coursenames = array();
        $grades = '';
        foreach ($gradereport['grades'] as $grade) {
            if ($grade['grade'] == '-') {
                continue;
            }
            $course = $DB->get_record('course', array('id' => $grade['courseid']), 'fullname');
            $coursename = self::get_preferred_course_name($course->fullname);
            $grades .= '<p>Your grade in ' . $coursename . ' is ' . $grade['grade'] . '.</p>';
        }

        if ($grades == '') {
            $responses = array(
                    'Sorry, you have no overall grades posted.',
                    'I apologize, but there are no overall grades posted for your courses.'
            );

            $outputspeech = $responses[rand(0, count($responses) - 1)];
            return self::complete_response($outputspeech);
        } else {
            $responses = array(
                    '<speak>Got it. Here are your overall course grades: ',
                    '<speak>Okay. These are your course grades overall: '
            );

            $outputspeech = $responses[rand(0, count($responses) - 1)] . $grades . '</speak>';
            return self::complete_response($outputspeech);
        }
    }

    /**
     * Get a user's due dates.
     *
     * @return array JSON response with events
     */
    private static function get_due_dates($token) {
        global $DB, $CFG, $USER;

        if ($token !== 'valid') {
            return self::request_account_linking('get due dates');
        }

        // User has set PIN access, but it has not been verified in this session.
        if (self::pin_exists() && self::$requestjson['session']['attributes']['pin'] != 'valid') {
            self::process_pin();
            if (stripos(self::$responsejson['response']['outputSpeech']['text'], 'PIN') !== false) {
                return self::$responsejson;
            }
            // PIN exists and is valid; continue with request.
        }

        $courses = enrol_get_my_courses('id');
        $courses = array_keys($courses);
        $groups = groups_get_my_groups();
        $groups = array_keys($groups);
        $eventparams = array('eventids' => array(), 'courseids' => $courses, 'groupids' => $groups, 'categoryids' => array());
        $options = array('userevents' => true, 'siteevents' => true, 'timestart' => time(), 'timeend' => null, 'ignorehidden' => null);
        $events = core_calendar_external::get_calendar_events($eventparams, $options);

        // Get site calendar setting for number of upcoming events.
        if (isset($CFG->calendar_maxevents) && is_number($CFG->calendar_maxevents)) {
            $limit = $CFG->calendar_maxevents;
        } else {
            $limit = 0;
        }

        // If over 5, limit to 5 initially for usability.
        if ($limit > 5) {
            $limit = 5;
        }

        // Get site calendar setting for days to look ahead.
        // If not set, use default of 3 weeks.
        if (isset($CFG->calendar_lookahead) && is_number($CFG->calendar_lookahead)) {
            $lookahead = $CFG->calendar_lookahead;
        } else {
            $lookahead = 21;
        }
        $lookahead = strtotime($lookahead . ' days');

        $duedates = '';
        $count = 0;
        foreach ($events['events'] as $event) {
            if ($count < $limit && $event['timestart'] < $lookahead) {
                $duedates .= '<p>' . $event['name'] . ' on ' . date('l F j Y g:i a', $event['timestart']) . '.</p>';
                $count++;
            }
        }

        if ($duedates == '') {
            $responses = array(
                    'Sorry, you have no upcoming events.',
                    'I apologize, but there are no upcoming events on your calendar.'
            );

            $outputspeech = $responses[rand(0, count($responses) - 1)];
            return self::complete_response($outputspeech);
        } else {
            $responses = array(
                    '<speak>Got it. Here are the next ' . $count . ' upcoming events: ',
                    '<speak>Okay. The next ' . $count . ' important dates are: '
            );

            $outputspeech = $responses[rand(0, count($responses) - 1)] . $duedates . '</speak>';
            return self::complete_response($outputspeech);
        }
    }

    /**
     * Parse course name from a regular expression.
     * Allows user to customize how Alexa says course names.
     *
     * @param string $coursefullname
     * @return string parsed course name
     */
    private static function get_preferred_course_name($coursefullname) {
        $coursename = $coursefullname;
        $pattern = get_config('local_alexaskill', 'alexaskill_coursenameregex');
        if (preg_match($pattern, $coursefullname, $coursenamearray)) {
            $coursename = $coursenamearray[1];
        }
        return strtolower($coursename);
    }

    /**
     * Return help response.
     *
     * @return array JSON response
     */
    private static function get_help($fallback = false) {
        if ($fallback) {
            $intro = "<speak>I'm sorry, I can't help you with that yet. ";
        } else {
            $intro = '<speak>';
        }
        $responses = array(
                $intro . 'You can get site announcements, <break time = "350ms"/>course announcements, <break time = "350ms"/>'
                    . 'grades, <break time = "350ms"/>or due dates. Which would you like?</speak>',
                $intro . 'I can get you site announcements, <break time = "350ms"/>course announcements, <break time = "350ms"/>'
                    . 'grades, <break time = "350ms"/>or due dates. Which would you like?</speak>'
        );

        $outputspeech = $responses[rand(0, count($responses) - 1)];
        return self::complete_response($outputspeech, false);
    }

    /**
     * Return reprompt.
     *
     * @return array reprompt object
     */
    private static function get_reprompt() {
        $reprompt = array();
        if (self::$responsejson['response']['outputSpeech']['type'] == 'PlainText') {
            $reprompt['type'] = 'PlainText';
            if (stripos(self::$responsejson['response']['outputSpeech']['text'], 'PIN') !== false) {
                // Response is from request_pin.
                $reprompt['text'] = "I didn't quite catch that. Please say your PIN.";
            }
        } else {
            $reprompt['type'] = 'SSML';
            if (stripos(self::$responsejson['response']['outputSpeech']['ssml'], 'Which would you like?') !== false) {
                // Response is from launch_request or get_course_announcements course prompt.
                $reprompt['ssml'] = "<speak>I didn't quite catch that. Which would you like?</speak>";
            }
        }
        return $reprompt;
    }

    /**
     * Return good-bye response.
     *
     * @return array JSON response
     */
    private static function say_good_bye() {
        $responses = array(
                'Okay, have a nice day!',
                'Great. Take care!',
                'Thanks. Good bye!',
                'Sure. Until next time!'
        );

        $outputspeech = $responses[rand(0, count($responses) - 1)];
        return self::complete_response($outputspeech);
    }
}