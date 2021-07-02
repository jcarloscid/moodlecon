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

 use \JCarlosCid\Entities\MoodleLink;
 use \JCarlosCid\Entities\DAO\MoodleLinkDAO;
 use \JCarlosCid\Entities\DAO\MoodleEnrolmentDAO;
 use \jcarloscid\MoodleWSHelper;

/**
 * Enrolment entity
 */
class MoodleEnrolment {

  /**
   * Types of enrolments
   */
  public const MODE_AUTO   = 'auto';
  public const MODE_MANUAL = 'manual';

  /**
   * Status for enrolments
   */
   public const STATUS_INIT = 'init';     // Initialized but not performed
   public const STATUS_OK   = 'ok';       // Performed successfuly
   public const STATUS_ERR  = 'err';      // Performed with errors

  /**
   * Moodle WS helper
   * @var MoodleWSHelper
   */
   private static $helper = null;

   /**
    * List of Moodle roles
    * @var array
    */
   private static $roles = null;

  /**
   * Default order by property
   * @var string
   */
  private const _DEFAULT_ORDER_ = 'date_add DESC';

  // Properties
  private $ID;
  private $id_link;
  private $id_order;
  private $id_order_detail;
  private $id_customer;
  private $moodle_user_id;
  private $mode;
  private $status;
  private $notes;
  private $link;
  private $date_add;

  // Constructor
  public function __construct($ID = null, $id_link = null, $id_order = null, $id_order_detail = null, $id_customer = null, $moodle_user_id = null, $mode = null, $status = null, $notes = null, $date_add = null) {
    $this->ID              = $ID;
    $this->id_link         = $id_link;
    $this->id_order        = $id_order;
    $this->id_order_detail = $id_order_detail;
    $this->id_customer     = $id_customer;
    $this->moodle_user_id  = $moodle_user_id;
    $this->mode            = $mode;
    $this->status          = $status;
    $this->notes           = $status;
    $this->date_add        = $date_add;
    $this->link            = null;
  }

  /**
   * Initializes class static variables (called at the end of this module)
   */
  public static function Initialize() {
    self::$helper = new MoodleWSHelper(\Configuration::get('MOODLECON_WS_ENDPOINT'), \Configuration::get('MOODLECON_WS_TOKEN'));
    self::$helper->setPassword(\Configuration::get('MOODLECON_DEFAULT_PASSWD'));
    self::$roles  = self::$helper->getRoles();
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
    $this->id_link         = $data[0]['id_link'];
    $this->id_order        = $data[0]['id_order'];
    $this->id_order_detail = $data[0]['id_order_detail'];
    $this->id_customer     = $data[0]['id_customer'];
    $this->moodle_user_id  = $data[0]['moodle_user_id'];
    $this->mode            = $data[0]['mode'];
    $this->status          = $data[0]['status'];
    $this->notes           = $data[0]['notes'];

    return true;              // Success
  }

  /**
   * Stores the object in the persistance storage.
   */
  public function add() {
    //
    // Validate data
    //
    if (empty($this->id_link) or intval($this->id_link) != $this->id_link) {
      return false;
    }
    $this->id_link = intval($this->id_link);

    if (empty($this->id_order) or intval($this->id_order) != $this->id_order) {
      return false;
    }
    $this->id_order = intval($this->id_order);

    if (empty($this->id_order_detail) or intval($this->id_order_detail) != $this->id_order_detail) {
      return false;
    }
    $this->id_order_detail = intval($this->id_order_detail);

    if (empty($this->id_customer) or intval($this->id_customer) != $this->id_customer) {
      return false;
    }
    $this->id_customer = intval($this->id_customer);

    if (!empty($this->moodle_user_id) and intval($this->moodle_user_id) == $this->moodle_user_id) {
      $this->moodle_user_id = intval($this->moodle_user_id);
    } else {
      $this->moodle_user_id = null;
    }

    if ($this->mode !== self::MODE_AUTO and $this->mode !== self::MODE_MANUAL) {
      return false;
    }

    if ($this->status !== self::STATUS_OK and $this->status !== self::STATUS_ERR) {
      return false;
    }

    if (empty($this->notes)) {
      $this->notes = null;
    }

    //
    // Make the object persist
    //
    $dao = new MoodleEnrolmentDAO($this);
    $this->ID = $dao->save();

    return true;
  }

  /**
   * Get all enrolments on the database as an associative array.
   *
   * @return array List of enrolments
   */
  public static function getAllEnrolments($as_objects = false) {
    // Perform DB query
    $raw_data = (new MoodleEnrolmentDAO())->load(null, MoodleEnrolment::_DEFAULT_ORDER_);

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
      foreach ($data as $enrolment) {
        $objects[] = new MoodleEnrolment($enrolment['ID'],
                                         $enrolment['id_link'],
                                         $enrolment['id_order'],
                                         $enrolment['id_order_detail'],
                                         $enrolment['id_customer'],
                                         $enrolment['moodle_user_id'],
                                         $enrolment['mode'],
                                         $enrolment['status'],
                                         $enrolment['notes'],
                                         $enrolment['date_add']);
      }
    }

