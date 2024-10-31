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
 * Description of class-octopush-sms-campaign-list
 *
 * @author mathieu
 */
if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

//WP_Comments_List_Table
if (!class_exists('Octopush_Sms_Recipient_List')) {

    class Octopush_Sms_Recipient_List extends WP_List_Table {

        protected static $_campaign;

        const PER_PAGE = 25;

        function __construct($args = null) {

            global $status, $page;

            //Set parent defaults
            parent::__construct(
                    array(
                        //singular name of the listed records
                        'singular' => __('recipient', 'octopush-sms'),
                        //plural name of the listed records
                        'plural' => __('recipients', 'octopush-sms'),
                        //does this table support ajax?
                        'ajax' => true,
                        'screen' => 'octopush-sms-campaign'
                    )
            );
            self::$_campaign = $args['campaign'];
        }

        public function set_campaign($campaign) {
            self::$_campaign = $campaign;
        }

        function get_columns() {
            //TODO gere different cas en fonction du statut
            $columns = array(
                'id_customer' => __('ID', 'octopush-sms'),
                'firstname' => __('Firstname', 'octopush-sms'),
                'lastname' => __('Lastname', 'octopush-sms'),
                'phone' => __('Phone', 'octopush-sms'),
                'iso_country' => __('Country', 'octopush-sms'),
                    //'transmitted' => $this->transmitted,
                    //'price' => __('Price', 'octopush-sms')
                    //__('Transmitted to OWS', 'octopush-sms')
                    //__('Status / Error', 'octopush-sms')
            );
            if (intval(self::$_campaign->status) == 0) {
                $column_to_add = array('user_actions' => __('Actions', 'octopush-sms'));
                $columns = array_merge($columns, $column_to_add);
            } else if (intval(self::$_campaign->status) >= 1) {
                $column_to_add = array(
                    'price' => __('Price', 'octopush-sms'),
                    'transmitted' => __('Transmitted to Octopush', 'octopush-sms'),
                    'status' => __('Status / Error', 'octopush-sms'),
                );
                $columns = array_merge($columns, $column_to_add);
            }
            return $columns;
        }

        function prepare_items() {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            //orderby and order
            //orderby and order
            $orderby = !empty($_REQUEST['orderby']) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'lastname';
            $order = !empty($_REQUEST['order']) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'desc';

            //pagination
            $current_page = absint($this->get_pagenum());

            $s = !empty($_REQUEST['s']) && '' != $_REQUEST['s'] ? $_REQUEST['s'] : '';

            $per_page = self::PER_PAGE;

            if (WP_DEBUG)
                error_log("before get_recipients");

            //$this->items = self::$_campaign->get_recipients_from_query($s, $orderby, $order, $this->get_pagenum(), self::PER_PAGE, $total_items);
            $results = self::$_campaign->get_recipients($current_page, $per_page, $orderby, $order, $s);
            $this->items = $results['recipients'];
            $total_items = $results['total_items'];
            $this->set_pagination_args(array(
                'total_items' => $total_items, //WE have to calculate the total number of items
                'per_page' => $per_page, //WE have to determine how many items to show on a page
                'total_pages' => ceil($total_items / $per_page),
                // Set ordering values if needed (useful for AJAX)
                'orderby' => $orderby,
                'order' => $order,
                's' => $s,
            ));

        }

        function display() {
            echo '<form method="post" id="search-form">
                <input type="hidden" name="page" value="my_list_recipient" />';
            $this->search_box(__('Search Recipients', 'octopush-sms'), 'search_id');


            wp_nonce_field('ajax-custom-list-nonce', '_ajax_custom_list_nonce');


            echo '<input id="order" type="hidden" name="order" value="' . $this->_pagination_args['order'] . '" />';
            echo '<input id="orderby" type="hidden" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';
            echo '<input id="s" type="hidden" name="s" value="' . $this->_pagination_args['s'] . '" />';
            echo '<input id="id_sendsms_campaign" type="hidden" name="id_sendsms_campaign" value="' . self::$_campaign->id_sendsms_campaign . '" />';
            echo '<input id="list" type="hidden" name="list" value="' . get_class($this) . '" />';


            parent::display();
            echo '</form>';
        }

        function ajax_response() {
            if (WP_DEBUG)
                error_log("ajax_response");

            check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');

            if (WP_DEBUG)
                error_log("ajax_response");

            $this->prepare_items();

            //extract($this->_args);
            //extract($this->_pagination_args, EXTR_SKIP);

            ob_start();
            if (!empty($_REQUEST['no_placeholder']))
                $this->display_rows();
            else
                $this->display_rows_or_placeholder();
            $rows = ob_get_clean();

            ob_start();
            $this->print_column_headers();
            $headers = ob_get_clean();

            ob_start();
            $this->pagination('top');
            $pagination_top = ob_get_clean();

            ob_start();
            $this->pagination('bottom');
            $pagination_bottom = ob_get_clean();

            $response = array('rows' => $rows);
            $response['pagination']['top'] = $pagination_top;
            $response['pagination']['bottom'] = $pagination_bottom;
            $response['column_headers'] = $headers;

            if (isset($total_items))
                $response['total_items_i18n'] = sprintf(_n('1 item', '%s items', $total_items), number_format_i18n($total_items));

            if (isset($total_pages)) {
                $response['total_pages'] = $total_pages;
                $response['total_pages_i18n'] = number_format_i18n($total_pages);
            }

            die(json_encode($response));
        }

        function get_sortable_columns() {
            $sortable_columns = array();
            /* TODO array(
              'ticket' => array('ticket', false),
              'title' => array('title', false),
              'status' => array('status', false),
              //TOTO octopussms history tab
              'simulation' => array('simulation', false),
              'nb_recipients' => array('nb_recipients', false),
              'nb_sms' => array('nb_sms', false),
              'price' => array('price', false),
              'send_date' => array('send_date', false)
              ); */
            return $sortable_columns;
        }

        function column_price($item) {
            return $item->price . ' €';
        }

        function column_id_customer($item) {
            if ($item->id_customer == 0) {
                return '';
            } else {
                return $item->id_customer;
            }
        }
        
        function column_status($item) {
            return Octopush_Sms_Admin::get_instance()->get_error_SMS($item->status);
        }

        function column_transmitted($item) {
            if (intval($item->transmitted) == 0)
                return __('no', 'octopush_sms');
            return __('yes', 'octopush_sms');
            return Octopush_Sms_Admin::get_instance()->get_status($item->status);
        }

        function column_default($item, $column_name) {
            switch ($column_name) {
                case 'user_actions' :
                    echo '<p>';
                    printf('<a id="%s" class="button tips %s" href="%s" data-tip="%s">%s</a>', $item->id_sendsms_recipient, 'delete', Octopush_Sms_Send_Tab::get_form_url() . '&action=Octopush_Sms_Send_Tab&id_sendsms_recipient=' . $item->id_sendsms_recipient, __('delete', 'octopush-sms'), __('delete', 'octopush-sms'));
                    //<td><a href="' . $this->get_form_url() . '&action=' . get_class($this) . '&id_sendsms_campaign=' . $campaign->id_sendsms_campaign . '"><img src="../img/admin/edit.gif" class="edit"></a></td>
                    echo '</p>';
                    break;
                case 'id_customer':
                case 'firstname':
                case 'lastname' :
                case 'phone' :
                case 'price' :
                case 'transmitted':
                case 'status':
                case 'iso_country' :
                    if (isset($item->$column_name))
                        return $item->$column_name;
                    else {
                        return '';
                    }
                default:
                    return print_r($item, true); //Show the whole array for troubleshooting purposes
            }
        }

    }

}
