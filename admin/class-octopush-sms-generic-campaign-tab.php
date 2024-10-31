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

if (!class_exists('Octopush_Sms_Generic_Campaign_Tab')) {

    include_once 'models/class-octopush-sms-campaign-model.php';
    include_once 'models/class-octopush-sms-campaign.php';
    include_once 'models/class-octopush-sms-recipient.php';
    include_once 'class-octopush-sms-campaign-list.php';
    include_once 'class-octopush-sms-recipient-list.php';

    abstract class Octopush_Sms_Generic_Campaign_Tab {

        protected $_campaign;
        //protected $_errors;
        private static $errors = array();
        private static $messages = array();

        /**
         * Add a message
         * @param string $text
         */
        public static function add_message($text) {
            self::$messages[] = $text;
        }

        /**
         * Add an error
         * @param string $text
         */
        public static function add_error($text) {
            self::$errors[] = $text;
        }

        public static function get_errors() {
            return self::$errors;
        }

        /**
         * Output messages + errors
         * @return string
         */
        public static function show_messages() {
            if (sizeof(self::$errors) > 0) {
                foreach (self::$errors as $error) {
                    echo '<div id="message" class="error fade"><p><strong>' . esc_html($error) . '</strong></p></div>';
                }
            } elseif (sizeof(self::$messages) > 0) {
                foreach (self::$messages as $message) {
                    echo '<div id="message" class="updated fade"><p><strong>' . esc_html($message) . '</strong></p></div>';
                }
            }
        }

        public function get_body() {
            wp_enqueue_script('octopush_sms_settings', WC()->plugin_url() . '/assets/js/admin/settings.min.js', array('jquery', 'jquery-ui-datepicker', 'jquery-ui-autocomplete', 'jquery-ui-sortable', 'iris', 'chosen'), WC()->version, true);

            if (WP_DEBUG)
                error_log('Octopush_Sms_Generic_Campaign_Tab - get_body() - $_REQUEST' . print_r($_REQUEST, true));
            //if post data, process the data

            $id_sendsms_campaign = (isset($_REQUEST['id_sendsms_campaign']) ? sanitize_key($_REQUEST['id_sendsms_campaign']) : null);
            $this->_campaign = new Octopush_Sms_Campaign($id_sendsms_campaign);

            $this->_post_process();

            // Add any posted messages
            if (!empty($_GET['wc_error'])) {
                self::add_error(stripslashes($_GET['wc_error']));
            }

            if (!empty($_GET['wc_message'])) {
                self::add_message(stripslashes($_GET['wc_message']));
            }

            self::show_messages();

            $html = '
		<div id="' . get_class($this) . '">';
            if (($this->_campaign->id_sendsms_campaign || isset($_REQUEST['newCampaign']) || sizeof(self::$errors)) && in_array($this->_campaign->status, $this->_status)) {
                ob_start();
                $this->output();
                $html .= ob_get_clean();
                //$html .= $this->get_body_one_campaign();
            } else {
                $html .= $this->get_body_campaigns();
            }
            $html .= '</div>';

            return $html;
        }

        abstract protected function _post_process();

        abstract protected function _get_display_status();

        public static function get_form_url() {
            $uri = $_SERVER['REQUEST_URI'];
            $pos = strpos($_SERVER['REQUEST_URI'], '&action=');
            if ($pos !== false)
                $uri = substr($_SERVER['REQUEST_URI'], 0, $pos);
            return esc_url_raw($uri);
        }

        protected function get_body_campaigns() {
            $html ='';        
            if (get_class($this) == 'Octopush_Sms_Send_Tab') {
                $html .='<h2>' . __('Campaigns not yet sent', 'octopush-sms');
            } else { 
                $html .='<h2>' . __('Campaigns history', 'octopush-sms');
            }                    
            $html .= '<a class="add-new-h2" href="' . $this->get_form_url() . '&action=Octopush_Sms_Send_Tab&newCampaign=1">' . __('Create a new campaign / Send a new SMS', 'octopush-sms') . '</a></h2>';


            $myListTable = new Octopush_Sms_Campaign_List(array("status" => $this->_get_display_status()));
            $html.='<div class="wrap">';
            $myListTable->prepare_items();
            ob_start();
            echo '<form method="post">
                <input type="hidden" name="page" value="my_list_campaign" />';
            $myListTable->search_box(__('Search campaign', 'octopush-sms'), 'search_id');
            echo '</form>';
            $myListTable->display();
            $html .= ob_get_clean();
            $html.='</div>';

            return $html;
        }

        /**
         * Output the metabox
         */
        public function output() {
            //self::init_address_fields();
            //get the balance
            $b_auth = get_option('octopush_sms_email') ? true : false;
            ?>

            <style type="text/css">
                #post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
            </style>

            <div class="postbox-container">
                <div id="sendsms_message" class="postbox doc">
                    <a id="back" href="<?php echo $this->get_form_url() . '&action=' . get_class($this); ?>">
                        <span class="previous"></span><?php _e('Back to the list', 'octopush-sms') ?></a>

                    <?php if (get_class($this) == 'Octopush_Sms_Send_Tab') { ?>
                        <p><?php
                            echo __('As soon as you choose recipient(s), your campaign will be automatically saved and you will find it in "Send SMS" tab for further modification if necessary.', 'octopush-sms') . '<br/>' .
                            __('Sending process :', 'octopush-sms') . '<br/>' .
                            __('1. Create your campaign, click on \'Save\' if you want to be able to modify it later.', 'octopush-sms') . '<br/>' .
                            __('2. When your campaign is ready, click "Transmit to Octopush" to send your request to the SMS plateform, SMS will not be sent immediatly ! Status becomes \"Transfert in progess\" and the campaign can\'t be modified anymore.', 'octopush-sms') . '<br/>' .
                            __('3. During the transfer, number of recipients, number of SMS and price are updated in the "Information" part.', 'octopush-sms') . '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
                            __('- If you stop the transfer by leaving the page (campaign with several recipients), you can complete it later by clicking "Transmit to Octopush" again.', 'octopush-sms') . '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
                            __('- When everything has been sent, you can validate the SMS by clicking "Accept & Send" or you can definitively cancel it.', 'octopush-sms') . '<br/>' .
                            __('4. If there\'s no error (not enough credit for example), your campaign will be sent. Else, please fix the problem and try again.', 'octopush-sms') . '<br/>' .
                            __('5. You can retrieve all campaigns whose are not in the status \"In Construction", "Transfer in progress" or "Waiting for validation" in the "SMS history" tab.', 'octopush-sms') . '<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' .
                            __('- You can still cancel your campaign unless your campaign has been sent by Octopush.', 'octopush-sms');
                            ?>
                        </p>
                    <?php } else { ?>
                        <br><br>
                    <?php } ?>
                </div>
            </div>

            <form id="sendsms_form" action="<?php echo $this->get_form_url(); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" id="action" name="action" value="<?php echo get_class($this) ?>"/>
                <input type="hidden" id="id_sendsms_campaign" name="id_sendsms_campaign" value="<?php echo $this->_campaign->id_sendsms_campaign; ?>"/>
                <input type="hidden" id="current_status" value="<?php echo $this->_campaign->status ?>"/>

                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2"><!-- body -->

                        <div id="postbox-container-2" class="postbox-container"><!-- left column-->
                            <div class="postbox">
                                <div class="panel-wrap woocommerce ">
                                    <div id="sendsms_data" class="panel">
                                   <!--<h2><?php printf(__('Campaign %s details', 'woocommerce'), esc_html($this->_campaign->id_sendsms_campaign)); ?></h2>-->

                                        <div class="sendsms_data_column_container">
                                            <div class="sendsms_data_column">
                                                <h2><span class="dashicons dashicons-admin-settings vmiddle"></span><span><?php ($this->_campaign->status == 0 ? _e('SMS settings', 'octopush-sms') : _e('SMS details', 'octopush-sms')) ?></span></h2>

                                                <?php
                                                if (!$b_auth) {
                                                    echo '<span class="failed> ' . __('Before sending a message, you have to enter your account information in the Settings Tab.', 'octopush-sms') . '</span><br/><br/>';
                                                } else {
                                                    echo '';
                                                }
                                                ?>

                                                <!-- campaign title -->
                                                <p class="form-field form-field-wide">
                                                    <label for="sendsms_title"><?php _e('Title of the campaign', 'octopush-sms') ?></label><br/>
                                                    <input type="text" id="sendsms_title" name="sendsms_title" maxlength="255" value="<?php echo htmlentities($this->_campaign->title, ENT_QUOTES, 'utf-8'); ?>" />
                                                </p>

                                                <!-- campaign message-->
                                                <p class="form-field form-field-wide">
                                                    <label for="sendsms_title"><?php _e('Message') ?></label><br/>
                                                    <textarea 
                                                    <?php echo ($this->_campaign->status == 0 ? '' : 'readonly') ?>
                                                        rows="5" cols="50" name="sendsms_message"><?php echo htmlentities($this->_campaign->message, ENT_QUOTES, 'utf-8'); ?></textarea>                                
                                                    <br/><?php _e('Variables you can use : {firstname}, {lastname}', 'octopush-sms'); ?>
                                                </p>

                                                <!-- campaign date -->
                                                <p class="form-field form-field-wide"><label for="sendsms_date"><?php _e('Send date', 'octopush-sms') ?></label><br/>
                                                    <input type="text" <?php echo ($this->_campaign->status < 2 ? '' : 'readonly') ?> class="date-picker-field datepicker" name="sendsms_date" id="sendsms_date" maxlength="10" value="<?php echo $this->_campaign->date_send != "0000-00-00 00:00:00" && $this->_campaign->date_send !="" ? date_i18n('Y-m-d', strtotime($this->_campaign->date_send)) : '' ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                                                    @<input type="text" <?php echo ($this->_campaign->status < 2 ? '' : 'readonly') ?> class="hour" style="width:2.5em" placeholder="<?php _e('h', 'octopush-sms') ?>" name="sendsms_date_hour" id="sendsms_date_hour" maxlength="2" size="2" value="<?php echo $this->_campaign->date_send != "0000-00-00 00:00:00" && $this->_campaign->date_send !="" ? date_i18n('H', strtotime($this->_campaign->date_send)) : '' ?>" pattern="\-?\d+(\.\d{0,})?" />
                                                    :<input type="text" <?php echo ($this->_campaign->status < 2 ? '' : 'readonly') ?> class="minute" style="width:2.5em" placeholder="<?php _e('m', 'octopush-sms') ?>" name="sendsms_date_minute" id="sendsms_date_minute" maxlength="2" size="2" value="<?php echo $this->_campaign->date_send != "0000-00-00 00:00:00" && $this->_campaign->date_send !="" ? date_i18n('i', strtotime($this->_campaign->date_send)) : '' ?>" pattern="\-?\d+(\.\d{0,})?" />
                                                    <br><?php echo __('Time Zone:', 'octpush-sms') . ' ' . date_default_timezone_get(); ?>
                                                </p>

                                            </div>

                                            <!-- <div class="sendsms_data_column"> -->
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="postbox-container-1" class="postbox-container"><!-- right column-->
                            <div class="postbox">
                                <?php $this->output_campaign_details(); ?>
                            </div>
                        </div>
                    </div>
                    <br class="clear">
                </div>
                <?php
                $this->output_choose_recipient();
                $this->output_button();
                echo "<br/>";
                ?>
            </form>
            <div id="sendsms_recipient" class="postbox-container postbox">                
                <?php
                $myListTable = new Octopush_Sms_Recipient_List(array('campaign' => $this->_campaign));
                echo '<h2><span class="dashicons dashicons-admin-users vmiddle"></span>' . __('List of recipients', 'octopush-sms') . '</h2>';
                ?>
                <?php //$myListTable->search_box('search', 'search_id'); ?>

                <?php
                $myListTable->prepare_items();
                $myListTable->display();
                ?>                
            </div>
            <?php
            //construct specific js script
            $jsScript = '';

            if ($this->_campaign->status == 0) {
                $jsScript .= 'var timeText = "' . __('Time', 'octopush-sms') . '";
                    var hourText = "' . __('Hour', 'octopush-sms') . '";
                    var minuteText = "' . __('Minute', 'octopush-sms') . '";
                    var secondText = "' . __('Second', 'octopush-sms') . '";
                    var currentText = "' . __('Now', 'octopush-sms') . '";
                    var closeText = "' . __('Closed', 'octopush-sms') . '";';
            }
            $jsScript .= '
                        var sendsms_error_phone_invalid = "' . __('That phone number is invalid.', 'octopush-sms') . '";
                        var sendsms_error_csv = "' . __('Please choose a valid CSV file', 'octopush-sms') . '";
                        var sendsms_error_orders = "' . __('That number must be greater or equal to 1', 'octopush-sms') . '";
                        var sendsms_confirm_cancel = "' . __('Are you sure you want to cancel that campaign ?', 'octopush-sms') . '";
                        var sendsms_confirm_delete = "' . __('Are you sure you want to delete that campaign ?', 'octopush-sms') . '";';
            if (get_class($this) == 'Octopush_Sms_Send_Tab' && isset($_REQUEST['sendsms_transmit']) && $this->_campaign->status == 1 && !sizeof(self::$errors)) {
                $jsScript .= 'transmitToOWS();';
            }

            $jsScript .= 'jQuery(document).ready(function() {
                jQuery(".datepicker").datepicker({
                dateFormat : "yy-mm-dd"
            });
            initTab();
            }); ';

            echo '<script>' . $jsScript . '</script>';

            // Ajax Chosen Customer Selectors JS
            /* wc_enqueue_js("


              " . $jsScript); */
        }

        public function output_campaign_details() {
            $balance = Octopush_Sms_API::get_instance()->get_balance();
            $balance = $balance !== '001' ? $balance : 0;

            echo '
        <style>
            .tooltip {
            position: relative;
            display: inline-block;
            }

            .tooltip .tooltiptext {
            visibility: hidden;
            width: 315px;
            background-color: black;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 15px 0;
            position: absolute;
            z-index: 1;
            bottom: 150%;
            margin-left: -148px;
            line-height: 20px;
            }

            .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: black transparent transparent transparent;
            }

            .tooltip:hover .tooltiptext {
            visibility: visible;
            }

            .question-mark-icon:after {
                content: url("https://api.iconify.design/dashicons:editor-help.svg?height=20");
            vertical-align: -0.190em;
            }
            #block_infos label{line-height:20px;}

