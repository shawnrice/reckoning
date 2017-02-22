<?php
/**
 * The Reckoning
 *
 * Adds a submenu (under tools) that tallies all the users' posts and comments on a blog. This
 * is especially useful for assement of blogs for classes.
 *
 * @package   Reckoning
 * @author    Shawn Patrick Rice <rice@shawnrice.org>
 * @license   MIT
 * @link      https://github.com/shawnrice/wp-reckoning
 * @copyright 2014 Shawn Patrick Rice
 *
 * @wordpress-plugin
 * Plugin Name:       Reckoning
 * Plugin URI:        https://github.com/shawnrice/wp-reckoning
 * Description:       Tallies posts / comments per user per blog.
 * Version:           2.0.1
 * Author:            Shawn Patrick Rice
 * Author URI:        http://shawnrice.org
 * Text Domain:		   reckoning
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * GitHub Plugin URI: https://github.com/shawnrice/wp-reckoning
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	add_action( 'admin_menu', 'reckoning_admin_menu' );
	add_action( 'admin_enqueue_scripts', 'enqueue_reckoning_admin_styles' );
	load_plugin_textdomain( 'reckoning', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Add admin menu link.
 *
 * Adds the admin menu link under the "users" tab.
 *
 * @since 1.0.0
 *
 */
function reckoning_admin_menu() {
	add_submenu_page( 'users.php', __( 'Content Reckoning', 'reckoning' ), __( 'User Summary', 'reckoning' ), 'manage_options', __( 'Reckoning', 'reckoning' ), 'display_reckoning_admin_page' );
} // reckoning_admin_menu

/**
 * Enqueues stylesheet.
 *
 * Enqueues the stylesheet that controls the tables.
 *
 * @since 1.0.0
 *
 */
function enqueue_reckoning_admin_styles() {
	wp_enqueue_style( 'reckoning-admin-styles', plugins_url( 'admin.css', __FILE__ ) );
} // enqueue_reckoning_admin_styles

/**
 * Control function for admin page.
 *
 * Parses the "view" argument in $_GET to display either the page for all users or for a single user.
 *
 * @since 1.0.0
 *
 */
function display_reckoning_admin_page() {
	$view = get_query_var( 'view' );

	if ( ! empty( $view ) ) {
		if ( 'all' === $view ) {
			display_reckoning_admin_page_all();
		} elseif ( $user = get_user_by( 'login', $view ) ) {
			display_reckoning_admin_page_individual( $user );
		} else {
			display_reckoning_admin_page_all();
		}
	} else {
		display_reckoning_admin_page_all();
	}
} // display_reckoning_admin_page

/**
 * Display summary page for user.
 *
 * Displays a summary page that tallies both posts and comments, including the content of each.
 *
 * @since 1.0.0
 *
 * @global $wpdb.
 *
 * @param object $var Description.
 */
