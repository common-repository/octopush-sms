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
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('Octopush_Sms_Send_Tab')) {

    include_once(plugin_dir_path(__FILE__) . 'class-octopush-sms-generic-campaign-tab.php');
    /* include_once 'models/class-octopush-sms-campaign-model.php';
      include_once 'models/class-octopush-sms-campaign.php';
      include_once 'models/class-octopush-sms-recipient.php'; */

    class Octopush_Sms_Send_Tab extends Octopush_Sms_Generic_Campaign_Tab {

        protected $_status = array(0, 1, 2);

        protected function _get_display_status() {
            return $this->_status;
        }

        protected $post;

        protected function _post_process() {
            global $wpdb;
            if (!isset($_REQUEST)) {
                return;
            }
            $this->post = $_REQUEST; //sanitize_post($_REQUEST, 'edit');

            if (isset($this->post['sendsms_save']) && $this->_campaign->status == 0) {
                //create campaign
                $this->_post_validation();
                if (!sizeof(self::get_errors())) {
                    $this->_campaign->ticket = (string) time();
                    $this->_campaign->title = sanitize_text_field($this->post['sendsms_title']);
                    $this->_campaign->message = $this->post['sendsms_message'];
                    $date = strtotime($this->post['sendsms_date'] . ' ' . (int) (isset($this->post['sendsms_date_hour']) ? $this->post['sendsms_date_hour'] : 0) . ':' . (isset($this->post['sendsms_date_minute']) ? $this->post['sendsms_date_minute'] : 0) . ':00');
                    $this->_campaign->date_send = date_i18n('Y-m-d H:i:s', $date);
                    $this->_campaign->save();
                    self::add_message(__('Your campaign has been saved.', 'octopush-sms'));
                }
            } else if ($this->_campaign->status == 0 && isset($_FILES['sendsms_csv']['tmp_name']) && !empty($_FILES['sendsms_csv']['tmp_name'])) {
                //import a csv file and create campaign if it not exists
                if (!$this->post['id_sendsms_campaign']) {
                    $this->_campaign->ticket = (string) time();
                    $this->_campaign->title = isset($this->post['sendsms_title']) && $this->post['sendsms_title'] != '' ? sanitize_text_field($this->post['sendsms_title']) : 'CAMPAIGN-' . $this->_campaign->ticket;
                    $this->_campaign->message = sanitize_text_field($this->post['sendsms_message']);
                    $date = strtotime($this->post['sendsms_date'] . ' ' . (int) (isset($this->post['sendsms_date_hour']) ? $this->post['sendsms_date_hour'] : 0) . ':' . (isset($this->post['sendsms_date_minute']) ? $this->post['sendsms_date_minute'] : 0) . ':00');
                    $this->_campaign->date_send = date_i18n('Y-m-d H:i:s', $date);
                    $this->_campaign->save();
                }
                $tempFile = $_FILES['sendsms_csv']['tmp_name'];
                if (!is_uploaded_file($tempFile))
                    self::add_error(__('The file has not been uploaded', 'octopush-sms'));
                else {
                    $cpt = 0;
                    $line = 0;
                    if (($fd = fopen($tempFile, "r")) !== FALSE) {
                        while (($data = fgetcsv($fd, 1000, ";")) !== FALSE) {
                            $line++;
                            if (count($data) >= 1) {
                                $phone = $data[0];
                                // If not international phone
                                if (substr($phone, 0, 1) != '+')
                                    continue;
                                $firstname = isset($data[1]) ? $data[1] : null;
                                $lastname = isset($data[2]) ? $data[2] : null;
                                // if phone is not valid
                                if (!WC_Validation::is_phone($phone))
                                    continue;
                                $recipient = new Octopush_Sms_Recipient();
                                $recipient->id_sendsms_campaign = $this->_campaign->id_sendsms_campaign;
                                $recipient->firstname = $firstname;
                                $recipient->lastname = $lastname;
                                $recipient->phone = $phone;
                                $recipient->status = 0;
                                // can fail if that phone number already exist for that campaign
                                try {
                                    $nbr = $recipient->save();
                                    if ($nbr)
                                        $cpt++;
                                } catch (Exception $e) {
                                    
                                }
                            }
                        }
                        fclose($fd);
                    }
                    if ($line == 0)
                        self::add_error(__('That file is not a valid CSV file.', 'octopush-sms'));
                    else {
                        $this->_campaign->compute_campaign();
                        self::add_message($cpt . ' ' . __('new recipient(s) were added to the list.', 'octopush-sms') . ($line - $cpt > 0 ? ' ' . ($line - $cpt) . ' ' . __('line(s) ignored.', 'octopush-sms') : ''));
                    }
                }
            } else if (isset($this->post['sendsms_transmit']) && $this->_campaign->status <= 1) {
                //transmit the campaign to Octopush
                // if it's the first time we call "transmit"
                if ($this->_campaign->status == 0) {
                    $this->_post_validation();
                    if (!sizeof(self::get_errors())) {
                        $this->_campaign->title = $this->post['sendsms_title'];
                        $this->_campaign->message = $this->post['sendsms_message'];

                        //check if there is a french number (beginning with +33)
                        //if this is the case, verify that the mention "STOP au XXXXX" is here
                        $count = $wpdb->get_var("
                            SELECT count(*)
                            FROM `" . $wpdb->prefix . "octopushsms_recipient` 
                            WHERE id_sendsms_campaign=" . $this->_campaign->id_sendsms_campaign . "
                            AND phone like '+33%'
                        "); //chunck to transmit to octopush
                        if ($count > 0 && strpos($this->_campaign->message, _STR_STOP_) == false) {
                            self::add_error(Octopush_Sms_Admin::get_instance()->get_error_SMS(_ERROR_STOP_MENTION_IS_MISSING_));
                        } else {
                            $this->_campaign->date_transmitted = current_time('mysql');
                            //$this->_campaign->date_send = $this->post['sendsms_date_send'];
                            $date = strtotime($this->post['sendsms_date'] . ' ' . (int) (isset($this->post['sendsms_date_hour']) ? $this->post['sendsms_date_hour'] : 0) . ':' . (isset($this->post['sendsms_date_minute']) ? $this->post['sendsms_date_minute'] : 0) . ':00');
                            $this->_campaign->date_send = current_time('mysql');
                            if(strtotime($this->_campaign->date_send) < strtotime($this->_campaign->date_transmitted)){
                                 $this->_campaign->date_send = current_time('mysql');
                            }
                            $this->_campaign->status = 1;
                        }
                        $this->_campaign->save();
                    }
                } else
                    self::add_message(__('Your campaign is currently being transmitted, please do not close the window.', 'octopush-sms'));
            } else if (isset($this->post['sendsms_validate']) && $this->_campaign->status == 2) {
                //validate the campaign to say to octopush that this campaign can be send
                $this->_post_validation();
                if (!sizeof(self::get_errors())) {
                    $this->_campaign->title = sanitize_text_field($this->post['sendsms_title']);
                    $date = strtotime($this->post['sendsms_date'] . ' ' . (int) (isset($this->post['sendsms_date_hour']) ? $this->post['sendsms_date_hour'] : 0) . ':' . (isset($this->post['sendsms_date_minute']) ? $this->post['sendsms_date_minute'] : 0) . ':00');
                    $this->_campaign->date_send = date_i18n('Y-m-d H:i:s', $date);
                    $this->_campaign->save();

                    //$date = new DateTime(strtotime($this->_campaign->date_send));
                    //$date->setTimezone(new DateTimeZone('Europe/Paris'));
                    $xml = Octopush_Sms_API::get_instance()->validate_campaign($this->_campaign->ticket, $this->_campaign->date_send); //strtotime($date->format('Y-m-d H:i:s')));
                    $xml = simplexml_load_string($xml);
                    if ($xml->error_code == '000') {
                        $this->_campaign->status = 3;
                        $this->_campaign->date_validation = current_time('mysql');
                        $this->_campaign->date_send = current_time('mysql');
                        if(strtotime($this->_campaign->date_send) < strtotime($this->_campaign->date_validation)){
                            $this->_campaign->date_send = current_time('mysql');
                        }
                        self::add_message(__('Your campaign is now validated and will be sent at', 'octopush-sms') . ' ' . $this->_campaign->date_send);
                        $this->_campaign->save();
                    } else {
                        self::add_error(Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code));
                    }
                } else {
                    self::add_error(Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code));
                }
            } else if (isset($this->post['sendsms_cancel']) && $this->_campaign->status >= 1 && $this->_campaign->status < 3 && !($this->_campaign->status == 3 && current_time('mysql') > $this->_campaign->date_send)) {
                //cancel a campaign if it is possible
                if ($this->_campaign->nb_recipients > 0) {
                    $xml = Octopush_Sms_API::get_instance()->cancel_campaign($this->_campaign->ticket);
                    $xml = simplexml_load_string($xml);
                    if ($xml->error_code == '000' || intval($xml->error_code) == _ERROR_BATCH_SMS_NOT_FOUND_) {
                        $this->_campaign->status = 4;
                        $this->_campaign->save();
                        self::add_message(__('Your campaign is now cancelled on octopush and can be deleted', 'octopush-sms', 'octopush-sms'));
                    } else {

                        self::add_error(Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code));
                    }
                } else {
                    $this->_campaign->status = 4;
                    $this->_campaign->save();
                    self::add_message(__('Your campaign is now cancelled and can be deleted', 'octopush-sms'));
                }
            } else if (isset($this->post['sendsms_duplicate']) && $this->_campaign->id_sendsms_campaign) {
                //duplicate a campaign
                $old_id = $this->_campaign->id_sendsms_campaign;
                $this->_campaign->id_sendsms_campaign = null;
                $this->_campaign->status = 0;
                $this->_campaign->nb_recipients = 0;
                $this->_campaign->nb_sms = 0;
                $this->_campaign->price = 0;
                $this->_campaign->ticket = (string) time();
                $this->_campaign->date_transmitted = NULL;
                $this->_campaign->date_validation = NULL;
                $this->_campaign->date_add = date('Y-m-d H:i:s');
                $this->_campaign->date_upd = date('Y-m-d H:i:s');
                $this->_campaign->save();
                if (WP_DEBUG) error_log ("Your campaign has been duplicated ".$this->_campaign->id_sendsms_campaign);

                //duplicate the recipients
                $wpdb->query('
                    INSERT INTO `' . $wpdb->prefix . 'octopushsms_recipient` (`id_sendsms_campaign`, `id_customer`, `firstname`, `lastname`, `phone`, `iso_country`, `transmitted`, `price`, `nb_sms`, `status`, `ticket`, `date_add`, `date_upd`)
                    SELECT ' . $this->_campaign->id_sendsms_campaign . ', `id_customer`, `firstname`, `lastname`, `phone`, `iso_country`, 0, 0, 0, 0, NULL, NOW(), NOW() FROM `' . $wpdb->prefix . 'octopushsms_recipient` WHERE `id_sendsms_campaign`=' . $old_id);
                $nb_recipients = $wpdb->get_var('SELECT count(*) AS total FROM `' . $wpdb->prefix . 'octopushsms_recipient` WHERE `id_sendsms_campaign`=' . $this->_campaign->id_sendsms_campaign);
                $this->_campaign->nb_recipients = $nb_recipients;
                $this->_campaign->save();
                
                self::add_message(__('Your campaign has been duplicated, you are now working on a new campaign.', 'octopush-sms'));
            } else if (isset($this->post['sendsms_delete']) && ($this->_campaign->status == 0 || $this->_campaign->status >= 3)) {
                //delete the campaign if it is possible
                $res = $this->_campaign->delete();
                if ($res == false) {
                    self::add_error(__('Your campaign can not be deleted.', 'octopush-sms'));
                } else {
                    $this->_campaign->id_sendsms_campaign = 0;
                    self::add_message(__('Your campaign has been deleted.', 'octopush-sms'));
                }
            }
        }

        private function _post_validation() {
            if (isset($this->post['sendsms_save']) || isset($this->post['sendsms_duplicate']) || isset($this->post['sendsms_transmit'])) {
                if (!$this->post['sendsms_title'])
                    self::add_error(__('Please enter a title', 'octopush-sms'));
                if (!$this->post['sendsms_message'])
                    self::add_error(__('Please enter a message', 'octopush-sms'));
                if (!$this->post['sendsms_date'])
                    self::add_error(__('Please enter a valid send date', 'octopush-sms'));
                else {
                    // Update date
                }
            }
        }

        public static function _ajax_process_transmitOWS() {
            global $wpdb;
            $post = $_REQUEST;

            if ($post['id_sendsms_campaign']) {
                if (WP_DEBUG)
                    error_log('Ajax transmitOWS ' . print_r($post, true));
                $campaign = new Octopush_Sms_Campaign($post['id_sendsms_campaign']);

                //send 200 by 200 recipients
                $result = $wpdb->get_results('
                    SELECT SQL_CALC_FOUND_ROWS *
                    FROM `' . $wpdb->prefix . 'octopushsms_recipient`
                    WHERE id_sendsms_campaign=' . $campaign->id_sendsms_campaign . '
                    AND transmitted = 0
                    ORDER BY id_sendsms_recipient ASC
                LIMIT 200'); //chunck to transmit to octopush
                $size = count($result);
                $total_rows = $wpdb->get_var('SELECT FOUND_ROWS()');
                if (WP_DEBUG)
                    error_log('Ajax transmitOWS ' . $size . " " . $total_rows);
                $finished = false;
                $campaign_can_be_send = false;
                if ((int) $size == (int) $total_rows)
                    $finished = true;

                $error = false;
                $message = false;
                //if there are other recipients to add
                if ($size != 0) {
                    //send recipient
                    $recipients = array();
                    foreach ($result as $recipient) {
                        $recipients[$recipient->phone] = $recipient;
                    }
                    // call OWS and get XML result                    
                    $xml = Octopush_Sms_API::get_instance()->send_trame($campaign->ticket, $recipients, $campaign->message, $finished);
                    $xml = simplexml_load_string($xml);
                    if (WP_DEBUG)
                        error_log('Ajax transmitOWS xml result' . print_r($xml, true));

                    if ($xml->error_code == '000') {
                        // success
                        foreach ($xml->successs->success as $success) {
                            $phone = (string) $success->recipient;
                            $recipients[$phone]->price = $success->cost;
                            //TODO $recipients[$phone]->nb_sms = $success->sms_needed;
                            $recipients[$phone]->status = 0;
                            $recipients[$phone]->ticket = (string) $xml->ticket;
                            $recipients[$phone]->transmitted = 1;
                        }

                        // errors
                        foreach ($xml->failures->failure as $failure) {
                            $phone = (string) $failure->recipient;
                            $recipients[$phone]->price = 0;
                            //TODO $recipients[$phone]->nb_sms = 0;
                            $recipients[$phone]->status = $failure->error_code;
                            $recipients[$phone]->ticket = (string) $xml->ticket;
                            $recipients[$phone]->transmitted = 1;
                        }

                        // convert recipient to Octopush_Sms_Recipient
                        foreach ($recipients as $key => $recipient) {
                            // update th recipient information
                            $obj = Octopush_Sms_Recipient::get_recipient($campaign->id_sendsms_campaign, $key);
                            foreach ($recipient as $field => $value)
                                $obj->$field = $value;
                            $obj->save();
                        }

                        // update the campaign totals
                        $campaign->date_transmitted = current_time('mysql');
                        $campaign->date_send = current_time('mysql');
                        $campaign->compute_campaign(1);  
                        $campaign->status_label = Octopush_Sms_Admin::get_instance()->get_status($campaign->status);                        
                    } else {
                        $error = Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code);
                    }
                }

                //if there is no more recipient to send, we check the status on octopush
                //if size=0, no more recipients to send, we only have to check the status
                //until octopush finish to do what he has to do
                if (!$error && $finished) {
                    $xml = Octopush_Sms_API::get_instance()->get_campaign_status($campaign->ticket);
                    $xml = simplexml_load_string($xml);
                    if ($xml->error_code == '000') {
                        $campaign->status = 2;
                        $campaign->status_label = Octopush_Sms_Admin::get_instance()->get_status($campaign->status);
                        $campaign->price = floatval($xml->cost);
                        $campaign->save();
                        $campaign_can_be_send = true;
                    } else {
                        $message = Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code);
                    }
                }
                wp_send_json(array('campaign' => $campaign, 'finished' => $campaign_can_be_send, 'total_rows' => $total_rows - $size, 'error' => $error, 'message' => $message));
            }
        }

        public static function _ajax_process_delRecipient() {
            if (WP_DEBUG)
                error_log("Request:" . print_r($_REQUEST, true));
            $post = $_REQUEST;

            $campaign = new Octopush_Sms_Campaign($post['id_sendsms_campaign']);
            if ($campaign->status == 0) {
                $recipient = new Octopush_Sms_Recipient($post['id_sendsms_recipient']);
                $recipient->delete();
                $campaign->compute_campaign();
            }
            wp_send_json(array('campaign' => $campaign, 'valid' => true));
        }

        /**
         * Filter customer
         */
        public static function _ajax_process_filter() {
            global $wpdb;
            /**
             * Get users
             */
            $admin_users = new WP_User_Query(
                    array(
                'role' => 'administrator',
                'fields' => 'ID'
                    )
            );

            $manager_users = new WP_User_Query(
                    array(
                'role' => 'shop_manager',
                'fields' => 'ID'
                    )
            );
            $per_page = 1000000;
            $current_page = 1;
            $query = new WP_User_Query(array(
                'exclude' => array_merge($admin_users->get_results(), $manager_users->get_results()),
                'number' => $per_page,
                'offset' => ( $current_page - 1 ) * $per_page
            ));

            $s = !empty($_REQUEST['q']) ? stripslashes($_REQUEST['q']) : '';

            $query->query_from .= " LEFT JOIN {$wpdb->usermeta} as meta2 ON ({$wpdb->users}.ID = meta2.user_id) ";
            $query->query_orderby = " ORDER BY meta2.meta_value, user_login ASC ";
            $query->query_fields .= " meta2.meta_key meta2.meta_value";
            if ($s) {
                $query->query_where .= " AND ( user_id LIKE '%" . esc_sql(str_replace('*', '', $s)) . "%' OR user_login LIKE '%" . esc_sql(str_replace('*', '', $s)) . "%' OR user_nicename LIKE '%" . esc_sql(str_replace('*', '', $s)) . "%' OR ( meta2.meta_key = 'billing_phone' and meta2.meta_value LIKE '%" . esc_sql(str_replace('*', '', $s)) . "%')  ";
                $query->query_where .= " OR ( meta2.meta_key = 'billing_first_name' and meta2.meta_value LIKE '%" . esc_sql(str_replace('*', '', $s)) . "%') ";
                $query->query_where .= " OR ( meta2.meta_key = 'billing_last_name' and meta2.meta_value LIKE '%" . esc_sql(str_replace('*', '', $s)) . "%') ";
                $query->query_where .= " )";
                $query->query_orderby = " GROUP BY ID " . $query->query_orderby;
            }

            $request = "select * " . " " .$query->query_from . " ".$query->query_where." " . $query->query_orderby;
            
            if (WP_DEBUG)
                error_log("Customer request:" . $request);//.",".print_r($query,true));
            
            $customers = $wpdb->get_results($request);
            $res = array();
            $nbr_value_return = 30;
            foreach ($customers as $customerSql) {
                    if ($nbr_value_return < count($res)) {
                        break;
                    }
                    $res[] = array(
                    'label' => get_user_meta($customerSql->ID,'billing_last_name')[0] . " " . get_user_meta($customerSql->ID,'billing_first_name')[0] . " " . get_user_meta($customerSql->ID,'billing_phone')[0],
                    'obj' => array('id_customer' => $customerSql->ID, 'phone' => get_user_meta($customerSql->ID,'billing_phone')[0], 'firstname' => get_user_meta($customerSql->ID,'billing_first_name')[0],
                         'lastname' => get_user_meta($customerSql->ID,'billing_last_name')[0], 'iso_country' => '', 'country' => '')
                );
            }                
            echo $_REQUEST['callback'] . json_encode($res) ;
            wp_die();
        }

        public static function _ajax_process_countRecipientsFromQuery() {
            $post = $_REQUEST;
            $campaign = new Octopush_Sms_Campaign($post['id_sendsms_campaign']);
            $result = $campaign->get_recipients_from_query(true);
            wp_send_json(array('total_rows' => (int) $result));
        }

        public static function _ajax_process_addRecipientsFromQuery() {
            global $wpdb;
            $post = $_REQUEST;
            //create campaign if the campaign does not exist
            if (!$post['id_sendsms_campaign']) {
                $campaign = new Octopush_Sms_Campaign();
                $campaign->ticket = (string) time();
                $campaign->title = $post['sendsms_title'] == '' ? 'CAMPAIGN-' . $campaign->ticket : $post['sendsms_title'];
                $campaign->message = $post['sendsms_message'];
                $campaign->date_send = $post['sendsms_date_send'];
                $campaign->save();
            } else
                $campaign = new Octopush_Sms_Campaign($post['id_sendsms_campaign']);

            //get the recipients from the query
            $result = $campaign->get_recipients_from_query(false);
            $cpt = 0;
            //we add each recipient to the campaign
            if (is_array($result)) {
                $phone_prefix = array();
                //preload phone prefix
                $phones_prefix = $wpdb->get_results("SELECT iso_code, prefix FROM `" . $wpdb->prefix . "octopushsms_phone_prefix`");
                foreach ($phones_prefix as $prefix) {
                    $phone_prefix[$prefix->iso_code] = $prefix->prefix;
                }
                foreach ($result as $row) {
                    $recipient = new Octopush_Sms_Recipient();
                    $recipient->id_sendsms_campaign = $campaign->id_sendsms_campaign;
                    $recipient->id_customer = (int) $row->ID;
                    $recipient->firstname = $row->billing_firstname;
                    $recipient->lastname = isset($row->billing_lastname) && $row->billing_lastname != '' ? $row->billing_lastname : $row->user_login;
                    $recipient->lastname = preg_replace('/[0-9]+/', '', $recipient->lastname);
                    $phone = Octopush_Sms_API::get_instance()->convert_phone_to_international($row->billing_phone, $row->billing_country);
                    if (is_null($phone))
                        continue;
                    $recipient->phone = $phone;
                    $recipient->iso_country = $row->billing_country;
                    $recipient->status = 0;
                    // can fail if that phone number already exist for that campaign
                    try {
                        if (!$recipient->save()) {
                            error_log("Error when try to add:".print_r($recipient,true));
                        } else {
                            $cpt++;
                        }
                    } catch (Exception $e) {
                        error_log("Error when try to add:".print_r($e,true));
                    }
                }
                $campaign->compute_campaign();
            }
            wp_send_json(array('campaign' => $campaign, 'total_rows' => (int) $cpt));
        }

        public static function findOrCreateCampaign($post) {
            //create campaign if the campaign does not exist
            if (!$post['id_sendsms_campaign']) {
                $campaign = new Octopush_Sms_Campaign();
                $campaign->ticket = (string) time();
                $campaign->title = $post['sendsms_title'] == '' ? 'CAMPAIGN-' . $campaign->ticket : $post['sendsms_title'];
                $campaign->message = $post['sendsms_message'];
                $campaign->date_send = $post['sendsms_date_send'];
                $campaign->save();
            } else
                $campaign = new Octopush_Sms_Campaign($post['id_sendsms_campaign']);
            return $campaign;
        }

        public static function addRecipients($result) {
            $cpt = 0;
            //we add each recipient to the campaign
            if (is_array($result)) {
                $phone_prefix = array();
                //preload phone prefix
                $phones_prefix = $wpdb->get_results("SELECT iso_code, prefix FROM `" . $wpdb->prefix . "octopushsms_phone_prefix`");
                foreach ($phones_prefix as $prefix) {
                    $phone_prefix[$prefix->iso_code] = $prefix->prefix;
                }
                foreach ($result as $row) {
                    $recipient = new Octopush_Sms_Recipient();
                    $recipient->id_sendsms_campaign = $campaign->id_sendsms_campaign;
                    $recipient->id_customer = (int) $row->ID;
                    $recipient->firstname = $row->billing_firstname;
                    $recipient->lastname = isset($row->billing_lastname) && $row->billing_lastname != '' ? $row->billing_lastname : $row->user_login;
                    $recipient->lastname = preg_replace('/[0-9]+/', '', $recipient->lastname);
                    $phone = Octopush_Sms_API::get_instance()->convert_phone_to_international($row->billing_phone, $row->billing_country);
                    if (is_null($phone))
                        continue;
                    $recipient->phone = $phone;
                    $recipient->iso_country = $row->billing_country;
                    $recipient->status = 0;
                    // can fail if that phone number already exist for that campaign
                    try {
                        if (!$recipient->save()) {
                            error_log("Error when try to add:".print_r($recipient,true));
                        } else {
                            $cpt++;
                        }
                    } catch (Exception $e) {
                        error_log("Error when try to add:".print_r($e,true));
                    }
                }
                $campaign->compute_campaign();
            }
            return $cpt;
        }

        /**
         * Filter user
         */
         public static function _ajax_process_filter_user() {
            global $wpdb;
            $post = $_REQUEST;

            $role = !empty($_REQUEST['q']) ? stripslashes($_REQUEST['q']) : '';
            $rolesafe = esc_sql(str_replace('*', '', $role));
            $id_sendsms_campaign=$post['id_sendsms_campaign'];
            $id_sendsms_campaign=esc_sql(str_replace('*', '', $id_sendsms_campaign));            
            
            $campaign = Octopush_Sms_Send_Tab::findOrCreateCampaign($post);
            $userIds = $campaign->get_recipients_from_role($rolesafe);
            
            //2 roles exceptions (all and none)
            // $userIds = $query_users->results;
            // foreach ($userIds as $id) {
            //     error_log("phoneee".get_user_meta($id,'billing_phone')[0]);                     
            // }
            wp_send_json(array('total_rows' => (int) count($userIds)));
            wp_die();
        }

        public static function _ajax_process_addRecipientsFromRole() {
            global $wpdb;
            $post = $_REQUEST;

            $role = !empty($_REQUEST['q']) ? stripslashes($_REQUEST['q']) : '';
            $rolesafe = esc_sql(str_replace('*', '', $role));
            $id_sendsms_campaign=$post['id_sendsms_campaign'];
            $id_sendsms_campaign=esc_sql(str_replace('*', '', $id_sendsms_campaign));            
            
            $campaign = Octopush_Sms_Send_Tab::findOrCreateCampaign($post);
            $userIds = $campaign->get_recipients_from_role($rolesafe);
            $result = $campaign->addRecipientsFromUserIds($userIds);
            wp_send_json(array('campaign' => $campaign, 'total_rows' => (int) $userIds-$result['added'],'errors' => $result['errors']));
        }        

        /**
         * Add recipient to a campaign (call via ajax)
         */
        public static function _ajax_process_addRecipient() {
            $post = $_REQUEST;

            $phone = $post['phone'];
            // if phone is not valid
            if (!WC_Validation::is_phone($phone))
                wp_send_json(array('error' => __('That phone number is invalid.', 'octopush-sms')));
            // if we know the country, try to convert the phone to international
            if ($post['iso_country']) {
                $phone = Octopush_Sms_API::get_instance()->convert_phone_to_international($phone, $post['iso_country']);
                if (is_null($phone))
                    wp_send_json(array('error' => __('The phone number and the country does not match.', 'octopush-sms')));
            }
            if (!$post['id_sendsms_campaign']) {
                $campaign = new Octopush_Sms_Campaign();
                $campaign->ticket = (string) time();
                $campaign->title = $post['sendsms_title'] == '' ? 'CAMPAIGN-' . $campaign->ticket : $post['sendsms_title'];
                $campaign->message = $post['sendsms_message'];
                $date = strtotime($post['sendsms_date'] . ' ' . (int) (isset($post['sendsms_date_hour']) ? $post['sendsms_date_hour'] : 0) . ':' . (isset($post['sendsms_date_minute']) ? $post['sendsms_date_minute'] : 0) . ':00');
                $campaign->date_send = date_i18n('Y-m-d H:i:s', $date);
                $campaign->save();
            } else
                $campaign = new Octopush_Sms_Campaign($post['id_sendsms_campaign']);
            if (WP_DEBUG)
                error_log("Campaign" . print_r($campaign, true));

            $recipient = new Octopush_Sms_Recipient();
            $recipient->id_sendsms_campaign = $campaign->id_sendsms_campaign;
            $recipient->id_customer = (int) $post['id_customer'];
            $recipient->firstname = isset($post['sendsms_firstname']) ? $post['sendsms_firstname'] : '';
            $recipient->lastname = isset($post['sendsms_lastname']) ? $post['sendsms_lastname'] : '';
            if ($recipient->firstname == '' && isset($post['firstname']))
                $recipient->firstname = $post['firstname'];
            if ($recipient->lastname == '' && isset($post['lastname']))
                $recipient->lastname = $post['lastname'];
            $recipient->phone = $phone;
            $recipient->iso_country = $post['iso_country'];
            $recipient->status = 0;
            // can fail if that phone number already exist for that campaign
            try {
                if (WP_DEBUG)
                    error_log("Save recipient: " . print_r($recipient, true));
                $res = $recipient->save();
                if (WP_DEBUG)
                    error_log("Saved recipient: " . print_r($res, true));
                if ($res) {
                    $campaign->compute_campaign();
                    wp_send_json(array('campaign' => $campaign, 'recipient' => $recipient));
                } else
                    wp_send_json(array('error' => __('That phone number is already in the list.', 'octopush-sms')));
            } catch (Exception $e) {
                wp_send_json(array('error' => __('That phone number is already in the list.', 'octopush-sms')));
            }
        }

    }

}