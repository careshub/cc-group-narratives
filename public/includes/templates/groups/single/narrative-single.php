<?php
/**
 * The default template for displaying content. Used for both single and index/archive/search.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php //if ( is_sticky() && is_home() && ! is_paged() ) : ?>
		<!-- <div class="featured-post">
			<?php // _e( 'Featured post', 'twentytwelve' ); ?>
		</div> -->
		<?php //endif; ?>

		<header class="entry-header">

			<?php if ( ccgn_is_single_post() ) : ?>
				<?php //the_post_thumbnail(); ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php else : ?>
				<?php if ( has_post_thumbnail()) : ?>
				   	<a href="<?php echo trailingslashit( ccgn_get_home_permalink() )  . $post->post_name; ?>" title="<?php the_title_attribute(); ?>" >
				   	<?php the_post_thumbnail('feature-large'); ?>
				   	</a>
				<?php endif; ?>
				<h1 class="entry-title">
					<a href="<?php echo trailingslashit( ccgn_get_home_permalink() )  . $post->post_name; ?>" title="<?php echo esc_attr( sprintf( __( 'Link to %s', 'twentytwelve' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
					<?php if ( 'draft' == get_post_status( get_the_ID() ) ) : ?>
						&emsp;<span class="draft-narrative" style="font-size:0.8em;color:#ABABAB;font-style:italic;">Draft Narrative</span>
					<?php endif; ?>
				</h1> 
			<?php endif; // is_single() ?>

			<?php //if ( comments_open() ) : ?>
				<!-- <div class="comments-link">
					<?php comments_popup_link( '<span class="leave-reply">' . __( 'Leave a reply', 'twentytwelve' ) . '</span>', __( '1 Reply', 'twentytwelve' ), __( '% Replies', 'twentytwelve' ) ); ?>
				</div> --><!-- .comments-link -->
			<?php //endif; // comments_open() ?>
			<?php ccgn_group_origin_statement(); ?>
		</header><!-- .entry-header -->

		<?php if ( ccgn_is_single_post() ) : // Display Excerpts when not a single page ?>
		<div class="entry-content">
			<?php the_content( __( 'Read more', 'twentytwelve' ) ); ?>
			<?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'twentytwelve' ), 'after' => '</div>' ) ); ?>
		</div><!-- .entry-content -->
		<?php else : ?>
		<div class="entry-summary">
			<?php the_excerpt(); ?>
		</div><!-- .entry-summary -->

		<?php endif; ?>	
		<?php 
		if ( ccgn_is_single_post() ) :
		// if ( function_exists('bp_share_favorite_post_button') ) {
		// 		bp_share_favorite_post_button( $post->ID );
		// 	}
			if ( function_exists('cc_add_comment_button') ) { 
					cc_add_comment_button( get_the_ID() ); 
				}
			if ( function_exists('love_it_button') ) { 
					love_it_button(); 
				}
			if ( function_exists('bp_share_post_button') ) { 
					bp_share_post_button(); 
				} 
		endif;
		?>

		<footer class="entry-meta">
			<?php twentytwelve_entry_meta(); ?>
			<?php //edit_post_link( __( 'Edit', 'twentytwelve' ), '<span class="edit-link">', '</span>' ); ?>
			<?php if ( is_singular() && get_the_author_meta( 'description' ) && is_multi_author() ) : // If a user has filled out their description and this is a multi-author blog, show a bio on their entries. ?>
				<div class="author-info">
					<div class="author-avatar">
						<?php echo get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'twentytwelve_author_bio_avatar_size', 68 ) ); ?>
					</div><!-- .author-avatar -->
					<div class="author-description">
						<h2><?php printf( __( 'About %s', 'twentytwelve' ), get_the_author() ); ?></h2>
						<p><?php the_author_meta( 'description' ); ?></p>
						<div class="author-link">
							<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
								<?php printf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'twentytwelve' ), get_the_author() ); ?>
							</a>
						</div><!-- .author-link	-->
					</div><!-- .author-description -->
				</div><!-- .author-info -->
			<?php endif; ?>
		</footer><!-- .entry-meta -->
		<div class="post-actions">
			<?php do_action( 'ccgn_post_actions' ) ; ?>
		</div>
	</article><!-- #post -->
