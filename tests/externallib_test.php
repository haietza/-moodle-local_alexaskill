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
 * External web service unit tests.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

global $CFG;
 
require_once($CFG->dirroot . '/local/alexaskill/externallib.php');

/**
 * External web service functions unit tests.
 *
 * @package    local_alexaskill
 * @category   external
 * @copyright  2018, Michelle Melton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_alexaskill
 */
class local_alexaskill_externallib_testcase extends externallib_advanced_testcase {
    
    /**
     * Tests set up.
     */
    protected function setUp() {
        set_config('alexaskill_applicationid', LOCAL_ALEXASKILL_TEST_CONFIG_APPLICATIONID, 'local_alexaskill');
        set_config('alexaskill_coursenameregex', LOCAL_ALEXASKILL_TEST_CONFIG_COURSENAMEREGEX, 'local_alexaskill');
    }
    
    /**
     * Tests tear down.
     */
    protected function tearDown() {
        
    }
    
    /**
     * Function to make private function accessible for tests.
     * 
     * @param string $methodname
     * @return ReflectionMethod
     */
    protected static function getMethod($methodname) {
        $class = new ReflectionClass('local_alexaskill_external');
        $method = $class->getMethod($methodname);
        $method->setAccessible(true);
        return $method;
    }
    
    /**
     * Test for valid signature certificate URL.
     */
    public function test_verify_signature_certificate_url_valid() {
        $this->resetAfterTest();
        $verifysignaturecertificateurl = self::getMethod('verify_signature_certificate_url');
        
        $certurl = 'https://s3.amazonaws.com/echo.api/echo-api-cert.pem';
        $actual = $verifysignaturecertificateurl->invokeArgs(null, array('certurl' => $certurl));
        $this->assertTrue($actual);
    }
    
    /**
     * Tests for invalid, empty, null signature certificate URL.
     */
    public function test_verify_signature_certificate_url_invalid() {
        $this->resetAfterTest();
        $verifysignaturecertificateurl = self::getMethod('verify_signature_certificate_url');
        
        $certurl = '';
        $actual = $verifysignaturecertificateurl->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);
        
