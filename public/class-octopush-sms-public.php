<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://octopush.com
 * @since      1.0.0
 *
 * @package    Octopush_Sms
 * @subpackage Octopush_Sms/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Octopush_Sms
 * @subpackage Octopush_Sms/public
 * @author     Your Name <email@example.com>
 */
class Octopush_Sms_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $octopush_sms    The ID of this plugin.
	 */
	private $octopush_sms;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $octopush_sms       The name of the plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $octopush_sms, $version ) {

		$this->octopush_sms = $octopush_sms;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Octopush_Sms_Public_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Octopush_Sms_Public_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->octopush_sms, plugin_dir_url( __FILE__ ) . 'css/octopush-sms-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Octopush_Sms_Public_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Octopush_Sms_Public_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->octopush_sms, plugin_dir_url( __FILE__ ) . 'js/octopush-sms-public.js', array( 'jquery' ), $this->version, false );

	}

}
