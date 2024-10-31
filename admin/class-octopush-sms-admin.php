<?php
/*
 * Copyright (C) 2014 octopush
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://octopush.com
 * @since      1.0.0
 *
 * @package    Octopush_Sms
 * @subpackage Octopush_Sms/admin
 */
class Octopush_Sms_Admin {

    private static $octopush_sms_admin;

    public static function get_instance() {
        global $octopush_sms_admin;
        if (is_null($octopush_sms_admin)) {
            $octopush_sms_admin = new Octopush_Sms_API();
        }
        return $octopush_sms_admin;
    }

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $octopush_sms    The ID of this plugin.
     */
    private $octopush_sms;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    
    /**
     * Admin messages with their title
     */
    public $admin_config;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @var      string    $octopush_sms       The name of this plugin.
     * @var      string    $version    The version of this plugin.
     */
    public function __construct($octopush_sms, $version) {
        global $octopush_sms_admin;

        $this->octopush_sms = $octopush_sms;
        $this->version = $version;

        $octopush_sms_admin = $this;

        //initialisation of possible admin message
        $this->admin_config = array(
            'action_create_account' => __('New account', 'octopush-sms'), //don't suppress                
            'action_send_message' => __('Message received', 'octopush-sms'),
            'action_validate_order' => __('Order for validation', 'octopush-sms'),
            //'action_order_return' => __('Order return', 'octopush-sms'),
            'action_update_quantity' => __('Stockout warning', 'octopush-sms'),
			'action_test_sms' => __('Send test sms', 'octopush-sms'),
            'action_admin_alert' => __('Low SMS balance', 'octopush-sms'),
            'action_daily_report' => __('Daily report', 'octopush-sms'));

        $this->customer_config = array(
            'action_create_account' => __('Welcome message', 'octopush-sms'),
            'action_password_renew' => __('Password recovery', 'octopush-sms'),
            //'action_customer_alert' => __('Send SMS when product is available', 'octopush-sms'),
            'action_send_message' => __('Message received', 'octopush-sms'),
            'action_validate_order' => __('Order confirmation', 'octopush-sms'),
            //'action_admin_orders_tracking_number_update' => __('Order tracking number update', 'octopush-sms'),
            'action_order_status_update' => __('Status update', 'octopush-sms'));
    }

    /**
     * Register the stylesheets for the Dashboard.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $position = strpos(get_current_screen()->id,'woocommerce_page_octopush-sms');
        if ($position === false ) {
        } else {
            wp_enqueue_style($this->octopush_sms, plugin_dir_url(__FILE__) . 'css/octopush-sms-admin.css', array(), $this->version, 'all');
            $jquery_version = isset($wp_scripts->registered['jquery-ui-core']->ver) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
            wp_enqueue_style('jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), WC_VERSION);
            //icon of woocommerce
            wp_enqueue_style(ABSPATH . 'wp-content/plugin/woocommerce.assets/css/mixins.css');
        }        
    }

    /**
     * Register the JavaScript for the dashboard.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * An instance of this class should be passed to the run() function
         * defined in Octopush_Sms_Admin_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Octopush_Sms_Admin_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script($this->octopush_sms, plugin_dir_url(__FILE__) . 'js/octopush-sms-admin.js', array('jquery'), $this->version, false);
        
        // Load the datepicker script (pre-registered in WordPress).
        wp_enqueue_script( 'jquery-ui-datepicker' );

        // You need styling for the datepicker. For simplicity I've linked to Google's hosted jQuery UI CSS.
        wp_register_style( 'jquery-ui', 'http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css' );
        wp_enqueue_style( 'jquery-ui' );  
    }    

    /**
     * Add menu send sms items to woocommerce menu
     */
    public function octopush_menu() {
        if (WP_DEBUG) {
                error_log("add_submenu_page: ");
            }
        $octopush_page = add_submenu_page('woocommerce', __('Send SMS', 'octopush-sms'), __('Send SMS', 'octopush-sms'), 'manage_woocommerce', 'octopush-sms', array($this, 'output'));
        
        //register_setting( 'woocommerce_status_settings_fields', 'woocommerce_status_options' );
        //add_action( 'load-' . $octopush_page, array( $this, 'octopush_page_init' ) );
    }

