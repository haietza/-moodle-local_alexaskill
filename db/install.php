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
 * Database inserts on install.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_local_alexaskill_install() {
    global $DB, $CFG;
    
    // Add webservice role.
    if (!$DB->record_exists('role', array('shortname' => 'webservice'))) {
        $webservicerole = new stdClass();
        $webservicerole->name = 'Web Service Role';
        $webservicerole->shortname = 'webservice';
        $webservicerole->description = '<p>Role for web service user accounts</p>';
        $webservicerole->archetype = 'user';
        $roleid = $DB->insert_record('role', $webservicerole, true);
    } else {
        $role = $DB->get_record('role', array('shortname' => 'webservice'), 'id');
        $roleid = $role->id;
    }
    
    // Add system context assignability to role.
    if (!$DB->record_exists('role_context_levels', array('roleid' => $roleid, 'contextlevel' => 10))) {
        $webservicerolecontext = new stdClass();
        $webservicerolecontext->roleid = $roleid;
        $webservicerolecontext->contextlevel = 10;
        $DB->insert_record('role_context_levels', $webservicerolecontext);
    }
    
    // Add capabilities to webservice role.
    if (!$DB->record_exists('role_capabilities', array('roleid' => $roleid, 'capability' => 'moodle/webservice:createtoken'))) {
        $tokencapability = new stdClass();
        $tokencapability->contextid = 1;
        $tokencapability->roleid = $roleid;
        $tokencapability->capability = 'moodle/webservice:createtoken';
        $tokencapability->permission = 1;
        $DB->insert_record('role_capabilities', $tokencapability);
    }
    
    if (!$DB->record_exists('role_capabilities', array('roleid' => $roleid, 'capability' => 'webservice/restalexa:use'))) {
        $restalexacapability = new stdClass();
        $restalexacapability->contextid = 1;
        $restalexacapability->roleid = $roleid;
        $restalexacapability->capability = 'webservice/restalexa:use';
        $restalexacapability->permission = 1;
        $DB->insert_record('role_capabilities', $restalexacapability);
    }
    
    // Add webservice user.
    if (!$DB->record_exists('user', array('username' => 'webservice'))) {
        $webserviceuser = new stdClass();
        $webserviceuser->confirmed = 1;
        $webserviceuser->mnethostid = 1;
        $webserviceuser->username = 'webservice';
        $webserviceuser->firstname = 'Web';
        $webserviceuser->lastname = 'Service';
        $host = parse_url($CFG->wwwroot, PHP_URL_HOST);
        $webserviceuser->email = 'noreply@' . $host;
        $userid = $DB->insert_record('user', $webserviceuser, true);
    } else {
        $user = $DB->get_record('user', array('username' => 'webservice'), 'id');
        $userid = $user->id;
    }
    
    // Assign webservice role to webservice user.
    if (!$DB->record_exists('role_assignments', array('userid' => $userid, 'roleid' => $roleid))) {
        $webserviceroleassign = new stdClass();
        $webserviceroleassign->roleid = $roleid;
        $webserviceroleassign->contextid = context_system::instance()->id;
        $webserviceroleassign->userid = $userid;
        $DB->insert_record('role_assignments', $webserviceroleassign);
    }
    
    // Create token for webservice user.
    //$externalservice = $DB->get_record('external_services', array('shortname' => 'alexa_skill_service'), 'id');
    //if (!$DB->record_exists('external_tokens', array('userid' => $userid, 'externalserviceid' => $externalservice->id))) {
    //    $externaltoken = new stdClass();
    //    $externaltoken->token = md5(uniqid(rand(), 1));
    //    $externaltoken->tokentype = EXTERNAL_TOKEN_PERMANENT;
    //    $externaltoken->userid = $userid;
    //    $externaltoken->externalserviceid = $externalservice->id;
    //    $externaltoken->contextid = context_system::instance()->id;
    //    $externaltoken->timecreated = time();
    //    $DB->insert_record('external_tokens', $externaltoken);
    //}
    
    // Enable web services.
    $enablewebservices = new stdClass();
    $enablewebservices->name = 'enablewebservices';
    $enablewebservices->value = 1;
    if (!$DB->record_exists('config', array('name' => 'enablewebservices'))) {
        $DB->insert_record('config', $enablewebservices);
    } else {
        $config = $DB->get_record('config', array('name' => 'enablewebservices'), 'id');
        $enablewebservices->id = $config->id;
        $DB->update_record('config', $enablewebservices);
    }
    
    // Enable RESTALEXA protocol.
    $enablerestalexa = new stdClass();
    $enablerestalexa->name = 'webserviceprotocols';
    $enablerestalexa->value = 'restalexa';
    if (!$DB->record_exists('config', array('name' => 'webserviceprotocols'))) {
        $DB->insert_record('config', $enablerestalexa);
    } else {
        $webserviceprotocols = $DB->get_record('config', array('name' => 'webserviceprotocols'), 'id, value');
        if (stripos($webserviceprotocols->value, 'restalexa') === false) {
            $enablerestalexa->id = $webserviceprotocols->id;
            if ($webserviceprotocols->value != '') {
                $enablerestalexa->value = $webserviceprotocols->value . ', restalexa';
            }
            $DB->update_record('config', $enablerestalexa);
        }
    }

    // Add category and field to default user profile fields.
    $categoryname = 'Amazon Alexa skill';

    // Add user info category.
    if (!$DB->record_exists('user_info_category', array('name' => $categoryname))) {
        $userinfocategory = new stdClass();
        $userinfocategory->name = $categoryname;
        $userinfocategory->sortorder = 1;
        $userinfocategoryid = $DB->insert_record('user_info_category', $userinfocategory, true);
    } else {
        $record = $DB->get_record('user_info_category', array('name' => $categoryname), 'id');
        $userinfocategoryid = $record->id;
    }

    // Add user info field.
    $userinfofieldname = 'amazonalexaskillpin';
    if (!$DB->record_exists('user_info_field', array('shortname' => $userinfofieldname))) {
        $userinfofield = new stdClass();
        $userinfofield->shortname = $userinfofieldname;
        $userinfofield->name = get_string('alexaskill_accountlinking_pin', 'local_alexaskill');
        $userinfofield->datatype = 'text';
        $userinfofield->categoryid = $userinfocategoryid;
        $userinfofield->description = get_string('alexaskill_accountlinking_pin_help', 'local_alexaskill');
        $userinfofield->sortorder = 1;
        $userinfofield->required = 0;
        $userinfofield->locked = 0;
        $userinfofield->visible = 0;
        $userinfofield->forceunique = 0;
        $userinfofield->signup = 0;
        $userinfofield->defaultdata = '';
        $userinfofield->param1 = 4;
        $userinfofield->param2 = 4;
        $userinfofield->param3 = 1;
        $userinfofield->param4 = null;
        $userinfofield->param5 = null;
        $DB->insert_record('user_info_field', $userinfofield, false);
    }
}