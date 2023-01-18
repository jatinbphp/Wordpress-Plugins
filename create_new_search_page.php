<?php
/* Template Name: Custom Search */

get_header();
global $wp_query;
?>
<div class="wapper">
  <div class="contentarea clearfix">
    <div class="content woocommerce" id="primary">
		<h1 class="ajax_search_title"> 
		  <?php _e( 'Search Results Found For', 'locale' ); ?>: "<?php the_search_query(); ?>" 
		</h1>

		<?php
			global $wp_query;
			$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			$post_per_page = 10;
			
			$q1 = new WP_Query( array(
				'post_type' => array( 'product','post','page' ),
				'post_status' => array( 'publish' ),
				'posts_per_page' => -1,
				's' => stripslashes( $_GET['s'] ),
			));
			
			$q1_arr = array();
			if ( $q1->have_posts() ) {

               while ( $q1->have_posts() ) {

                    $q1->the_post();

                    $q1_arr[] = get_the_ID() ;
               }
            }
            wp_reset_query();

			$q2 = new WP_Query( array(
				'post_type'              => array( 'product' ),
				'post_status'            => array( 'publish' ),
				'posts_per_page'         => '-1',
				'meta_query'             => array(
					array(
						'key'     => '_sku',
						'value'   => stripslashes( $_GET['s'] ),
						'compare' => 'LIKE',
					),
				)
			));
			
			$q2_arr = array();
			if ( $q2->have_posts() ) {

               while ( $q2->have_posts() ) {

                    $q2->the_post();

                    $q2_arr[] = get_the_ID() ;
               }
            }
            wp_reset_query();
			
			$mergePosts = array_merge( $q1_arr, $q2_arr);
			$uniquePosts = array_unique($mergePosts);
			
			$args = array(
				'post_type' => 'any',   
				'post__in' => $uniquePosts,
				'paged' => $paged,
				'orderby' => 'post__in',
				'order' => 'DESC',
			'posts_per_page' => $post_per_page
			);

			$wp_query = new WP_Query($args);
			  
		?>
		
        <?php if ( $wp_query->have_posts() ) { ?>

            <ul class="products columns-5 ajax_search_details">

            <?php while ( $wp_query->have_posts() ) { $wp_query->the_post(); ?>

               <li class="product">
                 <a href="<?php echo get_permalink(); ?>"><?php if ( has_post_thumbnail() && !empty(get_the_post_thumbnail_url(null, 'post-thumbnail')) && getimagesize(get_the_post_thumbnail_url(null, 'post-thumbnail')) !== false ) {  ?> <img src="<?php echo get_the_post_thumbnail_url(null, 'post-thumbnail'); ?>" >  <?php }else{ ?> <img src="/wp-content/uploads/woocommerce-placeholder-300x300.png">  <?php }?></a>
                 <h2 class="woocommerce-loop-product__title">
					 <a href="<?php echo get_permalink(); ?>">
						<?php the_title();  ?>
					</a>
				</h2>
                 <div class="h-readmore"> <a href="<?php the_permalink(); ?>">Read More</a></div>
               </li>

            <?php } ?>

            </ul>

           <?php
			$big = 999999999;
			$pages = paginate_links(array(
				'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
				'format' => '?page=%#%',
				'current' => max(1, get_query_var('paged')),
				'total' => $wp_query->max_num_pages,
				'prev_next' => false,
				'type' => 'array',
				'prev_next' => TRUE,
				'prev_text' => '&larr; Previous',
				'next_text' => 'Next &rarr;',
					));
			if (is_array($pages)) {
				$current_page = ( get_query_var('paged') == 0 ) ? 1 : get_query_var('paged');
				echo '<nav class="woocommerce-pagination custom_ajax_search_pagination"><ul class="pagination">';
				foreach ($pages as $i => $page) {
					if ($current_page == 1 && $i == 0) {
						echo "<li class='active'>$page</li>";
					} else {
						if ($current_page != 1 && $current_page == $i) {
							echo "<li class='active'>$page</li>";
						} else {
							echo "<li>$page</li>";
						}
					}
				}
				echo '</ul></nav>';
			}
			wp_reset_postdata();
			?>
        <?php }else{
			echo "Data Not Found.";
		} ?>

    </div>
  </div>
</div>
<?php get_footer(); ?>
