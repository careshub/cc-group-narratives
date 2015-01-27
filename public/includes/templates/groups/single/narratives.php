	<?php
	/*
	* Used to display the list, single and edit view of the Group Narratives pane.
	*/
	?>
	<div id="subnav" class="item-list-tabs no-ajax">
		<ul class="nav-tabs"> 
		<?php ccgn_options_menu(); ?>
		</ul>
	</div>

	<?php
	if( ccgn_is_single_post() ) {
		// BuddyPress forces comments closed on BP pages. Override that.
		remove_filter( 'comments_open', 'bp_comments_open', 10, 2 );

        // echo "is single post";
		$q = new WP_Query( ccgn_get_query() );
		// echo '<pre>';
		// print_r($q);
		// echo '</pre>';


		if ( $q->have_posts() ) : 

			do_action( 'bp_before_group_blog_post_content' );

			while( $q->have_posts()):$q->the_post();
				bp_get_template_part( 'groups/single/narrative-single' );
				comments_template();
			endwhile;

			do_action( 'bp_after_group_blog_content' );
		
		else: 
		?>

			<div id="message" class="info">
				<p><?php _e( 'That post does not appear to exist.', 'bcg' ); ?></p>
			</div>

		<?php 
		endif;
		// BuddyPress forces comments closed on BP pages. Put the filter back.
		add_filter( 'comments_open', 'bp_comments_open', 10, 2 );

    } else if ( ccgn_is_post_edit() ) {

		ccgn_get_post_form( bp_get_group_id() );

    } else { // Must be the narrative list
		?>
		<!-- This is the narrative list template, narrative list portion. -->
		<?php $q = new WP_Query( ccgn_get_query() ); ?>

		<?php if ( $q->have_posts() ) : ?>
			<?php do_action( 'bp_before_group_blog_content' ); ?>

			<div class="pagination no-ajax">
				<div id="posts-count" class="pag-count">
					<!-- TODO: pagination -->
					<?php //bcg_posts_pagination_count($q) ?>
				</div>

				<div id="posts-pagination" class="pagination-links">
					<!-- TODO: pagination -->
					<?php //bcg_pagination($q) ?>
				</div>

			</div>

			<?php 
			do_action( 'bp_before_group_blog_list' );

			while ( $q->have_posts() ) : $q->the_post();
				bp_get_template_part( 'groups/single/narrative-single' );
			endwhile;

			do_action( 'bp_after_group_blog_content' );
			?>

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'No narratives have been published yet.', 'bcg' ); ?></p>
			</div>

		<?php endif;
	}// End narrative display checks.
