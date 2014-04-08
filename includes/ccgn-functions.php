<?php
// Helper functions
/**
 * Get base 
 * 
 * @return boolean
 */
function ccgn_get_home_permalink( $group_id = false ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    $permalink = bp_get_group_permalink( groups_get_group( array( 'group_id' => $group_id ) ) ) .  ccgn_get_slug( $group_id );
    return apply_filters( "ccgn_home_permalink", $permalink, $group_id);
}

/**
 * Is group narratives enabled for this group?
 * 
 * @return boolean
 */
function ccgn_is_enabled( $group_id = false ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    $is_enabled = (bool) groups_get_groupmeta( $group_id, "ccgn_is_enabled" );
    return apply_filters( "ccgn_is_enabled", $is_enabled, $group_id);
}

/**
 * Get the slug of the Narratives tab if set
 * 
 * @return string: slug
 */
function ccgn_get_slug( $group_id = false ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    $slug = groups_get_groupmeta( $group_id, 'ccgn_url_slug' );
    $slug = !empty( $slug ) ? urlencode( $slug ) : 'narratives' ;
    return apply_filters( 'ccgn_get_slug', $slug);
}

/**
 * Get the label of the Narratives tab if set
 * 
 * @return string: label text
 */
function ccgn_get_tab_label( $group_id = false ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    $label = groups_get_groupmeta( $group_id, 'ccgn_tab_label' );
    $label = !empty( $label ) ? $label : 'Narratives' ;
    return apply_filters( 'ccgn_get_tab_label', $label);
}

/**
 * What membership level is required in this group to create a post?
 * 
 * @return string: member, mod or admin (admin is default)
 */
function ccgn_get_level_to_post( $group_id = false ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    $level_to_post = groups_get_groupmeta( $group_id, 'ccgn_level_to_post' );
    $level_to_post = !empty( $level_to_post ) ? $level_to_post : 'admin' ;
    return apply_filters( 'ccgn_get_tab_label', $label);
}

/**
 * Can the current user create and edit a narrative post?
 * 
 * @return boolean
 */
function ccgn_current_user_can_post(){
    $user_id =  bp_loggedin_user_id();
    $group_id =  bp_get_current_group_id();
    $level_to_post = ccgn_get_level_to_post( $group_id );
    $can_post = false;

    if ( $user_id ) {
        switch ( $level_to_post ) {
            case 'member':
                $can_post = groups_is_user_member( $user_id, $group_id );
                break;
            case 'mod':
                $can_post = ( groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id ) );
                break;
            case 'admin':
            default:
                $can_post = groups_is_user_admin( $user_id, $group_id );
                break;
        }
    }
    
    return apply_filters( 'ccgn_current_user_can_post', $can_post, $group_id, $user_id );
}
/**
 * Get the categories available to this group.
 * 
 * @return array of taxonomy ids
 */
function ccgn_get_categories( $group_id = false ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    return maybe_unserialize( groups_get_groupmeta( $group_id, 'ccgn_narrative_tax_terms' ) );
}

/**
 * Set the available categories for this group. Maybe site admins only?
 * 
 * @return boolean
 */
function ccgn_update_categories( $group_id, $cats ){
    $success = false;
    //groups_update_groupmeta returns false if the old value matches the new value, so we'll need to check for that case
    //groups_get_groupmeta sometimes unserializes the data, but not always. No idea why.
    $old_setting = maybe_unserialize( groups_get_groupmeta( $group_id, "ccgn_narrative_tax_terms" ) );
    $serialized_cats = maybe_serialize( $cats );

    switch ( $serialized_cats ) {
    	case ( $cats == $old_setting ) :
    		// No need to resave settings if they're the same
            $success = true;
    		break;
		case ( empty( $serialized_cats ) ) :
			// Remove existing entries
	        $success = groups_delete_groupmeta( $group_id, "ccgn_narrative_tax_terms" );
    		break;	
    	default:
	        $success = groups_update_groupmeta( $group_id, "ccgn_narrative_tax_terms", $serialized_cats );
        	break;
    }
        
    return $success;
}
/**
 * Saves settings, called from settings/admin page
 * 
 * @return boolean
 */
function ccgn_update_groupmeta( $group_id = false ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    $success = false;

    $input = array(
        'ccgn_is_enabled',
        'ccgn_tab_label',
        'ccgn_level_to_post',
        'ccgn_url_slug'       
        );

    foreach( $input as $field ) {
        //groups_update_groupmeta returns false if the old value matches the new value, so we'll need to check for that case
        $old_setting = groups_get_groupmeta( $group_id, $field );
        $new_setting = ( isset( $_POST[$field] ) ) ? $_POST[$field] : '' ;

        switch ( $new_setting ) {
        	case ( $new_setting == $old_setting ) :
        		// No need to resave settings if they're the same
	            $success = true;
        		break;
    		case ( empty( $new_setting ) ) :
    			// Remove existing entries
	            $success = groups_delete_groupmeta( $group_id, $field );
        		break;	
        	default:
        		$success = groups_update_groupmeta( $group_id, $field, $new_setting );
        		break;
        }
        
    }
    return $success;
}

