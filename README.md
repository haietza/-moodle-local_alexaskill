# Installation instructions
## Alexa developer console

- Upload interaction-model.json to the **JSON Editor** to use as a base for building your custom Moodle skill front-end. Make sure you update the placeholder invocation name and COURSE slot values for your skill (see Moodle instructions below for getting COURSE slot values). COURSE slot values should use preferred name format and include a comprehensive list of synonyms.
- Under **Endpoint**, select **HTTPS** and enter your web service URL in the **Default Region** field: `https://[YOUR MOODLE URL]/webservice/restalexa/server.php?wsfunction=local_alexaskill_alexa&wstoken=[YOUR WEB SERVICE USER ALEXA SKILL TOKEN]` (see Moodle instructions below for getting token). Select the appropriate **SSL certificate type** for your Moodle site.
- Enable **Account Linking**, select **Implicit Grant** for the authorization grant type, and enter your web service account linking URL in the **Authorization URI** field: `https://[YOUR MOODLE URL]/local/alexaskill/account_linking.php`. Enter the web service shortname `alexa_skill_service` in the **Client ID** field. 
  

## Moodle
- Install the `local_alexaskill` and `webservice_restalexa` plugins.
- Set the skill settings under **Site administration > Plugins > Local plugins > Alexa skill web service > Alexa skill settings**: 
  - Check **Development** if you are installing on a development instance not  internet-accessible to Alexa. There is no way to simulate a valid signature certificate, so this prevents the signature certificate validation code from running.
  - Enter the **Application ID** for your skill (found in the Alexa developer console).
  - Enter the **Redirect URIs** for your skill, comma separated or one per line (found under Account Linking in the Alexa developer console).
  - Enter the course name regular expression to parse the fullname of courses as you would like Alexa to speak them for course announcements and grades. There are several regular expression simulators available online to help with this.
- Under **Site administration > Plugins > Local plugins > Alexa skill web service > Alexa skill course slot values**, you'll find a list of course fullnames parsed according to the regular expression you input in the skill settings. Copy and paste this list into the Alexa developer console in the COURSE slot type Bulk Edit window (be sure to add optional ID and synonyms if desired).
- Under **Site administration > Plugins > Web services > Manage tokens**, create an Alexa Skill token for the web service user and append this to your Endpoint URL in the Alexa developer console.
- Assign the web service role to the users you want to have access to the Alexa skill.

### Notes
- The GetCourseAnnouncementsIntent is set to return the number of announcements configured in the specific course settings, up to 5.
- The GetDueDatesIntent is set to return the number of events to look ahead and days to look ahead configured in the site calendar settings.
- The following settings are configured on install:
  - Enable web services.
  - Add webservice role, with system context assignability and `moodle/webservice:createtoken` and `webservice/restalexa:use` capabilities.
  - Add webservice user and assign webservice role to webservice user.
  - Enable RESTALEXA protocol.
  - Add category and PIN field to default user profile fields.

## Unit Testing
Edit the config.php file to add the following configuration information near the end, but before the `require_once(dirname(__FILE__) . '/lib/setup.php');`.
```
$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '/var/www/phpu_moodledataalexa';
define('LOCAL_ALEXASKILL_TEST_CONFIG_APPLICATIONID', '[ENTER YOUR SKILL APPLICATION ID]');
define('LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURI', '[ENTER ONE OF YOUR VALID REDIRECT URIS]');
define('LOCAL_ALEXASKILL_TEST_CONFIG_REDIRECTURIS', '[ENTER COMMA SEPARATED LIST OF YOUR REDIRECT URIS]');
define('LOCAL_ALEXASKILL_TEST_CONFIG_COURSENAMEREGEX', '[ENTER YOUR COURSE NAME REGULAR EXPRESSION]');
define('LOCAL_ALEXASKILL_TEST_CONFIG_COURSENAMEREGEXMATCH', '[ENTER A COURSE NAME THAT MATCHES THE REGULAR EXPRESSION]');
define('LOCAL_ALEXASKILL_TEST_CONFIG_COURSENAMEREGEXNOMATCH', '[ENTER A COURSE NAME THAT DOES NOT MATCH THE REGULAR EXPRESSION]');
```
If you want to run just the unit tests for `local_alexaskill`, you can use the command `vendor/bin/phpunit --group=local_alexaskill`. To make sure this keeps working, please annotate all test classes with
```
/**
 * @group local_alexaskill
 */
```
