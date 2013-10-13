<?php
/**
 * Main plugin class
 */
class B5F_General_Updater_and_Plugin_Love
{
	/**
	 * Plugin instance.
	 * @type object
	 */
	protected static $instance = NULL;

    /**
     * Plugin updater slug
     * @var string 
     */
    private $config;// = 'Network-Deactivated-but-Active-Elsewhere';

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
        $this->config = apply_filters( 'b5f_updater_and_plugin_love', array() );
        if( empty( $this->config ) )
            return;
        
        # Updater class
        include_once 'plugin-update-checker.php';

        add_filter( 'upgrader_source_selection', array( $this, 'rename_github_zip' ), 1, 3 );
        add_filter( 'plugin_row_meta', array( $this, 'donate_link' ), 10, 4 );
        $updater = new PluginUpdateCheckerB(
    "https://raw.github.com/{$this->config['user']}/{$this->config['repo']}/master/inc/update.json", 
            B5F_NDBAE_FILE, 
            $this->config['repo'].'-master'
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
		$newsource = trailingslashit($path_parts['dirname']). trailingslashit( $this->repo_slug );
		rename($source, $newsource);
		return $newsource;
	}    

} 