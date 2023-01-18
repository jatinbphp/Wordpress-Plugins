<?php
/**
* Plugin Name: Custom Ajax Search Form
* Plugin URI: http://nxsol.com/
* Description: Custom ajax search for pages, posts, custom posts and also search product by SKU
* Author: Nxsol
* Version: 1.0.0
* Author URI: http://nxsol.com/
*
*/

if (!defined('ABSPATH')){
    exit;
}

function ja_global_enqueues() {

	wp_enqueue_style(
		'jquery-auto-complete',
		'https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.css',
		array(),
		'1.0.7'
	);
	
	wp_enqueue_style("csf-main-style", plugins_url()."/nxsol_ajax_search/css/style.css");

	wp_enqueue_script(
		'jquery-auto-complete',
		'https://cdnjs.cloudflare.com/ajax/libs/jquery-autocomplete/1.0.7/jquery.auto-complete.min.js',
		array( 'jquery' ),
		'1.0.7',
		true
	);

	wp_enqueue_script(
		'global',
		plugins_url(). '/nxsol_ajax_search/js/global.min.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);

	wp_localize_script(
		'global',
		'global',
		array(
			'ajax' => admin_url( 'admin-ajax.php' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ja_global_enqueues' );

/**
 * Live autocomplete search feature.
 *
 * @since 1.0.0
 */
function ja_ajax_search() {
	
	$items = array();
	
	$q1 = new WP_Query( array(
		'post_type' => array( 'product','post','page' ),
		'post_status' => array( 'publish' ),
		'posts_per_page' => -1,
		's' => stripslashes( $_POST['search'] ),
	));

	$q2 = new WP_Query( array(
		'post_type'              => array( 'product' ),
		'post_status'            => array( 'publish' ),
		'posts_per_page'         => '-1',
		'meta_query'             => array(
			array(
				'key'     => '_sku',
				'value'   => stripslashes( $_POST['search'] ),
				'compare' => 'LIKE',
			),
		)
	));

	$result = new WP_Query();
	$result->posts = array_unique( array_merge( $q1->posts, $q2->posts ), SORT_REGULAR );
	$result->post_count = count( $result->posts );
	
	if ( !empty( $result->posts ) ) {
		foreach ( $result->posts as $results ) {
			$items[] = array('value'=>$results->post_title,'url'=>get_permalink( $results->ID ));
		}
	}
	
	wp_send_json_success( $items );
}
add_action( 'wp_ajax_search_site',        'ja_ajax_search' );
add_action( 'wp_ajax_nopriv_search_site', 'ja_ajax_search' );

function call_custom_search_form_nxsol(){
	global $wpdb;
	
	ob_start();
	?>
		<form class="navbar-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<div class="custom_search_wrap">
				<span class="fa fa-search form-control-feedback"></span>
				<input type="text" name="s" value="<?php if(isset($_GET['s'])){ echo $_GET['s']; } ?>" class="form-control search-autocomplete" id="search_all_data" placeholder="Search..">
			</div>
		</form>
	<?php
	return ob_get_clean();
}
add_shortcode('custom_search_form_nxsol', 'call_custom_search_form_nxsol');

add_action( 'init', 'exclude_images_from_search_results' );
function exclude_images_from_search_results() {
	global $wp_post_types;

	$wp_post_types['attachment']->exclude_from_search = true;
}

function wp78649_extend_search( $query ) {
	$search_term = filter_input( INPUT_GET, 's', FILTER_SANITIZE_NUMBER_INT) ?: 0;
    
    if (
        $query->is_search
        && !is_admin()
        && $query->is_main_query()
    ) {
		$query->set('post_type', 'post');
		$query->set('post_type', 'page');
		$query->set('post_type', 'product');
		
		 $query->set('meta_query', [
            [
                'key' => '_sku',
                'value' => $search_term,
                'compare' => 'LIKE'
            ]
        ]);

        add_filter( 'get_meta_sql', function( $sql )
        {
            global $wpdb;

            static $nr = 0;
            if( 0 != $nr++ ) return $sql;

            $sql['where'] = mb_eregi_replace( '^ AND', ' OR', $sql['where']);

			
            return $sql;
        });
    }
     
     
    return $query;
}
add_action( 'pre_get_posts', 'wp78649_extend_search');

function template_chooser($template)   
{    
  global $wp_query;   
  $post_type = get_query_var('post_type');   
  if (
        $wp_query->is_search
        && !is_admin()
        && $wp_query->is_main_query()
    ) {
		
    return plugin_dir_path( __FILE__ ). '/create_new_search_page.php'; 
  }   
  return $template;   
}
add_filter('template_include', 'template_chooser');  
?>
