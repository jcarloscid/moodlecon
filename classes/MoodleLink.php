<?php
namespace JCarlosCid\Entities;
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

use \JCarlosCid\Entities\DAO\MoodleLinkDAO;

/**
 * (Product to Course) Link entity
 */
class MoodleLink {

  /**
   * Default order by property
   * @var string
   */
  private const _DEFAULT_ORDER_ = 'date_add';

  // Properties
  private $ID;
  private $id_product;
  private $product_combination;
  private $course_id;
  private $role_id;
  private $enrolment_duration;
  private $enrolment_enabled;

  // Constructor
  public function __construct($ID = null, $id_product = null, $product_combination = null, $course_id = null, $role_id = null, $enrolment_duration = null, $enrolment_enabled = false) {
    $this->ID                  = $ID;
    $this->id_product          = $id_product;
    $this->product_combination = $product_combination;
    $this->course_id           = $course_id;
    $this->role_id             = $role_id;
    $this->enrolment_duration  = $enrolment_duration;
    $this->enrolment_enabled   = $enrolment_enabled != 1 ? 0 : 1;
  }

  // Get property value
  public function __get($name){
    if (property_exists($this, $name)) {
      return $this->$name;
    }
    throw new \InvalidArgumentException('Unknown property: ' . $name);
  }

  // Set property value
  public function __set($name, $value){
    if (property_exists($this, $name)) {
      $this->$name = $value;
    } else {
      throw new \InvalidArgumentException('Unknown property: ' . $name);
    }
  }

  // Load object instance from the database using the ID
  public function load() {
    // ID must be set
    if (empty($this->ID) or intval($this->ID) != $this->ID) {
      return false;             // ID not set
    }
    $this->ID = intval($this->ID);

    // Load from database
    $data = (new MoodleLinkDAO())->load( [ ['field' => 'ID', 'op' => '=', 'type' => 'd', 'value' => $this->ID] ] );
    if (empty($data)) {
      return false;             // Not found
    }

    // Populate object properties
    $this->id_product          = $data[0]['id_product'];
    $this->product_combination = $data[0]['product_combination'];
    $this->course_id           = $data[0]['course_id'];
    $this->role_id             = $data[0]['role_id'];
    $this->enrolment_duration  = $data[0]['enrolment_duration'];
    $this->enrolment_enabled   = $data[0]['enrolment_enabled'];

    return true;              // Success
  }

  // Set link as enabled
  public function enable() {
    return $this->_set_status(1);
  }

  // Set link as disabled
  public function disable() {
    return $this->_set_status(0);
  }

  // Persist link status (enabled/disabled)
  private function _set_status($status) {
    // ID must be set
    if (empty($this->ID) or intval($this->ID) != $this->ID) {
      return false;
    }
    $this->ID = intval($this->ID);

    // Set new status
    $this->enrolment_enabled = $status;

    // Persist
    return (new MoodleLinkDAO())->updateByID( $this->ID, array( 'enrolment_enabled' => $this->enrolment_enabled ) );
  }

  /**
   * Adds a new object to the persistance storage.
   *
   * If a new object is added, the ID of the new object is set.
   *
   * If an identical object already exists, it returns true, but the ID is not set.
   *
   * @return bool true if the object persist and false if there are errors.
   */
  public function add() {
    //
    // Validate data
    //
    if (empty($this->id_product) or intval($this->id_product) != $this->id_product) {
      return false;
    }
    $this->id_product = intval($this->id_product);

    if (!is_null($this->product_combination) and intval($this->product_combination) != $this->product_combination) {
      return false;
    }
    $this->product_combination = is_null($this->product_combination) ? null : intval($this->product_combination);

    if (empty($this->course_id) or intval($this->course_id) != $this->course_id) {
      return false;
    }
    $this->course_id = intval($this->course_id);

    if (empty($this->role_id) or intval($this->role_id) != $this->role_id) {
      return false;
    }
    $this->role_id = intval($this->role_id);

    if (!empty($this->enrolment_duration)) {
      if (intval($this->enrolment_duration) != $this->enrolment_duration) {
        return false;
      }
      $this->enrolment_duration = intval($this->enrolment_duration);
      if ($this->enrolment_duration === 0) $this->enrolment_duration = null;
    } else {
      $this->enrolment_duration = null;
    }

    $this->enrolment_enabled = $this->enrolment_enabled != 1 ? 0 : 1;

    //
    // Check if an identical object already exists
    //
    $this->ID = null;
    if ($this->exists()) {
      return true;
    }

    //
    // Make the object persist
    //
    $dao = new MoodleLinkDAO($this);
    $this->ID = $dao->save();

    return true;
  }

