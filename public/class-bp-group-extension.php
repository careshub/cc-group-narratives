<?php 
if ( class_exists( 'BP_Group_Extension' ) ) : // Recommended, to prevent problems during upgrade or when Groups are disabled

class CC_Group_Narratives_Extension extends BP_Group_Extension {
    /**
     * Here you can see more customization of the config options
     */
    function __construct() {
        $args = array(
            'slug' => ccgn_get_slug(),
            'name' => 'Group Narratives',
            'visibility' => 'public',
            'enable_nav_item'   => true,//ccgn_is_enabled(),
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
 
    function display() {
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
 
    function settings_screen( $group_id ) {
        $group_id = bp_get_group_id( $group_id );
        $is_enabled = ccgn_is_enabled( $group_id );
        $tab_label = ccgn_get_tab_label( $group_id );
        $slug = ccgn_get_slug( $group_id );
        $level_to_post = ccgn_get_level_to_post( $group_id );
        ?>

        <p>
            Blog Categories for Groups allows your group to have a voice in this site&rsquo;s blog. If enabled, your group will be able to submit posts for publication within a specific category. Posts in this category (or categories) will also be displayed within your group&rsquo;s space.
        </p>
        <p>
           <label for="ccgn_is_enabled"> <input type="checkbox" name="ccgn_is_enabled" id="ccgn_is_enabled" value="1" <?php checked( $is_enabled, true ) ?> /> Enable group narratives for this group.</label>
        </p>
        <div id="ccgn_settings_details">
 
        <p>
            <label for='ccgn_tab_label'>Change the BuddyPress group tab label from 'Narratives' to whatever you'd like.</label>
            <input type="text" name="ccgn_tab_label" id="ccgn_tab_label" value="<?php echo esc_html( $tab_label ); ?>" />
         </p>
         <p>
            <label for='ccgn_url_slug'>Change the slug to something other than 'narratives'.</label>
           <input type="text" name="ccgn_url_slug" id="ccgn_url_slug" value="<?php echo esc_html( $slug ); ?>" />
         </p>
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

        $selected_cats = ccgn_get_categories( $group_id );
        echo "<p>".__("Select a category to associate its posts with this group.","bcg")."</p>";

        // $cat_ids=get_all_category_ids();
        $tax_args = array(
            'hide_empty'    => false,
            // 'name__like'    => '',
        );
        $cats = get_terms('related_groups', $tax_args);

        // print_r($cats);
        if(is_array($cats)){ ////it is sure but do not take risk
          foreach($cats as $cat){ //show the form
              $checked=0;
                if(!empty($selected_cats)&&in_array($cat->term_id,$selected_cats))
                        $checked=true;
                ?>
                <label  style="padding:5px;display:block;float:left;">
                    <input type="checkbox" name="ccgn_narrative_tax_terms[]" id="<?php $cat->term_id;?>" value="<?php echo $cat->term_id;?>" <?php if( $checked ) echo "checked='checked'" ;?>/>
                    <?php echo $cat->name;?>
                </label>
    <?php
           } //Ends foreach
    } else {
          ?>
        <div class="error">
            <p><?php _e("Please create the categories before trying to attach them to a group.","bcg");?></p>
        </div>
        <?php
         }
    ?>  </div> <!-- End #bcg_settings_details -->
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
 
    function settings_screen_save( $group_id ) {
      // $setting = isset( $_POST['group_extension_example_2_setting'] ) ? $_POST['group_extension_example_2_setting'] : '';
      // groups_update_groupmeta( $group_id, 'group_extension_example_2_setting', $setting );

      $group_relations = $_POST["ccgn_narrative_tax_terms"];
           //print_r($cats);

      if ( !ccgn_update_categories( $group_id, $group_relations ) || !ccgn_update_groupmeta( $group_id ) ) {
        bp_core_add_message( __( 'There was an error updating the Group Narratives settings, please try again.', 'bcg' ), 'error' );
      } else {
        bp_core_add_message( __( 'Group Narratives settings were successfully updated.', 'bcg' ) );
      }
    }
 
}
bp_register_group_extension( 'CC_Group_Narratives_Extension' );
 
endif;