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
 * Web service local plugin external functions and service definitions.
 * 
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_alexaskill_alexa' => array(
                'classname'   => 'local_alexaskill_external',
                'methodname'  => 'alexa',
                'classpath'   => 'local/alexaskill/externallib.php',
                'description' => 'Get type',
                'type'        => 'read',
        ),
        'local_alexaskill_hello_world' => array(
            'classname'   => 'local_alexaskill_external',
            'methodname'  => 'hello_world',
            'classpath'   => 'local/alexaskill/externallib.php',
            'description' => 'Says hello to user',
            'type'        => 'read',
        ),
        'local_alexaskill_get_site_news' => array(
                'classname'   => 'local_alexaskill_external',
                'methodname'  => 'get_site_news',
                'classpath'   => 'local/alexaskill/externallib.php',
                'description' => 'Gets site news',
                'type'        => 'read',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Alexa Skill' => array(
            'functions' => array ('local_alexaskill_alexa', 'local_alexaskill_hello_world', 'local_alexaskill_get_site_news'),
            //'requiredcapability' => 'mod/forum:viewdiscussion',
            'restrictedusers' => 0,
            'enabled'=> 1,
            'shortname' => 'alexa_skill_service',
        )
);