    return $objects; // Array of objects
  }

  /**
   * Get the enrolments performed for a particular order.
   *
   * @param  object  $order     PrestaShop Order instance
   * @param  boolean $auto_only Get only automatic enrolments
   * @return array              List of enrolments as MoodleEnrolment objects
   */
  public static function getEnrolmentsByOrder($order, $auto_only = false) {
    // default search condition id_order
    $conditions = [ ['field' => 'id_order', 'op' => '=', 'type' => 'd', 'value' => $order->id] ];

    // Add 'mode = auto' search condition?
    if ($auto_only) {
      $conditions[] = 'AND';
      $conditions[] = ['field' => 'mode', 'op' => '=', 'type' => 's', 'value' => MoodleEnrolment::MODE_AUTO];
    }

    // Perform DB query
    $raw_data = (new MoodleEnrolmentDAO())->load($conditions, MoodleEnrolment::_DEFAULT_ORDER_);

    // Return as an array of objects
    return self::_instantiate($raw_data);
  }

  /**
   * @return MoodleLink MoodleLink object linke to this enrolment
   */
  public function getLink() {
    if (empty($this->link)) {
      $this->link = new MoodleLink($this->id_link);
      $this->link->load();
    }

    return $this->link;
  }

  /**
   * @return string Name of the course on this enrolment
   */
  public function getCourseName() {
    return self::$helper->getCourseName($this->getLink()->course_id);
  }

  /**
   * @return string Name of the role
   */
  public function getRoleName() {
    return self::$roles[$this->getLink()->role_id];
  }

  /**
   * Compute the enrolments entitled for current order.
   *
   * @param  Order $order PS Order object
   * @return array        List of enrolments
   */
  public static function computeEnrolmenmts($order) {
    $links      = MoodleLink::getAllLinks(true);      // Get all PS <-> Moodle links defined
    $customer   = new \Customer($order->id_customer); // Get customer data
    $enrolments = array();                            // So far, no enrolments entitled

    // Check all oreder items
    foreach($order->getProductsDetail() as $order_item) {

      // Check all PS <-> Moodle links
      foreach($links as $link) {

        // Check if this product entitles for the linked course
        if ($link->entitled($order_item)) {

          // Entitled for this enrolement
          $enrolment = new MoodleEnrolment(null, $link->ID, $order->id, $order_item['id_order_detail'], $order->id_customer);

          $enrolment->link = $link;

          // Add to list
          $enrolments[] = $enrolment;
        }
      }
    }

    return $enrolments;           // All entitled enrolments
  }

  /**
   * Tries to perform the enrolments entitled for an order.
   *
   * @param  Order  $order PS order
   * @param  string $mode  auto/manual
   * @return array         List of MoodleEnrolment objects
   */
  public static function performEnrolments($order, $mode) {
    $valid_enrolments = array();

    // Compute the enrolments entitled for this order
    $enrolments = self::computeEnrolmenmts($order);

    // Is there any enrolment to perform?
    if (empty($enrolments)) {
      return array();              // Nothing to do
    }

    // Get Moodle's user ID (create new user if required)
    $customer = new \Customer($order->id_customer);
    $user_result = self::$helper->getOrCreateUser($customer->email,
                                                  $customer->firstname,
                                                  $customer->lastname);
    if ($user_result['error']) {
      foreach ($enrolments as $enrolment) {
        $enrolment->moodle_user_id = null;
        $enrolment->mode           = $mode;
        $enrolment->status         = self::STATUS_ERR;
        $enrolment->notes          = "Cannot create Moodle user";

        // Save into DB as error
        $enrolment->add();
      }
      return null;                // Error - cannot create user
    }

    // Process all enrolments
    foreach ($enrolments as $enrolment) {
      $enrolment->moodle_user_id = $user_result['moodle_user_id'];
      $enrolment->mode           = $mode;
      $enrolment->status         = self::STATUS_INIT;
      $enrolment->notes          = $user_result['is_new'] ? "New user. " : "";

      // Perform the enrolment in Moodle
      $result = self::$helper->enrol($enrolment->moodle_user_id,
                                     $enrolment->getLink()->course_id,
                                     $enrolment->getLink()->role_id,
                                     $enrolment->getLink()->enrolment_duration);

      // Comments or errors
      $enrolment->notes .= $result['msg'];

      // Set final operation result
      if ($result['error']) {
        $enrolment->status = self::STATUS_ERR;
      }  else {
        $enrolment->status = self::STATUS_OK;
      }

      // Save into DB
      if ($enrolment->add() and $enrolment->status == self::STATUS_OK) {
        $valid_enrolments[] = $enrolment;
      }
    }

    return $valid_enrolments;      // List of enrolments processed
  }
}

// Initialize class variables
MoodleEnrolment::Initialize();
