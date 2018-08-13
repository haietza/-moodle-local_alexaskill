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
 * Implement Privacy API.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_alexaskill\privacy;

use \core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

class provider implements
    // This plugin does store personal user data, but in the profilefield_text subsystem, which handles export and delete.
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider
{
    /**
     * Describe user preference stored for plugin and external link user data sent to.
     * 
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_subsystem_link('profilefield_text', [], 'privacy:metadata:alexaskill_alexa:userinfodata');

        $collection->add_external_location_link('alexaskill_alexa', [
                'token' => 'privacy:metadata:alexaskill_alexa:token',
                'firstname' => 'privacy:metadata:alexaskill_alexa:firstname',
                'coursefullname' => 'privacy:metadata:alexaskill_alexa:coursefullname',
                'forumdiscussionsubject' => 'privacy:metadata:alexaskill_alexa:forumdiscussionsubject',
                'forumdiscussionmessage' => 'privacy:metadata:alexaskill_alexa:forumdiscussionmessage',
                'gradereportoverview' => 'privacy:metadata:alexaskill_alexa:gradereportoverview',
                'usereventsname' => 'privacy:metadata:alexaskill_alexa:usereventsname',
                'usereventstimestart' => 'privacy:metadata:alexaskill_alexa:usereventstimestart',
                'groupeventsname' => 'privacy:metadata:alexaskill_alexa:groupeventsname',
                'groupeventstimestart' => 'privacy:metadata:alexaskill_alexa:groupeventstimestart',
                'courseeventsname' => 'privacy:metadata:alexaskill_alexa:courseeventsname',
                'courseeventstimestart' => 'privacy:metadata:alexaskill_alexa:courseeventstimestart',
                'categoryeventsname' => 'privacy:metadata:alexaskill_alexa:categoryeventsname',
                'categoryeventstimestart' => 'privacy:metadata:alexaskill_alexa:categoryeventstimestart',
                'siteeventsname' => 'privacy:metadata:alexaskill_alexa:siteeventsname',
                'siteeventstimestart' => 'privacy:metadata:alexaskill_alexa:siteeventstimestart'
        ], 'privacy:metadata:alexaskill_alexa');

        return $collection;
    }
}