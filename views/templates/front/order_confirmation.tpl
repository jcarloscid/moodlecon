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

<section id="moodlecon-order_confirmation" class="card definition-list">
  <div class="card-block">
    <div class="row">
      <div class="col-md-12 moodlecon-header">
        <img src="{$module_dir|escape}/views/img/logo-moodle.png" class="pull-left" id="moodlecon-logo" />
        <h3 class="card-title h3">{$moodle_name}</h3>
      </div>
      <div class="col-md-12 moodlecon-list">
        <h4 class="h4">{l s='You order entitles for enrolment on the following courses:' mod='moodlecon'}</h4>
        <ul>
          {foreach from=$courses item=course}
            <li>{$course}</li>
          {/foreach}
        </ul>
        <p>{l s='You will be automatically enrolled upon order payment confirmation.' mod='moodlecon'}</p>
      </div>
    </div>
  </div>
</section>