//get the appropriate query for various screens
function ccgn_get_query(){
    $bp = buddypress();
    $cats = ccgn_get_categories( $bp->groups->current_group->id );

    if( !empty( $cats ) )
        $cats_list = join( ",", $cats );
    else return "name=-1";//we know it will not find anything
 
    if( ccgn_is_single_post() ){
        $slug = $bp->action_variables[0];
        // return "name=".$slug;//"&cat=".$cats_list;
        $query = array(
            'name' => $slug,
            'post_type' => 'group_story'
        );
        
        return apply_filters("ccgn_get_query",$query);
    }

    $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
    // $query= "related_groups=".$cats_list;
    $query = array(
        'tax_query' => array(
            array(
                'taxonomy' => 'related_groups',
                'field' => 'id',
                'terms' => array( $cats_list ),
                'operator' => 'IN'
            )
        )
    );

    return apply_filters( "ccgn_get_query", $query );
}

/**
 * Where are we? Who am I? What is this beautiful house?
 * Helper functions to determine what is going on/ what action is being requested.
 * @return bool 
 */
function ccgn_is_component(){
    if ( bp_is_groups_component() && bp_is_current_action( ccgn_get_slug() ) )
        return true;
    
    return false;
}
//is bcg_home
function ccgn_is_home(){
    if ( ccgn_is_component() && ! ( bp_action_variables() ) )
        return true;

    return false;
}
function ccgn_is_single_post(){
    $action_variables = bp_action_variables();
    // var_dump($action_variables);
    if ( ccgn_is_component() && !empty( $action_variables ) && ( !in_array( $action_variables[0], array( 'edit','category' ) ) ) )
        return true;

    return false;
}
function ccgn_is_post_edit(){
    $action_variables = bp_action_variables();
    // var_dump($action_variables);

    if ( ccgn_is_component() && !empty( $action_variables ) && $action_variables[0]=='edit' )
        return true;

    return false;
}

//post form if one quick pot is installed
function ccgn_get_post_form( $group_id = false ){
    //TODO: check whether user can do this.

    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;

    //If the $_POST array is set, we should save the post and redirect to the edit page.
    if ( $_POST )
        ccgn_save_narrative( $group_id );

    //Edit page functionality
    $actions = bp_action_variables();

    if ( 'edit' == $actions[0] ) {
        if ( !( $actions[1] ) ) {
            // This is a new post and we need to auto-draft it.
            $post_id = wp_insert_post( array( 'post_title' => __( 'Auto Draft' ), 'post_type' => 'group_story', 'post_status' => 'auto-draft' ) );

        } else {
            //This is an existing post and we need to pre-fill the form
            $post_id = (int) $actions[1];
            $post = get_post( $post_id, OBJECT, 'edit' );
            $post_content = $post->post_content;
            $post_title = $post->post_title;
            $post_published = $post->post_status;
        }
    }
    ?>

    <form enctype="multipart/form-data" action="<?php echo ccgn_get_home_permalink() . "/edit/" . $post_id; ?>" method="post" class="standard-form">

        <label for="ccgn_title">Title: <input type="text" value="<?php echo apply_filters( "the_title", $post_title ); ?>" name="ccgn_title"></label>
    
        <?php
        $args = array(
                // 'textarea_rows' => 100,
                // 'teeny' => true,
                // 'quicktags' => false
                'media_buttons' => true,
                'editor_height' => 360,
                'tabfocus_elements' => 'insert-media-button,save-post',
            );
            wp_editor( $post_content, 'ccgn_content', $args); 
        ?>

        <?php ccgn_related_group_checkboxes( $group_id, $post_id ); ?>

        <p>
            <label for="ccgn_published">Published Status</label>
            <select name="ccgn_published" id="ccgn_published">
                <option <?php selected( $post_published, "publish" ); ?> value="publish">Published</option>
                <option <?php selected( $post_published, "draft" ); 
                    if ( empty( $post_published ) ) { echo 'selected="selected"' ; } 
                    ?> value="draft">Draft</option>
            </select>
        </p>

        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">

        <input type="hidden" name="post_form_url" value="<?php echo ccgn_get_home_permalink() . "/edit/" . $post_id; ?>">
        <br />
        <input type="submit" value="Post" name="group_narrative_post_submitted" id="submit">
    </form>
<?php
}

function ccgn_save_narrative( $group_id ) {
    //WP's update_post function does a bunch of data cleaning, so we can leave validation to that.
    $published_status = in_array( $_POST['ccgn_published'], array( 'publish', 'draft' ) ) ? $_POST['ccgn_published'] : 'draft';
    $title = isset( $_POST['ccgn_title'] ) ? $_POST['ccgn_title'] : 'Draft Narrative';

    $args = array(
        'post_title' => $title,
        'post_content' => $_POST['ccgn_content'],
        'post_name' => sanitize_title( $title ),
        'post_type' => 'group_story',
        'post_status' => $published_status,

    );

    //TODO: When would this ever not be true?
    if ( $pre_post_id = bp_action_variable( 1 ) )
        $args['ID'] = $pre_post_id;

    $post_id = wp_update_post( $args );

    //If successful save, do some other things, like taxonomies

    if ( $post_id ) {

        //Set the "related groups" terms
        if ( $related_groups = ( $_POST['related-groups'] ) ? array_map( 'intval', (array) $_POST['related-groups'] ) : NULL  ) {
            // First, clear terms
            wp_set_object_terms( $post_id, NULL, 'related_groups' );
            // Then, set the terms for the new post
            wp_set_object_terms( $post_id, $related_groups, 'related_groups' );
        }

        //Set some meta with the id of the group that created this post
        if ( $group_id = ( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : NULL  ) {
            // 
            update_post_meta( $post_id, 'ccgn_group_origin', $group_id );
        }

    }
}
