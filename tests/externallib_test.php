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
        
    }
    
    protected function tearDown() {
        
    }
    
    protected static function getMethod($name) {
        $class = new ReflectionClass('local_alexaskill_external');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
 
    /**
     * Test
     */
    public function test_launch_request() {
        global $SITE;
 
        $this->resetAfterTest();
        
        $launchrequest = self::getMethod('launch_request');
        $response = $launchrequest->invokeArgs(null, array());
 
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
                
        $this->assertTrue($expectedA == $response || $expectedB == $response);
    }
}