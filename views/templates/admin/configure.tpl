{*
* @package   	Moodle Connector
* @author     Carlos Cid <carlos@fishandbits.es>
* @copyright 	Copyleft 2021 http://fishandbits.es
* @license   	GNU/GPL 2 or later
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
*
* The "GNU General Public License" (GPL) is available at
* http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
*}

<script>
  var ajax_path = '{$ajax_path}';
  var ajax_token = '{$ajax_token}';
</script>
<div class="panel">
  <h3>{l s='What does this module do?' mod='moodlecon'}</h3>
  <img src="{$module_dir|escape}/views/img/logo-moodle.png" class="pull-left" id="moodlecon-logo" />
  <p>{l s='This module integrates your PrestaShop shop with a Moodle instance, making possible to buy a product (a course or a product that entitles for a course) in your shop and to automatically enrol the customer on the linked course on Moodle.' mod='moodlecon'}</p>
  <p>{l s='The following Moodle plugin is required:' mod='moodlecon'} <code>local_wsgetroles</code> (<a target='_blank' rel='noopener noreferrer nofollow' href='https://moodle.org/plugins/local_wsgetroles'>{l s='get the plugin' mod='moodlecon'}</a>)</p>
  <p>{l s='The module uses Moodle\'s web service in order to communicate with your Moodle instance. Only REST protocol is supported. You will need to enebale this service and create one access token for the module.' mod='moodlecon'}
     {l s='The following actions needs to be authorized for the service:' mod='moodlecon'}</p>
  <ul>
    <li><code>core_webservice_get_site_info</code></li>
    <li><code>core_course_get_courses</code></li>
    <li><code>core_user_get_users_by_field</code></li>
    <li><code>core_user_create_users</code></li>
    <li><code>enrol_manual_enrol_users</code></li>
    <li><code>local_wsgetroles_get_roles</code> ({l s='from plugin' mod='moodlecon'} <code>local_wsgetroles</code>)</li>
  </ul>
</div>
