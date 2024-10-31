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
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Octopush_Sms_Settings')) :

    /**
     * Octopush_Sms_Settings
     */
    class Octopush_Sms_Settings extends WC_Settings_Page {

        public $balance;
        public $bAuth = false;

        /**
         * Constructor.
         */
        public function __construct() {
            $this->id = 'octopush-sms';
            $this->label = __('Send SMS', 'octopush-sms');            
        }

        /**
         * Display tab and save setting if post data is received
         */
        public function output() {
            //save is date is send
            $this->_post_process();
            echo WC_Admin_Settings::show_messages();
            include_once( 'partials/html-octopush-sms-settings.php' );
        }

        /**
         * Save data
         */
        private function _post_process() {
            if (array_key_exists('octopush_sms_email', $_POST)) {
                sanitize_post($_POST);
                $email = wc_clean($_POST['octopush_sms_email']);
                if (array_key_exists('octopush_sms_email', $_POST))
                    $key = wc_clean($_POST['octopush_sms_key']);
                if (array_key_exists('octopush_sms_sender', $_POST))
                    $sender = wc_clean($_POST['octopush_sms_sender']);

                if (array_key_exists('octopush_sms_admin_phone', $_POST))
                    $admin_phone = wc_clean($_POST['octopush_sms_admin_phone']);
                if (array_key_exists('octopush_sms_admin_alert', $_POST))
                    $admin_alert = wc_clean($_POST['octopush_sms_admin_alert']);
                $freeoption = null;
                $product_id = 0;
                error_log(print_r($_POST, true));
                if (array_key_exists('octopush_sms_freeoption', $_POST)) {
                    if ($_POST['octopush_sms_freeoption'] == (int) 1) {
                        $freeoption = 1;
                    } else {
                        $freeoption = 0;
                        $product_id = wc_clean($_POST['octopush_sms_option_id_product']);
                    }
                } else {
                    $freeoption = 0;
                    $product_id = wc_clean($_POST['octopush_sms_option_id_product']);
                }

                //requiered field
                if (empty($email) || empty($key)) {
                    WC_Admin_Settings::add_error(__('Please enter your account information to login to www.octopush.com', 'octopush-sms'));
                }
                //email validation
                if (!is_email($email)) {
                    WC_Admin_Settings::add_error(__('The email you entered is not a valid email.', 'octopush-sms'));
                } else {
                    //save the option
                    update_option('octopush_sms_email', $email);
                }
                //key
                if (!empty($key)) {
                    //save the option
                    update_option('octopush_sms_key', $key);
                    $this->balance = Octopush_Sms_API::get_instance()->get_balance();
                    if ($this->balance === false)
                        WC_Admin_Settings::add_error(__('This account is not a valid account on www.octopush.com', 'octopush-sms'));
                    else if ($this->balance === '001')
                        WC_Admin_Settings::add_error(Octopush_Sms_Admin::get_instance()->get_error_SMS('001'));
                    $this->bAuth = $this->balance === false || $this->balance === '001' ? false : true;
                }

                if (!preg_match('/^[[:digit:]]{1,16}$/', $sender) && !preg_match('/^[[:alnum:]]{1,11}$/', $sender)) {
                    WC_Admin_Settings::add_error(__('Please enter a valid sender name : 11 chars max (letters + digits)', 'octopush-sms'));
                } else {
                    update_option('octopush_sms_sender', $sender);
                }
                //Admin phone
                if (empty($admin_phone) || !preg_match('/^\+[0-9]{6,16}$/', $admin_phone)) {
                    WC_Admin_Settings::add_error(__('Please enter a valid admin mobile number', 'octopush-sms'));
                } else {
                    update_option('octopush_sms_admin_phone', $admin_phone);
                }
                //Admin alert
                if ($admin_alert && !is_numeric($admin_alert)) {
                    WC_Admin_Settings::add_error(__('Please enter a valid integer value for alert', 'octopush-sms'));
                } else {
                    update_option('octopush_sms_admin_alert', $admin_alert);
                }
                //free option
                if (isset($freeoption)) {
                    if (!is_numeric($product_id)) {
                        WC_Admin_Settings::add_error(__('Please enter a valid integer value for product_id', 'octopush-sms'));
                    } else {
                        //TODO verify product exist
                        update_option('octopush_sms_option_id_product', $product_id);
                    }
                    update_option('octopush_sms_freeoption', $freeoption);
                }
            }
            $this->balance = Octopush_Sms_API::get_instance()->get_balance();
            $this->bAuth = $this->balance === false || $this->balance === '001' ? false : true;
        }

    }

    endif;

return new Octopush_Sms_Settings();

