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
 * OCtopush API class, handles all API calls
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('Octopush_Sms_API')) {

    include_once 'class-octopush-sms-reports.php';
    require_once plugin_dir_path(__FILE__) . 'API_SMS_PHP_Octopush/octopush_web_services.inc.php';
    require_once plugin_dir_path(__FILE__) . 'API_SMS_PHP_Octopush/sms.inc.php';

    class Octopush_Sms_API
    {

        private static $octopush_sms_api;
        public $_phone = null;
        public $_recipients = null;
        public $sms_type = SMS_WORLD; // ou encore SMS_STANDARD,SMS_PREMIUM
        public $sms_mode = DIFFERE; // ou encore DIFFERE
        public $sms_sender;
        //for campaign
        private $_recipient;
        private $_paid_by_customer = 0;
        private $_event = '';


        public static function test_credential($email, $api_key)
        {
            if (WP_DEBUG)
                error_log("octopush-sms - Test credentials");
            if (!empty($email) && !empty($api_key)) {
                $sms = new OWS();
                $sms->set_user_login($email);
                $sms->set_api_key($api_key);
                if (WP_DEBUG)
                    error_log("octopush-sms - get_account");
                $xml0 = $sms->get_balance();
                //TODO remove echo "Balance: $xml0";
                //echo '<textarea style="width:600px;height:600px;">' . $xml0 . '</textarea>';
                if (WP_DEBUG)
                    error_log("octopush-sms - get_account $xml0");

                $xml = simplexml_load_string($xml0);
                //print_r($xml);

                if (!key_exists('error_code', (array) $xml) || $xml->error_code == '000') {

                    //return the balance
                    //return (float) $xml->balance[0];
                    return true;
                } else if ($xml->error_code == '001') {
                    return "connexion failed";
                    //  return '001';
                } else if ($xml->error_code == '101') {
                    return "Incorrect login details";
                    //  return '001';
                } else {
                    return "Unknown error";
                }
            } else {
                return "User login and API key should not be empty";
            }

        }


        public static function get_instance()
        {
            global $octopush_sms_api;
            if (is_null($octopush_sms_api)) {
                $octopush_sms_api = new Octopush_Sms_API(get_option('octopush_sms_email'), get_option('octopush_sms_key'));
            }
            return $octopush_sms_api;
        }

        /**
         * Octopush user login
         * @var string
         * @access public
         * @since 1.0.0
         */
        public $user_login;

        /**
         * Octopush API Key
         * @var string
         * @access public
         * @since 1.0.0
         */
        public $api_key;

        /**
         * Constructor
         * @param string $user_login
         * @param string $api_key
         */
        public function __construct($user_login, $api_key)
        {
            $this->user_login = $user_login;
            $this->api_key = $api_key;
            $this->sms_sender = get_option('octopush_sms_sender');
        }

        public function get_account()
        {
            if (WP_DEBUG)
                error_log("octopush-sms - get_account ,parameters $this->user_login , $this->api_key");
            if (!empty($this->user_login) && !empty($this->api_key)) {
                $sms = new OWS();
                $sms->set_user_login($this->user_login);
                $sms->set_api_key($this->api_key);
                if (WP_DEBUG)
                    error_log("octopush-sms - get_account ");
                $xml0 = $sms->get_balance();

                //TODO remove echo "Balance: $xml0";
                //echo '<textarea style="width:600px;height:600px;">' . $xml0 . '</textarea>';
                if (WP_DEBUG)
                    error_log("octopush-sms - get_account $xml0");

                $xml = simplexml_load_string($xml0);

                if (!key_exists('error_code', (array)$xml) || $xml->error_code == '000') {
                    //if balance greater than alert_level init 'octopush_sms_admin_alert_sent' to say we don't have send the alert sms message
                    $alert_level = (int)get_option('octopush_sms_admin_alert');
                    if ($alert_level > 0 && (float)$xml->balance[0] > $alert_level) {
                        update_option('octopush_sms_admin_alert_sent', 0);
                    }
                    if (!$xml) return '001';
                    //return the balance
                    return (float)$xml->balance[0];
                } else if ($xml->error_code == '001') {
                    // connexion failed
                    return '001';
                } else
                    return false;
            }
            return false;
        }

        public function get_balance()
        {
            $result = $this->get_account();
            return $result;
        }

        /**
         * Get thenews on octopush server
         * @return string|boolean
         */
        public function get_news()
        {
            if (WP_DEBUG)
                error_log("octopush-sms - get_news ");
            $sms = new SMS();
            $sms->set_user_lang(substr(get_bloginfo('language'), 0, 2));
            $xml = simplexml_load_string($sms->getNews(), 'SimpleXMLElement', LIBXML_NOCDATA);
            return $xml;
        }

        public function send_trame($id_campaign, $recipients, $txt, $finished)
        {
            $values = array('{firstname}' => '{prenom}', '{lastname}' => '{nom}');
            $this->_event = 'sendsmsFree';
            $this->_paid_by_customer = 0;
            $this->_recipient = $recipients;

            //if (!empty($this->user_login) && !empty($this->api_key)) {
            $sms = new SMS();
            $sms->set_user_login($this->user_login);
            $sms->set_api_key($this->api_key);
            $sms->set_sms_mode($this->sms_mode);
            $sms->set_sms_type($this->sms_type);

            //campaign message
            $sms_text = str_replace(array_keys($values), array_values($values), $this->replace_for_GSM7($txt));
            $sms->set_sms_text($sms_text);
            $sms->set_sms_sender($this->sms_sender);
            $sms->set_option_transactional(1);

            $phones = array();
            $firstnames = array();
            $lastnames = array();
            foreach ($recipients as $recipient) {
                $phones[] = $recipient->phone;
                if (strpos($txt, '{firstname}') !== false)
                    $firstnames[] = $recipient->firstname;
                if (strpos($txt, '{lastname}') !== false)
                    $lastnames[] = $recipient->lastname;
            }
            $sms->set_sms_recipients($phones);
            $sms->set_recipients_first_names($firstnames);
            $sms->set_recipients_last_names($lastnames);
            $sms->set_user_batch_id($id_campaign);

            $sms->set_finished($finished ? 1 : 0);

            //TODO une fois finis on appelle status en boucle toute les minutes jusqu'à avoir statut
            return $sms->sendSMSParts();
        }

        public function test_auth()
        {
            //TODO chech authentification
            /* $response = $this->perform_request('/user/1', json_encode(array()), 'GET');
              if ($response['response']['code'] != 401) {
              return true;
              } else {
              return false;
              } */
        }

        /**
         * Get dummy value to generate message for test
         * @param type $hook
         * @return type
         */
        public function get_sms_values_for_test($hook)
        {
            $values = array();
            $method = '_get_' . $hook . '_values';
            if (strstr($hook, 'action_order_status_update')) {
                $method = '_get_action_order_status_update_values';
            }
            if (method_exists(__CLASS__, $method)) {
                $values = self::$method(true);
            }
            return $values;
        }

        private function _getBaseValues()
        {
            $values = array(
                '{shopname}' => get_bloginfo('name'),
                '{shopurl}' => get_bloginfo('wpurl')
            );
            return $values;
        }

        /**
         * Return the default text
         * @param type $hookId
         */
        public function get_sms_default_text($hook_key, $bAdmin = false)
        {
            $defaultMessage = "";
            $hookText = $hook_key;
            if ($bAdmin) {
                $hookText .= "_admin";
            }
            switch ($hookText) {
                case 'action_create_account_admin':
                    $defaultMessage = __("{firstname} {lastname} has just registered on {shopname}", 'octopush-sms');
                    break;
                case 'action_send_message_admin' :
                    $defaultMessage = __("{from} has sent a message to{contact_name} ({contact_mail}) : {message}", 'octopush-sms');
                    break;
                case 'action_validate_order_admin' :
                    $defaultMessage = __("New order from {firstname} {lastname}, id: {order_id}, payment: {payment}, total: {total_paid} {currency}.", 'octopush-sms');
                    break;
                /*case 'action_order_return_admin' :
                    $defaultMessage = __("Back order ({return_id}) done by the client {customer_id} about the order {order_id}. Reason : {message}", 'octopush-sms');
                    break;*/
                case 'action_update_quantity_admin' :
                    $defaultMessage = __("This item is almost out of order, id: {product_id}, ref: {product_ref}, name: {product_name}, quantity: {quantity}", 'octopush-sms');
                    break;
                case 'action_test_sms_admin' :
                    return $defaultMessage = __("This is a test from your Woocomerce store. It works!", 'octopush-sms');
                    break;
                case 'action_admin_alert_admin' :
                    $defaultMessage = __("Your SMS credit is almost empty. Your remaining balance is {balance} € available.", 'octopush-sms');
                    break;
                case 'action_daily_report_admin' :
                    $defaultMessage = __("date: {date}, inscriptions: {subs}, orders: {orders}, sales: {day_sales}, for the month of: {month_sales}", 'octopush-sms');
                    break;
                case 'action_create_account' :
                    $defaultMessage = __("{firstname} {lastname}, Welcome to {shopname} !", 'octopush-sms');
                    break;
                case 'action_password_renew' :
                    $defaultMessage = __("{firstname} {lastname}, your new password to access {shopname} is : {password}. {shopurl}", 'octopush-sms');
                    break;
                case 'action_customer_alert' :
                    $defaultMessage = __("{firstname} {lastname}, the item {product} is now available on {shopname} ({shopurl})", 'octopush-sms');
                    break;
                case 'action_send_message' :
                    $defaultMessage = __("Thank you for your message. We will answer you shortly. {shopname}", 'octopush-sms');
                    break;
                case 'action_validate_order' :
                    $defaultMessage = __("{firstname} {lastname}, we do confirm your order {order_id}, of {total_paid} {currency}. Thank you. {shopname}", 'octopush-sms');
                    break;
                /*case 'action_admin_orders_tracking_number_update' :
                    $defaultMessage = __("{firstname} {lastname}, your order {order_id} was delivered. Your shipping number is {shipping_number}. {shopname}", 'octopush-sms');
                    break;*/
                default:
                    //specific case for action_order_status_update where the hook key is action_order_status_update_[order_state] where [order_state] can take differents values
                    if (strstr($hook_key, 'action_order_status_update')) {
                        $defaultMessage = __('{firstname} {lastname}, your order {order_id} on {shopname} has a new status : {order_state}', 'octopush-sms');
                    } else {
                        $defaultMessage = __('Not defined', 'octopush-sms');
                        $defaultMessage .= $hookText;
                    }
                    break;
            }
            return $defaultMessage;
        }

        /**
         * Get values for specific hook: action_creat_account
         * This function returns the values to be replace in the sms send for this hook and the recipients list.
         * @param type $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
         * @param type $b_admin il this is for admin or for customer
         * @return array values to be replaced in the sms send.
         */
        private function _get_action_create_account_values($bSimu = false, $b_admin = false, $params = null)
        {
            if ($bSimu) {
                $values = array(
                    '{firstname}' => 'John',
                    '{lastname}' => 'Doe'
                );
            } else {
                //get customer id
                $customer_id = $params['customer_id'];

                //set sender phone and recipient
                $this->_set_phone(wc_clean($_POST['billing_phone']), wc_clean($_POST['billing_country']), $b_admin);

                $user_data = get_userdata($customer_id);
                if ($user_data == false) {
                    return;
                }
                $values = array(
                    '{firstname}' => isset($_POST['billing_first_name']) ? wc_clean($_POST['billing_first_name']) : $user_data->first_name,
                    '{lastname}' => isset($_POST['billing_last_name']) ? wc_clean($_POST['billing_last_name']) : $user_data->last_name,
                );
            }
            if (WP_DEBUG)
                error_log('action_create_account - _get_action_create_account_values ' . print_r($values, true));
            return array_merge($values, self::_getBaseValues());
        }

        /**
         * Get values for specific hook: action_admin_alert
         * This function returns the values to be replace in the sms send for this hook and the recipients list.
         * @param boolean $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
         * @param boolean $b_admin
         * @return array if not null, return values to be replace in the sms text otherwise if null no value is set and no message have to be send
         */
        public function _get_action_admin_alert_values($bSimu = false, $b_admin = false, $params = null)
        {
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_admin_alert_values - BEGIN');
            if ($bSimu) {
                $values = array(
                    '{balance}' => number_format('10', 3, ',', ' '),
                );
            } else {
                $this->_phone = null;
                //only for admin sms
                if (!$b_admin)
                    return null;

                // si l'alerte est active (> 0) et que le message n'a pas déjà été envoyé
                // et que le nb de SMS restant est < à la limite donnée, alors on envoie
                $alert_level = (int)get_option('octopush_sms_admin_alert');
                $balance = $this->get_balance();
                if (WP_DEBUG)
                    error_log("_get_action_admin_alert_values alert_level $alert_level , balance: $balance");
                if ((get_option('octopush_sms_admin_alert_sent') == null || (int)get_option('octopush_sms_admin_alert_sent') != 1) && $alert_level > 0 && $balance !== false && $balance !== '001' && (float)$balance <= (float)$alert_level) {
                    //remember we send the alert message
                    update_option('octopush_sms_admin_alert_sent', 1);
                    $this->_set_phone(null, null, true);
                    $values = array(
                        '{balance}' => number_format($balance, 3, ',', ' ')
                    );
                } else {
                    return null;
                }
            }
            if (WP_DEBUG)
                error_log('_get_action_admin_alert_values ' . print_r($values, true) . ' octopush_sms_admin_alert_sent:' . get_option('octopush_sms_admin_alert_sent'));

            return array_merge($values, self::_getBaseValues());
        }

        function _get_action_order_status_update_values($bSimu = false, $b_admin = false, $params = array())
        {
            if (WP_DEBUG)
                error_log('octopush-sms - __get_action_order_status_update_values - BEGIN ');
            if (WP_DEBUG)
                error_log('octopush-sms - __get_action_order_status_update_values - POST ' . print_r($_POST, true) . ' params:' . print_r($params, true));

            $currency = get_woocommerce_currency();
            if ($bSimu) {
                $values = array(
                    '{firstname}' => 'John',
                    '{lastname}' => 'Doe',
                    '{order_id}' => '000001',
                    '{order_state}' => 'xxx',
                    '{total_paid}' => '100',
                    '{currency}' => $currency
                );
            } else {
                if ($b_admin) {
                    //not for admin
                    return null;
                }
                $order_id = $params['order_id'];
                if ($order_id) {

                    //the send of this sms is optionnal. Verify that you can send it.
                    if (!$this->can_send_optional_sms($order_id, $b_admin))
                        return null;

                    $order = wc_get_order($order_id);
                    //never send to the admin
                    $this->_set_phone($order->billing_phone, $order->billing_country, false);

                    //object->get_order_number();
                    $values = array(
                        '{firstname}' => $order->billing_first_name,
                        '{lastname}' => $order->billing_last_name,
                        '{order_id}' => $order_id,
                        '{order_state}' => wc_get_order_status_name($params['new_status']),
                        '{total_paid}' => $order->get_total(),
                        '{currency}' => $currency
                    );
                } else {
                    return null;
                }
            }
            if (WP_DEBUG)
                error_log('octopush-sms - __get_action_order_status_update_values - values ' . print_r($values, true) . ' phone:' . print_r($this->_phone, true));

            return array_merge($values, self::_getBaseValues());
        }

        /**
         * Get values for specific hook: action_validate_order
         * This function returns the values to be replace in the sms send for this hook and the recipients list.
         * @param type $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
         * @param type $b_admin
         * @param type $params
         * @return type
         */
        public function _get_action_validate_order_values($bSimu = false, $b_admin = false, $params = null)
        {
            $currency = get_woocommerce_currency(); //get_woocommerce_currency_symbol();
            if ($bSimu) {
                $values = array(
                    '{firstname}' => 'John',
                    '{lastname}' => 'Doe',
                    '{order_id}' => '000001',
                    '{payment}' => 'Paypal',
                    '{total_paid}' => '100',
                    '{currency}' => $currency,
                );
            } else {
                if (WP_DEBUG)
                    error_log('POST:' . print_r($_POST, true));
                if (WP_DEBUG)
                    error_log('params:' . print_r($params, true));
                //init values
                $this->_recipients = null;
                $this->_phone = null;

                //the send of this sms is optionnal. Verify that you can send it.
                if (!$this->can_send_optional_sms($params['order_id'], $b_admin))
                    return null;

                $this->_set_phone(wc_clean($_POST['billing_phone']), wc_clean($_POST['billing_country']), $b_admin);

                //getorder
                $order = new WC_Order($params['order_id']);
                //error_log('order:' . print_r($order, true) . ' ' . print_r($order->get_order_item_totals(), true));
                //get payment method
                $payment_gateways = null;
                if (WC()->payment_gateways()) {
                    $payment_gateways = WC()->payment_gateways->payment_gateways();
                }
                $payment_method = !empty($order->payment_method) ? $order->payment_method : __('undefined', 'octopush_sms');
                if ($payment_method) {
                    $payment_method = (isset($payment_gateways[$payment_method]) ? esc_html($payment_gateways[$payment_method]->get_title()) : esc_html($payment_method));
                }

                $values = array(
                    '{firstname}' => isset($_POST['billing_first_name']) ? wc_clean($_POST['billing_first_name']) : __('undefined', 'octopush_sms'),
                    '{lastname}' => isset($_POST['billing_last_name']) ? wc_clean($_POST['billing_last_name']) : __('undefined', 'octopush_sms'),
                    '{order_id}' => $params['order_id'],
                    '{payment}' => $payment_method,
                    '{total_paid}' => isset($order) ? $order->get_total() : __('undefined', 'octopush_sms'),
                    '{currency}' => $currency
                );
            }
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_validate_order_values - values ' . print_r($values, true) . ' phone:' . print_r($this->_phone, true));
            return array_merge($values, self::_getBaseValues());
        }

        /**
         * If the option is not free (octopush_sms_freeoption == 0), verify the order contains the good product_id (octopush_sms_option_id_product) to send the sms
         * @param type $order_id
         * @param type $b_admin
         * @return boolean
         */
        function can_send_optional_sms($order_id, $b_admin)
        {
            if (!$b_admin && (int)get_option('octopush_sms_freeoption') == 0) {
                $option_product_id = get_option('octopush_sms_option_id_product');
                $b_find = false;
                $order = wc_get_order($order_id);
                if (isset($order)) {
                    foreach ($order->get_items() as $item) {
                        if ($option_product_id == $item['product_id']) {
                            $b_find = true;
                        }
                    }
                }
                //if not found sms is not send
                if (!$b_find)
                    return false;
            }
            return true;
        }

        /**
         * Get values for specific hook: action_password_renew
         * This function returns the values to be replace in the sms send for this hook and the recipients list.
         * @param type $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
         * @param type $b_admin
         * @param type $params
         * @return array values to replace in sms text
         */
        public function _get_action_password_renew_values($bSimu = false, $b_admin = false, $params = array())
        {
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_password_renew_values - BEGIN');
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_password_renew_values - POST' . print_r($_POST, true));
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_password_renew_values - params:' . print_r($params, true));
            if ($bSimu) {
                $values = array(
                    '{firstname}' => 'John',
                    '{lastname}' => 'Doe',
                    '{password}' => 'YourNewPass',
                );
            } else {
                $user = $params['user'];
                $new_pass = $params['new_pass'];

                //Take the billing phone
                $this->_set_phone(get_user_meta($user->ID, 'billing_phone', true), get_user_meta($user->ID, 'billing_country', true), false);

                $values = array(
                    '{firstname}' => get_user_meta($user->ID, 'first_name', true) ? get_user_meta($user->ID, 'first_name', true) : $user->user_nicename,
                    '{lastname}' => get_user_meta($user->ID, 'last_name', true) ? get_user_meta($user->ID, 'last_name', true) : $user->display_name,
                    '{password}' => $new_pass
                );
            }
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_password_renew_values - values ' . print_r($values, true));
            return array_merge($values, self::_getBaseValues());
        }


        //action_update_quantity
        //_get_action_update_quantity_values
        //action_test_sms_admin


        public function _get_action_test_sms_values($bSimu = false, $b_admin = false, $params = array())
        {
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_test_sms_admin_values - BEGIN');
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_test_sms_admin_values - POST ' . print_r($_POST, true));
            if ($bSimu) {
                $values = array(
                    '{contact_name}' => 'webmaster',
                    '{contact_mail}' => 'webmaster@woocommerce.com',
                    '{from}' => 'johndoe@gmail.com',
                    '{message}' => 'This is a message'
                );
            } else {


                $this->_set_phone(null, null, $b_admin);

                $values = array(
                    '{contact_name}' => 'webmaster',
                    '{contact_mail}' => 'webmaster@woocommerce.com',
                    '{from}' => 'johndoe@gmail.com',
                    '{message}' => 'This is a message'
                );
                if (WP_DEBUG)
                    error_log('octopush-sms - _get_action_test_sms_admin_values - values ' . print_r($values, true));
            }
            return array_merge($values, self::_getBaseValues());
        }

        //action_update_quantity
        public function _get_action_update_quantity_values($bSimu = false, $b_admin = false, $params = array())
        {
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_update_quantity_values - BEGIN');
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_update_quantity_values - POST ' . print_r($_POST, true));
            if ($bSimu) {
                $values = array(
                    '{product_id}' => '000001',
                    '{product_ref}' => 'REF-001',
                    '{product_name}' => 'Ipod Nano',
                    '{quantity}' => '2'
                );
            } else {
                $product = $params['product'];

                $this->_set_phone(null, null, $b_admin);

                $values = array(
                    '{product_id}' => $product->id,
                    '{product_ref}' => $product->get_sku(),
                    '{product_name}' => $product->get_title(),
                    '{quantity}' => $product->get_total_stock(),
                );
                if (WP_DEBUG)
                    error_log('octopush-sms - _get_action_update_quantity_values - values ' . print_r($values, true));
            }
            return array_merge($values, self::_getBaseValues());
        }

        private function _get_action_send_message_values($bSimu = false, $b_admin = false, $params = array())
        {
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_send_message_values - BEGIN');
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_send_message_values - POST ' . print_r($_POST, true));

            if ($bSimu) {
                $values = array(
                    '{contact_name}' => 'webmaster',
                    '{contact_mail}' => 'webmaster@woocommerce.com',
                    '{from}' => 'johndoe@gmail.com',
                    '{message}' => 'This is a message'
                );
            } else {
                error_log('octopush-sms - _get_action_send_message_values - POST ' . print_r($params, true));
                $comment = $params['comment'];

                if ($comment->comment_type != '') {
                    return null;
                }

                if ($b_admin) {
                    //if admin, send to admin
                    $this->_set_phone(null, null, $b_admin);
                } else {
                    //otherwise send to customer
                    if (isset($comment->user_id) && get_user_meta($comment->user_id, 'billing_phone', true) != null)
                        $this->_set_phone(get_user_meta($comment->user_id, 'billing_phone', true), get_user_meta($comment->user_id, 'billing_country', true), false);
                    error_log($comment->user_id . ' ' . get_user_meta($comment->user_id, 'billing_phone', true));
                }

                $values = array(
                    '{contact_name}' => get_bloginfo('admin_email'),
                    '{contact_mail}' => get_bloginfo('admin_email'),
                    '{from}' => $comment->comment_author_email,
                    '{message}' => $comment->comment_content,
                );
            }
            if (WP_DEBUG)
                error_log('action_send_message - _get_action_send_message_values ' . print_r($values, true) . ' phone:' . print_r($this->_phone, true));
            return array_merge($values, self::_getBaseValues());
        }

        /**
         * Get values for specific hook: action_daily_report
         * This function returns the values to be replace in the sms send for this hook and the recipients list.
         * @param boolean $bSimu if true, return dummy values for an example, if false give the values in function of the parameters given in array params
         * @param boolean $b_admin
         * @return array if not null, return values to be replace in the sms text otherwise if null no value is set and no message have to be send
         */
        function _get_action_daily_report_values($bSimu = false, $b_admin = false, $params = array())
        {
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_daily_report_values - BEGIN');
            if (WP_DEBUG)
                error_log('octopush-sms - _get_action_daily_report_values - POST' . print_r($_POST, true));

            $currency = get_woocommerce_currency();
            if ($bSimu) {
                $values = array(
                    '{date}' => date('Y-m-d'),
                    '{subs}' => '5',
                    //'{visitors}' => '42',
                    //'{visits}' => '70',
                    '{orders}' => '8',
                    '{day_sales}' => "50 $currency",
                    '{month_sales}' => "1000 $currency",
                );
            } else {

                $filter['period'] = 'month';
                $reports = new Octopush_Sms_Reports();
                $stat = $reports->get_sales_report(null, $filter);
                if (WP_DEBUG)
                    error_log(print_r($stat, true));

                //only for admin
                $this->_set_phone(null, null, $b_admin);

                $day_total = $stat['sales']['totals'][date('Y-m-d')];
                $values = array(
                    '{date}' => date('Y-m-d'),
                    '{subs}' => intval($day_total['customers']),
                    //'{visitors}' => 'NA',
                    //'{visits}' => 'NA',
                    '{orders}' => $day_total['orders'],
                    '{day_sales}' => $day_total['sales'] . ' ' . $currency,
                    '{month_sales}' => $stat['sales']['total_sales'] . ' ' . $currency,
                );
            }
            if (WP_DEBUG)
                error_log('action_daily_report - _get_action_daily_report_values ' . print_r($values, true));
            return array_merge($values, self::_getBaseValues());
        }

        /**
         * Send a sms (depends of the hook and parameters given)
         * @param type $hookName
         * @param type $hookId
         * @param type $params
         */
        public function send($hook, $params)
        {
            $result = array('customer' => '', 'admin' => '');
            //send sms for the client (check if sending the sms is needed, create the mesage...)
            $result['customer'] = self::_prepare_sms($hook, $params);
            //send sms for admin (check if sending the sms is needed, create the mesage...)
            $result['admin'] = self::_prepare_sms($hook, $params, true);

            //specific case of action_admin_alert
            //this function is call every time a hook is called to test the balance and send an alert sms if needed
            if ($hook != 'action_admin_alert') {
                Octopush_Sms_Admin::get_instance()->action_admin_alert();
            }

            return $result;
        }

        /**
         * Construct the sms values before send it.
         * The SMS values depend of the hook
         * @param type $b_admin
         */
        public function _prepare_sms($hook_id, $params, $b_admin = false)
        {
            if (WP_DEBUG)
                error_log("==========================  _prepare_sms hook_id:$hook_id , admin:$b_admin==================================================");
            //defined the method name to call to set the recipient phone and the value to replave in the sms text
            $method = '_get_' . $hook_id . '_values';
            if (WP_DEBUG)
                error_log('_prepare_sms ' . $method . 'params:' . print_r($params, true));
            //if method to get the values corresponding to this hook exist we continue
            if (method_exists(__CLASS__, $method)) {
                $this->_recipients = null;
                $this->_phone = null;
                //this internal hook is active?
                $is_active = get_option(Octopush_Sms_Admin::get_instance()->_get_isactive_hook_key($hook_id, $b_admin, $params));

                //get the text
                $text = get_option(Octopush_Sms_Admin::get_instance()->_get_hook_key($hook_id, $b_admin, $params));
                if (WP_DEBUG)
                    error_log('octopush-sms| ' . Octopush_Sms_Admin::get_instance()->_get_hook_key($hook_id, $b_admin, $params) . ' , text:' . $text . ' , isActive:' . $is_active);
                $locale = get_locale();
                //modification for send test sms to admin
                if ($hook_id == "action_test_sms" && $b_admin == true) {
                    $is_active = true;
                    $text = $this->get_sms_default_text($hook_id, $b_admin);
                }
                //if active and a text exists
                if ($is_active && $text) {
                    //get values to replace in sms text
                    $values = $this->$method(false, $b_admin, $params);
                    if (WP_DEBUG)
                        error_log('values to be replace:' . print_r($values, true));
                    //if we can send the sms, we send it
                    if (is_array($values)) {
                        //check if everything is valid for sending the sms (if $this->_phone is not send, nothing is send)
                        if ($this->_is_everything_valid_for_sending()) {
                            $text_to_send = str_replace(array_keys($values), array_values($values), $text);
                            return $this->_send_sms($text_to_send);
                        } else {
                            return array('error' => true, 'error_code' => 'Some thing is wrong with credentials.');
                        }
                    }
                }
                /* TODO faie gestion de langue de l'admin
                  switch (self::$_hookName) {
                  case 'actionOrderStatusPostUpdate':
                  $stateId = self::$_params['newOrderStatus']->id;
                  $keyActive .= '_' . $stateId;
                  $keyTxt .= '_' . $stateId;
                  self::$_event .= '_' . $stateId;
                  $order = new Order((int) self::$_params['id_order']);
                  if (!$b_admin)
                  $idLang = $order->id_lang;
                  break;
                  case 'actionAdminOrdersTrackingNumberUpdate':
                  $order = self::$_params['order'];
                  $idLang = $order->id_lang;
                  if (!$b_admin)
                  $idLang = $order->id_lang;
                  break;
                  default :
                  break;
                  } */
            }

            return false;
        }

        public function _is_everything_valid_for_sending()
        {
            return (get_option('octopush_sms_key') && get_option('octopush_sms_sender') && get_option('octopush_sms_admin_phone') && !empty($this->_phone) && is_array($this->_phone));
        }

        /**
         * Set the phone attribut (convert the phone number to international phone number)
         * @param type $phone
         * @param type $country iso country code (ex. FR)
         * @param type $b_admin
         */
        public function _set_phone($phone = null, $country = null, $b_admin = false)
        {
            $this->_phone = null;
            if ($b_admin) {
                $this->_phone = array(get_option('octopush_sms_admin_phone'));
            } else if (!empty($country)) {
                if (!empty($phone) && !empty($country)) {
                    $this->_phone = array($this->convert_phone_to_international($phone, $country));
                }
            }
            if (WP_DEBUG)
                error_log("_set_phone _phone:" . print_r($this->_phone, true));
        }


        /**
         * Send sms
         * @param type $text_to_send the textto send
         * @param type $recipients the recipients
         * @return boolean
         */
        public function _send_sms($sms_text)
        {
            if (WP_DEBUG)
                error_log('_send_sms ' . $sms_text . ' phone:' . print_r($this->_phone, true));
            $sms = new SMS();

            $sms_type = SMS_WORLD; //SMS_STANDARD; // ou encore SMS_STANDARD,SMS_PREMIUM
            $sms_mode = INSTANTANE; // ou encore DIFFERE
            $sms_sender = get_option('octopush_sms_sender');

            $sms->set_user_login($this->user_login);
            $sms->set_api_key($this->api_key);
            $sms->set_sms_mode($sms_mode);
            $sms->set_sms_text($this->replace_for_GSM7($sms_text));
            $sms->set_sms_recipients($this->_phone);
            $sms->set_sms_type($sms_type);
            $sms->set_sms_sender($sms_sender);
            $sms->set_sms_request_id(uniqid());
            $sms->set_option_with_replies(0);
            //$sms->set_sms_fields_1(array(''));
            //$sms->set_sms_fields_2(array('a'));
            $sms->set_option_transactional(1);
            $sms->set_sender_is_msisdn(0);
            //$sms->set_date(2016, 4, 17, 10, 19); // En cas d'envoi différé.
            //$sms->set_request_keys('TRS');

            if (WP_DEBUG)
                error_log('***** _send_sms ' . print_r($sms, true));
            $xml = $sms->send();
            if (WP_DEBUG)
                error_log('***** Result ' . $xml);
            if ($xml = simplexml_load_string($xml)) {
                if (!key_exists('error_code', (array) $xml) || $xml->error_code == '000') {
                    return true;
                } else {
                    error_log(Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code));
                    return array('error' => true, 'error_code' => $xml->error_code, 'error_details' => Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code));
                }
            }

            /*

              $sms = new SWS();
              $sms->setSmsLogin(Configuration::get('SENDSMS2_EMAIL'));
              $sms->setSmsPassword(Configuration::get('SENDSMS2_PASSWORD'));
              $sms->setSmsKey(Configuration::get('SENDSMS2_KEY'));
              $sms->setText(self::$_txt);
              $sms->setPhones(array(self::$_phone));
              $sms->setSender(Configuration::get('SENDSMS2_SENDER'));
              $sms->setRequestMode((int) Configuration::get('SENDSMS2_SIMULATION'));
              $xml = $sms->send();
              if ($xml = simplexml_load_string($xml)) {
              $date = date('Y-m-d H:i:s');
              $campaign = new SendsmsCampaign();
              $campaign->status = isset($xml->failures->failure->error_code) || $xml->error_code != '000' ? 5 : 3;
              $campaign->error_code = (string) $xml->failures->failure->error_code;
              $campaign->ticket = (string) $xml->ticket;
              $campaign->message = self::$_txt;
              $campaign->nb_recipients = isset($xml->failures->failure->error_code) ? 0 : 1;
              $campaign->nb_sms = isset($xml->failures->failure->error_code) ? 0 : $xml->number_of_sendings;
              $campaign->price = isset($xml->failures->failure->error_code) ? 0 : $xml->cost;
              $campaign->event = self::$_event;
              $campaign->paid_by_customer = self::$_paid_by_customer;
              $campaign->simulation = (int) Configuration::get('SENDSMS2_SIMULATION');
              $campaign->date_send = $date;
              $campaign->date_transmitted = $date;
              $campaign->date_validation = $date;
              $campaign->save();

              $recipient = new SendsmsRecipient();
              $recipient->id_sendsms_campaign = $campaign->id;
              if (isset(self::$_recipient)) {
              $recipient->id_customer = self::$_recipient->id;
              $recipient->firstname = self::$_recipient->firstname;
              $recipient->lastname = self::$_recipient->lastname;
              }
              $recipient->phone = self::$_phone;
              $recipient->id_country = self::$_id_country;
              $recipient->transmitted = 1;
              $recipient->price = isset($xml->failures->failure->error_code) ? 0 : $xml->successs->success->cost;
              $recipient->nb_sms = isset($xml->failures->failure->error_code) ? 0 : $xml->successs->success->sms_needed;
              $recipient->status = isset($xml->failures->failure->error_code) ? $xml->failures->failure->error_code : 0;
              $recipient->ticket = (string) $xml->ticket;
              $recipient->save();

              if ($xml->error_code == '000')
              return true;
              }
              return false; */

            return false;
        }

        private function _set_recipient($customer)
        {
            $this->_recipients = $customer;
        }

        public function replace_for_GSM7($txt)
        {
            $search = array('À', 'Á', 'Â', 'Ã', 'È', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ò', 'Ó', 'Ô', 'Õ', 'Ù', 'Ú', 'Û', 'Ý', 'Ÿ', 'á', 'â', 'ã', 'ê', 'ë', 'í', 'î', 'ï', 'ð', 'ó', 'ô', 'õ', 'ú', 'û', 'µ', 'ý', 'ÿ', 'ç', 'Þ', '°', '¨', '^', '«', '»', '|', '\\');
            $replace = array('A', 'A', 'A', 'A', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'Y', 'Y', 'a', 'a', 'a', 'e', 'e', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'y', 'y', 'c', 'y', 'o', '-', '-', '"', '"', 'I', '/');
            return str_replace($search, $replace, $txt);
        }

        public function is_GSM7($txt)
        {
            if (preg_match("/^[ÀÁÂÃÈÊËÌÍÎÏÐÒÓÔÕÙÚÛÝŸáâãêëíîïðóôõúûµýÿçÞ°{|}~¡£¤¥§¿ÄÅÆÇÉÑÖØÜßàäåæèéìñòöøùü,\.\-!\"#$%&()*+\/:;<=>?@€\[\]\^\w\s\\']*$/u", $txt))
                return true;
            else
                return false;
        }

        public function not_GSM7($txt)
        {
            return preg_replace("/[ÀÁÂÃÈÊËÌÍÎÏÐÒÓÔÕÙÚÛÝŸáâãêëíîïðóôõúûµýÿçÞ°{|}~¡£¤¥§¿ÄÅÆÇÉÑÖØÜßàäåæèéìñòöøùü,\.\-!\"#$%&()*+\/:;<=>?@€\[\]\^\w\s\\']/u", "", $txt);
        }

        /**
         * Convert phone number to international phone number.
         *
         * @param type $phone
         * @param type $iso_country
         * @param type $prefix
         * @return type
         * @global type $wpdb
         */
        public function convert_phone_to_international($phone, $iso_country, $prefix = null)
        {
            global $wpdb;

            $phone = preg_replace("/[^+0-9]/", "", $phone);

            if (is_null($prefix))
                $prefix = $wpdb->get_var("SELECT prefix FROM `" . $wpdb->prefix . "octopushsms_phone_prefix` WHERE `iso_code` = '" . $iso_country . "'");
            if (empty($prefix))
                return null;
            else {
                // s'il commence par + il est déjà international
                if (substr($phone, 0, 1) == '+') {
                    return $phone;
                } // s'il commence par 00 on les enlève et on vérifie le code pays pour ajouter le +
                else if (substr($phone, 0, 2) == '00') {
                    $phone = substr($phone, 2);
                    if (strpos($phone, $prefix) === 0) {
                        return '+' . $phone;
                    } else {
                        return null;
                    }
                } // s'il commence par 0, on enlève le 0 et on ajoute le prefix du pays
                else if (substr($phone, 0, 1) == '0') {
                    return '+' . $prefix . substr($phone, 1);
                } // s'il commence par le prefix du pays, on ajoute le +
                else if (strpos($phone, $prefix) === 0) {
                    return '+' . $phone;
                } else {
                    return '+' . $prefix . $phone;
                }
            }
        }

        /**
         * Validate the campaign.
         *
         * @param type $id_campaign
         * @param type $time
         * @return type
         */
        public function validate_campaign($ticket, $time)
        {
            $sms = new SMS();
            $action = 'send';
            $sms->set_user_login($this->user_login);
            $sms->set_api_key($this->api_key);
            $sms->set_user_batch_id($ticket);
            $sms->set_sms_type(SMS_WORLD);

            //Be careful to convert in function of the sms shop time GMT +1
            $gmt_offset = get_option('gmt_offset');

            //TODO Attention à convertir en GMT + 1 en fonction du fuseau horaire du shop
            //$campaign = new Octopush_Sms_Campaign($ticket);
            //error_log("OHHHHHHH2:" . print_r($campaign, true) . " " . $campaign->date_send);
            $sms->set_sms_mode(DIFFERE);
            $date_send = date_parse_from_format("Y-m-d H:i:s", $time);
            $sms->set_date($date_send['year'], $date_send['month'], $date_send['day'], $date_send['hour'] - $gmt_offset, $date_send['minute']); // En cas d'envoi diffÈrÈ.
            if (WP_DEBUG)
                error_log("validate_campaign sms:" . print_r($sms, true) . " JSON_ENCODE_SMS: " . json_encode($sms));
            $xml = $sms->SMSBatchAction($action);
            if (WP_DEBUG) {
                error_log("validate_campaign($ticket, $time) : $xml");
            }
            //alert if account under a certain level
            Octopush_Sms_Admin::get_instance()->action_admin_alert();

            return $xml;
        }

        /**
         * Get the campaign status on octopush.
         *
         * @param type $id_campaign
         * @param type $time
         * @return type
         */
        public function get_campaign_status($id_campaign)
        {
            $sms = new SMS();
            $action = 'status';
            $sms->set_user_login($this->user_login);
            $sms->set_api_key($this->api_key);
            $sms->set_user_batch_id($id_campaign);
            if (WP_DEBUG)
                error_log("get_campaign_status:" . print_r($sms, true));
            $xml = $sms->SMSBatchAction($action);
            if (WP_DEBUG) {
                error_log("get_campaign_status($id_campaign) : $xml");
            }
            return $xml;
        }

        /**
         * Cancel a campaign on octopus server if it is possible.
         * @param type $id_campaign
         * @return type
         */
        public function cancel_campaign($id_campaign)
        {
            $sms = new SMS();
            $action = 'delete';
            $sms->set_user_login($this->user_login);
            $sms->set_api_key($this->api_key);
            $sms->set_user_batch_id($id_campaign);
            $xml = $sms->SMSBatchAction($action);
            if (WP_DEBUG) {
                error_log("cancel_campaign($id_campaign) : $xml");
            }
            return $xml;
        }

    }

}