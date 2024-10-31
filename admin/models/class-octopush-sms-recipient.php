<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('Octopush_Sms_Recipient')) {

    class Octopush_Sms_Recipient {

        public $id_sendsms_recipient;
        public $id_sendsms_campaign;
        public $id_customer;
        public $firstname;
        public $lastname;
        public $phone;
        public $iso_country;
        public $transmitted = 0;
        public $price = 0;
        public $nb_sms = 0;
        public $status = 0;
        public $ticket;
        public $date_add;
        public $date_upd;
        public $errors = array();

        /* public static $definition = array(
          'table' => 'octopushsms_recipient',
          'primary' => 'id_sendsms_recipient',
          'fields' => array(
          'id_sendsms_campaign' => array('type' => '%d', 'validate' => 'isUnsignedId', 'required' => true),
          'id_customer' => array('type' => '%d', 'validate' => 'isUnsignedId'),
          'firstname' => array('type' => '%s', 'validate' => 'isName', 'size' => '32'),
          'lastname' => array('type' => '%s', 'validate' => 'isName', 'size' => '100'),
          'phone' => array('type' => '%s', 'validate' => 'isPhoneNumber', 'required' => true),
          'id_country' => array('type' => '%d', 'validate' => 'isUnsignedId'),
          'transmitted' => array('type' => '%s', 'validate' => 'isBool', 'required' => true),
          'price' => array('type' => '%f', 'validate' => 'isFloat', 'required' => true),
          'nb_sms' => array('type' => '%d', 'validate' => 'isInt', 'required' => true),
          'status' => array('type' => '%d', 'validate' => 'isInt', 'required' => true),
          'ticket' => array('type' => '%s', 'validate' => 'isString', 'size' => 255),
          'date_add' => array('type' => '%s', 'validate' => 'isDateFormat', 'required' => true),
          'date_upd' => array('type' => '%s', 'validate' => 'isDateFormat', 'required' => true)
          )
          ); */
        public static $fields = array(
            'id_sendsms_campaign' => array('type' => '%d', 'validate' => 'isUnsignedId', 'required' => true),
            'id_customer' => array('type' => '%d', 'validate' => 'isUnsignedId'),
            'firstname' => array('type' => '%s', 'validate' => 'isName', 'size' => '32'),
            'lastname' => array('type' => '%s', 'validate' => 'isName', 'size' => '100'),
            'phone' => array('type' => '%s', 'validate' => 'isPhoneNumber', 'required' => true),
            'iso_country' => array('type' => '%s', 'validate' => 'isName'),
            'transmitted' => array('type' => '%s', 'validate' => 'isBool', 'required' => true),
            'price' => array('type' => '%f', 'validate' => 'isFloat', 'required' => true),
            'nb_sms' => array('type' => '%d', 'validate' => 'isInt', 'required' => true),
            'status' => array('type' => '%d', 'validate' => 'isInt', 'required' => true),
            'ticket' => array('type' => '%s', 'size' => 255),
            'date_add' => array('type' => '%s', 'validate' => 'isDate', 'required' => true),
            'date_upd' => array('type' => '%s', 'validate' => 'isDate', 'required' => true)
        );

        public function validate() {
            foreach (self::$fields as $attr => $field) {
                if (isset($this->$attr) && isset($field['validate'])) {
                    $valid = call_user_func(array($this, $field['validate']), $this->$attr);
                    if (!$valid) {
                        $this->errors[] = array($attr => $attr . __(" no valid data is set", 'octopush-sms'));
                    }
                }
                if (isset($field['required']) && $field['required'] && !isset($this->$attr)) {
                    $this->errors[] = array($attr => __("value is required", 'octopush-sms'));
                }
                if (isset($field['size']) && isset($this->$attr) && strlen($this->$attr) > $field['size']) {
                    $this->errors[] = array($attr => __("maximum size is ", 'octopush-sms') . ' ' . $field['size']);
                }
                //echo "validate $attr , value ".$this->$attr." errors:".count($this->errors)." fct:".$field['validate']."<br/>";
            }
            return (count($this->errors) == 0);
        }

        public static function get_recipient($id_sendsms_campaign, $phone) {
            global $wpdb;
            $sql = "select * from " . Octopush_Sms_Recipient::_get_table() . " where id_sendsms_campaign = " . (int) $id_sendsms_campaign . " and phone = '" . $phone . "'";
            if (WP_DEBUG)
                error_log($sql);
            $res = $wpdb->get_row($sql);
           /* if (WP_DEBUG)
                error_log(print_r($res, true));*/
            if (isset($res)) {
                $recipient = new Octopush_Sms_Recipient();
                foreach ($recipient as $field => $value)
                    $recipient->$field = $value;
                return $recipient;
            }
            return null;
        }

        public function save() {
//TODO verification
            global $wpdb;
            $res = null;
            $this->date_upd = date('Y-m-d H:i:s');
            if (!$this->validate()) {
                if (WP_DEBUG) {
                    error_log(print_r($this->errors, true));
                }
                return false;
            }
            ob_start();
            $data = array(
                'id_sendsms_campaign' => $this->id_sendsms_campaign,
                'id_customer' => $this->id_customer,
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'phone' => $this->phone,
                'iso_country' => $this->iso_country,
                'transmitted' => $this->transmitted,
                'price' => $this->price,
                'nb_sms' => $this->nb_sms,
                'status' => $this->status,
                'ticket' => $this->ticket,
                'date_add' => $this->date_add,
                'date_upd' => $this->date_upd);

            if (isset($this->id_sendsms_recipient) && is_numeric($this->id_sendsms_recipient)) {
//recipient exists we update
                $where = array("id_sendsms_recipient" => $this->id_sendsms_recipient);
                $res = $wpdb->update($this->_get_table(), $data, $where);
            } else {
//recipient does not exist we create
                $res = $wpdb->insert($this->_get_table(), $data, array(
                    '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%s', '%s'
                ));
                $this->id_sendsms_recipient = $wpdb->insert_id;
                $res = $this->id_sendsms_recipient;
            }

            /*
              $res = $wpdb->insert(
              $this->_get_table(), array(
              'id_sendsms_campaign' => $this->id_sendsms_campaign,
              'id_customer' => $this->id_customer,
              'firstname' => $this->firstname,
              'lastname' => $this->lastname,
              'phone' => $this->phone,
              'id_country' => $this->id_country,
              'transmitted' => $this->transmitted,
              'price' => $this->price,
              'nb_sms' => $this->nb_sms,
              'status' => $this->status,
              'ticket' => $this->ticket,
              'date_add' => $this->date_add,
              'date_upd' => $this->date_upd), array(
              '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%d', '%s', '%s'
              )
              ); */
            ob_end_clean();
            if (WP_DEBUG) {
                error_log("Save recipients:".print_r($res, true));
            }
            return $res;
        }
        
        public function delete() {
            global $wpdb;
            $res=$wpdb->delete( self::_get_table(), array( 'id_sendsms_recipient' => $this->id_sendsms_recipient ) );
            return $res;
        }

        public static function _get_table() {
            global $wpdb;
            return $wpdb->prefix . 'octopushsms_recipient';
        }

        public function __construct($id_sendsms_recipient=null) {
            if (isset($id_sendsms_recipient)) {
                $row = self::find($id_sendsms_recipient);
            }
            if (isset($row)) {
                foreach($row as $key => $value) {
                    $this->$key=$value;
                }                
            } else {
                $this->date_add = date('Y-m-d H:i:s');
                $this->date_upd = date('Y-m-d H:i:s');
            }
        }
        
        /**
         * Find a recipient of id $id_sendsms_recipient
         * @global type $wpdb
         * @param type $id_sendsms_recipient the id of the rcipient to look for
         * @return array row of the recipient (or null)
         */
        public static function find($id_sendsms_recipient) {
            global $wpdb;
            if (!is_numeric($id_sendsms_recipient))
                return null;
            $sql = "select * from " . self::_get_table() . " where id_sendsms_recipient =" . sanitize_key($id_sendsms_recipient);
            $row = $wpdb->get_row($sql);
            return $row;
        }

        /**
         * Check for a float number validity
         *
         * @param float $float Float number to validate
         * @return boolean Validity is ok or not
         */
        static public function isFloat($float) {
            return strval(floatval($float)) == strval($float);
        }

        static public function isUnsignedFloat($float) {
            return strval(floatval($float)) == strval($float) AND $float >= 0;
        }

        /**
         * Check for a float number validity
         *
         * @param float $float Float number to validate
         * @return boolean Validity is ok or not
         */
        static public function isOptFloat($float) {
            return empty($float) OR self::isFloat($float);
        }

        /**
         * Check for name validity
         *
         * @param string $name Name to validate
         * @return boolean Validity is ok or not
         */
        static public function isName($name) {
            return preg_match('/^[^0-9!<>,;?=+()@#"°{}_$%:]*$/ui', stripslashes($name));
        }

        /**
         * Check for date validity
         *
         * @param string $date Date to validate
         * @return boolean Validity is ok or not
         */
        static public function isDate($date) {
            if (!preg_match('/^([0-9]{4})-((0?[1-9])|(1[0-2]))-((0?[1-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/ui', $date, $matches))
                return false;
            return checkdate(intval($matches[2]), intval($matches[5]), intval($matches[0]));
        }

        /**
         * Check for boolean validity
         *
         * @param boolean $bool Boolean to validate
         * @return boolean Validity is ok or not
         */
        static public function isBool($bool) {
            return is_null($bool) OR is_bool($bool) OR preg_match('/^[0|1]{1}$/ui', $bool);
        }

        /**
         * Check for phone number validity
         *
         * @param string $phoneNumber Phone number to validate
         * @return boolean Validity is ok or not
         */
        static public function isPhoneNumber($phoneNumber) {
            return preg_match('/^[+0-9. ()-]*$/ui', $phoneNumber);
        }

        /**
         * Check for an integer validity
         *
         * @param integer $id Integer to validate
         * @return boolean Validity is ok or not
         */
        static public function isInt($value) {
            return ((string) (int) $value === (string) $value OR $value === false);
        }

        /**
         * Check for an integer validity (unsigned)
         *
         * @param integer $id Integer to validate
         * @return boolean Validity is ok or not
         */
        static public function isUnsignedInt($value) {
            return (self::isInt($value) AND $value < 4294967296 AND $value >= 0);
        }

        /**
         * Check for an integer validity (unsigned)
         * Mostly used in database for auto-increment
         *
         * @param integer $id Integer to validate
         * @return boolean Validity is ok or not
         */
        static public function isUnsignedId($id) {
            return self::isUnsignedInt($id); /* Because an id could be equal to zero when there is no association */
        }

        static public function isNullOrUnsignedId($id) {
            return is_null($id) OR self::isUnsignedId($id);
        }

    }

}