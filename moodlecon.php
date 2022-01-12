<?php
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

/* This needs to be executed from inside PrestaShop */
if ( !defined('_PS_VERSION_') ) exit;

use \JCarlosCid\Entities\MoodleLink;
use \JCarlosCid\Entities\MoodleEnrolment;
use \JCarlosCid\Entities\DAO\MoodleLinkDAO;
use \JCarlosCid\Entities\DAO\MoodleEnrolmentDAO;
use \jcarloscid\MoodleWSHelper;

/*
 * Module: moodlecon - Main class
 */
class MoodleCon extends Module {

  // List of roles defined in moodle format: [ [id => shortname] ]
  private $moodle_roles;

  /**
   * Constructor
   */
  public function __construct() {
    $this->name = 'moodlecon';             // nombre del módulo el mismo que la carpeta y la clase.
    $this->tab = 'administration';         // pestaña en la que se encuentra en el backoffice.
    $this->version = '0.0.1';              // versión del módulo
    $this->author ='Carlos Cid';           // autor del módulo
    $this->need_instance = 1;              // si no necesita cargar la clase en la página módulos, 1 si fuese necesario.
    $this->ps_versions_compliancy = array('min' => '1.7.x.x', 'max' => _PS_VERSION_); // las versiones con las que el módulo es compatible.
    $this->bootstrap = true;               // si usa bootstrap.

    parent::__construct();                 // llamada al constructor padre.

    $this->displayName = $this->l('Moodle Connector');   // Nombre del módulo
    $this->description = $this->l('Allows to enrol customers into your Moodle School\'s courses, after buying the enrolment in PrestaShop'); // Descripción del módulo
    $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?'); // mensaje de alerta al desinstalar el módulo.

    if (!Configuration::get('MOODLECON_WS_ENDPOINT')) {
      Configuration::updateValue('MOODLECON_WS_ENDPOINT', '');
    }
    if (!Configuration::get('MOODLECON_WS_TOKEN')) {
      Configuration::updateValue('MOODLECON_WS_TOKEN', '');
    }
    if (!Configuration::get('MOODLECON_AUTO_ENROL')) {
      Configuration::updateValue('MOODLECON_AUTO_ENROL', 0);
    }
    if (!Configuration::get('MOODLECON_MANUAL_ENROL')) {
      Configuration::updateValue('MOODLECON_MANUAL_ENROL', 0);
    }
    if (!Configuration::get('MOODLECON_DEFAULT_PASSWD')) {
      Configuration::updateValue('MOODLECON_DEFAULT_PASSWD', '');
    }
    if (!Configuration::get('MOODLECON_SEND_EMAIL')) {
      Configuration::updateValue('MOODLECON_SEND_EMAIL', 0);
    }
  }

  /*
   * Install module:
   * - Register hooks.
   * - Set default values for module parameters
   * - Create database objects
   */
  public function install() {
    // As the module is not installed yet, we need to manually call to autoload
    require_once __DIR__ . '/vendor/autoload.php';

    //TODO: Install files (mails)
    return (parent::install()
            && $this->installFiles()
            && MoodleLinkDAO::install()
            && MoodleEnrolmentDAO::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('actionGetAdminOrderButtons')
            && $this->registerHook('displayOrderConfirmation')
            && Configuration::updateValue('MOODLECON_WS_ENDPOINT', '')
            && Configuration::updateValue('MOODLECON_WS_TOKEN', '')
            && Configuration::updateValue('MOODLECON_AUTO_ENROL', 0)
            && Configuration::updateValue('MOODLECON_MANUAL_ENROL', 0)
            && Configuration::updateValue('MOODLECON_DEFAULT_PASSWD', '')
            && Configuration::updateValue('MOODLECON_SEND_EMAIL', 0)
           );
  }

  /*
   * Uninstall module:
   * - Unregister hooks.
   * - Delete module parameters.
   * - Drop database objects
   */
  public function uninstall() {
    $this->_clearCache('*');

    //TODO: Uninstall files (mails)
    if (  !parent::uninstall()
       || $this->uninstallFiles()
       || !MoodleLinkDAO::uninstall()
       || !MoodleEnrolmentDAO::uninstall()
       || !$this->unregisterHook('displayHeader')
       || !$this->unregisterHook('displayBackOfficeHeader')
       || !$this->unregisterHook('actionOrderStatusPostUpdate')
       || !$this->unregisterHook('actionGetAdminOrderButtons')
       || !$this->unregisterHook('displayOrderConfirmation')
       || !Configuration::deleteByName('MOODLECON_WS_ENDPOINT')
       || !Configuration::deleteByName('MOODLECON_WS_TOKEN')
       || !Configuration::deleteByName('MOODLECON_AUTO_ENROL')
       || !Configuration::deleteByName('MOODLECON_MANUAL_ENROL')
       || !Configuration::deleteByName('MOODLECON_DEFAULT_PASSWD')
       || !Configuration::deleteByName('MOODLECON_SEND_EMAIL')
       )
      return false;

    return true;
  }

  /**
   * Returns a list of files to install
   */
  protected function get_files_to_install() {
    // Read current language.
    $language = strtolower(trim(strval(Language::getIsoById($this->context->language->id))));

    // All languages to be installed for.
    $languages = array_unique(array('en', 'es', $language));

    // List of templates
    $templates = array('moodlecon_notify.html',
                       'moodlecon_notify.txt');

    // Install email templates
    foreach ($languages as $language) {
      // For unknown languages install the English version
      $src_language = ($language == 'es') ? 'es' : 'en';
      $source = _PS_MODULE_DIR_ . $this->name . '/upload/mails/' . $src_language . '/';
      $target = _PS_MODULE_DIR_ . $this->name . '/mails/' . $language . '/';

      // Make sure the directory exists
      if (!is_dir($target)) {
        mkdir($target, 0755, true);
      }

      // Install all templates for this language
      foreach ($templates as $template) {
        $files[] = array(
            'name'   => $template,
            'source' => $source,
            'target' => $target
        );
      }
    }

    // Done
    return $files;
  }

