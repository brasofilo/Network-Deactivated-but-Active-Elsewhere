<?php

/**
 * Plugin Name: Network Deactivated but Active Elsewhere
 * Plugin URI: 
 * Description: Shows an indicator in the Network Plugins page whether a plugin is being used by any blog of the network. Shows the list of blogs on rollover. Better used as a mu-plugin.
 * Version: 1.0
 * Stable Tag: 1.0
 * Author: Rodolfo Buaiz
 * Author URI: http://rodbuaiz.com/
 * Network: true
 * Domain Path: /languages
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
				$this->plugin_url . '/ndbae.js', 
				array(), 
				false, 
				true 
		);
        wp_enqueue_style( 
				'ndbae-css', 
				$this->plugin_url . '/ndbae.css'
		);

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
}