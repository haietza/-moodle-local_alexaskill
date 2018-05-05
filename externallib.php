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
//require_once($CFG->dirroot . '/mod/forum/externallib.php');

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
        
        // Check the signature of the request
        if (!self::validate_signature($_SERVER['HTTP_SIGNATURECERTCHAINURL'], $_SERVER['HTTP_SIGNATURE'], $request)) {
            return http_response_code(400);
        }
        
        // Check the request timestamp.
        if (!self::verify_timestamp($json["request"]["timestamp"])) {
            error_log('Timestamp wrong');
            return http_response_code(400);
        }
        
        // Verify request is intended for my service.
        if (!self::verify_app_id($json["session"]["application"]["applicationId"])) {
            error_log('Application ID wrong');
            return http_response_code(400);
        }
        
        // Process request.
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
        } elseif ($json["request"]["type"] == 'SessionEndedRequest') {
            $text = $json["request"]["error"]["message"];
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
    
    /**
     * Function to verify appliation ID.
     * 
     * @param string $applicationId
     * @return true if valid
     */
    private static function verify_app_id($applicationId) {
        return $applicationId == self::APPLICATIONID;
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
                    error_log('Signature URL wrong');
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
            error_log('No parsed cert');
            return false;
        }
        
        // Check that signing certificate has not expired.
        $validFrom = $parsedcert['validFrom_time_t'];
        $validTo = $parsedcert['validTo_time_t'];
        $time = time();
        if (!($validFrom <= $time && $time <= $validTo)) {
            error_log('Expired cert');
            return false;
        }
        
        // Check SAN.
        if (strpos($parsedcert['extensions']['subjectAltName'], 'echo-api.amazon.com') === false) {
            error_log('SAN wrong');
            return false;
        }
        
        // Check all certs combine to trusted root CA???
        
        // Extract public key from signing certificate.
        $publickey = openssl_pkey_get_public($cert);
        
        // Base64-decode the Signature header on request to obtain encrypted signature.
        // Generate SHA-1 hash from full HTTPS request body to produce derived hash value.
        // Compare asserted and derived hashes for matching.
        $verifysig = openssl_verify($request, base64_decode($signature), $publickey, OPENSSL_ALGO_SHA1);
        if ($verifysig != 1) {
            error_log(openssl_error_string());
        //    return false;
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
        
        //return 'Welcome to ' . $SITE->fullname;
        return 'Welcome to As You Learn';
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
                        'shouldEndSession' => new external_value(PARAM_BOOL,'true if responses ends session')
                ))
        ));
    }
    
    /**
     * Function to get front page site announcements.
     * 
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
     * Function to get a user's grades.
     * 
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