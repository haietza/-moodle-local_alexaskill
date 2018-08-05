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
 * External account linking form unit tests.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/alexaskill/account_linking_form.php');

/**
 * External account linking form unit tests.
 *
 * @package    local_alexaskill
 * @copyright  2018, Michelle Melton
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      local_alexaskill
 */
class local_alexaskill_account_linking_form_testcase extends advanced_testcase {
    /**
     * Test account linking form, valid with no PIN.
     */
    public function test_account_linking_valid_login_no_pin() {
        global $CFG;
        $this->resetAfterTest();

        // Set wwwroot for phpu site to be same as site, so curl call in account linking will work.
        $CFG->wwwroot = LOCAL_ALEXASKILL_TEST_CONFIG_WWWROOT;
        $service = 'alexa_skill_service';
        $state = 'abc123';
        $responsetype = 'token';
        $redirecturi = 'https://pitangui.amazon.com/spa/skill/account-linking-status.html?vendorId=M1J0ZE9ZFRM0ST';

        // Set phpu account username and password to valid test account with token permission for valid test.
        // phpu created users cannot be verified in login/token.php.
        $accountlinkingdata = array(
                'username' => LOCAL_ALEXASKILL_TEST_CONFIG_USERNAME,
                'password' => LOCAL_ALEXASKILL_TEST_CONFIG_PASSWORD,
                'pin' => ''
        );

        $expectedfromform = new stdClass();
        $expectedfromform->username = LOCAL_ALEXASKILL_TEST_CONFIG_USERNAME;
        $expectedfromform->password = LOCAL_ALEXASKILL_TEST_CONFIG_PASSWORD;
        $expectedfromform->pin = 0;
        $expectedfromform->service = $service;
        $expectedfromform->state = $state;
        $expectedfromform->response_type = $responsetype;
        $expectedfromform->redirect_uri = $redirecturi;  

        account_linking_form::mock_submit($accountlinkingdata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->state = $state;
        $toform->service = $service;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);

        $actualfromform = $form->get_data();

        $this->assertEquals($expectedfromform, $actualfromform);
    }
    
    /**
     * Test account linking form, valid with PIN.
     */
    public function test_account_linking_valid_login_pin() {
        global $CFG;
        $this->resetAfterTest();
        
        // Set wwwroot for phpu site to be same as site, so curl call in account linking will work.
        $CFG->wwwroot = LOCAL_ALEXASKILL_TEST_CONFIG_WWWROOT;
        $service = 'alexa_skill_service';
        $state = 'abc123';
        $responsetype = 'token';
        $redirecturi = 'https://pitangui.amazon.com/spa/skill/account-linking-status.html?vendorId=M1J0ZE9ZFRM0ST';
        
        // Set phpu account username and password to valid test account with token permission for valid test.
        // phpu created users cannot be verified in login/token.php.
        $accountlinkingdata = array(
                'username' => LOCAL_ALEXASKILL_TEST_CONFIG_USERNAME,
                'password' => LOCAL_ALEXASKILL_TEST_CONFIG_PASSWORD,
                'pin' => '1111'
        );
        
        $expectedfromform = new stdClass();
        $expectedfromform->username = LOCAL_ALEXASKILL_TEST_CONFIG_USERNAME;
        $expectedfromform->password = LOCAL_ALEXASKILL_TEST_CONFIG_PASSWORD;
        $expectedfromform->pin = 1111;
        $expectedfromform->service = $service;
        $expectedfromform->state = $state;
        $expectedfromform->response_type = $responsetype;
        $expectedfromform->redirect_uri = $redirecturi;
        
        account_linking_form::mock_submit($accountlinkingdata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->state = $state;
        $toform->service = $service;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertEquals($expectedfromform, $actualfromform);
    }

    /**
     * Test account linking form, invalid no PIN.
     */
    public function test_account_linking_invalid_login_no_pin() {
        global $CFG;
        $this->resetAfterTest();

        $CFG->wwwroot = LOCAL_ALEXASKILL_TEST_CONFIG_WWWROOT;
        $username = 'test';
        $password = 'test';
        $service = 'alexa_skill_service';
        $state = 'abc123';
        $responsetype = 'token';
        $redirecturi = 'https://pitangui.amazon.com/spa/skill/account-linking-status.html?vendorId=M1J0ZE9ZFRM0ST';

        $accountlinkingdata = array(
                'username' => $username,
                'password' => $password,
                'pin' => 0
        );

        account_linking_form::mock_submit($accountlinkingdata);

        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->state = $state;
        $toform->service = $service;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);

        $actualfromform = $form->get_data();

        // Invalid users will display error on login form
        // but return null from get_data because they cannot be validated.
        $this->assertNull($actualfromform);
    }
    
    public function test_account_linking_invalid_login_pin() {
        global $CFG;
        $this->resetAfterTest();
        
        $CFG->wwwroot = LOCAL_ALEXASKILL_TEST_CONFIG_WWWROOT;
        $username = 'test';
        $password = 'test';
        $service = 'alexa_skill_service';
        $state = 'abc123';
        $responsetype = 'token';
        $redirecturi = 'https://pitangui.amazon.com/spa/skill/account-linking-status.html?vendorId=M1J0ZE9ZFRM0ST';
        
        $accountlinkingdata = array(
                'username' => $username,
                'password' => $password,
                'pin' => 12
        );
        
        account_linking_form::mock_submit($accountlinkingdata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->state = $state;
        $toform->service = $service;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        // Invalid users will display error on login form
        // but return null from get_data because they cannot be validated.
        $this->assertNull($actualfromform);
    }
    
    /**
     * Test account linking form, valid login with invalid PIN.
     */
    public function test_account_linking_valid_login_invalid_pin() {
        global $CFG;
        $this->resetAfterTest();
        
        // Set wwwroot for phpu site to be same as site, so curl call in account linking will work.
        $CFG->wwwroot = LOCAL_ALEXASKILL_TEST_CONFIG_WWWROOT;
        $service = 'alexa_skill_service';
        $state = 'abc123';
        $responsetype = 'token';
        $redirecturi = 'https://pitangui.amazon.com/spa/skill/account-linking-status.html?vendorId=M1J0ZE9ZFRM0ST';
        
        // Set phpu account username and password to valid test account with token permission for valid test.
        // phpu created users cannot be verified in login/token.php.
        $accountlinkingdata = array(
                'username' => LOCAL_ALEXASKILL_TEST_CONFIG_USERNAME,
                'password' => LOCAL_ALEXASKILL_TEST_CONFIG_PASSWORD,
                'pin' => '111122'
        );
        
        $expectedfromform = new stdClass();
        $expectedfromform->username = LOCAL_ALEXASKILL_TEST_CONFIG_USERNAME;
        $expectedfromform->password = LOCAL_ALEXASKILL_TEST_CONFIG_PASSWORD;
        $expectedfromform->pin = 111122;
        $expectedfromform->service = $service;
        $expectedfromform->state = $state;
        $expectedfromform->response_type = $responsetype;
        $expectedfromform->redirect_uri = $redirecturi;
        
        account_linking_form::mock_submit($accountlinkingdata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->state = $state;
        $toform->service = $service;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        // Invalid PIN will display error on login form
        // but return null from get_data because form data cannot be validated.
        $this->assertNull($actualfromform);
    }
}