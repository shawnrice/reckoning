<?php
/**
 * The Reckoning
 *
 * Adds a submenu (under tools) that tallies all the users' posts and comments on a blog. This
 * is especially useful for assement of blogs for classes.
 *
 * @package   Reckoning
 * @author    Shawn Patrick Rice <rice@shawnrice.org>
 * @license   GPL-2.0+
 * @link      http://example.com
 * @copyright 2014 Shawn Patrick Rice
 *
 * @wordpress-plugin
 * Plugin Name:       Reckoning
 * Plugin URI:        @TODO
 * Description:       Tallies posts / comments per user per blog.
 * Version:           1.0.0
 * Author:            Shawn Patrick Rice
 * Author URI:        http://shawnrice.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/shawnrice/wp-reckoning
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	add_action('admin_menu', 'reckoning_admin_menu');
	add_action( 'admin_enqueue_scripts', 'enqueue_reckoning_admin_styles' );
}

function reckoning_admin_menu() {
  add_submenu_page('tools.php', 'Reckoning', 'Reckoning', 'manage_options', 'Reckoning', 'display_reckoning_admin_page');
}

function enqueue_reckoning_admin_styles() {
	wp_enqueue_style( 'reckoning-admin-styles', plugins_url( 'admin.css', __FILE__ ));
}

function display_reckoning_admin_page() {
	?>
	<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

<?php
	global $wpdb;
	$date_format='m/d/Y';
	$blog = get_blog_details();
	$users = get_users();
	echo '<h2 class="entry-title">Summary of Activity on "' . $blog->blogname .'"</h2>';
	foreach ($users as $user) {
		echo "<table class = 'reckoning-table'>";
		$user_id=$user->data->ID;
		$where = 'WHERE comment_approved = 1 AND user_id = ' . $user_id ;
		$comment_count = $wpdb->get_var(
		"SELECT COUNT( * ) AS total
		FROM {$wpdb->comments}
		{$where}
		");
		$post_count = get_usernumposts($user_id);
		$posts = get_posts( array('author' => $user_id) );

		echo '<h3>' . $user->data->user_nicename . '</h3>';
		foreach ( $posts as $post ) {
			echo '<tr>';
			$date = date( $date_format , mktime($post->post_date));
			echo "<td><a href='$post->guid'>$post->post_title</a></td><td>$date</td>";
			echo '</tr>';
		}
		echo '<tr><td>Total Posts</td><td>' . $post_count .'</td></tr>';
		echo '<tr><td>Total Comments</td><td>' . $comment_count . '</td></tr>';
		echo '</table>';
	}
?>
	</div>
<?php
}