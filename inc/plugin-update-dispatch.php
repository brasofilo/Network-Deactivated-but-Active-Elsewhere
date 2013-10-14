<?php
/**
 * Custom updater class
 * 
 * $config (array)
 *     repo         => Network-Deactivated-but-Active-Elsewhere
 *     user         => brasofilo
 *     plugin_file  => plugin-folder/plugin-name.php
 *     donate_text  => Buy me a beer
 *     donate_icon  => &hearts;
 *     donate_link  => https://www.paypal.com/....
 */

if( !class_exists( 'B5F_General_Updater_and_Plugin_Love' ) ):
class B5F_General_Updater_and_Plugin_Love
{
    /**
     * Updater configuration
     * @var string 
     */
    private $config;

	/**
	 * Constructor
	 */
	public function __construct( $config ) 
    {
        if( empty( $config ) )
            return;
        
        $this->config = $config;

        # Updater class
        include_once 'plugin-update-checker.php';

        add_filter( 'upgrader_source_selection', array( $this, 'rename_github_zip' ), 1, 3 );
        if( !empty( $config['donate_text'] ) )
            add_filter( 'plugin_row_meta', array( $this, 'donate_link' ), 10, 4 );
        
        $updater = new PluginUpdateCheckerB(
    "https://raw.github.com/{$config['user']}/{$config['repo']}/master/inc/update.json", 
            $config['plugin_file'], 
            $config['repo'].'-master'
        );
	}

    
    /**
     * Add donate link to plugin description in /wp-admin/plugins.php
     * 
     * @param array $plugin_meta
     * @param string $plugin_file
     * @param string $plugin_data
     * @param string $status
     * @return array
     */
    public function donate_link( $plugin_meta, $plugin_file, $plugin_data, $status ) 
	{
		if( $this->config['plugin_file'] == $plugin_file )
			$plugin_meta[] = sprintf(
                '%s<a href="%s">%s</a>',
                $this->config['donate_icon'],
                $this->config['donate_link'],
                $this->config['donate_text']
            );
		return $plugin_meta;
	}


    /**
	 * Removes the prefix "-master" when updating from GitHub zip files
	 * 
	 * See: https://github.com/YahnisElsts/plugin-update-checker/issues/1
	 * 
	 * @param string $source
	 * @param string $remote_source
	 * @param object $thiz
	 * @return string
	 */
	public function rename_github_zip( $source, $remote_source, $thiz )
	{
		if(  strpos( $source, $this->config['repo'] ) === false )
			return $source;

		$path_parts = pathinfo($source);
		$newsource = trailingslashit($path_parts['dirname']). trailingslashit( $this->config['repo'] );
		rename($source, $newsource);
		return $newsource;
	}    

} 
endif;