  public function installFiles() {
    // Store the added files
    $files_added = array();

    // Get files to install.
    $files = $this->get_files_to_install();

    // Install files.
    foreach ($files as $file_data) {
      if (is_array($file_data) && !empty($file_data['name']) && !empty($file_data['source']) && !empty($file_data['target'])) {
        if (!file_exists($file_data['target'] . $file_data['name'])) {
          if (!copy($file_data['source'] . $file_data['name'], $file_data['target'] . $file_data['name'])) {
            // Add Error
            $this->context->controller->errors[] = 'Could not copy the file ' . $file_data['source'] . $file_data['name'] . ' to ' . $file_data['target'] . $file_data['name'];

            // Rollback the copied files in case of an error
            foreach ($files_added as $file_added) {
              if (file_exists($file_added)) {
                @unlink($file_added);
              }
            }

            // Abort Installation.
            return false;
          } else {
            $files_added[] = $file_data['target'] . $file_data['name'];
          }
        }
      }
    }

    return true;
  }

  public function uninstallFiles() {
    // Get files to remove.
    $files = $this->get_files_to_install();

    // Remove files
    foreach ($files as $file_data) {
      if (is_array($file_data) && !empty($file_data['name']) && !empty($file_data['source']) && !empty($file_data['target'])) {
        if (file_exists($file_data['target'] . $file_data['name'])) {
          @unlink($file_data['target'] . $file_data['name']);
        }
      }
    }

    return true;
  }

  /*
   * Header hook:
   * - Enqueue styles.
   * - Enqueue scripts.
   */
  public function hookDisplayHeader($params) {
    $this->context->controller->registerStylesheet('modules-moodlecon', 'modules/'.$this->name.'/views/css/moodlecon.css', ['media' => 'all', 'priority' => 150]);
    //[Empty]$this->context->controller->registerJavascript('modules-moodlecon', 'modules/'.$this->name.'/views/js/moodlecon.js', ['position' => 'bottom', 'priority' => 150]);
  }

