<?php

/**
 * Fired during plugin activation
 *
 * @link       http://octopush.com
 * @since      1.0.0
 *
 * @package    Octopush_Sms
 * @subpackage Octopush_Sms/includes
 */

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Octopush_Sms
 * @subpackage Octopush_Sms/includes
 * @author     Your Name <email@example.com>
 */
class Octopush_Sms_Activator {
    
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        //register_activation_hook(__FILE__, 'octopush_sms_activation');

        /**
         * On activation, set a time, frequency and name of an action to be scheduled.
         * This action is registered in class-octopush-sms.php
         */
        //function octopush_sms_activation() {
        wp_schedule_event(time(), 'daily', 'octopush_sms_event_daily_hook');
        //}
        self::_create_tables();
    }

    private static function _create_table_phone_prefix() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . $wpdb->prefix . "octopushsms_phone_prefix (
            iso_code varchar(3) NOT NULL,
            prefix int(10) unsigned DEFAULT NULL,
            PRIMARY KEY  (iso_code)
          ) CHARSET=$charset_collate;";

        
        dbDelta($sql);

        $wpdb->query("INSERT IGNORE INTO " . $wpdb->prefix . "octopushsms_phone_prefix (iso_code, prefix) VALUES
            ('AD', 376),('AE', 971),('AF', 93),('AG', 1268),('AI', 1264),('AL', 355),('AM', 374),('AN', 599),('AO', 244),
            ('AQ', 672),('AR', 54),('AS', 1684),('AT', 43),('AU', 61),('AW', 297),('AX', NULL),('AZ', 994),('BA', 387),
            ('BB', 1246),('BD', 880),('BE', 32),('BF', 226),('BG', 359),('BH', 973),('BI', 257),('BJ', 229),('BL', 590),('BM', 1441),
            ('BN', 673),('BO', 591),('BR', 55),('BS', 1242),('BT', 975),('BV', NULL),('BW', 267),('BY', 375),('BZ', 501),
            ('CA', 1),('CC', 61),('CD', 242),('CF', 236),('CG', 243),('CH', 41),('CI', 225),('CK', 682),('CL', 56),('CM', 237),
            ('CN', 86),('CO', 57),('CR', 506),('CU', 53),('CV', 238),('CX', 61),('CY', 357),('CZ', 420),('DE', 49),('DJ', 253),
            ('DK', 45),('DM', 1767),('DO', 1809),('DZ', 213),('EC', 593),('EE', 372),('EG', 20),('EH', NULL),('ER', 291),('ES', 34),
            ('ET', 251),('FI', 358),('FJ', 679),('FK', 500),('FM', 691),('FO', 298),('FR', 33),('GA', 241),('GB', 44),('GD', 1473),
            ('GE', 995),('GF', 594),('GG', NULL),('GH', 233),('GI', 350),('GL', 299),('GM', 220),('GN', 224),('GP', 590),('GQ', 240),
            ('GR', 30),('GS', NULL),('GT', 502),('GU', 1671),('GW', 245),('GY', 592),('HK', 852),('HM', NULL),('HN', 504),('HR', 385),
            ('HT', 509),('HU', 36),('ID', 62),('IE', 353),('IL', 972),('IM', 44),('IN', 91),('IO', 1284),('IQ', 964),('IR', 98),
            ('IS', 354),('IT', 39),('JE', 44),('JM', 1876),('JO', 962),('JP', 81),('KE', 254),('KG', 996),('KH', 855),('KI', 686),
            ('KM', 269),('KN', 1869),('KP', 850),('KR', 82),('KW', 965),('KY', 1345),('KZ', 7),('LA', 856),('LB', 961),('LC', 1758),
            ('LI', 423),('LK', 94),('LR', 231),('LS', 266),('LT', 370),('LU', 352),('LV', 371),('LY', 218),('MA', 212),('MC', 377),
            ('MD', 373),('ME', 382),('MF', 1599),('MG', 261),('MH', 692),('MK', 389),('ML', 223),('MM', 95),('MN', 976),('MO', 853),
            ('MP', 1670),('MQ', 596),('MR', 222),('MS', 1664),('MT', 356),('MU', 230),('MV', 960),('MW', 265),('MX', 52),('MY', 60),
            ('MZ', 258),('NA', 264),('NC', 687),('NE', 227),('NF', 672),('NG', 234),('NI', 505),('NL', 31),('NO', 47),('NP', 977),
            ('NR', 674),('NU', 683),('NZ', 64),('OM', 968),('PA', 507),('PE', 51),('PF', 689),('PG', 675),('PH', 63),('PK', 92),
            ('PL', 48),('PM', 508),('PN', 870),('PR', 1),('PS', NULL),('PT', 351),('PW', 680),('PY', 595),('QA', 974),('RE', 262),
            ('RO', 40),('RS', 381),('RU', 7),('RW', 250),('SA', 966),('SB', 677),('SC', 248),('SD', 249),('SE', 46),('SG', 65),
            ('SI', 386),('SJ', NULL),('SK', 421),('SL', 232),('SM', 378),('SN', 221),('SO', 252),('SR', 597),('ST', 239),('SV', 503),
            ('SY', 963),('SZ', 268),('TC', 1649),('TD', 235),('TF', NULL),('TG', 228),('TH', 66),('TJ', 992),('TK', 690),('TL', 670),
            ('TM', 993),('TN', 216),('TO', 676),('TR', 90),('TT', 1868),('TV', 688),('TW', 886),('TZ', 255),('UA', 380),('UG', 256),
            ('US', 1),('UY', 598),('UZ', 998),('VA', 379),('VC', 1784),('VE', 58),('VG', 1284),('VI', 1340),('VN', 84),('VU', 678),
            ('WF', 681),('WS', 685),('YE', 967),('YT', 262),('ZA', 27),('ZM', 260),('ZW', 263);"
        );
    }

    /**
     * Create tables for the octopush-sms plugin.
     * 
     * @global type $wpdb
     * @return boolean true if succed, otherwise false
     */
    static private function _create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();


        $sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'octopushsms_campaign (
                id_sendsms_campaign int unsigned NOT NULL auto_increment,
                ticket varchar(255) NOT NULL,
                title varchar(255) default NULL,
                status tinyint(1) unsigned NOT NULL default 0 COMMENT \'0=in construction, 1=in transfert, 2=waiting for validation, 3=sent, 4=canceled, 5=error\',
                error_code varchar(4) default NULL,
                message text default NULL,
                nb_recipients int unsigned NOT NULL default 0,
                nb_sms int unsigned NOT NULL default 0,
                price double(5,3) NOT NULL default 0,
                event varchar(64) NOT NULL default \'sendsmsFree\',
                paid_by_customer tinyint(1) unsigned NOT NULL default 0,
                simulation tinyint(1) unsigned NOT NULL default 0,
                date_send datetime default NULL,
                date_transmitted datetime default NULL,
                date_validation datetime default NULL,
                date_add datetime NOT NULL,
                date_upd datetime NOT NULL,
                PRIMARY KEY  (id_sendsms_campaign)
        ) CHARSET=' . $charset_collate . ';';
        dbDelta($sql);
        
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'octopushsms_recipient (
                id_sendsms_recipient int unsigned NOT NULL auto_increment,
                id_sendsms_campaign int unsigned NOT NULL,
                id_customer int unsigned default NULL,
                firstname varchar(32) default NULL,
                lastname varchar(100) default NULL,
                phone varchar(16) NOT NULL,
                iso_country char(2) NULL,
                transmitted tinyint(1) unsigned NOT NULL default 0,
                price double(5,3) NOT NULL default 0,
                nb_sms int unsigned NOT NULL default 0,
                status int unsigned NOT NULL DEFAULT 0,
                ticket varchar(255) default NULL,
                date_add datetime NOT NULL,
                date_upd datetime NOT NULL,
                PRIMARY KEY  (id_sendsms_recipient),
                UNIQUE index_unique_phone (id_sendsms_campaign , phone)
        ) CHARSET='.$charset_collate.';';
        dbDelta($sql);

        self::_create_table_phone_prefix();
        return true;
    }

}
