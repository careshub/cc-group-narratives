<?php 
/**
 * @package   CC Group Narratives
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */


if ( class_exists( 'BP_Group_Extension' ) ) : // Recommended, to prevent problems during upgrade or when Groups are disabled

class CC_Group_Narratives_Extension extends BP_Group_Extension {
		
		function __construct() {
				$args = array(
					'slug' => ccgn_get_slug(),
					'name' => 'Hub Narratives',
					// 'visibility' => 'public',
					// 'enable_nav_item'   => true,//ccgn_is_enabled(),
					'access' => 'anyone', // BP 2.1 means anyone can visit the tab regardless of group status
					'show_tab' => 'anyone', // BP 2.1 means anyone can see the nav tab regardless of group status
					'nav_item_position' => 105,
					'nav_item_name' => ccgn_get_tab_label(),
					'screens' => array(
							'edit' => array(
								'enabled' => true,
							),
							'create' => array(
									'enabled' => false,
									// 'position' => 100,
							),
							'admin' => array(
									'enabled' => false,
							),


					),
				);
				parent::init( $args );
		}
 
		public function display() {
			// Template location is handled via the template stack. see ccgn_load_template_filter()

			// bp_get_template_part( 'groups/single/narratives' );
			// if( bcg_is_single_post() ) {
			//   echo "is single post";
			//   bp_get_template_part( 'ccgn/single-post.php' );
			// } else if( bcg_is_post_create() ) {
			//   echo "is create";
			//   bp_get_template_part( 'ccgn/create.php' );
			// } else {
			//   echo "is list";
			//   bp_get_template_part( 'ccgn/narrative-list.php' );
			// }
		}
 