  /**
   * Check if a link already exists on the database.
   *
   * @return bool True if the object exists.
   */
  public function exists() {
    if (!empty($this->ID)) {
      // If ID is set, search by ID
      $data = (new MoodleLinkDAO())->load( [ ['field' => 'ID', 'op' => '=', 'type' => 'd', 'value' => $this->ID] ] );
    } else {
      // If no ID is set, search by main fields
      if (empty($this->product_combination)) {
        $combination_cond = ['field' => 'product_combination', 'op' => 'IS', 'type' => ' ', 'value' => 'NULL'];
      } else {
        $combination_cond = ['field' => 'product_combination', 'op' => '=',  'type' => 'd', 'value' => $this->product_combination];
      }
      $data = (new MoodleLinkDAO())->load( [
          ['field' => 'id_product', 'op' => '=', 'type' => 'd', 'value' => $this->id_product],
          'AND',
          $combination_cond,
          'AND',
          ['field' => 'course_id',  'op' => '=', 'type' => 'd', 'value' => $this->course_id],
          'AND',
          ['field' => 'role_id',    'op' => '=', 'type' => 'd', 'value' => $this->role_id]
        ] );
    }

    // If any data is retrieved, the object exists.
    return !empty($data);
  }

  /**
   * Deletes a link that already exists.
   *
   * @return bool True if the link is deleted.
   */
  public function delete() {
    // ID must be set
    if (empty($this->ID) or intval($this->ID) != $this->ID) {
      return false;
    }
    $this->ID = intval($this->ID);

    // Check that the link already exists
    if (!$this->exists()) {
      return false;
    }

    // Delete the record
    (new MoodleLinkDAO())->deleteByID( $this->ID );
    return true;
  }

  /**
   * Retrieves product information: name and combinations.
   *
   * @param  int $id_lang Which languge to use
   * @return object       Associative array with product name and combinations
   */
  public function getProductInfo($id_lang) {
    if (empty($this->id_product)) {
      return null;
    }

    $response = array();

    // Get product name
    $product = new \ProductCore($this->id_product);
    if ( !empty($product->name) ) {
      $response['name'] = $product->name[$id_lang];
    }

    // Get combinations
    $combinations = $product->getAttributesResume($id_lang);
    if ($combinations) {
      foreach($combinations as $combination) {
        $response['combinations'][] = array( 'id'  => $combination['id_product_attribute'],
                                             'txt' => $combination['attribute_designation']);
      }
    }

    return $response;
  }

  /**
   * Get product name.
   *
   * @param  int $id_lang Which languge to use
   * @return string       Product name
   */
  public function getProductName($id_lang) {
    if (empty($this->id_product)) {
      return null;                        // Product ID not set
    }

    $product = new \ProductCore($this->id_product);
    if ( !empty($product->name) ) {
      return $product->name[$id_lang];    // Success
    }

    return '';                            // Not found
  }

  /**
   * Get product combination description.
   *
   * @param  int $id_lang Which languge to use
   * @return string       Product combination description
   */
  public function getCombinationDescription($id_lang) {
    if (empty($this->id_product) or empty($this->product_combination)) {
      return null; // Product ID and/or combination not set
    }

    $product = new \ProductCore($this->id_product);
    $combinations = $product->getAttributesResume($id_lang);
    foreach($combinations as $combination) {
      if ($this->product_combination == $combination['id_product_attribute']) {
        return $combination['attribute_designation'];   // Success
      }
    }

    return "ATT[{$this->product_combination}]";         // Not found
  }

  /**
   * Get all links on the database as an associative array.
   *
   * @param  bool $as_objects Output format are objects / associative arrays
   * @return array List of links
   */
  public static function getAllLinks($as_objects = false) {
    // Perform DB query
    $raw_data = (new MoodleLinkDAO())->load(null, self::_DEFAULT_ORDER_);

    // Check the output format
    if (!$as_objects) {
      return $raw_data;                             // Associative array
    }

    return self::_instantiate($raw_data);           // Array of objects
  }

  /**
   * Converts an associative array into an array of objects.
   *
   * @param  array $data List of associative arrays with each element data
   * @return array       List of objects
   */
  private static function _instantiate($data) {
    // Instantiate records as objects of this class
    $objects = array();
    if (!empty($data)) {
      foreach ($data as $link) {
        $objects[] = new MoodleLink($link['ID'],
                                    $link['id_product'],
                                    $link['product_combination'],
                                    $link['course_id'],
                                    $link['role_id'],
                                    $link['enrolment_duration'],
                                    $link['enrolment_enabled']);
      }      
    }

    return $objects;                              // Array of objects
  }

  /**
   * Test if a PS <-> Moodle link entitles for the linked course, according to
   * a product in the order.
   *
   * @param  object $order_item An OrderDetails (order line)
   * @return bool               True if entitled
   */
  public function entitled($order_item) {

    // Only if link is enabled
    if ($this->enrolment_enabled) {

      // Test if product ID matches
      if ($this->id_product == $order_item['product_id']) {

        // Test if link is for all combinations or if combination matches
        if (empty($this->product_combination) or $this->product_combination == $order_item['product_attribute_id']) {
          return true;                            // Entitled
        }
      }
    }
    return false;                                 // Not entitled
  }

}
