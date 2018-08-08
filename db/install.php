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
    global $DB;

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