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
 * @group      local_alexaskill_account_linking
 */
class local_alexaskill_account_linking_form_testcase extends advanced_testcase {
    
    /**
     * Tests set up.
     */
    protected function setUp() {
        set_config('alexaskill_redirecturis', LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURIS, 'local_alexaskill');
    }
    
    /**
     * Test account linking form, valid with PIN.
     * 
     * @group
     */
    public function test_account_linking_valid_new_pin() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
        
        // Set valid form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'token';
        $state = 'abc123';
        $pin = 1111;
        
        // Create and login valid user, add webservice role.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        
        $submitteddata = array(
                'pin' => $pin
        );
        
        $expectedfromform = new stdClass();
        $expectedfromform->pin = $pin;
        $expectedfromform->service = $service;
        $expectedfromform->state = $state;
        $expectedfromform->response_type = $responsetype;
        $expectedfromform->redirect_uri = $redirecturi;
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertEquals($expectedfromform, $actualfromform);
    }
    
    /**
     * Test account linking form, valid with no PIN.
     */
    public function test_account_linking_valid_new_no_pin() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
        
        // Set valid form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'token';
        $state = 'abc123';
        
        // Create and login valid user, add webservice role.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        
        $submitteddata = array(
                'pin' => ''
        );
        
        $expectedfromform = new stdClass();
        $expectedfromform->pin = 0;
        $expectedfromform->service = $service;
        $expectedfromform->state = $state;
        $expectedfromform->response_type = $responsetype;
        $expectedfromform->redirect_uri = $redirecturi;
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertEquals($expectedfromform, $actualfromform);
    }
    
    /**
     * Test account linking form, valid with changed PIN.
     */
    public function test_account_linking_valid_change_pin() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
        
        // Set valid form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'token';
        $state = 'abc123';
        
        // Create and login valid user, add webservice role.
        // Create existing PIN for user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        $userinfofield = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $userinfodata = new stdClass();
        $userinfodata->userid = $user->id;
        $userinfodata->fieldid = $userinfofield->id;
        $userinfodata->data = '1234';
        $DB->insert_record('user_info_data', $userinfodata);
        
        $submitteddata = array(
                'pin' => '4321'
        );
        
        $expectedfromform = new stdClass();
        $expectedfromform->pin = '4321';
        $expectedfromform->service = $service;
        $expectedfromform->state = $state;
        $expectedfromform->response_type = $responsetype;
        $expectedfromform->redirect_uri = $redirecturi;
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertEquals($expectedfromform, $actualfromform);
    }
    
    /**
     * Test account linking form, valid with deleted PIN.
     */
    public function test_account_linking_valid_deleted_pin() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
        
        // Set valid form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'token';
        $state = 'abc123';
        
        // Create and login valid user, add webservice role.
        // Create existing PIN for user.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        $userinfofield = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        $userinfodata = new stdClass();
        $userinfodata->userid = $user->id;
        $userinfodata->fieldid = $userinfofield->id;
        $userinfodata->data = '1234';
        $DB->insert_record('user_info_data', $userinfodata);
        
        $submitteddata = array(
                'pin' => ''
        );
        
        $expectedfromform = new stdClass();
        $expectedfromform->pin = 0;
        $expectedfromform->service = $service;
        $expectedfromform->state = $state;
        $expectedfromform->response_type = $responsetype;
        $expectedfromform->redirect_uri = $redirecturi;
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertEquals($expectedfromform, $actualfromform);
    }
    
    /**
     * Test account linking form, invalid, no external service.
     */
    public function test_account_linking_invalid_no_external_service() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
        $DB->delete_records('external_services', array('shortname' => $service));
        
        // Set valid form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'token';
        $state = 'abc123';
        $pin = 1111;
        
        // Create and login valid user, add webservice role.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        
        $submitteddata = array(
                'pin' => $pin
        );
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertNull($actualfromform);
    }
    
    /**
     * Test account linking form, invalid redirect URI.
     */
    public function test_account_linking_invalid_redirect_uri() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
                
        // Set form values.
        $redirecturi = 'https://www.google.com';
        $responsetype = 'token';
        $state = 'abc123';
        $pin = 1111;
        
        // Create and login valid user, add webservice role.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        
        $submitteddata = array(
                'pin' => $pin
        );
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertDebuggingCalled();
        $this->assertNull($actualfromform);
    }
    
    /**
     * Test account linking form, invalid response_type.
     */
    public function test_account_linking_invalid_response_type() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
                
        // Set form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'foo';
        $state = 'abc123';
        $pin = 1111;
        
        // Create and login valid user, add webservice role.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        
        $submitteddata = array(
                'pin' => $pin
        );
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertDebuggingCalled();
        $this->assertNull($actualfromform);
    }
    
    /**
     * Test account linking form, invalid, no token capability.
     */
    public function test_account_linking_invalid_no_capability() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
        
        // Set valid form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'token';
        $state = 'abc123';
        $pin = 1111;
        
        // Login as guest user with no createtoken capability.
        $this->setGuestUser();
        
        $submitteddata = array(
                'pin' => $pin
        );
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertNull($actualfromform);
    }
    
    /**
     * Test account linking form, invalid PIN < 4-digits.
     */
    public function test_account_linking_invalid_short_pin() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
        
        // Set valid form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'token';
        $state = 'abc123';
        $pin = 11;
        
        // Create and login valid user, add webservice role.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        
        $submitteddata = array(
                'pin' => $pin
        );
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertNull($actualfromform);
    }
    
    /**
     * Test account linking form, invalid PIN > 4-digits.
     */
    public function test_account_linking_invalid_long_pin() {
        global $DB;
        
        $this->resetAfterTest();
        
        // Alexa Skill external service has already been created.
        $service = 'alexa_skill_service';
        
        // Set valid form values.
        $redirecturi = LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI;
        $responsetype = 'token';
        $state = 'abc123';
        $pin = 123456;
        
        // Create and login valid user, add webservice role.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $this->getDataGenerator()->role_assign($role->id, $user->id);
        
        $submitteddata = array(
                'pin' => $pin
        );
        
        account_linking_form::mock_submit($submitteddata);
        
        $form = new account_linking_form();
        $toform = new stdClass();
        $toform->service = $service;
        $toform->state = $state;
        $toform->response_type = $responsetype;
        $toform->redirect_uri = $redirecturi;
        $form->set_data($toform);
        
        $actualfromform = $form->get_data();
        
        $this->assertNull($actualfromform);
    }
}