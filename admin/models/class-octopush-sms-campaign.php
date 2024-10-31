<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('Octopush_Sms_Campaign')) {
    include_once 'class-octopush-sms-campaign-model.php';

    class Octopush_Sms_Campaign extends Octopush_Sms_Campaign_Model {

        public static $definition = array();/*
            'table' => 'sendsms_campaign',
            'primary' => 'id_sendsms_campaign',
            'fields' => array(
                'ticket' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255, 'required' => true),
                'title' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255),
                'status' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
                'error_code' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 4),
                'message' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
                'nb_recipients' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
                'nb_sms' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
                'price' => array('type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true),
                'event' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64, 'required' => true),
                'paid_by_customer' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
                'simulation' => array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
                'date_send' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
                'date_transmitted' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
                'date_validation' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
                'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true),
                'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true)
            )
        );*/
        
         

    }

}