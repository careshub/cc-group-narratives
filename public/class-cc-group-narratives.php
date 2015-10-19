<?php
/**
 * @package   CC Group Narratives
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
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
	const VERSION = '1.1.0';

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
		// Needs to happen before bp_init! (bp_init happens at 'init' p10)
		add_action( 'init', array( $this, 'register_cpt_group_story' ), 7 );
		add_action( 'init', array( $this, 'register_taxonomy_related_groups' ), 7 );

		// Modify permalinks so that they point to the story as persented in the origin group
		add_filter( 'post_type_link', array( $this, 'narrative_permalink_filter'), 10, 2);

		// Add filter to catch removal of a story from a group
		add_action( 'bp_init', array( $this, 'remove_story_from_group'), 75 );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Load style sheet and JavaScript for narrative edit screen.
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_edit_styles' ) );

		// We need to stop the evaluation of shortcodes on this plugin's group settings screen. If they're interpreted for display, then the code is consumed and lost upon the next save.
		add_action( 'bp_init', array( $this, 'remove_shortcode_filter_on_settings_screen') );

		// Handle redirects after submitting a comment
		// Typically the user is redirected to the permalink location, but, in this case, we want to redirect back to the referring page (the permalink might go to a different group).
		add_action( 'comment_form', array( $this, 'comments_add_redirect_to' ) );

		// Delete activity stream items when narrative are deleted.
		add_action( 'transition_post_status', array( $this, 'delete_post_activity' ), 10, 3 );

		/* Filter "map_meta_caps" to let our users do things they normally can't, like upload media */
		add_action( 'bp_init', array( $this, 'add_mmc_filter') );

		/* Only allow users to see their own items in the media library uploader. */
		// This functionality is shared between several plugins and has been moved to a standalone plugin "CC Manage Media and Permissions"

		//Filter plugin template
		//TODO: finish template stack logic - archive/search template???
		//TODO: Fails with BP group hierarcy
		// add_filter( 'bp_located_template', array( $this, 'load_template_filter'), 10, 2 );
		// Redux
		// Add our templates to BuddyPress' template stack.
		add_filter( 'bp_get_template_stack', array( $this, 'add_template_stack'), 10, 1 );

		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_edit_scripts' ), 98 );
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_media_scripts' ), 98 );
		// add_filter('media_view_strings', array( $this, 'custom_media_strings' ), 10, 2);
		// add_action( 'wp_footer', array( $this, 'print_media_controller_templates' ), 98 );

		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_media_stripped_scripts' ), 98 );
		// add_filter( 'media_view_settings', array( $this, 'my_media_view_settings'), 10, 2  );
		// Override relevant media manager javascript functions
		// add_action( 'wp_footer', array( $this, 'my_override_filter_object'), 51 );
		// add_action( 'wp_ajax_query-attachments', array( $this, 'my_wp_ajax_query_attachments'), 1 );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		// add_action( '@TODO', array( $this, 'action_method_name' ) );
		// add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		// @TODOs: Features to add
		// Post locking while the post is being edited.
			// Set a transient on loading the form (match the transient set by the typical WP editor)
			// Clear it on page unload. How to avoid fogotten locks? heartbeat API?
		// Activity stream updates:
			// Whomever created a new narrative
			// updated a narrative -- avoid repeats.

		// add_action( 'wp_footer', array( $this, 'print_media_templates' ) );
  //       add_action( 'wp_footer', array( $this, 'print_footer_scripts' ), 100 );
	 //    add_action( 'wp_ajax_dtbaker_mce_banner_button', array( $this, 'wp_ajax_dtbaker_mce_banner_button' ) );
	    // add_filter("mce_external_plugins", array($this, 'mce_plugin'));
	    // add_filter("mce_buttons", array($this, 'mce_button'));

	    // Change behavior of link button in wp_editor
	    // First, remove many of the post types from the query
	    add_filter( 'wp_link_query_args', array( $this, 'limit_link_suggestion_query' ) );
	    // Add back in docs, maps & reports
	    add_filter( 'wp_link_query', array( $this, 'filter_link_suggestions' ), 14, 2 );

		require_once( plugin_dir_path( __FILE__ ) . '/views/public.php' );


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
	        'name' => _x( 'Hub Stories', 'group_story' ),
	        'singular_name' => _x( 'Hub Story', 'group_story' ),
	        'add_new' => _x( 'Add New', 'group_story' ),
	        'add_new_item' => _x( 'Add New Hub Story', 'group_story' ),
	        'edit_item' => _x( 'Edit Hub Story', 'group_story' ),
	        'new_item' => _x( 'New Hub Story', 'group_story' ),
	        'view_item' => _x( 'View Hub Story', 'group_story' ),
	        'search_items' => _x( 'Search Hub Stories', 'group_story' ),
	        'not_found' => _x( 'No hub stories found', 'group_story' ),
	        'not_found_in_trash' => _x( 'No hub stories found in Trash', 'group_story' ),
	        'parent_item_colon' => _x( 'Parent Hub Story:', 'group_story' ),
	        'menu_name' => _x( 'Hub Stories', 'group_story' ),
	    );

	    $args = array(
	        'labels' => $labels,
	        'hierarchical' => false,
	        'description' => 'Used to collect new posts ("Narratives") from groups.',
	        'supports' => array( 'title', 'editor', 'author', 'revisions', 'comments' ),
	        'taxonomies' => array( 'post_tag', 'ccgn_related_groups' ),
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 52,
	        'show_in_nav_menus' => true,
	        'publicly_queryable' => true,
	        'exclude_from_search' => false,
	        'has_archive' => true,
	        'query_var' => true,
	        'can_export' => true,
	        'rewrite' => false,
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
	        'name' => _x( 'CCGN Related Groups', 'related_groups' ),
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
	        'menu_name' => _x( 'CCGN Related Groups', 'related_groups' ),
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

	    register_taxonomy( 'ccgn_related_groups', array('group_story'), $args );
	}

	/**
	 * Creates the rewrites necessary so that the group is really where this stuff lives.
	 *
	 * @since    1.0.0
	 */
	function narrative_permalink_filter( $permalink, $post ) {

	    if ( 'group_story' == get_post_type( $post )  ) {
	    	$group_id = ccgn_get_origin_group( $post->ID );
	        $permalink = ccgn_get_base_permalink( $group_id ) . $post->post_name;
	    }

	    return $permalink;
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if ( function_exists( 'bp_is_groups_component' ) && ccgn_is_component() )
			wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueue public-facing edit style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_edit_styles() {
		if ( function_exists( 'bp_is_groups_component' ) && ccgn_is_post_edit() )
			wp_enqueue_style( $this->plugin_slug . 'editor-plugin-styles', plugins_url( 'assets/css/edit.css', __FILE__ ), array(), self::VERSION );
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
	 * Register and enqueues JavaScript files necessary for group narrative edit page.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_edit_scripts() {
		if ( ccgn_is_post_edit() )  {
			// wp_enqueue_media();
			wp_enqueue_script( $this->plugin_slug . '-plugin-edit-script', plugins_url( 'assets/js/narrative-edit.js', __FILE__ ), array( 'jquery', 'underscore', 'backbone', 'mce-view' ), self::VERSION );
			// add_action( 'wp_footer', array( $this, 'print_media_controller_templates' ) );
		}
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

	/**
	 * Add filter to catch removal of a story from a group
	 *
	 * @since    1.0.0
	 */
	public function remove_story_from_group() {
		// Fires on bp_init action, so this is a catch-action type of filter.
		// Bail out if this isn't the narrative component.
		if ( ! ccgn_is_component() )
			return false;

	    // Set up an array of BP's action variables
		$action_variables = bp_action_variables();

		//Handle delete actions: Removing story from group
		if ( bp_is_action_variable( 'remove-story', 0 ) ) {

			// Is the nonce good?
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'ccgn-remove-story-from-group' ) )
				return false;

			// Parse the URI to create our necessary variables
			$post_id = bp_action_variable( 1 );

			// Is this user allowed to remove this item?
			if ( ! ccgn_current_user_can_moderate( $post_id ) )
				return false;

		   	// Get this group's term and disassociate it from the post
		    if ( $group_term_id = ccgn_get_group_term_id() ) {
		    	$success = wp_remove_object_terms( $post_id, $group_term_id, 'ccgn_related_groups' );
		    }
		    // On successful removal, we should delete related activity items.
	    	if ( $success ) {
	    		if ( $group_id = bp_get_current_group_id() ) {
		    		bp_activity_delete( array(
						'component'         => buddypress()->groups->id,
						'type'              => 'group_story_created',
						'item_id'           => $group_id,
						'secondary_item_id' => $post_id,
						) );
		    	}
	    	}

		    if ( $success && ! is_wp_error( $success ) ) {
   				bp_core_add_message( __( 'Successfully removed the item.', $this->plugin_slug ) );
		    } else {
				bp_core_add_message( __( 'Could not remove the item.', $this->plugin_slug ), 'error' );
		    }

			// Redirect and exit
			bp_core_redirect( wp_get_referer() );

			return false;
		}

	}

	/**
	 * We need to stop the evaluation of shortcodes on this plugin's group settings screen.
	 * If they're interpreted for display, then the code is consumed and lost upon the next save.
	 *
	 * @since    1.0.0
	 */
	public function remove_shortcode_filter_on_settings_screen() {
	      if ( ccgn_is_post_edit() ) {
	        	remove_filter( 'the_content', 'do_shortcode', 11);
	      }
	}

	/**
	 * Add a hidden "redirect_to" input on the comment form if it's a group story
	 * This ensures that commenters are returned to the url they commented from.
	 * (And not swept away to the origin group's view of the post.)
	 *
	 * @since    1.0.0
	 */
	public function comments_add_redirect_to( $post_id ){
		if ( 'group_story' != get_post_type( $post_id )  )
			return;

		$current_url = home_url( $_SERVER['REQUEST_URI'] );
		?>
		<input type="hidden" name="redirect_to" value="<?php echo $current_url ?>" />
		<?php
	}

	/**
	 * Delete activity associated with a post
	 *
	 * Run on transition_post_status, to catch deletes from all locations
	 *
	 * @since 1.0.0
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param obj WP_Post object
	 */
	public function delete_post_activity( $new_status, $old_status, $post ) {

		// Only work on group_narrative post types
		if ( 'group_story' != $post->post_type ) {
			return;
		}

		// Only when they change from publish. Fire on change to trash, draft.
		if ( ! ( $new_status != 'publish' && $old_status == 'publish' ) ) {
			return;
		}

		bp_activity_delete( array(
			'component'         => buddypress()->groups->id,
			'type'              => $post->post_type . '_created',
			'secondary_item_id' => $post->ID,
			) );
	}

	/**
	 * Filter "map_meta_caps" to let our users do things they normally can't.
	 *
	 * @since    1.0.0
	 */
	public function add_mmc_filter() {
		if ( ccgn_is_post_edit() || ( isset( $_POST['action'] ) && $_POST['action'] == 'upload-attachment' ) ) {
		    add_filter( 'map_meta_cap', array( $this, 'setup_map_meta_cap' ), 14, 4 );
		}
	}

	/**
	 * Filter "map_meta_caps" to let our users do things they normally can't.
	 * This enables the media button on the post edit form (allows an ordinary user to add media).
	 *
	 * @since    1.0.0
	 */
	public function setup_map_meta_cap( $primitive_caps, $meta_cap, $user_id, $args ) {
		// In order to upload media, a user needs to have caps.
		// Check if this is a request we want to filter.
		if ( ! in_array( $meta_cap, array( 'upload_files', 'edit_post', 'delete_post' ) ) ) {
	        return $primitive_caps;
	    }

		// It would be useful for a user to be able to delete her own uploaded media.
	    // If this is someone else's post, we don't want to allow deletion of that, though.
	    if ( $meta_cap == 'delete_post' && in_array( 'delete_others_posts', $primitive_caps ) ) {
	        return $primitive_caps;
	    }

	  	// We pass a blank array back, meaning there's no capability required.
	    $primitive_caps = array();

		return $primitive_caps;
	}

	/** TEMPLATE LOADER ************************************************/
	/**
	* If a template does not exist in the current theme, we will use our own
	* bundled templates.
	*
	* We're doing two things here:
	* 1) Support the older template format for themes that are using them
	* for backwards-compatibility (the template passed in
	* {@link bp_core_load_template()}).
	* 2) Route older template names to use our new template locations and
	* format.
	*
	* From work by r-a-y
	*
	* @since 1.1
	*/
	function load_template_filter( $found_template, $templates ) {
		// Only filter the template location when we're on the group invitation screen, the group creation invite-anyone step, or the members component
		if ( ! ccgn_is_component() )
			return $found_template;

		// $found_template may not empty if template files were found in the
		// parent or child theme
		if ( empty( $found_template ) ) {
			// locate_template() will attempt to find the plugins.php template in the
			// child and parent theme and return the located template when found
			$found_template = locate_template( '/groups/single/plugins.php', false, false );
		}

		// Register our theme compat directory.
		// This tells BP to look for templates in our plugin directory
		// if the template isn't found in the parent/child theme.
		bp_register_template_stack( array( $this, 'get_template_directory' ), 14 );

		if ( 1 == 1 ) {
			// add our hook to inject content into BP's group screen
			add_action( 'bp_template_content', create_function( '', "
				bp_get_template_part( 'groups/single/narratives' );
			" ) );
		}

		return apply_filters( 'ccgn_load_template_filter', $found_template );
	}

	/**
	* Get the template directory.
	*
	* @since 1.1
	*
	* @uses apply_filters()
	* @return string
	*/
	function get_template_directory() {
	return apply_filters( 'ccgn_get_template_directory', plugin_dir_path( __FILE__ ) . 'includes/templates' );
	}

	/**
	 * Add our templates to BuddyPress' template stack.
	 *
	 * @since    1.0.0
	 */
	public function add_template_stack( $templates ) {
	    // if we're on a page of our plugin and the theme is not BP Default, then we
	    // add our path to the template path array
	    if ( ccgn_is_component() ) {
	        $templates[] = trailingslashit( $this->get_template_directory() );
	    }

	    return $templates;
	}

	/**
	* Change what populates the "link to existing content" box in the wp_editor instance.
	*
	* @since 1.1
	*
	* @uses apply_filters()
	* @return string
	*/
	function limit_link_suggestion_query( $query ) {

		if ( ! ccgn_is_post_edit() )
			return $query;

	    // $towrite = PHP_EOL . 'group id: ' . print_r( bp_get_current_group_id(), TRUE );
	    // $towrite .= PHP_EOL . 'query, before' .  print_r($query, TRUE);
	    // Limit the post types that are queried
	    // We'll want to include bp_docs and group_stories, but they have weird queries (group-related) so we'll add them back in at the 'wp_link_query' filter.
	    $query['post_type'] = array();

   	    // If the search is included in the query, wp will find nothing and things break. Nice.
   	    if ( isset( $query['s'] ) ) {
	   	    $query['keyphrase'] = $query['s'];
	   	    unset( $query['s'] );
	   	}

   	 //    $towrite .= PHP_EOL . 'query, after' .  print_r($query, TRUE);
 	   //  $fp = fopen('link-to.txt', 'a');
	    // fwrite($fp, $towrite);
	    // fclose($fp);

	    return $query;
	}

	/**
	* Change what populates the "link to existing content" box in the wp_editor instance.
	*
	* @since 1.1
	*
	* @uses apply_filters()
	* @return string
	*/
	function filter_link_suggestions( $results, $query ) {

		if ( ! ccgn_is_post_edit() )
			return $results;

		// We're replacing the suggestions, so start with a blank slate.
		$results = array();

		// Fetch allowable bp_docs, maps, reports
		$docs = ccgn_get_shareable_docs();
		$maps = ccgn_get_shareable_maps_reports( $group_id = null, $type = 'map' );
		$reports = ccgn_get_shareable_maps_reports( $group_id = null, $type = 'report' );

		$results = array_merge( $docs, $maps, $reports );

		// Sort the results by datetime, descending
		// Create the sort column array for array_multisort to use
		foreach ( $results as $key => $value ) {
		    $datetime[$key]  = $value['datetime'];
		}
		// Add $results as the last parameter, to sort by the common key
		array_multisort( $datetime, SORT_DESC, $results );

		// Oh, handle search terms if included. Not awesome (doesn't search content).
		if ( isset( $query['keyphrase'] ) ) {
			$found = array();
			foreach ($results as $result) {
				if ( stripos( $result['title'], $query['keyphrase'] ) !== false ) {
					$found[] = $result;
				}
			}
			$results = $found;
		}

		// Return the correct records, based on the query.
		$results = array_slice( $results, $query['offset'], $query['posts_per_page'] );

	    // $towrite = PHP_EOL . 'results' . print_r($results, TRUE);
   	    // $towrite .= PHP_EOL . 'query' . print_r($query, TRUE);
  	    // $fp = fopen('link-to.txt', 'a');
	    // fwrite($fp, $towrite);
	    // fclose($fp);

	    return $results;
	}

	/* Work on media popup below ******************************************/


	// add_action('admin_enqueue_scripts', 'custom_add_script');
	public function enqueue_media_scripts(){
		// wp_enqueue_script('cc-narrative-media-menu', plugins_url('assets/js/media_menu.js', __FILE__), array('media-views'), false, true);
	}
	// add_action('admin_enqueue_scripts', 'custom_add_script');
	public function enqueue_media_stripped_scripts(){
		// wp_enqueue_script('cc-narrative-media-menu', plugins_url('assets/js/media_menu_stripped.js', __FILE__), array( 'jquery', 'underscore', 'backbone' ), false, true);
	}
	/*
	public function custom_media_strings( $strings, $post ){
		$strings['customMenuTitle'] = __('Custom Menu Title', 'custom');
		$strings['customButton'] = __('Custom Button', 'custom');
		return $strings;
	}

	public function print_media_controller_templates() {
	?>
	<script type="text/html" id="tmpl-thing-details">
		<div class="media-embed">
			<div class="embed-media-settings">
				<label class="setting">
					<span><?php _e( 'Name' ); ?></span>
					<input type="text" data-setting="name" value="{{ data.name }}" />
				</label>
				<label class="setting">
					<span><?php _e( 'Favorite Color' ); ?></span>
					<input type="text" data-setting="color" value="{{ data.color }}" />
				</label>
			</div>
		</div>
	</script>

	<script type="text/html" id="tmpl-thing-too">
		<div class="media-embed">
			<div class="embed-media-settings">
				<p>Name: {{ data.model.name }}<br/>Favorite Color: {{ data.model.color }}</p>
			</div>
		</div>
	</script>

	<script type="text/html" id="tmpl-editor-thing">
		<div class="toolbar">
			<div class="dashicons dashicons-edit edit"></div>
			<div class="dashicons dashicons-no-alt remove"></div>
		</div>
		<p>Name: {{ data.name }}<br/>Favorite Color: {{ data.color }}</p>
	</script>
	<?php
	}

	public function my_media_view_settings($settings, $post) {
    $towrite = PHP_EOL . 'before assignment: ' . print_r($settings, TRUE);
    $fp = fopen('media_view_settings.txt', 'a');
    fwrite($fp, $towrite);
    fclose($fp);

	    $post_types = array('post' => 'Posts', 'page' => 'Pages');

	    // Add in post types
	    foreach ($post_types as $slug => $label) {
	        if ($slug == 'attachment') continue;
	        $settings['postTypes'][$slug] = $label;
	    }

    $towrite = PHP_EOL . 'after assignment: ' . print_r($settings, TRUE);
    $fp = fopen('media_view_settings.txt', 'a');
    fwrite($fp, $towrite);
    fclose($fp);

	    return $settings;
	}

	public function my_override_filter_object() { ?>
	    <script type="text/javascript">
	    // Add custom post type filters
	    l10n = wp.media.view.l10n = typeof _wpMediaViewsL10n === 'undefined' ? {} : _wpMediaViewsL10n;
	    wp.media.view.AttachmentFilters.Uploaded.prototype.createFilters = function() {
	        var type = this.model.get('type'),
	            types = wp.media.view.settings.mimeTypes,
	            text;
	        if ( types && type )
	            text = types[ type ];

	        filters = {
	            all: {
	                text:  text || l10n.allMediaItems,
	                props: {
	                    uploadedTo: null,
	                    orderby: 'date',
	                    order:   'DESC'
	                },
	                priority: 10
	            },

	            uploaded: {
	                text:  l10n.uploadedToThisPost,
	                props: {
	                    uploadedTo: wp.media.view.settings.post.id,
	                    orderby: 'menuOrder',
	                    order:   'ASC'
	                },
	                priority: 20
	            }
	        };
	        // Add post types only for gallery
	        if (this.options.controller._state.indexOf('gallery') !== -1) {
	            delete(filters.all);
	            filters.image = {
	                text:  'Images',
	                props: {
	                    type:    'image',
	                    uploadedTo: null,
	                    orderby: 'date',
	                    order:   'DESC'
	                },
	                priority: 10
	            };
	            _.each( wp.media.view.settings.postTypes || {}, function( text, key ) {
	                filters[ key ] = {
	                    text: text,
	                    props: {
	                        type:    key,
	                        uploadedTo: null,
	                        orderby: 'date',
	                        order:   'DESC'
	                    }
	                };
	            });
	        }
	        this.filters = filters;

	    }; // End create filters
	    </script>
	<?php
	}

	public function my_wp_ajax_query_attachments() {
	    if ( ! current_user_can( 'upload_files' ) )
	        wp_send_json_error();

	    $query = isset( $_REQUEST['query'] ) ? (array) $_REQUEST['query'] : array();
	    $query = array_intersect_key( $query, array_flip( array(
	        's', 'order', 'orderby', 'posts_per_page', 'paged', 'post_mime_type',
	        'post_parent', 'post__in', 'post__not_in',
	    ) ) );

	    if (isset($query['post_mime_type']) && ($query['post_mime_type'] != "image")) {
	        // post type
	        $query['post_type'] = $query['post_mime_type'];
	        $query['post_status'] = 'publish';
	        unset($query['post_mime_type']);
	    } else {
	        // image
	        $query['post_type'] = 'attachment';
	        $query['post_status'] = 'inherit';
	        if ( current_user_can( get_post_type_object( 'attachment' )->cap->read_private_posts ) )
	            $query['post_status'] .= ',private';
	    }

	    $query = apply_filters( 'ajax_query_attachments_args', $query );
	    $query = new WP_Query( $query );

	    // $posts = array_map( 'wp_prepare_attachment_for_js', $query->posts );
	    $posts = array_map( $this->my_prepare_items_for_js, $query->posts );
	    $posts = array_filter( $posts );

	    wp_send_json_success( $posts );
	}

	function my_prepare_items_for_js($item) {
	    switch($item->post_type) {
	    case 'attachment':
	        return wp_prepare_attachment_for_js($item);
	    case 'post':
	    case 'page':
	    case 'gallery':
	    default:
	        return $this->my_prepare_post_for_js($item);
	    }
	}

	function my_prepare_post_for_js( $post ) {
	    if ( ! $post = get_post( $post ) )
	        return;

	    $attachment_id = get_post_thumbnail_id( $post->ID );
	    $attachment = get_post($attachment_id);
	    $post_link = get_permalink( $post->ID );

	    $type = $post->post_type; $subtype = 'none';
	    if ($attachment) {
	        $url = wp_get_attachment_url( $attachment->ID );
	    } else { // Show default image
	        $url = includes_url('images/crystal/default.png');
	    }

	    $response = array(
	        'id'          => $post->ID,
	        'title'       => $post->post_title,
	        'filename'    => wp_basename( $post_link ),
	        'url'         => $url,
	        'link'        => $post_link,
	        'alt'         => '',
	        'author'      => $post->post_author,
	        'description' => $post->post_content,
	        'caption'     => $post->post_excerpt,
	        'name'        => $post->post_name,
	        'status'      => $post->post_status,
	        'uploadedTo'  => $post->post_parent,
	        'date'        => strtotime( $post->post_date_gmt ) * 1000,
	        'modified'    => strtotime( $post->post_modified_gmt ) * 1000,
	        'menuOrder'   => '', // $attachment->menu_order,
	        'mime'        => '', // $attachment->post_mime_type,
	        'type'        => $type,
	        'subtype'     => $subtype,
	        'icon'        => $url, // wp_mime_type_icon( $attachment_id ),
	        'dateFormatted' => mysql2date( get_option('date_format'), $post->post_date ),
	        'nonces'      => array(
	            'update' => false,
	            'delete' => false,
	        ),
	        'editLink'   => false,
	    );

	    // Don't allow delete or update for posts. So don't create nonces.

	    return apply_filters( 'wp_prepare_post_for_js', $response, $post );
	}
	*/

	/**
     * Outputs the view inside the wordpress editor.
     */
    public function print_media_templates() {
        // if ( ! isset( get_current_screen()->id ) || get_current_screen()->base != 'post' )
        //     return;
        ?>
        <script type="text/html" id="tmpl-editor-boutique-banner">
			<div class="boutique_banner_{{ data.type }}"></div>
	        <div class="full_banner" id="banner_{{ data.id }}">
			    <span class="title">{{ data.title }}</span>
			    <span class="content">{{ data.innercontent }}</span>
		        <# if ( data.link ) { #>
		            <# if ( data.linkhref ) { #>
			            <a href="{{ data.linkhref }}" class="link dtbaker_button_light">{{ data.link }}</a>
					<# } #>
				<# } #>
			</div>
		</script>
        <?php
    }
    public function print_footer_scripts() {
        // if ( ! isset( get_current_screen()->id ) || get_current_screen()->base != 'post' )
        //     return;
        ?>
	    <script type="text/javascript">
		    (function($){
			    var media = wp.media, shortcode_string = 'boutique_banner';
			    wp.mce = wp.mce || {};
			    wp.mce.boutique_banner = {
				    shortcode_data: {},
					View: {
						template: media.template( 'editor-boutique-banner' ),
						postID: $('#post_ID').val(),
						initialize: function( options ) {
							this.shortcode = options.shortcode;
							wp.mce.boutique_banner.shortcode_data = this.shortcode;

						},
						getHtml: function() {
							var options = this.shortcode.attrs.named;
							options['innercontent'] = this.shortcode.content;
							return this.template(options);
						}
					},
				    edit: function( node ) {
						var data = window.decodeURIComponent( $( node ).attr('data-wpview-text') );
					    console.debug(this);
					    var values = this.shortcode_data.attrs.named;
						values['innercontent'] = this.shortcode_data.content;
					    console.log(values);

					    wp.mce.boutique_banner.popupwindow(tinyMCE.activeEditor, values);
						//$( node ).attr( 'data-wpview-text', window.encodeURIComponent( shortcode ) );
					},
				    // this is called from our tinymce plugin, also can call from our "edit" function above
				    // wp.mce.boutique_banner.popupwindow(tinyMCE.activeEditor, "bird");
				    popupwindow: function(editor, values, onsubmit_callback){
					    if(typeof onsubmit_callback != 'function'){
						    onsubmit_callback = function( e ) {
		                        // Insert content when the window form is submitted (this also replaces during edit, handy!)
							    var s = '[' + shortcode_string;
							    for(var i in e.data){
								    if(e.data.hasOwnProperty(i) && i != 'innercontent'){
									    s += ' ' + i + '="' + e.data[i] + '"';
								    }
							    }
							    s += ']';
							    if(typeof e.data.innercontent != 'undefined'){
								    s += e.data.innercontent;
								    s += '[/' + shortcode_string + ']';
							    }
		                        editor.insertContent( s );
		                    };
					    }
		                editor.windowManager.open( {
		                    title: 'Banner',
		                    body: [
			                    {
			                        type: 'textbox',
			                        name: 'title',
			                        label: 'Title',
				                    value: values['title']
		                        },
			                    {
			                        type: 'textbox',
			                        name: 'link',
			                        label: 'Button Text',
				                    value: values['link']
		                        },
			                    {
			                        type: 'textbox',
			                        name: 'linkhref',
			                        label: 'Button URL',
				                    value: values['linkhref']
		                        },
			                    {
			                        type: 'textbox',
			                        name: 'innercontent',
			                        label: 'Content',
				                    value: values['innercontent']
		                        }
		                    ],
		                    onsubmit: onsubmit_callback
		                } );
				    }
				};
			    wp.mce.views.register( shortcode_string, wp.mce.boutique_banner );
			}(jQuery));
	    </script>

        <?php
    }

}