        $certurl = null;
        $actual = $verifysignaturecertificateurl->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);
        
        $certurl = 'http://s3.amazonaws.com/echo.api/echo-api-cert.pem';
        $actual = $verifysignaturecertificateurl->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);
        
        $certurl = 'https://amazonaws.com/echo.api/echo-api-cert.pem';
        $actual = $verifysignaturecertificateurl->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);
        
        $certurl = 'https://s3.amazonaws.com/amazon.api/echo-api-cert.pem';
        $actual = $verifysignaturecertificateurl->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);
        
        $certurl = 'https://s3.amazonaws.com:22/echo.api/echo-api-cert.pem';
        $actual = $verifysignaturecertificateurl->invokeArgs(null, array('certurl' => $certurl));
        $this->assertFalse($actual);
    }
    
    /**
     * Test for valid timestamp.
     */
    public function test_verify_timestamp_valid() {
        $this->resetAfterTest();
        $verifytimestamp = self::getMethod('verify_timestamp');
        
        local_alexaskill_external::$json['request']['timestamp'] = gmdate('Y-m-d\TH:i:s\Z', time());
        $actual = $verifytimestamp->invokeArgs(null, array());
        $this->assertTrue($actual);
    }
    
    /**
     * Tests for invalid, empty, null timestamp.
     */
    public function test_verify_timestamp_invalid() {
        $this->resetAfterTest();
        $verifytimestamp = self::getMethod('verify_timestamp');
        
        local_alexaskill_external::$json['request']['timestamp'] = '';
        $actual = $verifytimestamp->invokeArgs(null, array());
        $this->assertFalse($actual);
        
        local_alexaskill_external::$json['request']['timestamp'] = null;
        $actual = $verifytimestamp->invokeArgs(null, array());
        $this->assertFalse($actual);
        
        local_alexaskill_external::$json['request']['timestamp'] = gmdate('Y-m-d\TH:i:s\Z', time() - 1000);
        $actual = $verifytimestamp->invokeArgs(null, array());
        $this->assertFalse($actual);
    }
    /**
     * Test for valid application ID.
     */
    public function test_verify_application_id_valid() {
        $this->resetAfterTest();
        $verifyapplicationid = self::getMethod('verify_application_id');
        
        local_alexaskill_external::$json['session']['application']['applicationId'] = LOCAL_ALEXASKILL_TEST_CONFIG_APPLICATIONID;
        $actual = $verifyapplicationid->invokeArgs(null, array());
        $this->assertTrue($actual);
    }
    
    /**
     * Tests for invalid, empty, null application ID.
     */
    public function test_verify_application_id_invalid() {
        $this->resetAfterTest();
        $verifyapplicationid = self::getMethod('verify_application_id');
        
        local_alexaskill_external::$json['session']['application']['applicationId'] = '';
        $actual = $verifyapplicationid->invokeArgs(null, array());
        $this->assertFalse($actual);
        
        local_alexaskill_external::$json['session']['application']['applicationId'] = null;
        $actual = $verifyapplicationid->invokeArgs(null, array());
        $this->assertFalse($actual);
        
        local_alexaskill_external::$json['session']['application']['applicationId'] = 'abc123';
        $actual = $verifyapplicationid->invokeArgs(null, array());
        $this->assertFalse($actual);
    }
 
    /**
     * Test launch request.
     */
    public function test_launch_request() {
        global $SITE;
        $this->resetAfterTest();
        $launchrequest = self::getMethod('launch_request');
        
        $actual = $launchrequest->invokeArgs(null, array());
        
        $expectedA = array(
                'version' => '1.0',
                'response' => array (
                        'outputSpeech' => array(
                                'type' => 'SSML',
                                'ssml' => '<speak>Welcome to ' . $SITE->fullname . '. You can get site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>'
                        ),
                        'shouldEndSession' => false
                )
        );
        
        $expectedB = array(
                'version' => '1.0',
                'response' => array (
                        'outputSpeech' => array(
                                'type' => 'SSML',
                                'ssml' => '<speak>Hello. I can get you site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>'
                        ),
                        'shouldEndSession' => false
                )
        );
                
        $this->assertTrue($expectedA == $actual || $expectedB == $actual);
        
        // Set the required capabilities by the external function
        //$contextid = context_XXXX::instance()->id;
        //$roleid = $this->assignUserCapability('moodle/CAPABILITYNAME', $contextid);
        
        //$params = array(PARAM1, PARAM2, ...);
        
        //$returnvalue = COMPONENT_external::FUNCTION_NAME($params);
        
        // We need to execute the return values cleaning process to simulate the web service server
        //$returnvalue = external_api::clean_returnvalue(COMPONENT_external::FUNCTION_NAME_returns(), $returnvalue);
        
        // Some PHPUnit assert
        //$this->assertEquals(EXPECTED_VALUE, RETURNED_VALUE);
        
        // Call without required capability
        //$this->unassignUserCapability('moodle/CAPABILITYNAME', $contextid, $roleid);
        //$this->setExpectedException('required_capability_exception');
        //$returnvalue = COMPONENT_external::FUNCTION_NAME($params);
    }
    
    public function test_get_site_announcements_valid() {
        $this->resetAfterTest();
        $getsiteannouncements = self::getMethod('get_site_announcements');
        
        // else == Y
        local_alexaskill_external::$json['request']['dialogState'] = 'IN_PROGRESS';
        local_alexaskill_external::$json['request']['intent']['slots']['else']['resolutions']['resolutionsPerAuthority'][0]['values'][0]['value']['name'] = 'yes';
        
        $actual = $getsiteannouncements->invokeArgs(null, array());
        
        $expectedA = array(
                'version' => '1.0',
                'response' => array (
                        'outputSpeech' => array(
                                'type' => 'SSML',
                                'ssml' => '<speak>You can get site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>'
                        ),
                        'shouldEndSession' => false
                )
        );
        
        $expectedB = array(
                'version' => '1.0',
                'response' => array (
                        'outputSpeech' => array(
                                'type' => 'SSML',
                                'ssml' => '<speak>I can get you site announcements <break time = "350ms"/>course announcements <break time = "350ms"/>grades <break time = "350ms"/>or due dates. Which would you like?</speak>'
                        ),
                        'shouldEndSession' => false
                )
        );
        
        $this->assertTrue($expectedA == $actual || $expectedB == $actual);
        
        // else == N
        
        // 0 announcements
        
        // 1-5 announcements
        
        // 5+ announcements
    }
}