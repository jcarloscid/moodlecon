<?php
namespace JCarlosCid\Controllers;

// Do PrestaShop stuff
include_once('../../../../config/config.inc.php');
include_once('../../../../init.php');
include_once('../../../../modules/oauthsso/includes/functions.php');

// Otherwise it will not work in various browsers.
header('Access-Control-Allow-Origin: *');

use \Tools;
use \Configuration;
use \JCarlosCid\Entities\MoodleLink;
use \jcarloscid\MoodleWSHelper;

//
// Controller for AJAX actions.
//
// All methods should return an associative array or an object.
//
class AjaxController {
  //
  // Action: getProductInfo
  //
  // Retrieves product information: name and combinations.
  //
  public static function getProductInfo() {
    return ( new MoodleLink(null, Tools::getValue('id_product')) )->getProductInfo( (int)Configuration::get('PS_LANG_DEFAULT') );
  }

  //
  // Action: getCourseName
  //
  // Retrieves course name from Moodle
  //
  public static function getCourseName() {
    $helper = new MoodleWSHelper(Configuration::get('MOODLECON_WS_ENDPOINT'), Configuration::get('MOODLECON_WS_TOKEN'));
    $course_name = $helper->getCourseName(Tools::getValue('course_id'));
    return array( 'name' => $course_name );
  }
}

//
// Process AJAX call.
//
// The call must have an action parameter and a security token.
//
// If token is correct and the controller implements the action, the action
// method is called and it result is returned as JSON.
//
if (Tools::getValue('action') != '' and (Tools::getValue('ajax_token') == sha1(_COOKIE_KEY_ . 'MOODLECON'))) {
  $action = Tools::getValue('action');
  if (method_exists('\\JCarlosCid\\Controllers\\AjaxController', $action)) {
    $result = \JCarlosCid\Controllers\AjaxController::{$action}();
    echo json_encode($result);
  }
}
die();
