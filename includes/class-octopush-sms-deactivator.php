<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://octopush.com
 * @since      1.0.0
 *
 * @package    Octopush_Sms
 * @subpackage Octopush_Sms/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Octopush_Sms
 * @subpackage Octopush_Sms/includes
 * @author     Your Name <email@example.com>
 */
class Octopush_Sms_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        global $wpdb;
        //register_deactivation_hook(__FILE__, 'prefix_deactivation');

        /**
         * On deactivation, remove all functions from the scheduled action hook.
         */
        //function prefix_deactivation() {
        wp_clear_scheduled_hook('octopush_sms_event_daily_hook');
        //}
        self::_uninstall_DB();
        
        //TODO enable delete plugins options
        $sql = 'delete * from '.$wpdb->prefix."options where option_name like 'octopush_sms%'";
        //$wpdb->query($sql);
    }

    /**
     * Drop the database used by the plugin.
     * @global type $wpdb
     * @return boolean
     */
    private static function _uninstall_DB() {
        global $wpdb;
        // remove phone prefix from database
        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'octopushsms_campaign`');
        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'octopushsms_recipient`');
        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->prefix . 'octopushsms_phone_prefix`');
        return true;
    }

}
