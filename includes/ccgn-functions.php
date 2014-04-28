<?php
// Helper functions
/**
 * Get base 
 * 
 * @return boolean
 */
function ccgn_get_home_permalink( $group_id = false ) {
    // If a group_id is supplied, it is probably because the post originated from another group (and editing should occur from the original group's space).
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
function ccgn_current_user_can_post( $post_id = null ){
    $user_id =  bp_loggedin_user_id();

    // If specific to a post, base ability to edit on origin group, not the group where the post is displayed. 
    // For general use, like checking to display "create new", use the current group id (post_id will be null).
    $group_id = ( $post_id ) ? ccgn_get_origin_group( $post_id ) : bp_get_current_group_id();

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
 * Can the current user moderate posts?
 * Mods and Admins of groups that posts are sydicated to should be able to disassociate posts from that group 
 * (but NOT the origin group-- they can edit it there.)
 * 
 * @return boolean
 */
function ccgn_current_user_can_moderate( $post_id = null ){
    // We need to know the origin group of this post, so if we can't figure out the post_id and the origin group, bail.
    if ( ! $post_id = ( $post_id ) ? $post_id : get_the_ID() )
        return false;

    if ( ! $origin_group = ccgn_get_origin_group( $post_id ) )
        return false;

    $can_mod = false;
    $user_id = bp_loggedin_user_id();
    $current_group = bp_get_current_group_id();

    // User must be a mod or admin in the current group (and the current group can't be the origin group)
    if ( ( $origin_group != $current_group ) && $user_id )
        $can_mod = ( groups_is_user_admin( $user_id, $origin_group ) || groups_is_user_mod( $user_id, $origin_group ) );
    
    return apply_filters( 'ccgn_current_user_can_moderate', $can_mod, $current_group, $origin_group, $user_id );
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
 * Get the taxonomy term specific to group.
 * 
 * @return array of taxonomy ids
 */
function ccgn_get_group_term_id( $group_id = false ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    if ( $term = get_term_by( 'slug', ccgn_create_taxonomy_slug( $group_id ), 'ccgn_related_groups' ) ) {
        return $term->term_id;
    } else {
        return false;
    }

}
/**
 * Add this group's taxonomy term to an array. Useful for creating/saving posts, setting up the settings screen.
 * 
 * @return array of taxonomy ids
 */
function ccgn_add_this_group_term( $terms, $group_term_id = null ) {
    // var_dump($terms);
    // var_dump($group_term_id);
    // Make sure that the terms argument is an array
    $terms = ( !array( $terms ) ) ? (array) $terms : $terms;

    $group_term_id = ( ! $group_term_id ) ? ccgn_get_group_term_id( bp_get_current_group_id() ) : $group_term_id;
    
    // $towrite = PHP_EOL . 'in add this term, terms: ' . print_r( $terms, TRUE ); 
    // $towrite .= PHP_EOL . 'this group term: ' . print_r( $group_term_id, TRUE ); 
    // $fp = fopen('narrative-taxonomy.txt', 'a');
    // fwrite($fp, $towrite);
    // fclose($fp);

    // If $terms and $group_term_id aren't empty, merge them if necessary 
    if ( $terms && $group_term_id && ! in_array( $group_term_id, $terms ) ) {
        $terms = array_merge( $terms, (array) $group_term_id );
    // Probably $terms is empty, so we'll pass back the group_term_id as an array.
    } else if ( ! $terms && $group_term_id ) {
        $terms = (array) $group_term_id;
    }
    // $towrite .= PHP_EOL . 'to return: ' . print_r( $terms, TRUE ); 
    // $fp = fopen('narrative-taxonomy.txt', 'a');
    // fwrite($fp, $towrite);
    // fclose($fp);

    // Run intval on the values so that everything's saved as serialized arrays of integers.
    return array_map('intval', $terms);

}

/**
 * Get the group id where this was posted.
 * 
 * @return int group id, 0 if error
 */
function ccgn_get_origin_group( $post_id = null ) {
    if ( ! $post_id )
        return 0;

    $origin_group = get_post_meta( $post_id, 'ccgn_origin_group', true );
    // If not set, assume that the current group is the origin
    if ( ! $origin_group )
        $origin_group = bp_get_current_group_id();
    
    return (int) $origin_group;

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

        // Filter the slug on save
        // Todo: Don't use this slug for the back end. Maybe not possible with current BP? 
        // Probably will need to modify BP_Group_Extension and submit ticket.
        if ( $field == 'ccgn_url_slug') {
            $new_setting = sanitize_title( $new_setting );
        }

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
    // $cats = ccgn_get_categories( $bp->groups->current_group->id );

    // if( !empty( $cats ) )
    //     $cats_list = join( ",", $cats );
    // else return "name=-1";//we know it will not find anything

    // New method: posts that should be shown will have this group's taxonomy term. Easy-peasy.
    $group_term_id = ccgn_get_group_term_id( $bp->groups->current_group->id );
 
    if( ccgn_is_single_post() ){
        $slug = $bp->action_variables[0];
        // return "name=".$slug;//"&cat=".$cats_list;
        $query = array(
            'name' => $slug,
            'post_type' => 'group_story',
            'post_status' => array( 'publish', 'draft'),
        );
        
        return apply_filters("ccgn_get_query",$query);
    }

    $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
    // $query= "related_groups=".$cats_list;
    $query = array(
        'post_status' => array( 'publish', 'draft'),
        'post_type' => 'group_story',
        'tax_query' => array(
            array(
                'taxonomy' => 'ccgn_related_groups',
                'field' => 'id',
                'terms' => $group_term_id,
                'include_children' => false,
                // 'operator' => 'IN'
            )
        )
    );
    $towrite = PHP_EOL . 'query array: ' . print_r( apply_filters( "ccgn_get_query", $query ), TRUE ); 
    $fp = fopen('narrative-taxonomy.txt', 'a');
    fwrite($fp, $towrite);
    fclose($fp);

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

function ccgn_get_post_form( $group_id = false ){
    $group_id = ( ! $group_id ) ? bp_get_current_group_id() : $group_id ;
    
    // Should the user be able to visit this page?
    if ( ! ccgn_current_user_can_post() ) {
        echo '<div id="message" class="error"><p>You do not have the capability to edit or create posts in this group.</p></div>';
        return;
    }

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
    //Warn WP that we're going to want the media js
    //TODO I'm skeptical of this
    $args = array( 'post' => $post_id );
    wp_enqueue_media( $args );
    // $GLOBALS['post_ID'] = $post_id;

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
        <div id="ccgn_categories" class="ccgn_category_checkboxes">
            <h4>Syndication</h4>
            <p class="info">Choose the groups that this narrative should be published to.</p>
            <?php ccgn_related_group_checkboxes( $group_id, $post_id ); ?>
        </div>

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
        <!-- This is created for the media modal to reference 
        TODO: This doesn't work.-->
        <input id="post_ID" type="hidden" value="<?php echo $group_id; ?>" name="post_ID">

        <input type="hidden" name="post_form_url" value="<?php echo ccgn_get_home_permalink() . "/edit/" . $post_id; ?>">
        <br />
        <input type="submit" value="Save Changes" name="group_narrative_post_submitted" id="submit">
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
        $related_groups = ( $_POST['related-groups'] ) ? array_map( 'intval', (array) $_POST['related-groups'] ) : array();
        // Make sure that this group's term is always included
        // $home_group_term_id = ccgn_get_group_term_id( $group_id );

        $related_groups = ccgn_add_this_group_term( $related_groups, ccgn_get_group_term_id( $group_id ) );

        // First, clear terms
        wp_set_object_terms( $post_id, NULL, 'ccgn_related_groups' );
        // Then, set the terms for the new post
        wp_set_object_terms( $post_id, $related_groups, 'ccgn_related_groups' );

        //Set some meta with the id of the group that created this post
        if ( $group_id = ( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : bp_get_current_group_id() ) {
            // 
            update_post_meta( $post_id, 'ccgn_origin_group', $group_id );
        }

    }
}

// Media modal functionality
function wpse_76980_add_upload_tab( $tabs ) {
    $newtab = array( 'super_duper' => 'Super Duper' );
    return array_merge( $tabs, $newtab );
}
// add_filter( 'media_upload_tabs', 'wpse_76980_add_upload_tab' );

function wpse_76980_media_upload() {
    // display tab contents
    $good_docs = cc_get_associatable_bp_docs_narrative_form( 2 );
    print_r($good_docs);
    foreach ($good_docs as $doc) {
        echo '<input type="checkbox">';
        print_r($doc);
        echo '<br/>';
        # code...
    }
            print_r($_GET);
            print_r($_REQUEST);

}
// add_action( 'media_upload_super_duper', 'wpse_76980_media_upload' );

// add_filter('attachment_fields_to_edit', 'my_plugin_action_button', 20, 2);
// add_filter('media_send_to_editor', 'my_plugin_image_selected', 10, 3);
 
function my_plugin_action_button($form_fields, $post) {
 
        $send = "<input type='submit' class='button' name='send[$post->ID]' value='" . esc_attr__( 'Use as Default' ) . "' />";
 
    $form_fields['buttons'] = array('tr' => "\t\t<tr class='submit'><td></td><td class='savesend'>$send</td></tr>\n");
    $form_fields['context'] = array( 'input' => 'hidden', 'value' => 'shiba-gallery-default-image' );
    return $form_fields;
}
 
function my_plugin_image_selected($html, $send_id, $attachment) {
    ?>
    <script type="text/javascript">
    /* <![CDATA[ */
    var win = window.dialogArguments || opener || parent || top;
                 
    win.jQuery( '#default_image' ).val('<?php echo $send_id;?>');
    // submit the form
    win.jQuery( '#shiba-gallery_options' ).submit();
    /* ]]> */
    </script>
    <?php
    exit();
}
// Helper function to build the taxonomy slug
function ccgn_create_taxonomy_slug( $group_id = null ) {
    $group_id = !( $group_id ) ? bp_get_current_group_id() : $group_id ;
    return 'ccgn_related_group_' . $group_id;
}