    /**
     * Handles output of admin pages.
     */
    public static function output() {
        $current_tab = !empty($_REQUEST['tab']) ? sanitize_title($_REQUEST['tab']) : 'messages';
        $current_action = !empty($_REQUEST['action']) ? sanitize_title($_REQUEST['action']) : '';
        if (WP_DEBUG)
            error_log(print_r($_REQUEST, true));
        include_once( 'partials/html-admin-page.php' );
    }

    /**
     * Handles output of the news page in admin.
     */
    public static function octopush_sms_news() {
        $news = new Octopush_Sms_News();
        $news->output();
    }

    /**
     * Handles output of the settings page in admin.
     */
    public static function octopush_sms_settings() {
        $settings = new Octopush_Sms_Settings();
        $settings->output();
    }

    /**
     * Get the key of a "hook".
     * 
     * @param type $hookId
     * @param type $b_admin
     * @param type $params
     * @return type
     */
    public function _get_hook_key($hookId, $b_admin = false, $params = null) {
        if (is_array($params) && key_exists('new_status', $params)) {
            return 'octopush_sms_txt_' . $hookId . '_wc-' . $params['new_status'] . ($b_admin ? '_admin' : '');
        }
        return 'octopush_sms_txt_' . $hookId . ($b_admin ? '_admin' : '');
    }

    /**
     * Return if the "hook" is valid or not.
     * 
     * @param type $hookId
     * @param type $b_admin
     * @param type $params
     * @return type
     */
    public function _get_isactive_hook_key($hookId, $b_admin = false, $params = null) {
        if (is_array($params) && key_exists('new_status', $params)) {
            return 'octopush_sms_isactive_' . $hookId . '_wc-' . $params['new_status'] . ($b_admin ? '_admin' : '');
        }
        return 'octopush_sms_isactive_' . $hookId . (($b_admin) ? '_admin' : '');
    }

