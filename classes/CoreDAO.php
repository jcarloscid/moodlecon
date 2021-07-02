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

//
// Abstract class to implement a Data Access Object (DAO) class
//
abstract class CoreDAO {

  /**
   * Database table name (must override)
   * @var string
   */
  protected const TABLE_NAME = '<undefined>';

  /**
   * Name of the field for the ID column (must override)
   * @var string
   */
  protected const ID_FIELD  = '<undefined>';

  /**
   * Name of the field with the last uopdate timestamp (can override)
   * @var string
   */
  protected const UPDATE_TS_FIELD  = null;

  /**
   * List of all field names (must override)
   * @var array
   */
  protected const FIELDS  = null;

  /**
   * Data holded by the object. Can be a single record or a set.
   * @var mixed
   */
  protected $data;

  /**
   * Constructor
   */
  public function __construct($data = null) {
    $this->data = $data;
  }

  /**
   * Drop supporting database table
   */
  public static function uninstall() {
    // Drop table
    $query = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . static::TABLE_NAME . '`';
    \Db::getInstance()->execute($query);
    return true;
  }

  /**
   * Initializes the object by clearing any previous data stored on it.
   */
  public function initialize() {
    $this->data = null;
  }

  /**
   * Gets data from the DAO object.
   *
   * It can return a single element ($index) or the entire array.
   *
   * @param  mixed $index Index of the element to return
   * @return mixed        Requested data or null
   */
  public function getData($index = false) {
    if ( !$index ) {
      return $this->data;
    }
    if ($index and is_array($this->data)) {
      return $this->data[$index];
    }
    return null;
  }

  /**
   * @return int Number of elements stored in the DAO object.
   */
  public function count() {
    if (is_null($this->data) or empty($this->data)) {
      return 0;
    } elseif (is_array($this->data)) {
      return sizeof($this->data);
    }
    return 1;
  }

  /**
   * Loads data from the database into the object instance and returns this data.
   *
   * If $conditions is null, all data is retrieved.
   *
   * $conditions can be a single condition or an array
   *
   * A single condition can be like 'id_product IS NOT NULL' or an array like
   * ['field' => 'id_product', 'op' => '=', 'value' => '400']
   * or a logical condition operator like 'AND'
   *
   * $order can be like 'date_add ASC' or an array like [ 'date_add', 'date_updated', 'DESC']
   *
   * @param  mixed $conditions Conditions to meet
   * @param  mixed $order      Order of the data
   * @return mixed             Data retrieved from the database
   */
  public function load($conditions = null, $order = null) {

    // List of field names
    $fields = '';
    foreach(static::FIELDS as $field) {
      $fields .= empty($fields) ? '' : ', ';
      $fields .= "`{$field}`";
    }

    // Create a basic query
    $dbquery = new \DbQuery();
    $dbquery->select($fields);
    $dbquery->from(static::TABLE_NAME);

    // Process $conditions
    if ( !empty($conditions) ) {
      if ( is_array($conditions) ) {
        // process each condition in the array
        $where = '';
        foreach ($conditions as $condition) {
          if ( is_array($condition) ) {
            // the condition is an array like
            // ['field' => 'id_product', 'op' => '=', 'value' => '400']
            $field = empty($condition['field']) ? '1' : $condition['field'];
            $op    = $condition['op'];
            if ($condition['type'] == 's') {
              $value = "'{$condition['value']}'";
            } else {
              $value = $condition['value'];
            }

            // add subclause
            $where .= " {$field} {$op} {$value} ";
          } else {
            // a direct condition - add subclause
            $where .= " {$condition} ";
          }
        }
        // Set the query condition
        $dbquery->where($where);
      } else {
        // $conditions is a single string
        $dbquery->where($conditions);
      }
    }

    // Process $order
    if ( !empty($order) ) {
      if ( is_array($order) ) {
        // it is an array, convert to a string
        $dbquery->orderBy(implode(' ', $order));
      } else {
        // it is a direct ORDER BY clause
        $dbquery->orderBy($order);
      }
    }

    // Run the query
    $this->data = \Db::getInstance()->executeS($dbquery->build());

    // Return query result
    return $this->data;
  }

  /**
   * Deletes a record using its ID.
   *
   * @param  int $id Record ID
   * @return bool
   */
  public function deleteByID($id) {
    $id_field = static::ID_FIELD;
    return \Db::getInstance()->delete(static::TABLE_NAME, "{$id_field} = {$id}", 1);
  }

  /**
   * Updates a single record using its ID.
   *
   * @param  int $id        ID of the record to update
   * @param  array $updates List of updates ( 'field' => 'new-value' )
   * @return bool
   */
  public function updateByID($id, $updates) {
    $id_field = static::ID_FIELD;
    $all_updates = is_null(static::UPDATE_TS_FIELD) ? $updates : array_merge($updates, array(static::UPDATE_TS_FIELD => 'CURRENT TIMESTAMP'));
    return \Db::getInstance()->update(static::TABLE_NAME, $all_updates, "{$id_field} = {$id}", 1);
  }

  /**
   * Save data into the database as new records.
   *
   * If no data is specified, the data holded by the objet is inserted.
   *
   * @param  mixed $data Data to save. Can be a single element or an array.
   * @return mixed       ID(s) of the inserted records.
   */
  public function save($data = null) {
    if (!empty($data)) {
      $this->data = $objects;
    }

    if (is_array($this->data)) {
      $ids = array();
      foreach ($this->data as $element) {
        $ids[] = $this->_saveElement($element);
      }
    } else {
      $ids = $this->_saveElement($this->data);
    }

    return $ids;
  }

  /**
   * Save a single element into the database
   *
   * @param  object $element Element to be saved
   * @return int             ID assigned to the new element
   */
  private function _saveElement($element) {
    // Insert a single record
    \Db::getInstance()->insert(static::TABLE_NAME, static::getFields($element), true);

    // Return inserted ID
    return \Db::getInstance()->Insert_ID();
  }

  /*
   * ######################################################
   * # Abtract methods to be implemented by child classes #
   * ######################################################
   */

  /**
   * Creates supporting table on the database
   *
   * @return bool Success/failure
   */
  public static abstract function install();

  /**
   * Builds an array with the pairs of fields => value with the properties
   * of $object.
   *
   * This function is aware of the entity (class) behind this DAO object.
   *
   * @param  object $object Data source
   * @return array          Object fields as an array
   */
  protected abstract function getFields($element);
}