function display_reckoning_admin_page_individual( $user ) {
	global $wpdb;
	$blog     = get_blog_details();
	$comments = get_comments( array( 'user_id' => $user->data->ID ) );
	$posts    = get_posts( array(
		 'author' => $user->data->ID,
		 'numberposts' => '-1',
		 'post_status' => array(
				'publish',
				'private',
			),
	) );

	echo '<div class="wrap">';
	echo '<h2>' . esc_html( get_admin_page_title() ) . '</h2>';

	// Print a "return to overview" link
	echo '<h4>';
	echo '<a href="users.php?page=Reckoning">';
	echo '« ' . esc_html( __( 'Return to Blog Overview',  'reckoning' ) );
	echo '</a>';
	echo '</h4>';

	echo '<h2 class="entry-title">';
	echo esc_html( __( 'Summary of User Activity for ',  'reckoning' ) );
	echo '"' . esc_html( ucwords( $user->display_name ) ) . '" ';
	// If the display name and the nicename are different, then show both.
	if ( $user->display_name !== $user->data->user_nicename ) {
		echo '(' . esc_html( $user->data->user_nicename ) . ') ';
	}
	echo 'on "' . esc_html( $blog->blogname ) . '"';
	echo '</h2>';

	// Start processing Posts. Check if there are posts, if so, print the table with all of the posts;
	// if not, print a message saying there are no posts.
	if ( ! count( $posts ) ) {
		echo '<h3>';
		echo '"' . esc_html( ucwords( $user->display_name ) ) . '" ';
		echo esc_html( __( 'has not written a post', 'reckoning' ) ) . '.';
		echo '</h3>';
	} else {
		echo '<h3>' . esc_html( __( 'Total Posts', 'reckoning' ) ) . ': ' . esc_html( count( $posts ) ) . '</h3>';
		echo "<table class = 'reckoning-table'>";
		echo "<tr><th colspan='2'>User Posts</th></tr>";
		foreach ( $posts as $post ) :
			echo '<tr>';
			preg_match( '/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $post->post_date, $matches );
			$date = "{$matches[2]}/{$matches[3]}/{$matches[1]}";
			echo '<td><a href="' . esc_url( $post->guid ) . '">';
			echo wp_kses_post( $post->post_title );
			echo '</a></td>';
			echo '<td>' . esc_html( $date ) . '</td>';
			echo '</tr>';
			echo '<tr><td colspan="2">' . wp_kses_post( $post->post_content ) . '</td></tr>';
			endforeach;
		echo '</table>';
	}

	echo '<p>&nbsp;</p>';

	// Start processing comments. Check if there are comments, if so, print the table with all of the comments;
	// if not, print a message saying there are no comments.
	if ( ! count( $comments ) ) {
		echo '<h3>';
		echo '"' . esc_html( ucwords( $user->display_name ) ) . '" ';
		echo esc_html( __( 'has not posted a comment', 'reckoning' ) );
		echo '.</h3>';
	} else {
		echo '<h3>' . esc_html( __( 'Total Comments', 'reckoning' ) ) . ': ' . count( $comments ) . '</h3>';
		echo "<table class = 'reckoning-table'>";
		echo "<tr><th colspan='2'>User Comments</th></tr>";
		foreach (  $comments as $comment ) :
			$post = get_post( $comment->comment_post_ID );
			preg_match( '/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $comment->comment_date, $matches );
			$date = "{$matches[2]}/{$matches[3]}/{$matches[1]}";
			echo '<tr>';
			echo '<td>';
			echo esc_html( __( 'On', 'reckoning' ) );
			echo ' <a href="' . esc_url( $post->guid . '#comment-' . $comment->comment_ID ) . '">';
			echo esc_html( $post->post_title ) . '</a></td>';
			echo '<td>' . esc_html( $date ) . '</td>';
			echo '</tr>';
			echo '<tr><td colspan="2">' . wp_kses_post( $comment->comment_content ) . '</td></tr>';
		endforeach;
		echo '</table>';
	}
	echo '<p>&nbsp;</p>';

	// Print a "return to overview" link
	echo '<h4>';
	echo '<a href="users.php?page=Reckoning">';
	echo '« ' . esc_html( __( 'Return to Blog Overview', 'reckoning' ) );
	echo '</a>';
	echo '</h4>';

} // display_reckoning_admin_page_individual


/**
 * Display summary page for all users.
 *
 * Displays a summary page that tallies the number of both posts and comments,
 * per user and displays links to the post/comment, sorted by date.
 *
 * @since 1.0.0
 *
 * @global $wpdb.
 */
function display_reckoning_admin_page_all() {
	global $wpdb;
	$blog = get_blog_details();
	$users = get_users( 'orderby=display_name&order=ASC' );
?>
	<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

<?php
	echo '<h2 class="entry-title">';
	echo esc_html( __( 'Summary of User Activity on', 'reckoning' ) );
	echo '"' . esc_html( $blog->blogname ) . '"';
	echo '</h2>';
	echo '<small>';
	echo esc_html( __( 'Click the name of the user to see a more detailed report for that user', 'reckoning' ) ) . '.';
	echo '</small>';

	// Start looping through each user.
foreach ( $users as $user ) :
	$posts = get_posts( array(
		'author'      => $user->data->ID,
		'numberposts' => '-1',
		'post_status' => array(
			'publish',
			'private',
		),
	) );
	$comments = get_comments( array( 'user_id' => $user->data->ID ) );

	// Show the username with a link to a more detailed view of the user.
	echo '<h3>';
	echo '<a href="' . esc_url( 'users.php?page=Reckoning&view=' . $user->data->user_login ) . '">';
	echo esc_html( ucwords( $user->data->display_name ) );
	echo '</a>';
	// If the display name and the nicename are different, then show both.
	if ( $user->data->display_name !== $user->data->user_nicename ) {
		echo ' (' . esc_html( $user->data->user_nicename ) . ') ';
	}
	echo '</h3>';

	// Start processing Posts. Check if there are posts, if so, print the table with all of the posts;
	// if not, print a message saying there are no posts.
	if ( ! count( $posts ) ) {
		echo '<h4>';
		echo '"' . esc_html( ucwords( $user->display_name ) ) . '" ';
		echo esc_html( __( 'has not written a post', 'reckoning' ) ) . '.';
		echo '</h4>';
	} else {
		echo "<table class = 'reckoning-table reckoning-table-all'>";
		echo "<tr><th colspan='2'><h3>User Posts</h3></th></tr>";
		foreach ( $posts as $post ) :
			preg_match( '/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $post->post_date, $matches );
			$date = "$matches[2]/$matches[3]/$matches[1]";
			echo '<tr><td>';
			echo '<a href="' . esc_url( $post->guid ) . '">';
			echo wp_kses_post( $post->post_title );
			echo '</a>';
			echo '</td>';
			echo '<td>' . esc_html( $date ) . '</td></tr>';
		endforeach;
		echo '<tr>';
		echo '<td>' . esc_html( __( 'Total Posts', 'reckoning' ) ) . '</td>';
		echo '<td>' . (int) count( $posts ) . '</td>';
		echo '</tr>';
		echo '</table>';
	}

	// Start processing comments. Check if there are comments, if so, print the table with all of the comments;
	// if not, print a message saying there are no comments.
	if ( ! count( $comments ) ) {
		echo '<h4>';
		echo '"' . esc_html( ucwords( $user->display_name ) . '" ' . __( 'has not posted a comment', 'reckoning' ) ) . '.';
		echo '</h4>';
	} else {
		echo '<table class="reckoning-table reckoning-table-all">';
		echo '<tr><th colspan="2"><h3>User Comments</h3></th></tr>';
		foreach ( $comments as $comment ) :
			$post = get_post( $comment->comment_post_ID );
			preg_match( '/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $comment->comment_date, $matches );
			$date = "{$matches[2]}/{$matches[3]}/{$matches[1]}";
			echo '<tr>';
			echo '<td>On ';
			echo '<a href="' . esc_url( $post->guid . '#comment-' . $comment->comment_ID ) . '">';
			echo esc_html( $post->post_title );
			echo '</a>';
			echo '</td>';
			echo '<td>' . esc_html( $date ) . '</td>';
			echo '</tr>';
		endforeach;
		echo '<tr>';
		echo '<td>' . esc_html( __( 'Total Comments', 'reckoning' ) ) . '</td>';
		echo '<td>' . (int) count( $comments ) . '</td>';
		echo '</tr>';
		echo '</table>';
	}
	echo '<p>&nbsp;</p>';
	endforeach;

	echo '</div>';

} // display_reckoning_admin_page_all