    /**
     * Filter to add settings link in plugin page.
     */
    public function settings_link($links,$file) {
        $plugin_file = 'octopush-sms/octopush-sms.php';
	//make sure it is our plugin we are modifying
	if ( $file == $plugin_file ) {
            	$settings_link = '<a href="' .
			admin_url( 'admin.php?page=octopush-sms&tab=settings' ) . '">' .
			__('Settings','octopush-sms') . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
    }

    /**
     * Create account hook for admin.
     * @param type $customer_id
     */
    public function action_create_account($customer_id) {
        if (WP_DEBUG)
            error_log('action_create_account -BEGIN');
        if (WP_DEBUG)
            error_log('action_create_account - $_POST' . print_r($_POST, true) . ' customer_id ' . $customer_id);
        //send only if the check box create account is selected on the form
        if (isset($_POST['createaccount']) && wc_clean($_POST['createaccount'] == 1)) {
            $octopush_sms_api = Octopush_Sms_API::get_instance();
            $octopush_sms_api->send('action_create_account', array('customer_id' => $customer_id));
        } else {
            if (WP_DEBUG)
                error_log('action_create_account - No account creation');
        }
    }

    /**
     * Comment add hook for admin : when a user had a comment
     * @param type $comment
     */
    public function action_send_message($id, $comment) {
        if (WP_DEBUG)
            error_log('action_send_message -BEGIN');
        if (WP_DEBUG)
            error_log('action_send_message - $_POST' . print_r($_POST, true));
        if ($comment->comment_author != 'WooCommerce') {
            $octopush_sms_api = Octopush_Sms_API::get_instance();
            $octopush_sms_api->send('action_send_message', array('comment' => $comment, 'id' => $id));
        }
    }

    public function action_validate_order($order_id) {
        if (WP_DEBUG)
            error_log('action_validate_order -BEGIN');
        if (WP_DEBUG)
            error_log('action_validate_order - $_POST' . print_r($_POST, true));
        $octopush_sms_api = Octopush_Sms_API::get_instance();
        $octopush_sms_api->send('action_validate_order', array('order_id' => $order_id));
    }

    //'action_order_status_update'
    //do_action( 'woocommerce_order_status_changed', $this->id, $old_status, $new_status );
    public function action_order_status_update($order_id, $old_status, $new_status) {
        if (WP_DEBUG)
            error_log('action_order_status_update -BEGIN');
        if (WP_DEBUG)
            error_log('action_order_status_update - $_POST' . print_r($_POST, true));
        $octopush_sms_api = Octopush_Sms_API::get_instance();
        $octopush_sms_api->send('action_order_status_update', array('order_id' => $order_id, 'old_status' => $old_status, 'new_status' => $new_status));
    }

    public function action_password_renew($user, $new_pass) {
        if (WP_DEBUG)
            error_log('action_password_renew -BEGIN');
        if (WP_DEBUG)
            error_log('action_password_renew - $_POST' . print_r($_POST, true));
        $octopush_sms_api = Octopush_Sms_API::get_instance();
        $octopush_sms_api->send('action_password_renew', array('user' => $user, 'new_pass' => $new_pass));
    }

    public function action_order_return() {
        if (WP_DEBUG)
            error_log('action_send_message -BEGIN');
        if (WP_DEBUG)
            error_log('action_send_message - $_POST' . print_r($_POST, true));
        $octopush_sms_api = Octopush_Sms_API::get_instance();
        $octopush_sms_api->send('action_send_message', array('customer_id' => $customer_id));
    }

    public function action_update_quantity($product) {
        if (WP_DEBUG)
            error_log('action_update_quantity -BEGIN');
        if (WP_DEBUG)
            error_log('action_update_quantity - $_POST' . print_r($_POST, true));
        $octopush_sms_api = Octopush_Sms_API::get_instance();
        $octopush_sms_api->send('action_update_quantity', array('product' => $product));
    }

    public function action_test_sms() {
        if (WP_DEBUG)
            error_log('action_test_sms -BEGIN');
        if (WP_DEBUG)
            error_log('action_test_sms - $_POST' . print_r($_POST, true));
        $octopush_sms_api = Octopush_Sms_API::get_instance();
        $test_sms = $octopush_sms_api->send('action_test_sms', array());
        
        return $test_sms['admin'];
    }

    public function action_wp_login($login) {
        //TODO global $user_ID;
        $user = get_user_by('login', $login);
        update_user_meta($user->ID, 'last_login', date('Y:m:d H:i:s'));
    }

    public function action_admin_alert() {
        if (WP_DEBUG)
            error_log('action_admin_alert -BEGIN');
        if (WP_DEBUG)
            error_log('action_admin_alert - $_POST' . print_r($_POST, true));
        $octopush_sms_api = Octopush_Sms_API::get_instance();
        $octopush_sms_api->send('action_admin_alert', array());
    }

    /**
     * Send a daily report if needed
     */
    public function action_daily_report() {
        if (WP_DEBUG)
            error_log('action_daily_report -BEGIN');
        $octopush_sms_api = Octopush_Sms_API::get_instance();
        $octopush_sms_api->send('action_daily_report', array());
    }

    /* Handle ajax call */

    /**
     * Ajax for add_recipient
     */
    function action_add_recipient() {
        Octopush_Sms_Send_Tab::_ajax_process_addRecipient();
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Ajax for transmit to Octopush Web Service
     */
    function action_transmit_ows() {
        Octopush_Sms_Send_Tab::_ajax_process_transmitOWS();
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    function action_del_recipient() {
        Octopush_Sms_Send_Tab::_ajax_process_delRecipient();
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    function action_add_recipients_from_query() {
        Octopush_Sms_Send_Tab::_ajax_process_addRecipientsFromQuery();
        wp_die();
    }

    function action_count_recipients_from_query() {
        Octopush_Sms_Send_Tab::_ajax_process_countRecipientsFromQuery();
        wp_die();
    }

    /**
     * Ajax list
     */
    function _ajax_fetch_custom_list_callback() {
        if (isset($_REQUEST['list']) && $_REQUEST['list'] == 'Octopush_Sms_Recipient_List') {
            if (isset($_REQUEST['id_sendsms_campaign'])) {
                if (WP_DEBUG) {
                    error_log("_ajax_fetch_custom_list_callback()" . print_r($_REQUEST, true));
                }
                $wp_list_table = new Octopush_Sms_Recipient_List(array("campaign" => new Octopush_Sms_Campaign_Model((intval($_REQUEST['id_sendsms_campaign'])))));
                $wp_list_table->ajax_response();
            } else {
                wp_send_json(array("error" => __("id_sendsms_campaign is not set.", "octopush-sms")));
            }
        } else {
            $wp_list_table = new Octopush_Sms_Campaign_List(array("status" => array(3, 4, 5)));
            $wp_list_table->ajax_response();
        }
    }

    function action_filter() {
        Octopush_Sms_Send_Tab::_ajax_process_filter();
        wp_die();
    }

    //#2
    function action_filter_user() {
        Octopush_Sms_Send_Tab::_ajax_process_filter_user();
        wp_die();
    }

    function action_add_recipients_from_role() {
        Octopush_Sms_Send_Tab::_ajax_process_addRecipientsFromRole();
        wp_die();
    }

    public static function octopush_sms_messages() {
        $messages = new Octopush_Sms_Messages();
        echo $messages->getBody();
    }

    /**
     * Display campaign tab
     */
    public static function octopush_sms_campaigns() {
        $send_tab = new Octopush_Sms_Send_Tab();
        echo $send_tab->get_body();
    }

    /**
     * Display history tab
     */
    public static function octopush_sms_history() {
        $history_tab = new Octopush_Sms_History_Tab();
        echo $history_tab->get_body();
    }

    /**
     * Load the settings class
     * @param  array $settings
     * @return array
     */
    public function load_settings_class($settings) {
        $settings[] = include 'class-octopush-sms-settings.php';
        return $settings;
    }

    /**
     * Handles output of report
     */
    public static function status_report() {
        //include_once( 'views/html-admin-page-status-report.php' );
    }

    public function get_form_url() {
        $uri = $_SERVER['REQUEST_URI'];
        $pos = strpos($_SERVER['REQUEST_URI'], '&action=');
        if ($pos !== false)
            $uri = substr($_SERVER['REQUEST_URI'], 0, $pos);
        return esc_url_raw($uri);
    }

    public function get_status($status) {
        switch ($status) {
            case 0:
                return __('In construction', 'octopush-sms');
            case 1:
                return __('Transfer in progess', 'octopush-sms');
            case 2:
                return __('Waiting for validation', 'octopush-sms');
            case 3:
                return __('Sent', 'octopush-sms');
            case 4:
                return __('Cancelled', 'octopush-sms');
            case 5:
                return __('Error', 'octopush-sms');
            default:
                break;
        }
    }

    /*
      public function get_error_SMS($code) {
      switch ($code) {
      // only valid for recipient status
      case 0:
      return __('-', 'octopush-sms');
      case '001':
      return __('Connexion to www.octopush-sms.com failed', 'octopush-sms');
      case 100:
      return __('Missing post parameters', 'octopush-sms');
      case 101:
      return __('Bad connection information', 'octopush-sms');
      case 102:
      return __('Your SMS is longer than 160 chars', 'octopush-sms');
      case 103:
      return __('No recipients found', 'octopush-sms');
      case 104:
      return __('You have not enought credits in your account', 'octopush-sms');
      case 106:
      return __('Bad sender name', 'octopush-sms');
      case 107:
      return __('The text of the message is empty', 'octopush-sms');
      case 108:
      return __('Missing login parameter', 'octopush-sms');
      case 109:
      return __('Missing password parameter', 'octopush-sms');
      case 110:
      return __('Missing phones parameter', 'octopush-sms');
      case 112:
      return __('Missing quality parameter', 'octopush-sms');
      case 150:
      return __('There is no country corresponding to that number', 'octopush-sms');
      case 151:
      return __('This country is not supported by the service', 'octopush-sms');
      case 155:
      return __('This campaign can\'t be modified anymore', 'octopush-sms');
      case 156:
      return __('This campaign can\'t be modified anymore', 'octopush-sms');
      case 157:
      return __('This campaign can\'t be canceled on smsworldsender', 'octopush-sms');
      case 158:
      return __('This campaign doesn\'t exist on smsworldsender', 'octopush-sms');
      case 159:
      return __('This campaign is now processed by smsworldsender platform, please retry in a few seconds', 'octopush-sms');
      case 160:
      return __('This action is not possible on that campaign (not ready for that)', 'octopush-sms');
      default:
      return $code;
      }
      } */

    /**
     * TODO englis message
     * Return error messages
     * @param type $code
     * @return type
     */
    public function get_error_SMS($code) {
        error_log("Test" . $code);
        if (isset($code) && array_key_exists(intval($code), $GLOBALS['errors'])) {
            return $GLOBALS['errors'][intval($code)];
        }
        return __('Error unknown', 'octopush-sms') . " $code";
    }

}
