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
// MoodleEnrolment Data Access Object
//
// Database table: moodlecon_enrolments
//
class MoodleEnrolmentDAO extends CoreDAO {

  /**
   * Database table name
   * @var string
   */
  protected const TABLE_NAME = 'moodlecon_enrolments';

  /**
   * Name of the field for the ID column
   * @var string
   */
  protected const ID_FIELD  = 'ID';

  /**
   * List of all field names
   * @var array
   */
  protected const FIELDS  = array('ID', 'id_link', 'id_order','id_order_detail', 'id_customer', 'moodle_user_id', 'mode', 'status', 'notes', 'date_add');

  /**
   * Creates supporting table on the database
   * @return bool Success/failure
   */
  public static function install() {
    // Create moodlecon_enrolments table.
    $query = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . MoodleEnrolmentDAO::TABLE_NAME . '` (
      `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `id_link` int(10) UNSIGNED NOT NULL,
      `id_order` int(10) UNSIGNED NOT NULL,
      `id_order_detail` int(10) UNSIGNED NOT NULL,
      `id_customer` int(10) UNSIGNED NOT NULL,
      `moodle_user_id` int(10) UNSIGNED DEFAULT NULL,
      `mode` char(6) NOT NULL,
      `status` char(5) NOT NULL,
      `notes` varchar(512) DEFAULT NULL,
      `date_add` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    $fields = array( 'id_link'          => (int)$object->id_link,
                     'id_order'         => (int)$object->id_order,
                     'id_order_detail'  => (int)$object->id_order_detail,
                     'id_customer'      => (int)$object->id_customer,
                     'mode'             => $object->mode,
                     'status'           => $object->status);

    if (!is_null($object->moodle_user_id)) {
      $fields['moodle_user_id'] = (int)$object->moodle_user_id;
    }

    if (!is_null($object->notes)) {
      $fields['notes'] = $object->notes;
    }

    return $fields;
  }
}
