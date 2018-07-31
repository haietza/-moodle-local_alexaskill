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
    public function test_account_linking_valid() {
        global $CFG;
        $this->resetAfterTest();
        
        $CFG->wwwroot = LOCAL_ALEXASKILL_TEST_CONFIG_WWWROOT;
        $service = 'alexa_skill_service';
        $state = 'abc123';
        $responsetype = 'token';
        $redirecturi = 'https://pitangui.amazon.com/spa/skill/account-linking-status.html?vendorId=M1J0ZE9ZFRM0ST';

        $accountlinkingdata = array(
                'username' => LOCAL_ALEXASKILL_TEST_CONFIG_USERNAME,
                'password' => LOCAL_ALEXASKILL_TEST_CONFIG_PASSWORD
        );

        $expectedfromform = new stdClass();
        $expectedfromform->username = LOCAL_ALEXASKILL_TEST_CONFIG_USERNAME;
        $expectedfromform->password = LOCAL_ALEXASKILL_TEST_CONFIG_PASSWORD;
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
}