  /*
   * BackOffice Header hook:
   * - Enqueue styles.
   * - Enqueue scripts.
   */
  public function hookDisplayBackOfficeHeader($params) {
    if (Tools::getValue('configure') == $this->name) {
      $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/views/css/moodlecon-admin.css');
      $this->context->controller->addJS(Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/views/js/moodlecon-admin.js');
    }
  }

  /*
   * actionOrderStatusPostUpdate hook:
   *
   * This hook works when automatic enrolment is active.
   *
   * The hook is triggered when the status of an order has changed. If the new
   * status implies that the order has been paid, we check if we have already
   * performed the automatic enrolments for this order. In the negative case
   * they are performed now.
   *
   * Does not produce any output to the user.
   */
  public function hookActionOrderStatusPostUpdate($params) {
    // Only if automatic enrolment is enabled
    if (Configuration::get('MOODLECON_AUTO_ENROL') === '1') {
      // Check wether new order status implies 'payment confirmed'
      $newOrderStatus = $params['newOrderStatus'];
      if ($newOrderStatus->paid === '1') {
        $id_order = $params['id_order'];
        $order = new Order((int)$id_order);

        // Check if we have already performed the enrolments
        $enrolments = MoodleEnrolment::getEnrolmentsByOrder($order, true);
        if (empty($enrolments)) {
          // Perform enrolments now
          $enrolments = MoodleEnrolment::performEnrolments($order, MoodleEnrolment::MODE_AUTO);

          // Send e-mail notifications (if required)
          $this->sendNotifications($enrolments);
        }
      }
    }

    return;
  }

  /*
   * actionGetAdminOrderButtons hook:
   *
   * This hook works when manual  enrolment is active.
   *
   * Displays a button on the admin order page.
   *
   * The button allows to trigger the enrolment of customers on the courses
   * according to the rules defined for this module.
   */
  public function hookActionGetAdminOrderButtons($params) {
    // Only if manual enrolment is enabled
    if (Configuration::get('MOODLECON_MANUAL_ENROL') !== '1') {
        return;
    }

    # Taken from: https://www.prestashop.com/forums/topic/1042047-show-notification-in-admin-order-view-page/
    $id_order = $params['id_order'];
  	$order = new Order((int)$id_order);
  	$order_state = new OrderState((int)$order->current_state);

    // Only display button for paid orders.
    if ($order_state->paid !== '1') {
      return;
    }

   	/** @var \Symfony\Bundle\FrameworkBundle\Routing\Router $router */
   	$router = $this->get('router');

   	/** @var \PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButtonsCollection $bar */
   	$bar = $params['actions_bar_buttons_collection'];
   	$viewOrderUrl = $router->generate('admin_orders_view', ['orderId'=> (int)$id_order]);
   	$enrolNowUrl = $viewOrderUrl.'&enrol_now=1';
   	$bar->add(
   		new \PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButton(
   			'btn btn-action', ['href' => $enrolNowUrl], '<i class="material-icons">cast_for_education</i> '. $this->l('Enrol Now')
   		)
   	);

    // This process the button click event
    if (Tools::getValue('enrol_now')) {
      // Perform enrolments now
      $enrolments = MoodleEnrolment::performEnrolments($order, MoodleEnrolment::MODE_MANUAL);

      // Send e-mail notifications (if required)
      $this->sendNotifications($enrolments);

      // Test output
      if (is_null($enrolments)) {
        // Errors while performing enrolments
        $message_txt = $this->l('There was an error while trying to perform enrolments. Check logs.');
        $type = "error";
        $this->get('session')->getFlashBag()->add($type, $message_txt);
      } elseif (empty($enrolments)) {
        // No enrolments performed
        $message_txt = $this->l('No enrolments entitled for current order.');
        $type = "warning";
        $this->get('session')->getFlashBag()->add($type, $message_txt);
      } else {
        // Display the name of the courses
        foreach($enrolments as $enrolment) {
          $message_txt = $this->l('Enroled in course') . " '{$enrolment->getCourseName()}'.";
          $type = "success";
          $this->get('session')->getFlashBag()->add($type, $message_txt);
        }
      }

      Tools::redirectAdmin($viewOrderUrl);
    }

    return;
  }

  /*
   * displayOrderConfirmation hook:
   *
   * This hook works when automatic enrolment is active.
   *
   * Ths hook displays information on the order confirmation page.
   *
   * The user is informed of the enrolments he/she entitles because of current
   * order (this does not mean that the enrolments has been performed yet).
   *
   * If there are no enrolments, no output is produced.
   */
  public function hookDisplayOrderConfirmation($params) {
    // Only if manual enrolment is enabled
    if (Configuration::get('MOODLECON_AUTO_ENROL') !== '1') {
        return;
    }

    // Compute the enrolments for this order
    $enrolments = MoodleEnrolment::computeEnrolmenmts($params['order']);

    // Only show block if there are something to report
    if (empty($enrolments)) {
      return;
    }

    // Get the name of moodle instance
    $helper = new MoodleWSHelper(\Configuration::get('MOODLECON_WS_ENDPOINT'), \Configuration::get('MOODLECON_WS_TOKEN'));
    $site_name = $helper->testWebService();

    // Get the list of course names
    $courses = array();
    foreach($enrolments as $enrolment) {
      array_push($courses, $enrolment->getCourseName());
    }

    // Prepare template variables
    $this->context->smarty->assign(array(
      'module_dir' => $this->_path,
      'moodle_name' => $site_name,
      'courses' => $courses,
    ));

    // Apply template order_confirmation.tpl
    return $this->context->smarty->fetch($this->local_path.'views/templates/front/order_confirmation.tpl');
  }

  /*
   * Config module.
   * Validate and save new values (after submit).
   * Draw the admin interface.
   */
  public function getContent() {
    $output = null;
    $warnings = false;
    $filter_options = array(
      'options' => array( 'min_range' => 0)
    );
    $new_link_cleanup = false;

    //
    // Process actions (buttons)
    //
    if (Tools::isSubmit('submitWSSettings')) {
      //
      // Save Web Service settings
      //
      $base_url = Tools::getValue('MOODLECON_WS_ENDPOINT');
      $ws_token = Tools::getValue('MOODLECON_WS_TOKEN');

      if (empty($base_url)) {
        $output .= $this->displayError($this->l('Moodle WS End Point is required'));
        $warnings = true;
      } else {
        if (!Configuration::updateValue('MOODLECON_WS_ENDPOINT', $base_url)) {
          $output .= $this->displayError($this->l('Cannot update settings'));
          $warnings = true;
        }
      }

      if (empty($ws_token)) {
        $output .= $this->displayError($this->l('Moodle WS Token is required'));
        $warnings = true;
      } else {
        if (!Configuration::updateValue('MOODLECON_WS_TOKEN', $ws_token)) {
          $output .= $this->displayError($this->l('Cannot update settings'));
          $warnings = true;
        }
      }

      if ( !$warnings ) {
        $output .= $this->displayConfirmation($this->l('Settings updated'));
      }
    } elseif (Tools::getValue('action') == 'test') {
      //
      // Test connection to Moodle Web service
      //
      $end_point = Configuration::get('MOODLECON_WS_ENDPOINT');
      $token = Configuration::get('MOODLECON_WS_TOKEN');;

      if (empty($end_point) or empty($token)) {
        $output .= $this->displayError($this->l('Set and save Moodle WS parameters first'));
      } else {
        $helper = new MoodleWSHelper(\Configuration::get('MOODLECON_WS_ENDPOINT'), \Configuration::get('MOODLECON_WS_TOKEN'));
        $site_name = $helper->testWebService();
        if ( !is_null($site_name) ) {
          $output .= $this->displayConfirmation($this->l('Successfuly connected to') . " {$site_name}" );
        } else {
          $output .= $this->displayError($this->l('Errors while connecting to Moodle. Please, check connection settings.'));
        }
      }
    } elseif (Tools::isSubmit('submitBehaviourSettings')) {
      //
      // Save behaviour settings
      //
      if (!Configuration::updateValue('MOODLECON_AUTO_ENROL', (int) Tools::getValue('MOODLECON_AUTO_ENROL'))) {
        $output .= $this->displayError($this->l('Cannot update settings'));
        $warnings = true;
      }
      if (!Configuration::updateValue('MOODLECON_MANUAL_ENROL', (int) Tools::getValue('MOODLECON_MANUAL_ENROL'))) {
        $output .= $this->displayError($this->l('Cannot update settings'));
        $warnings = true;
      }
      if (!Configuration::updateValue('MOODLECON_DEFAULT_PASSWD', Tools::getValue('MOODLECON_DEFAULT_PASSWD'))) {
        $output .= $this->displayError($this->l('Cannot update settings'));
        $warnings = true;
      }
      if (!Configuration::updateValue('MOODLECON_SEND_EMAIL', Tools::getValue('MOODLECON_SEND_EMAIL'))) {
        $output .= $this->displayError($this->l('Cannot update settings'));
        $warnings = true;
      }

      if ( !$warnings ) {
        $output .= $this->displayConfirmation($this->l('Settings updated'));
      }
    } elseif (Tools::isSubmit('submitAddLink')) {
      //
      // Add new link
      //
      $id_product          = Tools::getValue('MOODLECON_ID_PRODUCT');
      $set_combination     = Tools::getValue('MOODLECON_SET_COMBINATION') == 1;
      $product_combination = Tools::getValue('MOODLECON_PRODUCT_COMBINATION');
      $course_id           = Tools::getValue('MOODLECON_COURSE_ID');
      $role_id             = Tools::getValue('MOODLECON_ROLE_ID');
      $enrolment_duration  = Tools::getValue('MOODLECON_ENROLMENT_DURATION');
      $enrolment_enabled   = Tools::getValue('MOODLECON_ENROLMENT_ENABLED') == 1;

      if (empty($id_product)) {
        $output .= $this->displayError($this->l('PrestaShop Product ID is required'));
        $warnings = true;
      } elseif ( filter_var( $id_product, FILTER_VALIDATE_INT, $filter_options ) == FALSE) {
        $output .= $this->displayError($this->l('PrestaShop Product ID must be a positive integer'));
        $warnings = true;
      }

      if (empty($course_id)) {
        $output .= $this->displayError($this->l('Moodle Course ID is required'));
        $warnings = true;
      } elseif ( filter_var( $course_id, FILTER_VALIDATE_INT, $filter_options ) == FALSE) {
        $output .= $this->displayError($this->l('Moodle Course ID must be a positive integer'));
        $warnings = true;
      }

      if (empty($role_id)) {
        $output .= $this->displayError($this->l('Moodle Role ID is required'));
        $warnings = true;
      } elseif ( filter_var( $role_id, FILTER_VALIDATE_INT, $filter_options ) == FALSE) {
        $output .= $this->displayError($this->l('Moodle Role ID must be a positive integer'));
        $warnings = true;
      }

      if (!$set_combination) {
        $product_combination = null;
      } else {
        if (empty($product_combination)) {
          $product_combination = 0;
        }
      }

      if (!empty($enrolment_duration)) {
        if (filter_var( $enrolment_duration, FILTER_VALIDATE_INT, $filter_options ) == FALSE) {
          $output .= $this->displayError($this->l('Enrolment Duration must be a positive integer'));
          $warnings = true;
        } else {
          $enrolment_duration = ($enrolment_duration == 0) ? null : intval($enrolment_duration);
        }
      } else {
        $enrolment_duration = null;
      }

      if ( !$warnings ) {
        $link = new MoodleLink(null, $id_product, $product_combination, $course_id, $role_id, $enrolment_duration, $enrolment_enabled);
        if ($link->add()) {
          if (is_null($link->ID)) {
            $output .= $this->displayWarning($this->l('The link already exists. Not added.'));
          } else {
            $output .= $this->displayConfirmation($this->l('Link added successfuly') . " (ID={$link->ID})");
          }
          $new_link_cleanup = true;
        } else {
          $output .= $this->displayError($this->l('Cannot add a link'));
          $warnings = true;
        }
      }
    } elseif (Tools::getValue('action') == 'toggle') {
      //
      // Toggle link status
      //
      $link = new MoodleLink(Tools::getValue('ID'));
      $link->load();
      if ($link->enrolment_enabled == 1) {
        $result = $link->disable();
      } else {
        $result = $link->enable();
      }
      if ($result) {
        $output .= $this->displayConfirmation($this->l('Link status changed') . " (ID={$link->ID})");
      } else {
        $output .= $this->displayError($this->l('Cannot toggle link status'));
        $warnings = true;
      }
    } elseif (Tools::getValue('action') == 'remove') {
      //
      // Remove link
      //
      $link = new MoodleLink(Tools::getValue('ID'));
      if ($link->delete()) {
        $output .= $this->displayConfirmation($this->l('Link deleted') . " (ID={$link->ID})");
      } else {
        $output .= $this->displayError($this->l('Cannot delete a link'));
        $warnings = true;
      }
    }

    // Display admin area panels
    return $output .
           $this->displayModuleHelp() .
           $this->displaySettingsForm($new_link_cleanup) .
           $this->displayLinksList() .
           $this->displayEnrolmentsList();
  }

  /*
   * Module help panel - Injects path and token for AJAX
   */
  public function displayModuleHelp() {
    $this->context->smarty->assign(array(
        'ajax_token' => sha1(_COOKIE_KEY_ . 'MOODLECON'),
        'ajax_path' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/moodlecon/controllers/admin/ajax.php',
        'module_dir' => $this->_path,
        'module_local_dir' => $this->local_path,
    ));

    return $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
  }

  /*
   * Settings form
   */
  public function displaySettingsForm($new_link_cleanup) {
    // Get default language
    $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

    //
    // Product name and combinations
    //
    $id_product = $new_link_cleanup ? '' : Tools::getValue('MOODLECON_ID_PRODUCT');
    $product_name = '';
    $product_combination_options = [ ['id_option' => 0, 'name' => $this->l('No combinations / Link all combinations')] ];
    if (!empty($id_product)) {
      $product_info = (new MoodleLink(null, $id_product))->getProductInfo($defaultLang);
      $product_name = $product_info['name'];
      if (!empty($product_info['combinations'])) {
        foreach($product_info['combinations'] as $combination) {
          $product_combination_options[] = array('id_option' => $combination['id'], 'name' => $combination['txt'] );
        }
      }
    }

    //
    // Course name and moodle roles
    //
    $helper = new MoodleWSHelper(\Configuration::get('MOODLECON_WS_ENDPOINT'), \Configuration::get('MOODLECON_WS_TOKEN'));
    $course_id = Tools::getValue('MOODLECON_COURSE_ID');
    $course_name = '';
    if (!empty($course_id) and intval($course_id) == $course_id and $course_id > 0) {
      $course_name = $helper->getCourseName($course_id);
    }
    $this->moodle_roles = $helper->getRoles();
    $enrolment_roles = array();
    if (sizeof($this->moodle_roles) > 0 ) {
      foreach($this->moodle_roles as $id => $name) {
        $enrolment_roles[] = array('role_id' => $id, 'role_name' => $name);
      }
    } else {
      $enrolment_roles[] = array('role_id' => 0, 'role_name' => $this->l('Set Web Services settings to retrieve roles from Moodle'));
    }

    //
    // First panel: Moddle access
    //
    $fieldsForm[0]['form'] = [
        'legend' => [
            'title' => $this->l('Set up how to access your Moodle instance via the Web Service'),
            'icon' => 'icon-cogs'
        ],
        'input' => [
            [
                'type' => 'text',
                'label' => $this->l('Moodle WS End Point'),
                'desc' => $this->l('URL to access your Moodle instance web service via REST protocol.'),
                'placeholder' => 'https://my_moodle.com/webservice/rest/server.php',
                'hint' => $this->l('Only REST protocol is supported'),
                'name' => 'MOODLECON_WS_ENDPOINT',
                'class'    => 'lg',
                'required' => true
            ],
            [
                'type' => 'text',
                'label' => $this->l('Moodle WS Token'),
                'desc' => $this->l('Security to token to access Moodle via the Web Service.'),
                'placeholder' => '0123456789abcdef0123456789abcdef',
                'hint' => $this->l('Hexadecimal string of 32 characters'),
                'name' => 'MOODLECON_WS_TOKEN',
                'class'    => 'lg',
                'required' => true
            ]
        ],
        'submit' => [
            'title' => $this->l('Save'),
            'name' => 'submitWSSettings',
            'class' => 'btn btn-default pull-right'
        ],
        'buttons' => [
             [
                 'href' => AdminController::$currentIndex.'&configure='.$this->name.'&action=test&token='.Tools::getAdminTokenLite('AdminModules'),
                 'title' => $this->l('Test connection'),
                 'icon' => 'process-icon-download'
             ]
         ]
    ];

    //
    // Second panel: Module behaviour
    //
    $fieldsForm[1]['form'] = [
        'legend' => [
            'title' => $this->l('Set up the behaviour of this module'),
            'icon' => 'icon-cogs'
        ],
        'input' => [
          [
            'type' => 'switch',
            'is_bool' => true, //retro compat 1.5
            'label' => $this->l('Automatic Enrolment'),
            'name' => 'MOODLECON_AUTO_ENROL',
            'desc' => $this->l('When set, students are automatically enroled on courses right after the order is confirmed.'),
            'values' => [
                [
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->trans('Enabled', array(), 'Admin.Global')
                ],
                [
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->trans('Disabled', array(), 'Admin.Global')
                ]
            ]
          ],
          [
            'type' => 'switch',
            'is_bool' => true, //retro compat 1.5
            'label' => $this->l('Manual Enrolment'),
            'name' => 'MOODLECON_MANUAL_ENROL',
            'desc' => $this->l('When set, the order management panel will display a button to trigger the enrolment of the courses linked to that order.'),
            'values' => [
                [
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->trans('Enabled', array(), 'Admin.Global')
                ],
                [
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->trans('Disabled', array(), 'Admin.Global')
                ]
            ]
          ],
          [
              'type' => 'text',
              'label' => $this->l('Default password'),
              'desc' => $this->l('Password set to Moodle users created by this plugin. Left blank to let Moodle create a random temporary password that will be sent to the user by mail.'),
              'hint' => $this->l('This password must conform to the complexity rules defined in your Moodle instance'),
              'name' => 'MOODLECON_DEFAULT_PASSWD',
              'class'    => 'lg',
              'required' => false
          ],
          [
            'type' => 'switch',
            'is_bool' => true, //retro compat 1.5
            'label' => $this->l('Send e-mail notifications'),
            'name' => 'MOODLECON_SEND_EMAIL',
            'desc' => $this->l('Send an e-mail to the customer to confirm that s/he has been enrolled into a set of courses.'),
            'values' => [
                [
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->trans('Enabled', array(), 'Admin.Global')
                ],
                [
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->trans('Disabled', array(), 'Admin.Global')
                ]
            ]
          ]
        ],
        'submit' => [
            'title' => $this->l('Save'),
            'name' => 'submitBehaviourSettings',
            'class' => 'btn btn-default pull-right'
        ]
    ];

    //
    // Third panel: Create new link
    //
    $fieldsForm[2]['form'] = [
        'legend' => [
            'title' => $this->l('Create new link between a product and a course'),
            'icon' => 'icon-plus'
        ],
        'input' => [
            [
                'type' => 'text',
                'label' => $this->l('PrestaShop Product ID'),
                'desc' => $this->l('Set the ID of the product in PrestaShop that you wnat to link to an enrolment.'),
                'hint' => $this->l('This is an integer number (eg: 1234)'),
                'name' => 'MOODLECON_ID_PRODUCT',
                'class' => 'lg',
                'required' => true
            ],
            [
                'type' => 'text',
                'label' => $this->l('Product Name'),
                'name' => 'MOODLECON_PRODUCT_NAME',
                'class' => 'lg',
                'required' => false
            ],
            [
              'type' => 'switch',
              'is_bool' => true, //retro compat 1.5
              'label' => $this->l('Link only a single combination '),
              'name' => 'MOODLECON_SET_COMBINATION',
              'values' => [
                  [
                      'id' => 'active_on',
                      'value' => 1,
                      'label' => $this->trans('Enabled', array(), 'Admin.Global')
                  ],
                  [
                      'id' => 'active_off',
                      'value' => 0,
                      'label' => $this->trans('Disabled', array(), 'Admin.Global')
                  ]
              ]
            ],
            [
              'type' => 'select',
              'label' => $this->l('Product Combination'),
              'desc' => $this->l('The enrolment is only performed if this particular product combination is on the order.'),
              'name' => 'MOODLECON_PRODUCT_COMBINATION',
              'hint' => $this->l('Set the product ID to display its combinations'),
              'class' => 'lg',
              'options' => [
                'query' => $product_combination_options,
                'id' => 'id_option',
                'name' => 'name'
              ]
            ],
            [
                'type' => 'text',
                'label' => $this->l('Moodle Course ID'),
                'desc' => $this->l('Set the ID of the course on your Moodle instance linked to the product above.'),
                'hint' => $this->l('This is an integer number (eg: 1234)'),
                'name' => 'MOODLECON_COURSE_ID',
                'class'    => 'lg',
                'required' => true
            ],
            [
                'type' => 'text',
                'label' => $this->l('Moodle Course Name'),
                'name' => 'MOODLECON_COURSE_NAME',
                'class'    => 'lg',
                'required' => false
            ],
            [
              'type' => 'select',
              'label' => $this->l('Enrolment Role'),
              'desc' => $this->l('Role assigned to the user for the course enrolment (eg: Student).'),
              'name' => 'MOODLECON_ROLE_ID',
              'class' => 'lg',
              'options' => [
                'query' => $enrolment_roles,
                'id' => 'role_id',
                'name' => 'role_name'
              ]
            ],
            [
                'type' => 'text',
                'label' => $this->l('Enrolment Duration'),
                'desc' => $this->l('Set the maximum number of days that the customer has to complete the course.'),
                'hint' => $this->l('Set to 0 or blank if the enrolment never expires'),
                'name' => 'MOODLECON_ENROLMENT_DURATION',
                'suffix' => $this->l('days'),
                'class'    => 'lg',
                'required' => false
            ],
            [
              'type' => 'switch',
              'is_bool' => true, //retro compat 1.5
              'label' => $this->l('Link Enabled'),
              'name' => 'MOODLECON_ENROLMENT_ENABLED',
              'desc' => $this->l('Allows to temporarily disable a link. When disabled, the enrolment is not performed neither on Manual nor Automatic mode.'),
              'hint' => $this->l('To disable a link permanently, delete it'),
              'values' => [
                  [
                      'id' => 'active_on',
                      'value' => 1,
                      'label' => $this->trans('Enabled', array(), 'Admin.Global')
                  ],
                  [
                      'id' => 'active_off',
                      'value' => 0,
                      'label' => $this->trans('Disabled', array(), 'Admin.Global')
                  ]
              ]
            ]
        ],
        'submit' => [
            'title' => $this->l('Add'),
            'name' => 'submitAddLink',
            'class' => 'btn btn-default pull-right',
            'icon' => 'process-icon-plus'
        ]
    ];

    // Remove combination option is combinations are not enabled
    if (!Combination::isFeatureActive()) {
      unset($fieldsForm[2]['form']['input'][2]);
      unset($fieldsForm[2]['form']['input'][3]);
    }

    $helper = new HelperForm();

    // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

    // Language
    $helper->default_form_language = $defaultLang;
    $helper->allow_employee_form_lang = $defaultLang;

    // Load current value
    $helper->fields_value['MOODLECON_WS_ENDPOINT']         = Configuration::get('MOODLECON_WS_ENDPOINT');
    $helper->fields_value['MOODLECON_WS_TOKEN']            = Configuration::get('MOODLECON_WS_TOKEN');

    $helper->fields_value['MOODLECON_AUTO_ENROL']          = Configuration::get('MOODLECON_AUTO_ENROL');
    $helper->fields_value['MOODLECON_MANUAL_ENROL']        = Configuration::get('MOODLECON_MANUAL_ENROL');
    $helper->fields_value['MOODLECON_DEFAULT_PASSWD']      = Configuration::get('MOODLECON_DEFAULT_PASSWD');
    $helper->fields_value['MOODLECON_SEND_EMAIL']          = Configuration::get('MOODLECON_SEND_EMAIL');

    $helper->fields_value['MOODLECON_ID_PRODUCT']          = $new_link_cleanup ? '' : Tools::getValue('MOODLECON_ID_PRODUCT');
    $helper->fields_value['MOODLECON_PRODUCT_NAME']        = $new_link_cleanup ? '' : $product_name;
    $helper->fields_value['MOODLECON_SET_COMBINATION']     = $new_link_cleanup ?  0 : Tools::getValue('MOODLECON_SET_COMBINATION');
    $helper->fields_value['MOODLECON_PRODUCT_COMBINATION'] = $new_link_cleanup ?  0 : Tools::getValue('MOODLECON_PRODUCT_COMBINATION');
    $helper->fields_value['MOODLECON_COURSE_ID']           = $new_link_cleanup ? '' : Tools::getValue('MOODLECON_COURSE_ID');
    $helper->fields_value['MOODLECON_COURSE_NAME']         = $new_link_cleanup ? '' : $course_name;
    $helper->fields_value['MOODLECON_ROLE_ID']             = $new_link_cleanup ?  0 : Tools::getValue('MOODLECON_ROLE_ID');
    $helper->fields_value['MOODLECON_ENROLMENT_DURATION']  = $new_link_cleanup ? '' : Tools::getValue('MOODLECON_ENROLMENT_DURATION');
    $helper->fields_value['MOODLECON_ENROLMENT_ENABLED']   = $new_link_cleanup ?  0 : Tools::getValue('MOODLECON_ENROLMENT_ENABLED');

    return $helper->generateForm($fieldsForm);
  }

  //
  // Links list panel
  //
  public function displayLinksList() {
    $fields_list = array(
        'ID' => array(
            'title' => $this->l('ID'),
            'search' => false,
        ),
        'id_product' => array(
            'title' => $this->l('Product ID'),
            'search' => false,
        ),
        'product_name' => array(
            'title' => $this->l('Product Name'),
            'search' => false,
        ),
        'product_combination' => array(
            'title' => $this->l('Combination'),
            'search' => false,
        ),
        'course_id' => array(
            'title' => $this->l('Course ID'),
            'search' => false,
        ),
        'course_name' => array(
            'title' => $this->l('Course Name'),
            'search' => false,
        ),
        'enrolment_role' => array(
            'title' => $this->l('Enrolment Role'),
            'search' => false,
        ),
        'enrolment_duration' => array(
            'title' => $this->l('Duration'),
            'search' => false,
        ),
        'enrolment_enabled' => array(
            'title' => $this->l('Enabled'),
            'type' => 'bool',
            'active' => 'toggle',
            'search' => false,
        ),
    );

    // Remove combination column is combinations are not enabled
    if (!Combination::isFeatureActive()) {
      unset($fields_list['product_combination']);
    }

    $helper_list = new HelperList();
    $helper_list->module = $this;
    $helper_list->title = $this->l('List of current links');
    $helper_list->shopLinkType = '';
    $helper_list->no_link = true;
    $helper_list->show_toolbar = true;
    $helper_list->simple_header = false;
    $helper_list->identifier = 'ID';
    $helper_list->table = 'moodlelinks';
    $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
    $helper_list->token = Tools::getAdminTokenLite('AdminModules');
    $helper_list->actions = array('deleteLink');

    // This is needed for displayEnableLink to avoid code duplication
    $this->_helperlist = $helper_list;

    // Retrieve list data
    $links = $this->getLinks();
    $helper_list->listTotal = count($links);

    // Paginate the result
    $page = ($page = Tools::getValue('submitFilter' . $helper_list->table)) ? $page : 1;
    $pagination = ($pagination = Tools::getValue($helper_list->table . '_pagination')) ? $pagination : 50;
    $links = $this->paginateList($links, $page, $pagination);

    return $helper_list->generateList($links, $fields_list);
  }

  //
  // Enrolments list panel
  //
  public function displayEnrolmentsList() {
    $fields_list = array(
        'ID' => array(
            'title' => $this->l('ID'),
            'search' => false,
        ),
        'id_link' => array(
            'title' => $this->l('Link ID'),
            'search' => false,
        ),
        'mode' => array(
            'title' => $this->l('Type'),
            'search' => false,
        ),
        'order' => array(
            'title' => $this->l('Order'),
            'search' => false,
        ),
        'product_name' => array(
            'title' => $this->l('Product Name'),
            'search' => false,
        ),
        'product_combination' => array(
            'title' => $this->l('Combination'),
            'search' => false,
        ),
        'course_name' => array(
            'title' => $this->l('Course Name'),
            'search' => false,
        ),
        'customer_email' => array(
            'title' => $this->l('Customer Email'),
            'search' => false,
        ),
        'status' => array(
            'title' => $this->l('Status'),
            'search' => false,
        ),
        'date_add' => array(
            'title' => $this->l('Date'),
            'search' => false,
        ),
    );

    // Remove combination column is combinations are not enabled
    if (!Combination::isFeatureActive()) {
      unset($fields_list['product_combination']);
    }

    $helper_list = new HelperList();
    $helper_list->module = $this;
    $helper_list->title = $this->l('List of enrolments');
    $helper_list->shopLinkType = '';
    $helper_list->no_link = true;
    $helper_list->show_toolbar = true;
    $helper_list->simple_header = false;
    $helper_list->identifier = 'ID';
    $helper_list->table = 'moodleenrolments';
    $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
    $helper_list->token = Tools::getAdminTokenLite('AdminModules');

    // This is needed for displayEnableLink to avoid code duplication
    $this->_helperlist = $helper_list;

    // Retrieve list data
    $enrolments = $this->getEnrolments();
    $helper_list->listTotal = count($enrolments);

    // Paginate the result
    $page = ($page = Tools::getValue('submitFilter' . $helper_list->table)) ? $page : 1;
    $pagination = ($pagination = Tools::getValue($helper_list->table . '_pagination')) ? $pagination : 50;
    $enrolments = $this->paginateList($enrolments, $page, $pagination);

    return $helper_list->generateList($enrolments, $fields_list);
  }

  //
  // Links list panel - Delete button
  //
  public function displayDeleteLinkLink($token = null, $id, $name = null) {
    $this->smarty->assign(array(
        'href' => $this->_helperlist->currentIndex . '&' . $this->_helperlist->identifier . '=' . $id . '&action=remove&' . '&token=' . $token,
        'action' => $this->l('Delete'),
        'disable' => !((int) $id > 0),
        'confirmation' => $this->l('Are you sure that you want to delete this link?') . " [ID={$id}]"
    ));

    return $this->display(__FILE__, 'views/templates/admin/list_action_deletelink.tpl');
  }

  //
  // Links list panel - Enable / disable button
  //
  public function displayEnableLink($token, $id, $value, $active, $id_category = null, $id_product = null, $ajax = false) {
    $this->smarty->assign(array(
        'ajax' => $ajax,
        'enabled' => (bool) $value,
        'url_enable' => $this->_helperlist->currentIndex . '&' . $this->_helperlist->identifier . '=' . $id . '&action=' . $active .  '&token=' . $token,
    ));

    return $this->display(__FILE__, 'views/templates/admin/list_action_enable.tpl');
  }

  //
  // Retrieve links list data
  //
  public function getLinks() {
    // Default language
    $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');


    // If product combinatios is active, load product attributes
    if (Combination::isFeatureActive()) {
      $attributes = AttributeCore::getAttributes($defaultLang);
    }

    // Get all links
    $links = MoodleLink::getAllLinks();

    //
    // Collect the list of courses (ids) and get their names
    //
    $courses_ids = array();
    foreach($links as $link) {
      if (!in_array($link['course_id'], $courses_ids)) {
        $courses_ids[] = $link['course_id'];
      }
    }
    $moodle = new MoodleWSHelper(\Configuration::get('MOODLECON_WS_ENDPOINT'), \Configuration::get('MOODLECON_WS_TOKEN'));
    $courses = $moodle->getCoursesNames($courses_ids);

    // Complete links list data
    foreach($links as &$link) {
      $helper = new MoodleLink($link['ID'], $link['id_product'], $link['product_combination']);

      //
      // Get a descriptive product combination
      //
      if (!Combination::isFeatureActive()) {
        // Product combinations are not active
        $link['product_combination'] = $this->l('No combinations');
      } else {
        // NULL or 0  means that any combinatio is linked
        // or that the product does not have combinations
        if (empty($link['product_combination']) or $link['product_combination'] == 0) {
          // This is the default description.
          $link['product_combination'] = $this->l('All combinations');
        } else {
          // Set the description of this combination
          $link['product_combination'] = $helper->getCombinationDescription($defaultLang);
        }
      }

      //
      // Get current product name
      //
      $link['product_name'] = $helper->getProductName($defaultLang);

      //
      // Get course name
      //
      $link['course_name'] = array_key_exists($link['course_id'], $courses) ? $courses[$link['course_id']] : '';

      //
      // Get role name
      //
      $link['enrolment_role'] = array_key_exists($link['role_id'], $this->moodle_roles) ? $this->moodle_roles[$link['role_id']] : '';
    }

    // Return the fibal list of links
    return $links;
  }

  //
  // Retrieve enrolments list data
  //
  public function getEnrolments() {
    // Default language
    $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

    // Get all enrolments
    $enrolments_o = MoodleEnrolment::getAllEnrolments(true);

    //
    // Collect the list of courses (ids) and get their names
    //
    $courses_ids = array();
    foreach($enrolments_o as $enrolment) {
      $link = $enrolment->getLink();
      if (!in_array($link->course_id, $courses_ids)) {
        $courses_ids[] = $link->course_id;
      }
    }
    $moodle = new MoodleWSHelper(\Configuration::get('MOODLECON_WS_ENDPOINT'), \Configuration::get('MOODLECON_WS_TOKEN'));
    $courses = $moodle->getCoursesNames($courses_ids);

    //
    // Collect the list of orders and customers
    //
    $customers = array();
    $orders = array();
    foreach($enrolments_o as $enrolment) {
      $id_order = $enrolment->id_order;
      if (!in_array($id_order, $orders)) {
        $order = new Order((int)$id_order);
        $orders[$id_order] = $order;
        $id_customer = $order->id_customer;
        if (!in_array($id_customer, $customers)) {
          $customers[$id_customer] = new Customer((int)$id_customer);
        }
      }
    }

    // Generate enrolments list data
    $enrolments = array();
    foreach($enrolments_o as $enrolment_o) {
      $link = $enrolment_o->getLink();

      $enrolment = array();
      $enrolment['ID']       = $enrolment_o->ID;
      $enrolment['id_link']  = $enrolment_o->id_link;
      $enrolment['date_add'] = $enrolment_o->date_add;
      $enrolment['mode']     = ($enrolment_o->mode === MoodleEnrolment::MODE_AUTO) ? $this->l('Automatic') : $this->l('Manual');
      $enrolment['status']   = ($enrolment_o->status === MoodleEnrolment::STATUS_OK) ? $this->l('Ok') : $this->l('Error');

      //
      // Order (reference) and customer email
      //
      $order = $orders[$enrolment_o->id_order];
      $enrolment['order'] = "{$enrolment_o->id_order} ({$order->reference})";
      $enrolment['customer_email'] = $customers[$order->id_customer]->email;

      //
      // Get a descriptive product combination
      //
      if (!Combination::isFeatureActive()) {
        // Product combinations are not active
        $enrolment['product_combination'] = $this->l('No combinations');
      } else {
        // NULL or 0  means that any combinatio is linked
        // or that the product does not have combinations
        if (empty($enrolment['product_combination']) or $enrolment['product_combination'] == 0) {
          // This is the default description.
          $enrolment['product_combination'] = $this->l('All combinations');
        } else {
          // Set the description of this combination
          $enrolment['product_combination'] = $link->getCombinationDescription($defaultLang);
        }
      }

      //
      // Get current product name
      //
      $enrolment['product_name'] = $link->getProductName($defaultLang);

      //
      // Get course name
      //
      $course_id = $link->course_id;
      $enrolment['course_name'] = array_key_exists($course_id, $courses) ? $courses[$course_id] : '';

      //
      // Get role name
      //
      $role_id = $link->role_id;
      $enrolment['enrolment_role'] = array_key_exists($role_id, $this->moodle_roles) ? $this->moodle_roles[$role_id] : '';

      // Add to the list
      $enrolments[] = $enrolment;
    }

    // Return the final list of links
    return $enrolments;
  }

  //
  // List table pagination
  //
  public function paginateList($elements, $page = 1, $pagination = 50) {
    if (count($elements) > $pagination) {
        $elements = array_slice($elements, $pagination * ($page - 1), $pagination);
    }

    return $elements;
  }

  //
  // Send an e-mail notification to the customer.
  //
  public function sendNotifications($enrolments) {
    if (Configuration::get('MOODLECON_SEND_EMAIL') === '1') {
      if (is_array($enrolments) and sizeof($enrolments) > 0) {
        // Get the language identifier.
        $context = Context::getContext();
        $language_id = $context->language->id;

        // Get order and customer data
        $order = new Order((int)$enrolments[0]->id_order);
        $customer = new Customer((int)$enrolments[0]->id_customer);

        // Get the name of moodle instance
        $helper = new MoodleWSHelper(\Configuration::get('MOODLECON_WS_ENDPOINT'), \Configuration::get('MOODLECON_WS_TOKEN'));
        $site_name = $helper->testWebService();

        // Build the list of course names
        $courses = '<ol>';
        $courses_txt = '';
        foreach($enrolments as $enrolment) {
          $courses .= '<li>' . $enrolment->getCourseName() . '</li>';
          $courses_txt = $enrolment->getCourseName() . '\r\n';
        }
        $courses .= '</ol>';

        // Setup the mail vars.
        $mail_vars = array();
        $mail_vars['{firstname}'] = $customer->firstname;
        $mail_vars['{lastname}'] = $customer->lastname;
        $mail_vars['{order}'] = $order->reference;
        $mail_vars['{moodle-site}'] = $site_name;
        $mail_vars['{courses}'] = $courses;
        $mail_vars['{courses_txt}'] = $courses_txt;

        // Send mail to customer
        @Mail::Send((int) $language_id,
                    'moodlecon_notify',
                    Mail::l('You have been enrolled!'),
                    $mail_vars,
                    $customer->email,
                    trim($customer->firstname . ' ' . $customer->lastname),
                    null,
                    null,
                    null,
                    null,
                    dirname(__FILE__) . '/mails/');
      }
    }
  }
}
