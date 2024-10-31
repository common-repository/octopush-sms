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


if (!class_exists('Octopush_Sms_Campaign_List')) {

    class Octopush_Sms_Campaign_List extends WP_List_Table {
        
        /**
         * @var _status the campaign with this status are displayed
         */
        public static $_status = array();

        const PER_PAGE = 25;

        function __construct($args = null) {

            global $status, $page;

            //Set parent defaults
            parent::__construct(
                    array(
                        //singular name of the listed records
                        'singular'  => __('campaign','octopush-sms'),
                        //plural name of the listed records
                        'plural'    => __('campaigns','octopush-sms'),
                        //does this table support ajax?
                        'ajax' => true,
                        'screen' => 'octopush-sms'
                    )
            );
            self::$_status = $args['status'];
        }

        function get_columns() {
            $columns = array(
                'ticket' => __('Ticket', 'octopush-sms'),
                'title' => __('Title', 'octopush-sms'),
                'status' => __('Status', 'octopush-sms'),
                //'simulation' => __('Simulation', 'octopush-sms'),
                'nb_recipients' => __('Nb of recipients', 'octopush-sms'),
                //'nb_sms' => __('Nb SMS', 'octopush-sms'),
                'price' => __('Price', 'octopush-sms'),
                'date_send' => __('Sending date', 'octopush-sms'),
                'user_actions' => __('Actions', 'octopush-sms')
            );

            return $columns;
        }

        function prepare_items() {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            //orderby and order
            $orderby = !empty($_REQUEST['orderby']) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'id_sendsms_campaign';
            $order = !empty($_REQUEST['order']) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'desc';

            //pagination
            $current_page = absint($this->get_pagenum());

            $s=! empty( $_REQUEST['s'] ) && '' != $_REQUEST['s'] ? $_REQUEST['s'] : '';
            $total_items = Octopush_Sms_Campaign_Model::count_campaigns(self::$_status,$s);
            $per_page = self::PER_PAGE;
            $this->set_pagination_args(array(
                'total_items' => $total_items, //WE have to calculate the total number of items
                'per_page' => $per_page, //WE have to determine how many items to show on a page
                'total_pages' => ceil($total_items / $per_page),
                // Set ordering values if needed (useful for AJAX)
                'orderby' => $orderby,
                'order' => $order,
                's'     => $s,
            ));
            
            $this->items = Octopush_Sms_Campaign_Model::get_campaigns(self::$_status, $s,$orderby, $order, $this->get_pagenum(), self::PER_PAGE);
        }

        /**
         * Display the list
         */
        function display() {

            wp_nonce_field('ajax-custom-list-nonce', '_ajax_custom_list_nonce');

            echo '<input id="order" type="hidden" name="order" value="' . $this->_pagination_args['order'] . '" />';
            echo '<input id="orderby" type="hidden" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';
            echo '<input id="s" type="hidden" name="s" value="' . $this->_pagination_args['s'] . '" />';
 
            parent::display();
        }

        function ajax_response() {
            if (WP_DEBUG) {
                error_log("request: ".print_r($_REQUEST,true));
            }
            check_ajax_referer('ajax-custom-list-nonce', '_ajax_custom_list_nonce');

            $this->prepare_items();
            
            if (WP_DEBUG) {
                error_log("_args: ".print_r($this->_args,true));
            }
            if (WP_DEBUG) {
                error_log("_pagination_args: ".print_r($this->_pagination_args,true));
            }

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

        /*function get_sortable_columns() {
            $sortable_columns = array(
                'ticket' => array('ticket', false),
                'title' => array('title', false),
                'status' => array('status', false),
                //TOTO octopussms history tab
                'simulation' => array('simulation', false),
                'nb_recipients' => array('nb_recipients', false),
                'nb_sms' => array('nb_sms', false),
                'price' => array('price', false),
                'date_send' => array('date_send', false)
            );
            return $sortable_columns;
        }*/

        function column_price($item) {
            return $item->price . ' €';
        }

        function column_status($item) {
            return Octopush_Sms_Admin::get_instance()->get_status($item->status);
        }

        function column_default($item, $column_name) {
            switch ($column_name) {
                case 'ticket':
                case 'title':
                case 'status' :
                case 'simulation' :
                case 'nb_recipients' :
                case 'nb_sms' :
                case 'price':
                case 'date_send':
                    if (isset($item->$column_name))
                        return $item->$column_name;
                    else {
                        return '';
                    }
                case 'user_actions' :
                    echo '<p>';
                    printf('<a class="button tips %s" href="%s" data-tip="%s">%s</a>', 'edit', Octopush_Sms_Send_Tab::get_form_url() . '&action=Octopush_Sms_Send_Tab&id_sendsms_campaign=' . $item->id_sendsms_campaign, __('edit', 'octopush-sms'), __('edit', 'octopush-sms'));
                    //<td><a href="' . $this->get_form_url() . '&action=' . get_class($this) . '&id_sendsms_campaign=' . $campaign->id_sendsms_campaign . '"><img src="../img/admin/edit.gif" class="edit"></a></td>
                    echo '</p>';
                    break;
                default:
                    return "PB:" . print_r($item, true); //Show the whole array for troubleshooting purposes
            }
        }

    }

}
