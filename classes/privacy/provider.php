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
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;

defined('MOODLE_INTERNAL') || die();

class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider
{
    /**
     * Describe user preference stored for plugin and external link user data sent to.
     * 
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_user_preference('amazonalexaskillpin',
                'privacy:metadata:preference:local_alexaskill_pin');

        $collection->add_external_location_link('alexaskill_alexa', [
                'accessToken' => 'privacy:metadata:alexaskill_alexa:accessToken'
        ], 'privacy:metadata:alexaskill_alexa');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $contextlist->add_user_context($userid);
        return $contextlist;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $alexapin = get_user_preferences('amazonalexaskillpin', null, $userid);
        if (null !== $alexapin) {
            $alexapindescription = get_string('alexaskill_accountlinking_pin_help', 'local_alexaskill');
        }
        writer::export_user_preference('local_alexaskill', 'amazonalexaskillpin', $alexapin, $alexapindescription);
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        if (!$field) {
            return;
        }

        $DB->delete_records('user_info_data', array('fieldid' => $field->id));
    }
    
    /**
     * Delete all personal data for user in specified context.
     * 
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $field = $DB->get_record('user_info_field', array('shortname' => 'amazonalexaskillpin'), 'id');
        if (!$field) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('user_info_data', array('userid' => $userid, 'fieldid' => $field->id));
    }
}