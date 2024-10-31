<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('Octopush_Sms_Campaign_Model')) {

    class Octopush_Sms_Campaign_Model {

        var $id_sendsms_campaign;
        var $ticket;
        var $title;
        var $status;
        var $error_code;
        var $message;
        var $nb_recipients;
        var $nb_sms;
        var $price;
        var $event;
        var $paid_by_customer;
        var $simulation;
        var $date_send;
        var $date_transmitted;
        var $date_validation;
        var $date_add;
        var $date_upd;

        public function save() {
            global $wpdb;
            $this->message = stripslashes($this->message);
            $data = array(
                'ticket' => $this->ticket,
                'title' => $this->title,
                'status' => $this->status,
                'error_code' => $this->error_code,
                'message' => stripslashes($this->message),
                'nb_recipients' => $this->nb_recipients,
                'nb_sms' => $this->nb_sms,
                'price' => $this->price,
                'event' => $this->event,
                'paid_by_customer' => $this->paid_by_customer,
                'simulation' => $this->simulation,
                'date_send' => $this->date_send,
                'date_transmitted' => $this->date_transmitted,
                'date_validation' => $this->date_validation,
                'date_add' => $this->date_add,
                'date_upd' => $this->date_upd,
            );

            if (isset($this->id_sendsms_campaign) && is_numeric($this->id_sendsms_campaign)) {
                //campaign exist we update
                $where = array("id_sendsms_campaign" => $this->id_sendsms_campaign);
                $data['date_upd']=  current_time('mysql');
                return $wpdb->update($this->_get_table(), $data, $where);
            } else {
                //campaign does not exist we create
                $res = $wpdb->insert($this->_get_table(), $data);
                if (WP_DEBUG) {
                    error_log("campaign insert " . $this->_get_table() . print_r($data, true));
                }
                $this->id_sendsms_campaign = $wpdb->insert_id;
                return $res;
            }
        }

        // get list of campaigns with the given status
        static public function get_campaigns($status, $s = null, $orderby = null, $order = null, $page_nbr = null, $per_page = null) {
            global $wpdb;

            $_orderby = '`date_upd`';
            $_order = 'desc';

            if (isset($orderby)) {
                $_orderby = sanitize_text_field($orderby);
            }
            if (isset($order)) {
                $_order = sanitize_text_field($order);
            }

            //pagination LIMIT 10 OFFSET $offset
            $pagination = '';
            if (isset($page_nbr) && isset($per_page)) {
                $pagination = ' LIMIT ' . $per_page . ' OFFSET ' . (($page_nbr - 1) * $per_page);
            }

            $sql = '
			SELECT c.*
			FROM `' . $wpdb->prefix . 'octopushsms_campaign` AS c
			WHERE `status` IN (' . implode(',', $status) . ') ' .
                    self::get_filter($s, $status) . ' 
			ORDER BY ' . $_orderby . ' ' . $_order . $pagination;
            $campaigns = $wpdb->get_results($sql);
            return $campaigns;
        }

        static public function get_filter($s, $status) {
            $_filter = '';
            if (isset($s) && !empty($s)) {
                $_filter = " and ( ticket like '%$s%' ";
                $_filter.=" or title like '%$s%' ";
                foreach ($status as $statut) {
                    if (strpos(Octopush_Sms_Admin::get_instance()->get_status($statut), $s) != false) {
                        $_filter.=" or status = $statut ";
                    }
                }
                if (intval($s)) {
                    $_filter.=" or nb_recipients = " . intval($s);
                    $_filter.=" or nb_sms = " . intval($s);
                    $_filter.=" or price = " . intval($s);
                } else if (strpos($s, ">") == 0 && intval(str_replace(">", "", $s)) > 0) {
                    $_filter.=" or nb_recipients > " . intval(str_replace(">", "", $s));
                    $_filter.=" or nb_sms > " . intval(str_replace(">", "", $s));
                    $_filter.=" or price > " . intval(str_replace(">", "", $s));
                } else if (strpos($s, "<") == 0 && intval(str_replace("<", "", $s)) > 0) {
                    $_filter.=" or nb_recipients < " . intval(str_replace(">", "", $s));
                    $_filter.=" or nb_sms < " . intval(str_replace(">", "", $s));
                    $_filter.=" or price < " . intval(str_replace(">", "", $s));
                }
                $_filter.=" or date_send like '%$s%' )";
            }
            return $_filter;
        }

        // get list of campaigns with the given status
        static public function count_campaigns($status, $s = null) {
            global $wpdb;
            $count_campaigns = $wpdb->get_var('
			SELECT count(*)
			FROM `' . $wpdb->prefix . 'octopushsms_campaign` AS c
			WHERE `status` IN (' . implode(',', $status) . ') ' . self::get_filter($s, $status));
            return $count_campaigns;
        }

        // get recipients of the campaign
        public function get_recipients($page_nbr = 1, $per_page = 1000, $orderby = null, $order = null, $s = null) {
            //TODO filter
            global $wpdb, $woocommerce;
            if (!is_array($orderby))
                $sort = '`date_add` DESC';
            else {
                $sort = '';
                foreach ($orderby as $key => $value) {
                    $sort .= (empty($sort) ? '' : ', ') . ($key + 2) . ($value ? ' DESC' : ' ASC');
                }
            }

            //filter request

            $condition = ' ';
            $cols = array('id_customer', 'firstname', 'lastname', 'phone', 'price', 'transmitted', 'status');
            if (isset($s) && !empty($s)) {
                $condition = ' AND ( 1=0 ';
                foreach ($cols as $value) {
                    $condition .= ' OR ' . $value . ' LIKE \'%' . addslashes($s) . '%\'';
                }
                $condition.=") ";
            }

            //TODO country
            //WC()->countries->countries[ $order->shipping_country ];
            $qry = '
			SELECT SQL_CALC_FOUND_ROWS id_sendsms_recipient, id_customer, firstname, lastname, iso_country,phone' . ($this->status > 0 ? ', price , transmitted, status' : '') . '
			FROM `' . Octopush_Sms_Recipient::_get_table() . '` 
			WHERE `id_sendsms_campaign`=' . (int) $this->id_sendsms_campaign . $condition . '
			ORDER BY ' . $sort .
                    ($per_page > 0 ? ' LIMIT ' . (($page_nbr - 1) * $per_page) . ', ' . (int) $per_page : '');
            if (WP_DEBUG) {
                error_log($qry);
            }
            $recipients = $wpdb->get_results($qry);
            $total_items = $wpdb->get_var('SELECT FOUND_ROWS()');
            return array('total_items' => $total_items, 'recipients' => $recipients);
        }

        /**
         * Calculate the totals and setthe status
         */
        public function compute_campaign($status = 0) {
            global $wpdb;
            $result = $wpdb->get_row('
			SELECT COUNT(id_sendsms_recipient) AS nb_recipients, SUM(nb_sms) AS nb_sms, SUM(price) AS price
			FROM `' . Octopush_Sms_Recipient::_get_table() . '`
			WHERE `id_sendsms_campaign`=' . (int) $this->id_sendsms_campaign . '
			AND `status`=0');
            $this->nb_recipients = (int) $result->nb_recipients;
            $this->nb_sms = (int) $result->nb_sms;
            $this->price = (float) $result->price;
            $this->status = $status;            
            $this->save();
        }

        /**
         * Delete the campaign
         * @global type $wpdb
         * @return boolean
         */
        public function delete() {
            //verifi that we can delete the campaign
            if (!($this->status == 0 || $this->status >= 3))
                return false;

            global $wpdb;
            //delete recipients
            $wpdb->delete(Octopush_Sms_Recipient::_get_table(), array("id_sendsms_campaign" => $this->id_sendsms_campaign));
            //delete campaign
            return $wpdb->delete($this->_get_table(), array("id_sendsms_campaign" => $this->id_sendsms_campaign));
        }

        private function get_recipient_query($count = false) {
            global $wpdb;
            $query_select = "select * , meta2.meta_value as billing_phone , meta4.meta_value as billing_country, meta6.meta_value as billing_lastname , meta7.meta_value as billing_firstname"
            . " , (select count(*) from wp_posts post left join wp_postmeta meta0 on (meta0.post_id = post.ID) where post.post_type='shop_order' and meta0.meta_key='_customer_user' and meta0.meta_value=wp_users.ID) as order_nbr";
            if ($count) {
                $query_select = "select count(*) as total_rows ";
            }
            $query_from = "from wp_users "
                    . "left join wp_usermeta meta2 on (wp_users.ID = meta2.user_id and meta2.meta_key='billing_phone')
                left join wp_usermeta meta3 on (wp_users.ID = meta3.user_id and meta3.meta_key='last_login')
                left join wp_usermeta meta4 on (wp_users.ID = meta4.user_id and meta4.meta_key='billing_country')
                left join wp_usermeta meta5 on (wp_users.ID = meta5.user_id and meta5.meta_key='wp_capabilities')               
                left join wp_usermeta meta6 on (wp_users.ID = meta6.user_id and meta6.meta_key='billing_lastname')
                left join wp_usermeta meta7 on (wp_users.ID = meta7.user_id and meta7.meta_key='billing_firstname') ";
            $query_where = " where meta5.meta_value like '%customer%' ";

            if (isset($this->id_sendsms_campaign)) {
                $query_where .= " and not exists (select * from " . $wpdb->prefix . "octopushsms_recipient as r where meta2.meta_value = r.phone and r.id_sendsms_campaign = " . $this->id_sendsms_campaign . ")";
            }
            return $query_select." ".$query_from." ".$query_where;
        }

        private function execute_recipient_query($query,$count = false) {
            global $wpdb;
            if (WP_DEBUG)
                error_log("Recipient request:" . $query);
            if ($count) {
                return $wpdb->get_var($query);
            } else {
                $items = $wpdb->get_results($query);
                if (WP_DEBUG)
                    error_log(print_r($items, true));
                return $items;
            }
        }

        /**
         * Get the recipients from the given query
         */
        public function get_recipients_from_query($count = false) {
            global $wpdb;
            if (WP_DEBUG)
                error_log("Request: " . print_r($_REQUEST, true));
            //the query
            
            $query = $this->get_recipient_query($count);

            $query_where = "";
            //take parameter into account
            $post = sanitize_post($_REQUEST);

            $register_format = $lastvisit_format = '%Y-%m-%d';
            //if only year is checked
            if (isset($post['sendsms_query_registered_years']))
                $register_format = '%m-%d';
            if (isset($post['sendsms_query_connected_years']))
                $lastvisit_format = '%m-%d';

            if ($post['sendsms_query_registered_from']) {
                $date = strtotime($post['sendsms_query_registered_from'] . ' 00:00:00');
                $date = date_i18n('Y-m-d H:i:s', $date);
                $query_where .= ' AND ( DATE_FORMAT(wp_users.user_registered , \'' . $register_format . '\') >= DATE_FORMAT(\'' . $date . '\', \'' . $register_format . '\') ) ';
            }
            if ($post['sendsms_query_registered_to']) {
                $date = strtotime($post['sendsms_query_registered_to'] . ' 00:00:00');
                $date = date_i18n('Y-m-d H:i:s', $date);
                $query_where .= ' AND ( DATE_FORMAT(wp_users.user_registered , \'' . $register_format . '\') <= DATE_FORMAT(\'' . $date . '\', \'' . $register_format . '\') ) ';
            }
            //TODO finish with others parameters
            if ($post['sendsms_query_connected_from']) {
                $date = strtotime($post['sendsms_query_connected_from'] . ' 00:00:00');
                $date = date_i18n('Y-m-d H:i:s', $date);
                $query_where .= ' AND ( DATE_FORMAT(meta3.meta_value , \'' . $register_format . '\') >= DATE_FORMAT(\'' . $date . '\', \'' . $lastvisit_format . '\') ) ';
            }
            if ($post['sendsms_query_connected_to']) {
                $date = strtotime($post['sendsms_query_connected_to'] . ' 00:00:00');
                $date = date_i18n('Y-m-d H:i:s', $date);
                $query_where .= ' AND ( DATE_FORMAT(meta3.meta_value , \'' . $register_format . '\') <= DATE_FORMAT(\'' . $date . '\', \'' . $lastvisit_format . '\') ) ';
            }

            //order number
            if ((isset($post['sendsms_query_orders_from']) && !empty($post['sendsms_query_orders_from'])) 
                    || (isset($post['sendsms_query_orders_to']) && !empty($post['sendsms_query_orders_to'])) 
                    || (isset($post['sendsms_query_orders_none'])) && !empty($post['sendsms_query_orders_none'])) {
                $query_where .= ' AND (';

                if ($post['sendsms_query_orders_from']) {
                    if ($post['sendsms_query_orders_to']) {
                        $query_where .= ' ( ';
                    }
                    $query_where .= " (select count(*) from wp_posts post left join wp_postmeta meta0 on (meta0.post_id = post.ID) where post.post_type='shop_order' and meta0.meta_key='_customer_user' and meta0.meta_value=wp_users.ID) >= " . $post['sendsms_query_orders_from'];
                }
                if ($post['sendsms_query_orders_to']) {
                    if ($post['sendsms_query_orders_from']) {
                        $query_where .= ' AND ';
                    }
                    $query_where .= "(select count(*) from wp_posts post left join wp_postmeta meta0 on (meta0.post_id = post.ID) where post.post_type='shop_order' and meta0.meta_key='_customer_user' and meta0.meta_value=wp_users.ID) <= " . $post['sendsms_query_orders_to'];
                    if ($post['sendsms_query_orders_from']) {
                        $query_where .= ' ) ';
                    }
                }
                if (array_key_exists('sendsms_query_orders_none',$post) && isset($post['sendsms_query_orders_none'])) {                    
                    $query_where .= "(select count(*) from wp_posts post left join wp_postmeta meta0 on (meta0.post_id = post.ID) where post.post_type='shop_order' and meta0.meta_key='_customer_user' and meta0.meta_value=wp_users.ID) = 0 ";                    
                }
                $query_where .= ') ';
            }

            $query .= " $query_where";
            return $this->execute_recipient_query($query,$count);            
        }

        public function get_recipients_from_role($rolesafe) {
            global $wpdb;
            $phoneRequest = "select r.phone from " . $wpdb->prefix . "octopushsms_recipient as r where r.id_sendsms_campaign = " . $this->id_sendsms_campaign ;
            $phones = $wpdb->get_results($phoneRequest);
            $phones_array = array();
            foreach($phones as $phone) {
                $phones_array[] = $phone->phone;
            }

            /**
             * Get users
             */
            $per_page = 1000000;
            $current_page = 1;
            $query_users = new WP_User_Query(
                array(
                    'role' => $rolesafe,
                    'fields' => 'ID',
                    'number' => $per_page,                
                    'meta_query' => array(
                        'relation'  => 'AND',
                        array(
                            'key' => 'billing_phone',
                            'compare' => 'NOT IN',
                            'value' => $phones_array
                        )
                    ),
                    'offset' => ( $current_page - 1 ) * $per_page
                )
            );
            
            return $query_users->results;            
        }

        public function getPhonePrefixs() {
            global $wpdb;
            $phone_prefix = array();
            //preload phone prefix
            $phones_prefix = $wpdb->get_results("SELECT iso_code, prefix FROM `" . $wpdb->prefix . "octopushsms_phone_prefix`");
            foreach ($phones_prefix as $prefix) {
                $phone_prefix[$prefix->iso_code] = $prefix->prefix;
            }
            return $phone_prefix;
        }
        public function addRecipientsFromUserIds($userIds) {
            $cpt = 0;
            $errors = array();           
            $phone_prefix = $this->getPhonePrefixs();
            
            foreach ($userIds as $userId) {
                $user = get_userdata( $userId );
                $recipient = new Octopush_Sms_Recipient();
                $recipient->id_sendsms_campaign = $this->id_sendsms_campaign;
                $recipient->id_customer = (int) $userId;
                $recipient->firstname = get_user_meta($userId,'billing_firstname',true);
                $billing_lastname = get_user_meta($userId,'billing_lastname',true);
                $recipient->lastname = isset($billing_lastname) && $billing_lastname != '' ? $billing_lastname : $user->user_login;
                $recipient->lastname = preg_replace('/[0-9]+/', '', $recipient->lastname);
                $phone = Octopush_Sms_API::get_instance()->convert_phone_to_international(get_user_meta($userId,'billing_phone',true), get_user_meta($userId,'billing_country',true));
                if (is_null($phone)) {
                    $errors[] = array('message'=> __('Phone number cannot be found (lack country or phone) for user', 'octopush-sms', 'octopush-sms')." ".$user->user_login." (id=$userId)");
                    continue;
                }
                $recipient->phone = $phone;
                $recipient->iso_country = get_user_meta($userId,'billing_country',true);
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
            $this->compute_campaign();            
            return array('added'=>$cpt,'errors'=>$errors);
        }

        public function __construct($id_sendsms_campaign = null) {
            //default value
            $this->status = 0;
            $this->error_code = '';
            $this->nb_recipients = 0;
            $this->nb_sms = 0;
            $this->price = 0;
            $this->event = 'sendsmsFree';
            $this->paid_by_customer = 0;
            $this->simulation = 0;
            $this->date_transmitted = NULL;
            $this->date_validation = NULL;
            $this->date_add = current_time('mysql');
            $this->date_upd = current_time('mysql');

            $row = false;
            if (isset($id_sendsms_campaign)) {
                $row = self::find($id_sendsms_campaign);
            }
            if ($row) {
                $this->id_sendsms_campaign = $row->id_sendsms_campaign;
                $this->ticket = $row->ticket;
                $this->title = $row->title;
                $this->status = $row->status;
                $this->error_code = $row->error_code;
                $this->message = $row->message;
                $this->nb_recipients = $row->nb_recipients;
                $this->nb_sms = $row->nb_sms;
                $this->price = $row->price;
                $this->event = $row->event;
                $this->paid_by_customer = $row->paid_by_customer;
                $this->simulation = $row->simulation;
                $this->date_send = $row->date_send;
                $this->date_transmitted = $row->date_transmitted;
                $this->date_validation = $row->date_validation;
                $this->date_add = $row->date_add;
                $this->date_upd = $row->date_upd;
                if (WP_DEBUG) {
                    error_log("OHHHHHHH" . print_r($row, true));
                    error_log("OHHHHHHH" . print_r($this, true));
                }
            }
        }

        /**
         * Find a campaign of id $id_sendsms_campaign
         * @global type $wpdb
         * @param type $id_sendsms_campaign the id of the campaign to look for
         * @return array row of the campaign (or null)
         */
        public static function find($id_sendsms_campaign) {
            global $wpdb;
            if (!is_numeric(intval($id_sendsms_campaign)))
                return null;
            $sql = "select * from " . self::_get_table() . " where id_sendsms_campaign =" . intval($id_sendsms_campaign);
            $row = $wpdb->get_row($sql);
            return $row;
        }

        public static function _get_table() {
            global $wpdb;
            return $wpdb->prefix . 'octopushsms_campaign';
        }

    }

}