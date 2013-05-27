<?php

/**
 * Plugin Name: Network Deactivated but Active Elsewhere
 * Plugin URI: https://github.com/brasofilo/Network-Deactivated-but-Active-Elsewhere
 * Description: Inserts an indicator in the Network Plugins page whether a plugin is being used by any blog of the network. Shows the list of blogs on rollover. Better used as a mu-plugin.
 * Version: 1.2
 * Author: Rodolfo Buaiz
 * Author URI: http://rodbuaiz.com/
 * Network: true
 * License: GPLv2 or later
 *
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */


//register_activation_hook( 
//		__FILE__, 
//		array( 'B5F_Blog_Active_Plugins_Multisite', 'on_activation' ) 
//);

if( is_admin() && is_multisite() )
{
	add_action(
		'plugins_loaded',
		array ( B5F_Blog_Active_Plugins_Multisite::get_instance(), 'plugin_setup' )
	);
}

class B5F_Blog_Active_Plugins_Multisite
{
	/**
	 * Plugin instance.
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * Holds list of all network blog ids.
	 * @type array
	 */
	public $blogs = array();

	/**
	 * URL to this plugin's directory.
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 * @type string
	 */
	public $plugin_path = '';

	/**
	 * Access this plugin's working instance.
	 *
	 * @wp-hook plugins_loaded
	 * @since   2012.09.13
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}

	public static function on_activation()
	{
		$plugin = plugin_basename( __FILE__ );
		if( !is_network_only_plugin( $plugin ) )
			wp_die(
				'Sorry, this plugin is meant for Network Activation only', 
				'Network only',  
				array( 
					'response' => 500, 
					'back_link' => true 
				)
			);    
	}
	/**
	 * Used for regular plugin work, ie, magic begins.
	 *
	 * @wp-hook plugins_loaded
	 * @return  void
	 */
	public function plugin_setup()
	{
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		add_action( 
			'load-plugins.php', 
			array( $this, 'load_blogs' ) 
		);
	}

	/**
	 * Constructor. Intentionally left empty and public.
	 *
	 * @see plugin_setup()
	 * @since 2012.09.12
	 */
	public function __construct() {}
		
	/**
	 * Dispatch all actions.
	 * Store all blog IDs in $this->blogs.
	 *
	 * @wp-hook load-{$pagenow}
	 * @return void
	 */
	public function load_blogs()
	{ 
		// Load only in /wp-admin/network/
		global $current_screen;
		if( !$current_screen->is_network )
			return;
		
		add_action( 
				'network_admin_plugin_action_links', 
				array( $this, 'list_plugins' ), 
				10, 4 
		);
		add_action(
				'admin_print_scripts',
				array( $this, 'enqueue')
		);
		add_filter( 
				'views_plugins-network', 
				array( $this, 'inactive_views' ), 
				10, 1 
		);

		add_filter( 'admin_init', array( $this, 'admin_init' ) );

		// Store all blogs IDs
		global $wpdb;
		$blogs = $wpdb->get_results(
				" SELECT blog_id, domain 
				FROM {$wpdb->blogs}
				WHERE site_id = '{$wpdb->siteid}'
				AND spam = '0'
				AND deleted = '0'
				AND archived = '0' "
		);	
		
		foreach( $blogs as $blog )
		{
			$this->blogs[] = array(
				'blog_id' => $blog->blog_id,
				'name'    => get_blog_option( $blog->blog_id, 'blogname' )
			);
		}
	}
	
	/**
	 * Enqueue script and style.
	 * 
	 * @wp-hook admin_print_scripts
	 * @return array
	 */
	public function enqueue()
	{
		wp_enqueue_script( 
				'ndbae-js', 
				$this->plugin_url . 'ndbae.js', 
				array(), 
				false, 
				true 
		);
        wp_enqueue_style( 
				'ndbae-css', 
				$this->plugin_url . 'ndbae.css'
		);

	}
	
	/**
	 * Button to show/hide locally active plugins in the screen "Inactive plugins"
	 * 
	 * @wp-hook views_plugins-network
	 * @param array $views
	 * @return array
	 */
	public function inactive_views( $views ) 
	{
		if( 
			isset( $_GET['plugin_status'] ) 
			&& in_array( $_GET['plugin_status'], array('inactive','all') ) 
		)
			$views['metakey'] = '<label><input type="checkbox" id="hide_network_but_local"> Hide locally active plugins</label>';
		return $views;
	}
	
	/**
	 * Each plugin row action links. Check if active is any site. If so, mark it.
	 *
	 * @wp-hook network_admin_plugin_action_links
	 * @return array
	 */
	public function list_plugins( $actions, $plugin_file, $plugin_data, $context )
	{
		$check_plugin = $this->get_network_plugins_active( $plugin_file );
		if( !empty( $check_plugin ) )
		{
			$class = isset( $actions['deactivate'] ) ? 'red-blogs' : 'blue-blogs';
			$separator = ' - - ';
			$sites_list = 
				'[-' 
				. implode( $separator, $check_plugin ) 
				. '-]';
			$actions[] = "<a href='#' 
				title='$sites_list' 
				class='ndbae-act-link add-new-h2 $class'>Active Elsewhere</a>";
		}
		return $actions;
	}

	/**
	 * Check if plugin is active in any blog
	 * 
	 * @param string $plug
	 * @return boolean
	 */
	private function get_network_plugins_active( $plug )
	{
		$active_in_blogs = array();
		foreach( $this->blogs as $blog )
		{
			$the_plugs = get_blog_option( $blog['blog_id'], 'active_plugins' );
			foreach( $the_plugs as $value )
			{
				if( $value == $plug )
					$active_in_blogs[] = $blog['name'];
			}
		}
		return $active_in_blogs;
	}
	
		/**
	 * Add settings to wp-admin/options-general.php page
	 * 
	 * @return void 
	 */
	public function admin_init() 
	{

		register_setting( 
				'network', 
				'b5f_nbdae', // option name
				'esc_attr' 
		);
		
		add_settings_section( 
				'nbdae_section', 
				sprintf(
						'<h3><a name="psb" id="psb">%s</a></h2>',
						__( 'Post Status Bubbles', 'b5f-psb' ),
						$this->plugin_url . 'images/icon.png'
				), 
				array( $this, 'section_text'), 
				'network' 
		);

		add_settings_field(
			'b5f_nbdae',
			__( 'Hide bubble when viewing post screen', 'b5f-psb' ),
			array( $this, 'hide_bubble_html' ),
			'network',
			'nbdae_section',
			array( 'label_for' => 'b5f_nbdae' )
		);
	}
	
	/**
	 * Settings section description
	 * 
	 * @return string
	 */	
	function section_text() 
	{
		printf(
				'<p><i>%s</i></p>',
				__( 'Select the post types to show the bubbles, and the status to count for. You can also hide the bubble when viewing the correspondent page.', 'b5f-psb' )
		);
	}
	
	
	/**
	 * Hide bubble settings field
	 * 
	 * @return string
	 */	
	public function hide_bubble_html()
	{
		$get_option = get_option( 'b5f_nbdae' );
		//$saved = isset( $get_option['ppb_hide_curr_screen'] );
		printf(
				'<input type="checkbox" name="%1$s"  id="%1$s" %3$s />'.
				'<label for="%1$s"> %2$s' .
				'</label><br>',
				esc_attr('b5f_nbdae'),
				 __( 'Enable?', 'b5f-psb' ),
				checked( $get_option, true, false )
		);
	}
		
		

} 