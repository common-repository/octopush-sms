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

if (!class_exists('Octopush_Sms_History_Tab')) {

    include_once(plugin_dir_path(__FILE__) . 'class-octopush-sms-generic-campaign-tab.php');
    /* include_once 'models/class-octopush-sms-campaign-model.php';
      include_once 'models/class-octopush-sms-campaign.php';
      include_once 'models/class-octopush-sms-recipient.php'; */

    class Octopush_Sms_History_Tab extends Octopush_Sms_Generic_Campaign_Tab {

        protected $_status = array(3, 4, 5);

        protected function _get_display_status() {
            return $this->_status;
        }

        protected $post;

        protected function _post_process() {
            global $wpdb;
            if (!isset($_REQUEST)) {
                return;
            }
            if (WP_DEBUG) {
                error_log("_post_process ".print_r($this->_campaign,true));
            }
            $this->post = sanitize_post($_REQUEST, 'edit');
            if (isset($this->post['sendsms_cancel'])) {// && $this->_campaign->status < 3 && the_date('Y-m-d H:i:s') < $this->_campaign->date_send) {
                $xml = Octopush_Sms_API::get_instance()->cancel_campaign($this->_campaign->ticket);
                $xml = simplexml_load_string($xml);
                if (WP_DEBUG) {
                    error_log("Cancel campaign result: ".$xml);
                }
                if ($xml->error_code == '000' || intval($xml->error_code) == _ERROR_BATCH_SMS_NOT_FOUND_) {
                    //if Campaign not found, ok no problem
                    $this->_campaign->status = 4;
                    $this->_campaign->save();
                    self::add_message(__('Your campaign is now cancelled on octopush and can be deleted', 'octopush-sms'));
                } else {
                    self::add_error(Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code));
                }
            //} else if (isset($this->post['sendsms_cancel'])) {
              //  self::add_error(__('This campaign is already send. You cannot cancel it.', 'octopush-sms'));
            } else if (isset($this->post['sendsms_delete']) && $this->_campaign->status >= 3) {
                $this->_campaign->delete();
                $this->_campaign=new Octopush_Sms_Campaign();
                self::add_message(__('Your campaign has been deleted.', 'octopush-sms'));
            } else if (isset($this->post['sendsms_duplicate']) && $this->_campaign->id_sendsms_campaign) {
                if (WP_DEBUG) error_log ("Your campaign will be duplicated ".$this->_campaign->id_sendsms_campaign);
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
                self::add_message(__('Your campaign has been duplicated, you can access to your new campaign in tab "Campaigns".', 'octopush-sms'));
            }
        }

    }

}