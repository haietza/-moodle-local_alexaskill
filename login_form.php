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
 * External web service template.
 *
 * @package   local_alexaskill
 * @author    Michelle Melton <meltonml@appstate.edu>
 * @copyright 2018, Michelle Melton
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
$formaction = $CFG->wwwroot . '/local/alexaskill/token.php';
$state= $_GET['state'];
$client_id= $_GET['client_id'];
$response_type=$_GET['response_type'];
$redirect_uri=$_GET['redirect_uri'];
?>

<div class="row">
	<div class="col-md-12">
		<div class="card">
			<div class="card-block">
				<h2>Amazon Alexa Skill Log in</h2>
				<hr>
				<form action='<?php echo $formaction; ?>' method="post">
					<label for="username" class="sr-only">Username</label>
					<input type="text" name="username" placeholder="Username" class="form-control">
					<label for="password" class="sr-only">Password</label>
					<input type="password" name="password" class="form-control">
					<input type="hidden" name="service" value="alexa_skill_service">
					<input type="hidden" name="state" value='<?php echo $state; ?>'>
					<input type="hidden" name="client_id" value='<?php echo $client_id; ?>'>
					<input type="hidden" name="response_type" value='<?php echo $response_type; ?>'>
					<input type="hidden" name="redirect_uri" value='<?php echo $redirect_uri; ?>'>
					<button type="submit" class="btn btn-primary btn-block">Log in</button>
                </form>
			</div>
		</div>
	</div>
</div>