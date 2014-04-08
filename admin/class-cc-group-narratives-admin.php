<?php
/**
 * Plugin Name.
 *
 * @package   CC Group Narratives Admin
 * @author    David Cavins
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Your Name or Company Name
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package Plugin_Name_Admin
 * @author  David Cavins
 */
class CC_Group_Narratives_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	// Used for the Meta box functions
	private $nonce = 'group_stories_custom_meta_box_nonce';
    private $meta_box_name = 'group_stories_custom_meta_box';

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		/*
		 * Call $plugin_slug from public plugin class.
		 *
		 * @TODO:
		 *
		 * - Rename "Plugin_Name" to the name of your initial plugin class
		 *
		 */
		$plugin = CC_Group_Narratives::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		// add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		// add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		// add_action( '@TODO', array( $this, 'action_method_name' ) );
		// add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		// Meta box functionality
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'meta_save' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Plugin_Name::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @TODO:
	 *
	 * - Rename "Plugin_Name" to the name your plugin
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Plugin_Name::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * @TODO:
		 *
		 * - Change 'Page Title' to the title of your plugin admin page
		 * - Change 'Menu Text' to the text for menu item for the plugin settings page
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Page Title', $this->plugin_slug ),
			__( 'Menu Text', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

	    /**
     * Adds the meta box container.
     */
    public function add_meta_box() {

        add_meta_box( 
            $this->meta_box_name, 
            'Related Docs', 
            array( $this, 'render_meta_box_content' ), 
            'group_story', 
            'normal', 
            'high'
            ); 
    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box_content( $post ) {
    
        // Add an nonce field so we can check for it later.
        wp_nonce_field( $this->meta_box_name, $this->nonce );

        $meta_field = 'group_story_related_docs';

        // Use get_post_meta to retrieve an existing value from the database.
        $doc_associations = get_post_meta( $post->ID, $meta_field, true ); // Use true to actually get an unserialized array back

        // Get candidate docs: must be associated with the group, must be readable by anyone. We can search for docs that are associated with the group, then in the while loop ignore those with privacy not "read:anyone"
        
        //This assumes that each group only has one associated category, otherwise we'll have docs crossing over.
        $category_ids = wp_get_post_terms($post->ID, 'related_groups', array("fields" => "ids"));
        $group_ids = $this->get_group_ids( $category_ids[0] );

        $docs_args = array( 'group_id' =>  $group_ids );

        echo '<p class="howto">In order to associate a document with a group story, the doc must be able to be read by anyone and be associated with the group that is producing the story.</p>';
        if ( bp_docs_has_docs( $docs_args ) ) :
            echo '<ul>';
            while ( bp_docs_has_docs() ) : 
                bp_docs_the_doc();
                //Only allow to attach docs that have read set to anyone.
                // $doc = get_post();
                // print_r($doc);
                $doc_id = get_the_ID();
                $settings = bp_docs_get_doc_settings( $doc_id );
                if ( $settings['read'] == 'anyone') { 
                    ?>
                    <li>
                        <input type="checkbox" id="<?php echo $meta_field; ?>-<?php echo $doc_id; ?>" name="<?php echo $meta_field; ?>[]" value="<?php echo $doc_id; ?>" <?php checked( in_array( $doc_id , $doc_associations ) ); ?> />
                        <label for="<?php echo $meta_field; ?>-<?php echo $doc_id ?>"><?php the_title() ?></label>
                    </li>
                    <?php
                    // the_title();
                    // echo '<pre>' . PHP_EOL;
                    // print_r($settings);
                    // echo '</pre>';                
                }
                
            endwhile;
            echo '</ul>';
        endif;

        // Display the form, using the current value.
        ?>
        <!-- <label for="<?php echo $meta_field; ?>" class="description"><h4>Featured video URL</h4>
            <em>e.g.: http://www.youtube.com/watch?v=UueU0-EFido</em></label><br />
        <input type="text" id="<?php echo $meta_field; ?>" name="<?php echo $meta_field; ?>" value="<?php echo esc_attr( $value); ?>" size="75" /> -->

<?php
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function meta_save( $post_id ) {
    
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // First, make sure the user can save the post and only fire when editing this post type
        if( get_post_type( $post_id ) == 'group_story' && $this->user_can_save( $post_id, $this->nonce ) ) {

            $meta_field = 'group_story_related_docs';
                    
            // Sanitize the user input.
            // $input = sanitize_text_field( $_POST[ $meta_field ] );

            // Update the meta field.
            // update_post_meta( $post_id, $meta_field, $input );

            if ( empty($_POST[ $meta_field ]) ) {
                //If this element of POST is empty, then we should delete any stored values if they exist
                delete_post_meta($post_id, $meta_field);
            }

            if ( !empty($_POST[ $meta_field ]) && is_array($_POST[ $meta_field ]) ) {
                    // delete_post_meta( $post_id, $meta_field );
                    // foreach ($_POST[ $meta_field ] as $association) {
                        update_post_meta($post_id, $meta_field, $_POST[ $meta_field ] );
                    // }
                }

        }

    }

    /*--------------------------------------------*
     * Helper Functions
     *--------------------------------------------*/

    /**
     * Determines whether or not the current user has the ability to save meta data associated with this post.
     *
     * @param       int     $post_id    The ID of the post being save
     * @param       bool                Whether or not the user has the ability to save this post.
     */
    public function user_can_save( $post_id, $nonce ) {
        
        // Don't save if the user hasn't submitted the changes
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return false;
        } // end if

        // Verify that the input is coming from the proper form
        if( ! wp_verify_nonce( $_POST[ $nonce ], $this->meta_box_name ) ) {
            return false;
        } // end if

        // Make sure the user has permissions to post
        // if( 'post' == $_POST['post_type'] ) {
        //  if( ! current_user_can( 'edit_post', $post_id ) ) {
        //      return;
        //  } // end if
        // } // end if/else

        return true;
     
    } // end user_can_save

    public function get_group_ids( $category_id ) {
        //Todo: This will need to be updated when we switch to buddyforms.
        // Getting the associated group is going to be kind of funky, since the blog_categories plugin stores the group => associated categories as serialized data.

        global $wpdb, $bp;
        // We want to look for meta_value LIKE '%\"1132\"%' so weve got to do some wrapping
        $category_id = '%"' . $category_id . '"%';
 
        $sql = $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = %s AND meta_value LIKE %s", 'group_blog_cats', $category_id );
 
        return wp_parse_id_list( $wpdb->get_col( $sql ) );
    }

}