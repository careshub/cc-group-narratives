<?php
/**
 * Plugin Name.
 *
 * @package   CC Group Narratives
 * @author    David Cavins
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Your Name or Company Name
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-plugin-name-admin.php`
 *
 * @TODO: Rename this class to a proper name for your plugin.
 *
 * @package CC Group Narratives
 * @author  David Cavins
 */
class CC_Group_Narratives {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'cc-group-narratives';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Load plugin custom post type and taxonomy
		add_action( 'init', array( $this, 'register_cpt_group_story' ) );
		add_action( 'init', array( $this, 'register_taxonomy_related_groups' ) );

		//Filter plugin template
		//TODO: finish template stack logic
		add_filter( 'bp_located_template', array( $this, 'ccgn_load_template_filter'), 10, 2 );


		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		// add_action( '@TODO', array( $this, 'action_method_name' ) );
		// add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		require_once( plugin_dir_path( __FILE__ ) . '/includes/ccgn-template-tags.php' );


	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Creates the group story custom post type.
	 *
	 * @since    1.0.0
	 */
	public function register_cpt_group_story() {

	    $labels = array( 
	        'name' => _x( 'Group Stories', 'group_story' ),
	        'singular_name' => _x( 'Group Story', 'group_story' ),
	        'add_new' => _x( 'Add New', 'group_story' ),
	        'add_new_item' => _x( 'Add New Group Story', 'group_story' ),
	        'edit_item' => _x( 'Edit Group Story', 'group_story' ),
	        'new_item' => _x( 'New Group Story', 'group_story' ),
	        'view_item' => _x( 'View Group Story', 'group_story' ),
	        'search_items' => _x( 'Search Group Stories', 'group_story' ),
	        'not_found' => _x( 'No group stories found', 'group_story' ),
	        'not_found_in_trash' => _x( 'No group stories found in Trash', 'group_story' ),
	        'parent_item_colon' => _x( 'Parent Group Story:', 'group_story' ),
	        'menu_name' => _x( 'Group Stories', 'group_story' ),
	    );

	    $args = array( 
	        'labels' => $labels,
	        'hierarchical' => false,
	        'description' => 'Used to collect new posts ("Narratives") from spaces.',
	        'supports' => array( 'title', 'editor', 'author', 'revisions' ),
	        'taxonomies' => array( 'post_tag', 'related_groups' ),
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 37,
	        'show_in_nav_menus' => true,
	        'publicly_queryable' => true,
	        'exclude_from_search' => false,
	        'has_archive' => true,
	        'query_var' => true,
	        'can_export' => true,
	        'rewrite' => true,
	        'capability_type' => 'post'
	    );

	    register_post_type( 'group_story', $args );
	}

	/**
	 * Creates the group story custom taxonomy.
	 *
	 * @since    1.0.0
	 */
	public function register_taxonomy_related_groups() {

	    $labels = array( 
	        'name' => _x( 'Related Groups', 'related_groups' ),
	        'singular_name' => _x( 'Related Group', 'related_groups' ),
	        'search_items' => _x( 'Search Related Groups', 'related_groups' ),
	        'popular_items' => _x( 'Popular Related Groups', 'related_groups' ),
	        'all_items' => _x( 'All Related Groups', 'related_groups' ),
	        'parent_item' => _x( 'Parent Related Group', 'related_groups' ),
	        'parent_item_colon' => _x( 'Parent Related Group:', 'related_groups' ),
	        'edit_item' => _x( 'Edit Related Group', 'related_groups' ),
	        'update_item' => _x( 'Update Related Group', 'related_groups' ),
	        'add_new_item' => _x( 'Add New Related Group', 'related_groups' ),
	        'new_item_name' => _x( 'New Related Group', 'related_groups' ),
	        'separate_items_with_commas' => _x( 'Separate related groups with commas', 'related_groups' ),
	        'add_or_remove_items' => _x( 'Add or remove related groups', 'related_groups' ),
	        'choose_from_most_used' => _x( 'Choose from the most used related groups', 'related_groups' ),
	        'menu_name' => _x( 'Related Groups', 'related_groups' ),
	    );

	    $args = array( 
	        'labels' => $labels,
	        'public' => true,
	        'show_in_nav_menus' => true,
	        'show_ui' => true,
	        'show_tagcloud' => false,
	        'show_admin_column' => true,
	        'hierarchical' => true,
	        'rewrite' => true,
	        'query_var' => true
	    );

	    register_taxonomy( 'related_groups', array('group_story'), $args );
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

	function ccgn_load_template_filter( $found_template, $templates ) {
	    global $bp;
	 
	    // Only filter the template location when we're on the follow component pages.
	    if ( ! ccgn_is_component() )
	        return $found_template;
	 
	    // $found_template is not empty when the older template files are found in the
	    // parent and child theme
	    //
	    // When the older template files are not found, we use our new template method,
	    // which will act more like a template part.
	    if ( empty( $found_template ) ) {
	 
	        // register our theme compat directory
	        //
	        // this tells BP to look for templates in our plugin directory last
	        // when the template isn't found in the parent / child theme
	        bp_register_template_stack( 'ccgn_get_template_directory', 14 );
	 
	        // plugins.php is the preferred template to use, since all we'd need to do is
	        // inject our content into BP
	        //
	        // note: this is only really relevant for bp-default themes as theme compat
	        // will kick in on its own when this template isn't found
	        $found_template = locate_template( 'groups/single/plugins.php', false, false );
	 
	        // add our hook to inject content into BP
	        //
	        // note the new template name for our template part
	        add_action( 'bp_template_content', create_function( '', "
	            bp_get_template_part( 'groups/single/narratives' );
	        " ) );
	    }
	 
	    return apply_filters( 'ccgn_load_template_filter', $found_template );
	}
}