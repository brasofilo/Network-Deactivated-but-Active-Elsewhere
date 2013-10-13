<?php

/**
 * Plugin Name: Network Deactivated but Active Elsewhere
 * Plugin URI: https://github.com/brasofilo/Network-Deactivated-but-Active-Elsewhere
 * Description: Inserts an indicator in the Network Plugins page whether a plugin is being used by any blog of the network. Shows the list of blogs on rollover.
 * Version: 2013.10.13
 * Author: Rodolfo Buaiz
 * Author URI: http://rodbuaiz.com/
 * Network: true
 * License: GPLv2 or later
 *
 * 
 * This program is free software; you can redistribute it
 * and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  
 * You may NOT assume that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty 
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */


# Busted!
!defined( 'ABSPATH' ) AND exit(
	"<pre>Hi there! I'm just part of a plugin, <h1>&iquest;what exactly are you looking for?"
);

if( is_network_admin() )
{
    # Main class
    require_once __DIR__ . '/inc/core.php';

    # Dispatch updater
    include_once 'inc/plugin-update-dispatch.php';

    define( 'B5F_NDBAE_FILE', plugin_basename( __FILE__ ) );
    # STart uP
    add_action(
        'plugins_loaded',
        array ( B5F_NDBAE_Main::get_instance(), 'plugin_setup' ), 
        10
    );
    add_action(
        'plugins_loaded',
        array ( B5F_General_Updater_and_Plugin_Love::get_instance(), 'plugin_setup' ),
        11
    );
}