		public function settings_screen( $group_id = null ) {
				// $group_id = bp_get_group_id( $group_id );
				$is_enabled = ccgn_is_enabled( $group_id );
				$tab_label = ccgn_get_tab_label( $group_id );
				$slug = ccgn_get_slug( $group_id );
				$level_to_post = ccgn_get_level_to_post( $group_id );

				// Needed to use wp_terms_checklist()
				require_once( ABSPATH.'wp-admin/includes/template.php' );
				?>

				<p>
					Blog Categories for Groups allows your group to have a voice in this site&rsquo;s blog. If enabled, your group will be able to submit posts for publication within a specific category. Posts in this category (or categories) will also be displayed within your group&rsquo;s space.
				</p>
				<p>
					<label for="ccgn_is_enabled"> <input type="checkbox" name="ccgn_is_enabled" id="ccgn_is_enabled" value="1" <?php checked( $is_enabled, true ) ?> /> Enable group narratives for this group.</label>
				</p>

				<?php 
				// Only show the other settings once the plugin has been enabled for this group
				// This is necessary because the term for associating posts to this group is only created upon submit of the "enable" checkbox
				if ( $is_enabled ) : ?>
					<div id="ccgn_settings_details">
	 
					<p>
							<label for='ccgn_tab_label'>Change the BuddyPress group tab label from 'Narratives' to whatever you'd like.</label>
							<input type="text" name="ccgn_tab_label" id="ccgn_tab_label" value="<?php echo esc_html( $tab_label ); ?>" />
					 </p>
					 <?php /* ?>
					 <p>
						<label for='ccgn_url_slug'>Change the slug to something other than 'narratives'.</label>
						<input type="text" name="ccgn_url_slug" id="ccgn_url_slug" value="<?php echo esc_html( $slug ); ?>" />
					 </p>
					 <?php */ ?>
					<p>
							<label for='ccgn_level_to_post'>Who should be able to create new posts?</label>
							<select name="ccgn_level_to_post" id="ccgn_level_to_post">
								<option <?php selected( $level_to_post, "admin" ); ?> value="admin">Group Admins Only</option>
								<option <?php selected( $level_to_post, "mod" ); 
												if ( empty( $level_to_post ) ) { echo 'selected="selected"' ; } 
												?> value="mod">Group Admins and Moderators</option>
								<option <?php selected( $level_to_post, "member" ); ?> value="member">Any Group Member</option> 
							</select>
					 </p>
					 <?php

					// TODO Maybe only site admins should be able to add other groups
					// Handle group associations, which are a taxonomy
					echo "<p>Select other groups that narratives published in this group could be syndicated to.</p>";

					// Get the syndicated groups selected for this group, add this group's term.
					$selected_terms = ccgn_add_this_group_term( ccgn_get_categories( $group_id ), ccgn_get_group_term_id( $group_id ) );

					$tax_args = array(
							'hide_empty'    => false,
					);
					$terms = get_terms( 'ccgn_related_groups', $tax_args );

					// get_terms either returns an array of terms or a WP_Error_Object if there's a problem
					if( ! empty( $terms ) && ! is_wp_error( $terms ) ){
						$args = array(
							'descendants_and_self'  => 0,
							'selected_cats'         => $selected_terms,
							'popular_cats'          => false,
							'walker'                => null,
							'taxonomy'              => 'ccgn_related_groups',
							'checked_ontop'         => false
						);
						wp_terms_checklist( 0, $args ); 
					} else {
						?>
					<div class="error">
							<p>Looks like no group terms are set up yet. Check back later.</p>
					</div>
					<?php
					 }
				?>  
				</div> <!-- End #bcg_settings_details -->
		
			<?php endif; // ends the "enabled" check ?>
		
				<div class="clear"></div>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						//match visibility on page load
						if ( jQuery('#ccgn_is_enabled').is(':checked') ) {
									jQuery('#ccgn_settings_details').show();
							} else {
									jQuery('#ccgn_settings_details').hide();
							}
						//update visibility on change
						jQuery('#ccgn_is_enabled').click(function() {
							if ( jQuery(this).is(':checked') ) {
									jQuery('#ccgn_settings_details').show();
							} else {
									jQuery('#ccgn_settings_details').hide();
							}
						});      
					});
				</script>

		<?php
		}
 
		public function settings_screen_save( $group_id = null ) {
			// First, set up a new taxonomy term if this group doesn't already have one. Kind of painful since we want to keep this list hierarchical. 
			// Two options: update existing term or create new. (Updating could be useful for fixing hierarchy problems.)
			if ( $_POST["ccgn_is_enabled"] ) {
				// Are we using BP Group Hierarchy?
				$hierarchy_active = class_exists( 'BP_Groups_Hierarchy' );

				// Create a group object, using BP Group Hierarchy or not.
				$group_object = $hierarchy_active ? new BP_Groups_Hierarchy( $group_id ) : groups_get_group( array( 'group_id' => $group_id ) );

				$group_name = $group_object->name;
				$term_args['description'] = 'Group narratives associated with ' . $group_name;

				// Check for a term for this group's parent group, set a value for the term's 'parent' arg
				// Depends on BP_Group_Hierarchy being active
				if  ( ( $parent_group_id = $group_object->vars['parent_id'] )  &&  
							( $parent_group_term = get_term_by( 'slug', ccgn_create_taxonomy_slug( $parent_group_id ), 'ccgn_related_groups' ) ) 
						) {
					$term_args['parent'] = (int) $parent_group_term->term_id;
				}

				if ( $existing_term_id = ccgn_get_group_term_id( $group_id ) ) {
					$term_args['name'] = $group_name;
					$term_array = wp_update_term( $existing_term_id, 'ccgn_related_groups', $term_args );
				} else {
					$term_args['slug'] = ccgn_create_taxonomy_slug( $group_id );
					$term_array = wp_insert_term( $group_name, 'ccgn_related_groups', $term_args );
				}
			} // End "is_enabled" check

			$towrite = PHP_EOL . 'submitted: ' . print_r($_POST['tax_input']['ccgn_related_groups'], TRUE); 
			$towrite .= PHP_EOL . 'this group term: ' . print_r($term_array['term_id'], TRUE); 
			$fp = fopen('narrative-taxonomy.txt', 'a');
			fwrite($fp, $towrite);
			fclose($fp);

			// Next, handle relating the group to the right taxonomy terms
			// Make sure that this group is always included - otherwise once the narrative is created it might disappear from the front end
			// TODO: If only site admins can add terms, we may not want to change this unless the submitter is a site admin.
			$group_relations = ccgn_add_this_group_term( $_POST['tax_input']['ccgn_related_groups'], $term_array['term_id'] );

			$towrite = PHP_EOL . 'ready to save: ' . print_r($group_relations, TRUE);    
			$fp = fopen('narrative-taxonomy.txt', 'a');
			fwrite($fp, $towrite);
			fclose($fp);

			if ( ! ccgn_update_categories( $group_id, $group_relations ) || ! ccgn_update_groupmeta( $group_id ) ) {
				bp_core_add_message( __( 'There was an error updating the Group Narratives settings, please try again.', 'ccgn' ), 'error' );
			} else {
				bp_core_add_message( __( 'Group Narratives settings were successfully updated.', 'ccgn' ) );
			}
		}
}
bp_register_group_extension( 'CC_Group_Narratives_Extension' );
 
endif;