</style>
        <fieldset id="block_infos">
        <h2><span class="dashicons dashicons-info vmiddle"></span>' . __('Information') . '</h2>
        <label><b>' . __('Current Balance', 'octopush-sms') . '</b></label>
        <div id="balance">' . number_format((float) $balance, 0, '', ' ') . ' SMS</div>
        <div class="clear"></div>
        <label>' . __('Campaign ID', 'octopush-sms') . '</label>
        <div id="id_campaign">' . $this->_campaign->id_sendsms_campaign . '</div>
        <div class="clear"></div>
        <label>' . __('Ticket', 'octopush-sms') . '</label>
        <div id="ticket">' . $this->_campaign->ticket . '</div>
        <div class="clear"></div>
        <label>' . __('Status', 'octopush-sms') . '</label>
        <div id="status">' . Octopush_Sms_Admin::get_instance()->get_status($this->_campaign->status) . '</div>
        <div class="clear"></div>' .
            ($this->_campaign->status == 5 ? '
        <label>' . __('Error', 'octopush-sms') . '</label>
        <div id="error_code">' . Octopush_Sms_Admin::get_instance()->get_error_SMS($this->_campaign->error_code) . '</div>
        <div class="clear"></div>' : '') .
            (get_class($this) == 'Octopush_Sms_History_Tab' && $this->_campaign->simulation ? '
        <label>' . __('Simulation', 'octopush-sms') . '</label>
        <div id="simulation">' . __('Yes', 'octopush-sms') . '</div>' : '') .
            (get_class($this) == 'Octopush_Sms_History_Tab' && $this->_campaign->paid_by_customer ? '
        <label>' . __('Paid by customer', 'octopush-sms') . '</label>
        <div id="paid_by_customer">' . __('Yes', 'octopush-sms') . '</div>' : '') . '
        <div class="clear"></div>
        <label>' . __('Recipients', 'octopush-sms') . '</label>
        <div id="nb_recipients">' . $this->_campaign->nb_recipients . '</div>
        <div class="clear"></div>
        <!--<label>' . __('Nb of SMS', 'octopush-sms') . '</label>
        <div id="nb_sms">' . $this->_campaign->nb_sms . '</div>
        <div class="clear"></div>-->
        <label>' . __('Price', 'octopush-sms') . '</label>
        <div id="price">' . number_format($this->_campaign->price, 3, '.', '') . ' €</div>
        <div class="clear"></div>       
        
        <label class="tooltip">' . __('Send date', 'octopush-sms').'
         <span class="tooltiptext">This indicates when your campaign will be delivered</span><span class="question-mark-icon"></span>
        </label>
        <div>' . ($this->_campaign->date_send != "0000-00-00 00:00:00" && $this->_campaign->date_send !="" ? date_i18n('d-m-Y H:i:s', strtotime($this->_campaign->date_send)) : '') . '</div>
        <div class="clear"></div>       
        <label class="tooltip">' . __('Transmission date', 'octopush-sms').'
         <span class="tooltiptext">The date when your request was sent to Octopush from the module module</span><span class="question-mark-icon"></span>
        </label>     
        <div>' .($this->_campaign->date_transmitted != "0000-00-00 00:00:00" && $this->_campaign->date_transmitted !="" ? date_i18n('d-m-Y H:i:s', strtotime($this->_campaign->date_transmitted)) : '') . '</div>
        <div class="clear"></div>     
        <label class="tooltip">' . __('Validation date', 'octopush-sms').'
         <span class="tooltiptext">This is the date you clicked on "Accept & Send"</span><span class="question-mark-icon"></span>
        </label> 
        <div>' . ($this->_campaign->date_validation != "0000-00-00 00:00:00" && $this->_campaign->date_validation !="" ? date_i18n('d-m-Y H:i:s', strtotime($this->_campaign->date_validation)) : '') . '</div>
    </fieldset>';
        }

        function output_choose_recipient() {
            if (get_class($this) == 'Octopush_Sms_Send_Tab' && $this->_campaign->status == 0) {
                ?>
                <div class="poststuff">
                    <div id="sendsms_choose_recipient" class="postbox">
                        <h2><span class="dashicons dashicons-search vmiddle"></span><?php _e('Choose recipients', 'octopush-sms') ?></h2>
                        <div><h3><span class="dashicons dashicons-info vmiddle"></span><?php _e('4 methods to choose your recipients', 'octopush-sms') ?></h3></div>


                        <table class="form-table">

                            <tbody>
                                <tr valign="top">
                                    <th scope="row" class="titledesc">
                                        <label for="sendsms_recipient"><span class="dashicons dashicons-arrow-right"></span><?php _e('Enter your recipient details', 'octopush-sms') ?></label>
                                    </th>
                                    <td class="forminp" style="width:80%">
                                        <input placeholder="<?php _e('Phone (international e.g: +41782345679)', 'octopush-sms') ?>" type="text" size="30" maxlength="16" name="sendsms_phone" id="sendsms_phone" />
                                        <input placeholder="<?php _e('First Name (optional)', 'octopush-sms') ?>" type="text" size="30" maxlength="32" name="sendsms_firstname" id="sendsms_firstname" /></div>
                                        <input placeholder="<?php _e('Last Name (optional)', 'octopush-sms') ?>" type="text" size="30" maxlength="32" name="sendsms_lastname" id="sendsms_lastname" /></div>
                                        <span class="plus" id="add_recipient"></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row" class="titledesc">
                                        <label for="sendsms_customer"><span class="dashicons dashicons-arrow-right"></span><?php _e('Or search among your customers', 'octopush-sms') ?></label>
                                    </th>
                                    <td class="forminp forminp-select">
                                        <input type="text" size="30" id="sendsms_customer_filter" value="" /> <?php _e('Search will be applied on phone, id_customer, firstname, lastname', 'octopush-sms') ?>
                                    <div id="resultFilter">
                                    </div>
                                    </td>
                                </tr>
                                <tr valign="top" class="">
                                    <th scope="row" class="titledesc"><span class="dashicons dashicons-arrow-right"></span><?php _e('Or upload a CSV file', 'octopush-sms') ?></th>
                                    <td class="forminp forminp-checkbox" >
                                        <input id="sendsms_csv" type="file" name="sendsms_csv" />
                                        <span class="plus" id="add_csv"></span>
                                        <br/>
                                        <a href="<?php echo plugin_dir_url(__FILE__) ?>assets/example.csv"><?php echo _e('See example', 'octopush-sms') ?></a>
                                    </td>
                                </tr>
                                <!-- #2 add selection by role -->
                                <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="sendsms_user"><span class="dashicons dashicons-arrow-right"></span><?php _e('Or add by role', 'octopush-sms') ?></label>
                                </th>
                                    <td class="forminp forminp-text" id="sendsms_query_user">
                                        <div>
                                            <div style="display:block;float:left">
                                                <!-- choose a country -->
                                                <?php
                                                function get_roles() {
                                                    
                                                        $wp_roles = new WP_Roles();
                                                        $roles = $wp_roles->get_names();
                                                        $roles = array_map( 'translate_user_role', $roles );
                                                    
                                                        return $roles;
                                                    }
                                                ?>
                                                <select name="sendsms_query_user_role" id="sendsms_query_user_role" class="country_to_state country_select" >
                                                    <?php
                                                    $user_roles = get_roles();
                                                    // print the full list of roles with the primary one selected.
                                                    wp_dropdown_roles($user_roles);
                                                    ?>
                                                </select>
                                                <br />
                                                <span><?php _e('Only users with phone number are selected', 'octopush-sms') ?></span>                                                                                              
                                            </div>
                                            <div style="display:block;float:left">
                                                <span id="sendsms_query_user_result"></span> <?php _e('user(s) found', 'octopush-sms') ?> <span id="sendsms_query_user_add" class="plus" title="<?php _e('Add', 'octopush-sms') ?>"></span> 
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <!-- #2 fin -->
                                <tr valign="top" >
                                    <th scope="row" class="titledesc">
                                        <label for="woocommerce_demo_store_notice"><span class="dashicons dashicons-arrow-right"></span><?php _e('Or create your own query', 'octopush-sms') ?></label>
                                    </th>
                                    <td class="forminp forminp-text" id="sendsms_query">
                                        <div>
                                            <div style="display:block;float:left">
                                                <!-- choose a country -->
                                                <select name="sendsms_query_country" id="sendsms_query_country" class="country_to_state country_select" >
                                                    <option value=""><?php _e('-- All countries --', 'octopush-sms') ?></option>
                                                    <?php
                                                    $wccountries = new WC_Countries();
                                                    $field = '';
                                                    foreach ($wccountries->get_countries() as $ckey => $cvalue)
                                                        $field .= '<option value="' . esc_attr($ckey) . '" >' . __($cvalue, 'woocommerce') . '</option>';
                                                    echo $field;
                                                    ?>
                                                </select>

                                                <br />
                                                <span class="filter_label"><?php _e('Registered', 'octopush-sms') ?></span> <?php _e('From', 'octopush-sms') ?> <input type="text" class="datepicker" name="sendsms_query_registered_from" size="10" maxlength="10" />
                                                <?php _e('To', 'octopush-sms') ?> <input type="text" class="datepicker" name="sendsms_query_registered_to" size="10" maxlength="10" />
                                                <span class="filter_label"><?php _e('Ignore years', 'octopush-sms') ?></span> <input type="checkbox" name="sendsms_query_registered_years" value="1" /><br>
                                                <span class="filter_label"><?php _e('Connected', 'octopush-sms') ?></span> <?php _e('From', 'octopush-sms') ?> <input type="text" class="datepicker" name="sendsms_query_connected_from" size="10" maxlength="10" />
                                                <?php _e('To', 'octopush-sms') ?> <input type="text" class="datepicker" name="sendsms_query_connected_to" size="10" maxlength="10" />
                                                <span class="filter_label"><?php _e('Ignore years', 'octopush-sms') ?></span> <input type="checkbox" name="sendsms_query_connected_years" value="1" /><br>
                                                <span class="filter_label"><?php _e('Number of orders', 'octopush-sms') ?></span> <?php _e('From', 'octopush-sms') ?> <input type="text" id="sendsms_query_orders_from" name="sendsms_query_orders_from" size="10" maxlength="10" />
                                                <?php _e('To', 'octopush-sms') ?> <input type="text" id="sendsms_query_orders_to" name="sendsms_query_orders_to" size="10" maxlength="10" />
                                                <span class="filter_label"><?php _e('Or no order', 'octopush-sms') ?></span> <input type="checkbox" id="sendsms_query_orders_none" name="sendsms_query_orders_none" value="1" />
                                            </div>
                                            <div style="display:block;float:left">
                                                <span id="sendsms_query_result"></span> <?php _e('customer(s) found', 'octopush-sms') ?> <span id="sendsms_query_add" class="plus" title="<?php _e('Add', 'octopush-sms') ?>"></span> 
                                            </div>
                                        </div>
                                    </td>
                                </tr>							                                    
                            </tbody>
                        </table>
                        <?php if ($this->_campaign->status == 0) { ?>
                            <div style="line-height:30px"><span class="dashicons dashicons-info"></span> <?php _e('All duplicates will be automatically removed', 'octopush-sms') ?></div>
                        <?php } ?>

                    </div>
                </div>
                <?php
            }
        }

        public function output_button() {
            $b_auth = get_option('octopush_sms_email') ? true : false;
            ?>
            <div id="sendsms_buttons">
                <div id="buttons" class="clear center" style="display: <?php (isset($_REQUEST['sendsms_transmit']) && $this->_campaign->status == 1 && !sizeof(self::$errors) ? 'none' : 'block') ?>">
                    <?php if (get_class($this) == 'Octopush_Sms_Send_Tab') { ?>
                        <input type="submit" id="sendsms_save" name="sendsms_save" value="<?php _e('Save the campaign', 'octopush-sms') ?>" class="button button-primary" />
                        <input <?php (!$b_auth ? 'disabled="disabled"' : '') ?> type="submit" id="sendsms_transmit" name="sendsms_transmit" value="<?php _e('Transmit to Octopush', 'octopush-sms') ?>" class="button button-primary" />
                        <?php if ($this->_campaign->status < 3) { ?>
                            <input <?php (!$b_auth ? 'disabled="disabled"' : '') ?> type="submit" id="sendsms_validate" name="sendsms_validate" value="<?php _e('Accept & Send', 'octopush-sms') ?>" class="button button-primary" /> 
                        <?php } ?>
                        <?php
                    }
                    if ($this->_campaign->status >= 1 || $this->_campaign->status < 3 || ($this->_campaign->status == 3 && the_date('Y-m-d H:i:s') < $this->_campaign->date_send)) {
                        ?>
                        <input <?php (!$b_auth ? 'disabled' : '') ?> type="submit" id="sendsms_cancel" name="sendsms_cancel" value="<?php _e('Cancel this campaign', 'octopush-sms') ?>" class="button button-primary" />
                    <?php } ?>
                    <input type="submit" id="sendsms_delete" name="sendsms_delete" value="<?php _e('Delete the campaign', 'octopush-sms') ?>" class="button button-primary" /> 
                    <?php if ($this->_campaign->event == 'sendsmsFree') { ?>
                        <input type="submit" id="sendsms_duplicate" name="sendsms_duplicate" value="<?php _e('Duplicate this campaign', 'octopush-sms') ?>" class="button button-primary" />
                    <?php } ?>
                </div>
			</div>
            <?php
            if (get_class($this) == 'Octopush_Sms_Send_Tab' && isset($_POST['sendsms_transmit']) && $this->_campaign->status == 1 && !sizeof(self::$errors)) {
                echo '<div id="progress_bar" class="error fade">' . __('Transfer in progress :', 'octopush-sms') . ' <span id="waiting_transfert">' . $this->_campaign->nb_recipients . '</span> ' . __('remaining recipients', 'octopush-sms') . '</div>';
            }
        }

    }

}
