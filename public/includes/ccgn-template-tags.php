<?php
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

function ccgn_related_group_checkboxes( $group_id, $post_id ) {
	// Get terms that can be used by this group
	$possibly_related_groups = ccgn_get_categories( $group_id );
	//Get ids of terms that _are_ related to this post
    //TODO: If mutiple groups can edit a post, you could lose "related groups" association if group 2 can't associate with group 1.
	$related_groups = wp_get_post_terms( $post_id, 'related_groups',  array("fields" => "ids") );

    // print_r($cat_selected);
    if ( empty( $possibly_related_groups ) ){
             _e('This group has no categories associated with it. To post to group blog, first associate one or more categories with it.','bcg');
            return;
    } else { 
    	foreach ( (array) $possibly_related_groups as $possible_relation ) {

    		$checked = ( !empty( $related_groups ) && in_array( $possible_relation, $related_groups ) ) ? true : false ;

       		$term = get_term( $possible_relation , 'related_groups' );

    		?> <label for="related-group-<?php echo $possible_relation; ?>"> <input type="checkbox" name="related-groups[]" id="related-group-<?php echo $possible_relation; ?>" value="<?php echo $possible_relation; ?>" <?php if ( $checked ) { echo 'checked="checked"'; } ?> /> <?php echo $term->name; ?></label>
    		<?php
    	}
    }
}

add_action( 'ccgn_post_actions', 'ccgn_edit_post_link' );
function ccgn_edit_post_link() {
    if ( ccgn_current_user_can_post() ) {
        global $post;
        // var_dump($post);
        echo '<a href="' . ccgn_get_home_permalink() . '/edit/' . $post->ID . '" class="button">Edit</a>';
    }
}