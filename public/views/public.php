<?php
/**
 * Represents the view for the public-facing component of the plugin.
 *
 * This typically includes any information, if any, that is rendered to the
 * frontend of the theme when the plugin is activated.
 * @package   CC Group Narratives
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */


//sub menu
function ccgn_options_menu() {
	?>
    <li <?php if ( ccgn_is_home () ) : ?> 
		class="current"<?php endif;?>><a href="<?php echo ccgn_get_home_permalink(); ?>">Narratives</a>
	</li>
    <?php if( ccgn_current_user_can_post() ): ?>
        <li <?php if( ccgn_is_post_edit() ): ?> 
        	class="current"<?php endif;?>><a href="<?php echo ccgn_get_home_permalink(); ?>/edit">Create New Narrative</a>
        </li>
  <?php endif;?>
 <?php
}

/**
 * Generates the possible syndication groups taxonomy list on the post create/edit screen.
 * 
 * @return Unordered list of checkboxes showing terms available for assignment.
 */
function ccgn_related_group_checkboxes( $group_id, $post_id ) {
	// Get terms that can be used by this group
	$possibly_related_groups = ccgn_get_categories( $group_id );
	//Get ids of terms that _are_ related to this post
    //TODO: If mutiple groups can edit a post, you could lose "related groups" association if group 2 can't associate with group 1. Editing should always take place in the origin group.
	$related_groups = wp_get_post_terms( $post_id, 'ccgn_related_groups',  array("fields" => "ids") );
    if ( empty( $possibly_related_groups ) ){
             _e('This group has no categories associated with it. To post to group blog, first associate one or more categories with it.','bcg');
            return;
    } else { 
        // Get this group's term; we'll always want it to be checked for UI transparency
        $home_group_term_id = ccgn_get_group_term_id( $group_id );
        ?>
        <ul class="ccgn-related-groups">
        <?php
    	foreach ( (array) $possibly_related_groups as $possible_relation ) {
    		$checked = ( ( ! empty( $related_groups ) && in_array( $possible_relation, $related_groups ) ) || ( $possible_relation == $home_group_term_id ) ) ? true : false ;
       		$term = get_term( $possible_relation , 'ccgn_related_groups' );
    		?>
            <li>
                <label for="related-group-<?php echo $possible_relation; ?>"> <input type="checkbox" name="related-groups[]" id="related-group-<?php echo $possible_relation; ?>" value="<?php echo $possible_relation; ?>" <?php if ( $checked ) { echo 'checked="checked"'; } ?> /> <?php echo $term->name; ?></label>
            </li>
            <?php
    	}
        ?>
        </ul>
        <?php 
    }
}
function ccgn_group_origin_statement( $post_id = null ) {
    if ( ! $post_id = ( $post_id ) ? $post_id : get_the_ID() )
        return false;
    $origin_group = groups_get_group( array( 'group_id' => ccgn_get_origin_group( $post_id ) ) );

    $group_name = bp_get_group_name( $origin_group );
    $group_permalink = bp_get_group_permalink( $origin_group );

    echo '<span class="origin-group meta">Posted in the hub <a href="' . $group_permalink . '">' .  $group_name . '</a>.</span>';
}

add_action( 'ccgn_post_actions', 'ccgn_edit_post_link' );
function ccgn_edit_post_link() {
    //TODO: I think that the narrative should only be editable from within the group where it originated, so should only be editable by users who are "can_post" for that group.
    $post_id = get_the_ID();

    if ( ccgn_current_user_can_post( $post_id ) ) {
        // Get the origin group
        $origin_group = ccgn_get_origin_group( $post_id );
        // var_dump($post);
        echo '<a href="' . ccgn_get_home_permalink( $origin_group ) . '/edit/' . $post_id . '" class="button">Edit</a>';
    }
}

add_action( 'ccgn_post_actions', 'ccgn_moderate_post_link' );
function ccgn_moderate_post_link() {
    $post_id = get_the_ID();

    if ( ccgn_current_user_can_moderate( $post_id ) )
        echo '<a href="' . wp_nonce_url( ccgn_get_home_permalink() . '/remove-story/' . $post_id , 'ccgn-remove-story-from-group' ) . '" class="button confirm">' . __( 'Remove from hub', 'ccgn-remove-story-from-group' ) . '</a>';
}