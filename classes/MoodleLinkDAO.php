<?php
namespace JCarlosCid\Entities\DAO;
/**
 * @package       Moodle Connector
 * @author        Carlos Cid <carlos@fishandbits.es>
 * @copyright     Copyleft 2021 http://fishandbits.es
 * @license       GNU/GPL 2 or later
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
 */

use \JCarlosCid\Entities\DAO\CoreDAO;

//
// MoodleLink Data Access Object
//
// Database table: moodlecon_links
//
class MoodleLinkDAO extends CoreDAO {

  /**
   * Database table name
   * @var string
   */
  protected const TABLE_NAME = 'moodlecon_links';

  /**
   * Name of the field for the ID column
   * @var string
   */
  protected const ID_FIELD  = 'ID';

  /**
   * List of all field names (must override)
   * @var array
   */
  protected const FIELDS  = array('ID', 'id_product', 'product_combination', 'course_id', 'role_id', 'enrolment_duration', 'enrolment_enabled', 'date_add', 'date_updated');

  /**
   * Name of the field with the last uopdate timestamp (can override)
   * @var string
   */
  protected const UPDATE_TS_FIELD  = 'date_updated';

  /**
   * Creates supporting table on the database
   *
   * @return bool Success/failure
   */
  public static function install() {
    // Create moodlecon_links table.
    $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . MoodleLinkDAO::TABLE_NAME . '` (
      `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `id_product` int(10) UNSIGNED DEFAULT NULL,
      `product_combination` int(10) UNSIGNED DEFAULT NULL,
      `course_id` int(10) UNSIGNED DEFAULT NULL,
      `role_id` int(10) UNSIGNED DEFAULT NULL,
      `enrolment_duration` int(10) UNSIGNED DEFAULT NULL,
      `enrolment_enabled` tinyint(1) DEFAULT NULL,
      `date_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`ID`))';
    if (!\Db::getInstance()->execute($query)) {
      error_log('Failed to execute: ' . $query);
      return false;
    }
    return true;
  }

  /**
   * Builds an array with the pairs of fields => value with the properties
   * of $object.
   *
   * This function is aware of the entity (class) behind this DAO object.
   *
   * @param  object $object Data source
   * @return array          Object fields as an array
   */
  protected function getFields($object) {
    $fields = array( 'id_product'          => (int)$object->id_product,
                     'course_id'           => (int)$object->course_id,
                     'role_id'             => (int)$object->role_id,
                     'enrolment_enabled'   => (int)$object->enrolment_enabled);

    if (!is_null($object->product_combination)) {
      $fields['product_combination'] = (int)$object->product_combination;
    }

    if (!is_null($object->enrolment_duration)) {
      $fields['enrolment_duration'] = (int)$object->enrolment_duration;
    }

    return $fields;
  }
}
