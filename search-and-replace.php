<?php
/*
Plugin Name: Search &amp; Replace
Plugin URI: http://bueltge.de/wp-suchen-und-ersetzen-de-plugin/114/
Description: A simple search for find strings in your database and replace the string. 
Author: Frank B&uuml;ltge
Author URI: http://bueltge.de/
Version: 2.5
License: GPL
*/


/**
Um dieses Plugin zu nutzen, musst du das File in den 
Plugin-Ordner deines WP kopieren und aktivieren.
Es fuegt einen neuen Tab im Bereich "Verwalten" hinzu.
Dort koennen Strings dann gesucht und ersetzt werden.
*/

//avoid direct calls to this file, because now WP core and framework has been used
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );


function searchandreplace_textdomain() {

	if (function_exists('load_plugin_textdomain')) {
		if ( !defined('WP_PLUGIN_DIR') ) {
			load_plugin_textdomain('searchandreplace', str_replace( ABSPATH, '', dirname(__FILE__) ) . '/languages');
		} else {
			load_plugin_textdomain('searchandreplace', false, dirname(plugin_basename(__FILE__)) . '/languages');
		}
	}
}


/**
 * credit in wp-footer
 */
function searchandreplace_admin_footer() {
	$plugin_data = get_plugin_data( __FILE__ );
	$plugin_data['Title'] = $plugin_data['Name'];
	if ( !empty($plugin_data['PluginURI']) && !empty($plugin_data['Name']) )
		$plugin_data['Title'] = '<a href="' . $plugin_data['PluginURI'] . '" title="'.__( 'Visit plugin homepage' ).'">' . $plugin_data['Name'] . '</a>';
	
	if ( basename($_SERVER['REQUEST_URI']) == 'search-and-replace.php') {
		printf('%1$s ' . __('plugin') . ' | ' . __('Version') . ' <a href=" http://bueltge.de/wp-suchen-und-ersetzen-de-plugin/114/#historie" title="' . __('History', 'pxsmail') . '">%2$s</a> | ' . __('Author') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
	}
}


/**
 * Add action link(s) to plugins page
 * Thanks Dion Hulse -- http://dd32.id.au/wordpress-plugins/?configure-link
 */
function searchandreplace_filter_plugin_actions($links, $file){
	static $this_plugin;

	if( !$this_plugin ) $this_plugin = plugin_basename(__FILE__);

	if( $file == $this_plugin ){
		$settings_link = '<a href="options-general.php?page=search-and-replace/search-and-replace.php">' . __('Settings') . '</a>';
		$links = array_merge( array($settings_link), $links); // before other links
	}
	return $links;
}


/**
 * settings in plugin-admin-page
 */
function searchandreplace_add_settings_page() {
	if ( current_user_can('edit_plugins') && is_admin() ) {
		add_options_page( __('Search &amp; Replace', 'searchandreplace'), __('Search &amp; Replace', 'searchandreplace'), 10, __FILE__, 'searchandreplace_page');
		add_filter('plugin_action_links', 'searchandreplace_filter_plugin_actions', 10, 2);
	}
}


if ( is_admin() ) {
	add_action('init', 'searchandreplace_textdomain');
	add_action('in_admin_footer', 'searchandreplace_admin_footer');
	add_action('admin_menu', 'searchandreplace_add_settings_page');
	add_action('admin_print_scripts', 'searchandreplace_add_js_head' );
}

/* this does the important stuff! */
function searchandreplace_doit($search_text,
																$replace_text,
																$content              = TRUE,
																$guid                 = TRUE,
																$id                   = TRUE,
																$title                = TRUE,
																$excerpt              = TRUE,
																$meta_value           = TRUE,
																$comment_content      = TRUE,
																$comment_author       = TRUE,
																$comment_author_email = TRUE,
																$comment_author_url   = TRUE,
																$comment_count        = TRUE,
																$cat_description      = TRUE,
																$tag                  = TRUE,
																$user_id              = TRUE,
																$user_login           = TRUE
																) {
	global $wpdb;

	$myecho = '';
	// slug string
	$search_slug  = strtolower($search_text);
	$replace_slug = strtolower($replace_text);
	
	if (!$content && !$id && !$guid && !$title && !$excerpt && !$meta_value && !$comment_content && !$comment_author && !$comment_author_email && !$comment_author_url && !$comment_count && !$cat_description && !$tag && !$user_id && !$user_login) {
		return '<div class="error"><p><strong>' . __('Nothing (Checkbox) selected to modify!', 'searchandreplace'). '</strong></p></div><br class="clear" />';
	}

	$myecho .= '<div class="updated fade">' . "\n" . '<ul>';
	
	// post content
	if ($content) {
		$myecho .= "\n" . '<li>' . __('Looking @ post content', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('post_content', 'posts', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->posts ";
		$query .= "SET post_content = ";
		$query .= "REPLACE(post_content, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}

	// post id
	if ($id) {
		$myecho .= "\n" . __('Looking @ ID', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('ID', 'posts', $search_text);
		$myecho .= searchandreplace_results('post_parent', 'posts', $search_text);
		$myecho .= searchandreplace_results('post_id', 'postmeta', $search_text);
		$myecho .= searchandreplace_results('object_id', 'term_relationships', $search_text);
		$myecho .= searchandreplace_results('comment_post_ID', 'comments', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->posts ";
		$query .= "SET ID = ";
		$query .= "REPLACE(ID, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
		
		$query  = "UPDATE $wpdb->posts ";
		$query .= "SET post_parent = ";
		$query .= "REPLACE(post_parent, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);

		$query  = "UPDATE $wpdb->postmeta ";
		$query .= "SET post_id = ";
		$query .= "REPLACE(post_id, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);

		$query  = "UPDATE $wpdb->term_relationships ";
		$query .= "SET object_id = ";
		$query .= "REPLACE(object_id, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);

		$query  = "UPDATE $wpdb->comments ";
		$query .= "SET comment_post_ID = ";
		$query .= "REPLACE(comment_post_ID, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// post guid
	if ($guid) {
		$myecho .= "\n" . '<li>' . __('Looking @ GUID', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('guid', 'posts', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->posts ";
		$query .= "SET guid = ";
		$query .= "REPLACE(guid, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// post title
	if ($title) {
		$myecho .= "\n" . '<li>' . __('Looking @ Titeln', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('post_title', 'posts', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->posts ";
		$query .= "SET post_title = ";
		$query .= "REPLACE(post_title, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// post excerpt
	if ($excerpt) {
		$myecho .= "\n" . '<li>' . __('Looking @ post excerpts', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('post_excerpt', 'posts', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->posts ";
		$query .= "SET post_excerpt = ";
		$query .= "REPLACE(post_excerpt, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// meta_value
	if ($meta_value) {
		$myecho .= "\n" . '<li>' . __('Looking @ Meta Daten', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('meta_value', 'postmeta', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->postmeta ";
		$query .= "SET meta_value = ";
		$query .= "REPLACE(meta_value, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// comment content
	if ($comment_content) {
		$myecho .= "\n" . '<li>' . __('Looking @ modifying comments text', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('comment_content', 'comments', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->comments ";
		$query .= "SET comment_content = ";
		$query .= "REPLACE(comment_content, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// comment_author
	if ($comment_author) {
		$myecho .= "\n" . '<li>' . __('Looking @ modifying comments author', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('comment_author', 'comments', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->comments ";
		$query .= "SET comment_author = ";
		$query .= "REPLACE(comment_author, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// comment_author_email
	if ($comment_author_email) {
		$myecho .= "\n" . '<li>' . __('Looking @ modifying comments author e-mail', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('comment_author_email', 'comments', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->comments ";
		$query .= "SET comment_author_email = ";
		$query .= "REPLACE(comment_author_email, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// comment_author_url
	if ($comment_author_url) {
		$myecho .= "\n" . '<li>' . __('Looking @ modifying comments author URLs', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('comment_author_url', 'comments', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->comments ";
		$query .= "SET comment_author_url = ";
		$query .= "REPLACE(comment_author_url, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}

	// comment_count
	if ($comment_count) {
		$myecho .= "\n" . '<li>' . __('Looking @ Comment-Count', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('comment_count', 'posts', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->posts ";
		$query .= "SET comment_count = ";
		$query .= "REPLACE(comment_count, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}

	// category description
	if ($cat_description) {
		$myecho .= "\n" . '<li>' . __('Looking @ category description', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('description', 'term_taxonomy', $search_text);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->term_taxonomy ";
		$query .= "SET description = ";
		$query .= "REPLACE(description, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
	}
	
	// tags and category
	if ($tag) {
		$myecho .= "\n" . '<li>' . __('Looking @ Tags', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('name', 'terms', $search_text);
		$myecho .= searchandreplace_results('slug', 'terms', $search_slug);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->terms ";
		$query .= "SET name = ";
		$query .= "REPLACE(name, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
		
		$query  = "UPDATE $wpdb->terms ";
		$query .= "SET slug = ";
		$query .= "REPLACE(slug, \"$search_slug\", \"$replace_slug\") ";
		$wpdb->get_results($query);
	}

	// user_id
	if ($user_id) {
		$myecho .= "\n" . '<li>' . __('Looking @ User-ID', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('ID', 'users', $search_text);
		$myecho .= searchandreplace_results('user_id', 'usermeta', $search_slug);
		$myecho .= searchandreplace_results('post_author', 'posts', $search_slug);
		$myecho .= searchandreplace_results('link_owner', 'links', $search_slug);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->users ";
		$query .= "SET ID = ";
		$query .= "REPLACE(ID, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
		
		$query  = "UPDATE $wpdb->usermeta ";
		$query .= "SET user_id = ";
		$query .= "REPLACE(user_id, \"$search_slug\", \"$replace_slug\") ";
		$wpdb->get_results($query);
		
		$query  = "UPDATE $wpdb->posts ";
		$query .= "SET post_author = ";
		$query .= "REPLACE(post_author, \"$search_slug\", \"$replace_slug\") ";
		$wpdb->get_results($query);
		
		$query  = "UPDATE $wpdb->links ";
		$query .= "SET link_owner = ";
		$query .= "REPLACE(link_owner, \"$search_slug\", \"$replace_slug\") ";
		$wpdb->get_results($query);
	}

	// user_login
	if ($user_login) {
		$myecho .= "\n" . '<li>' . __('Looking @ User Login', 'searchandreplace') . ' ...';
		
		$myecho .= "\n" . '<ul>' . "\n";
		$myecho .= searchandreplace_results('user_login', 'users', $search_text);
		$myecho .= searchandreplace_results('user_nicename', 'users', $search_slug);
		$myecho .= "\n" . '</ul>' . "\n" . '</li>' . "\n";
		
		$query  = "UPDATE $wpdb->users ";
		$query .= "SET user_login = ";
		$query .= "REPLACE(user_login, \"$search_text\", \"$replace_text\") ";
		$wpdb->get_results($query);
		
		$query  = "UPDATE $wpdb->users ";
		$query .= "SET user_nicename = ";
		$query .= "REPLACE(user_nicename, \"$search_slug\", \"$replace_slug\") ";
		$wpdb->get_results($query);
	}

	$myecho .= "\n" . '</ul>' . "\n" . '</div><br class="clear"/>' . "\n";
	return $myecho;
}

/**
 * View results
 * @var: $field, $tabel
 */
function searchandreplace_results($field, $table, $search_text) {
	global $wpdb;
	
	$myecho  = '';
	$results = '';

	$myecho .= "\n" . '<li>';
	$results = "SELECT $field FROM " . $wpdb->$table . " WHERE $field LIKE \"%$search_text%\"";
	//$myecho .= $results . '<br />';
	$myecho .= __('... in table ', 'searchandreplace');
	$myecho .= '<code>' . $table . '</code>,' . ' field <code>' . $field . '</code>: ';
	$results = mysql_query($results);
	$total_results = (int)( @mysql_num_rows($results) );
	
	if (!$results) {
		$myecho .= __('The inquiry could not be implemented:', 'searchandreplace') . ' ' . mysql_error();
	} else {
	
		if ($total_results == 0) {
			$myecho .= ' - <strong>' . $total_results . '</strong> ';
		} else {
			
			while ( $row = mysql_fetch_assoc($results) ) {
				//echo $row[$field] . "\n";
				$myecho .= '|';
			}
			$myecho .= ' - <strong>' . $total_results . '</strong> ';
		}
		$myecho .= __('entries found.', 'searchandreplace');
		$myecho .= '</li>' . "\n";
	}
	return $myecho;
}


/**
 * add js to the head that fires thr 'new node' function
 */
function searchandreplace_add_js_head() {
?>
<script type="text/javascript">
/* <![CDATA[ */
function selectcb(thisobj,var1){
	var o = document.forms[thisobj].elements;
	if(o){
		for (i=0; i<o.length; i++){
			if (o[i].type == 'checkbox'){
				o[i].checked = var1;
			}
		}
	}
}
/* ]]> */
</script>
<?php
}


function searchandreplace_action() {

	if ( isset($_POST['submitted']) ) {
		check_admin_referer('searchandreplace_nonce');
		$myecho = '';
		if ( empty($_POST['search_text']) ) {
			$myecho .= '<div class="error"><p><strong>&raquo; ' . __('You must specify some text to replace!', 'searchandreplace') . '</strong></p></div><br class="clear">';
		} else {
			$myecho .= '<div class="updated fade">';
			$myecho .= '<p><strong>&raquo; ' . __('Attempting to perform search and replace ...', 'searchandreplace') . '</strong></p>';
			$myecho .= '<p>&raquo; ' . __('Search', 'searchandreplace') . ' <code>' . $_POST['search_text'] . '</code> ... ' . __('and replace with', 'searchandreplace') . ' <code>' . $_POST['replace_text'] . '</code></p>';
			$myecho .= '</div><br class="clear" />';
	
			$error = searchandreplace_doit(
																		$_POST['search_text'],
																		$_POST['replace_text'],
																		isset($_POST['content']),
																		isset($_POST['guid']),
																		isset($_POST['id']),
																		isset($_POST['title']),
																		isset($_POST['excerpt']),
																		isset($_POST['meta_value']),
																		isset($_POST['comment_content']),
																		isset($_POST['comment_author']),
																		isset($_POST['comment_author_email']),
																		isset($_POST['comment_author_url']),
																		isset($_POST['comment_count']),
																		isset($_POST['cat_description']),
																		isset($_POST['tag']),
																		isset($_POST['user_id']),
																		isset($_POST['user_login'])
																		);
											
			
			if ($error != '') {
				//$myecho .= '<div class="error"><p>' . __('Es gab eine St&ouml;rung!', 'searchandreplace') . '</p>';
				$myecho .= $error;
			} else {
				$myecho .= '<p>' . __('Completed successfully!', 'searchandreplace') . '</p></div>';
			}
		}

		echo $myecho;
	}
}


function searchandreplace_page() {
?>
	<div class="wrap" id="top">
		<h2><?php _e('Search &amp; Replace', 'searchandreplace'); ?></h2>

		<?php
		if ( current_user_can('edit_plugins') ) {
			searchandreplace_action();
		} else {
			wp_die('<div class="error"><p>' . __('You do not have sufficient permissions to edit plugins for this blog.', 'searchandreplace') . '</p></div>');
		}
		?>

		<div id="poststuff" class="dlm">
			<div class="postbox closed">
				<h3><?php _e('Information Search &amp; Replace', 'searchandreplace') ?></h3>
				<div class="inside">
					
					<p><?php _e('This plugin uses an standard SQL query so it modifies your database directly!<br /><strong>Attention: </strong>You <strong>cannot</strong> undo any changes made by this plugin. <strong>It is therefore advisable to <a href="http://bueltge.de/wp-datenbank-backup-mit-phpmyadmin/97/" title=\"click for tutorial\">backup your database</a> before running this plugin.</strong> No legal claims to the author of this plugin! <strong>Aktivate</strong> the plugin <strong>only</strong>, if you want to use it!', 'searchandreplace'); ?></p>
					<p><?php _e('Text search is case sensitive and has no pattern matching capabilites. This replace function matchs raw text so it can be used to replace HTML tags too.', 'searchandreplace'); ?></p>

				</div>
			</div>
		</div>

		<div id="poststuff" class="dlm">
			<div class="postbox" >
				<h3><?php _e('Search in', 'searchandreplace') ?></h3>
				<div class="inside">
					
					<form name="replace" action="" method="post">
						<?php wp_nonce_field('searchandreplace_nonce') ?>
						<table summary="config" class="widefat">
							<tr>
								<th><label for="content_label"><?php _e('Content', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='content' id='content_label' /></td>
								<td><label for="content_label"><?php _e('field:', 'searchandreplace'); ?> <code>post_content</code> <?php _e('table:', 'searchandreplace'); ?> <code>_posts</code></label></td>
							</tr>
							<?php if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$wpdb->prefix . 'terms'."'") ) == 1) { ?>
							<tr class="form-invalid">
								<th><label for="id_label"><?php _e('ID', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='id' id='id_label' /></td>
								<td><label for="id_label"><?php _e('field:', 'searchandreplace'); ?> <code>ID</code>, <code>post_parent</code>, <code>post_id</code>, <code>object_id</code> <?php _e('and', 'searchandreplace'); ?> <code>comments</code><br /><?php _e('table:', 'searchandreplace'); ?> <code>_posts</code>, <code>_postmeta</code>, <code>_term_relationships</code> <?php _e('and', 'searchandreplace'); ?> <code>_comment_post_ID</code></label></td>
							</tr>
							<?php } ?>
							<tr>
								<th><label for="guid_label"><?php _e('GUID', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='guid' id='guid_label' /></td>
								<td><label for="guid_label"><?php _e('field:', 'searchandreplace'); ?> <code>guid</code> <?php _e('table:', 'searchandreplace'); ?> <code>_posts</code></label></td>
							</tr>
							<tr class="form-invalid">
								<th><label for="title_label"><?php _e('Titles', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='title' id='title_label' /></td>
								<td><label for="title_label"><?php _e('field:', 'searchandreplace'); ?> <code>post_tilte</code> <?php _e('table:', 'searchandreplace'); ?> <code>_posts</code></label></td>
							</tr>
							<tr>
								<th><label for="excerpt_label"><?php _e('Excerpts', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='excerpt' id='excerpt_label' /></td>
								<td><label for="excerpt_label"><?php _e('field:', 'searchandreplace'); ?> <code>post_excerpt</code> <?php _e('table:', 'searchandreplace'); ?> <code>_posts</code></label></td>
							</tr>
							<tr class="form-invalid">
								<th><label for="meta_value_label"><?php _e('Meta Data', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='meta_value' id='meta_value_label' /></td>
								<td><label for="meta_value_label"><?php _e('field:', 'searchandreplace'); ?> <code>meta_value</code> <?php _e('table:', 'searchandreplace'); ?> <code>_postmeta</code></label></td>
							</tr>
							<tr>
								<th><label for="comment_content_label"><?php _e('Comments content', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='comment_content' id='comment_content_label' /></td>
								<td><label for="comment_content_label"><?php _e('field:', 'searchandreplace'); ?> <code>comment_content</code> <?php _e('table:', 'searchandreplace'); ?> <code>_comments</code></label></td>
							</tr>
							<tr class="form-invalid">
								<th><label for="comment_author_label"><?php _e('Comments author', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='comment_author' id='comment_author_label' /></td>
								<td><label for="comment_author_label"><?php _e('field:', 'searchandreplace'); ?> <code>comment_author</code> <?php _e('table:', 'searchandreplace'); ?> <code>_comments</code></label></td>
							</tr>
							<tr>
								<th><label for="comment_author_email_label"><?php _e('Comments author e-mail', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='comment_author_email' id='comment_author_email_label' /></td>
								<td><label for="comment_author_email_label"><?php _e('field:', 'searchandreplace'); ?> <code>comment_author_email</code> <?php _e('table:', 'searchandreplace'); ?> <code>_comments</code></label></td>
							</tr>
							<tr class="form-invalid">
								<th><label for="comment_author_url_label"><?php _e('Comments author URL', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='comment_author_url' id='comment_author_url_label' /></td>
								<td><label for="comment_author_url_label"><?php _e('field:', 'searchandreplace'); ?> <code>comment_author_url</code> <?php _e('table:', 'searchandreplace'); ?> <code>_comments</code></label></td>
							</tr>
							<tr>
								<th><label for="comment_count_label"><?php _e('Comments-Counter', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='comment_count' id='comment_count_label' /></td>
								<td><label for="comment_count_label"><?php _e('field:', 'searchandreplace'); ?> <code>comment_count</code> <?php _e('table:', 'searchandreplace'); ?> <code>_posts</code></label></td>
							</tr>
							<tr class="form-invalid">
								<th><label for="cat_description_label"><?php _e('Category description', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='cat_description' id='cat_description_label' /></td>
								<td><label for="cat_description_label"><?php _e('field:', 'searchandreplace'); ?> <code>description</code> <?php _e('table:', 'searchandreplace'); ?> <code>_term_taxonomy</code></label></td>
							</tr>
							<tr>
								<th><label for="tag_label"><?php _e('Tags &amp; Categories', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='tag' id='tag_label' /></td>
								<td><label for="tag_label"><?php _e('field:', 'searchandreplace'); ?> <code>name</code> <?php _e('and', 'searchandreplace'); ?> <code>slug</code> <?php _e('table:', 'searchandreplace'); ?> <code>_terms</code></label></td>
							</tr>
							<tr class="form-invalid">
								<th><label for="user_id_label"><?php _e('User-ID', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='user_id' id='user_id_label' /></td>
								<td><label for="user_id_label"><?php _e('field:', 'searchandreplace'); ?> <code>ID</code>, <code>user_id</code>, <code>post_author</code> <?php _e('and', 'searchandreplace'); ?> <code>link_owner</code><br /><?php _e('table:', 'searchandreplace'); ?><code>_users</code>, <code>_usermeta</code>, <code>_posts</code> <?php _e('and', 'searchandreplace'); ?> <code>_links</code></label></td>
							</tr>
							<tr>
								<th><label for="user_login_label"><?php _e('User-login', 'searchandreplace'); ?></label></th>
								<td colspan="2" style="text-align: center;"><input type='checkbox' name='user_login' id='user_login_label' /></td>
								<td><label for="user_login_label"><?php _e('field:', 'searchandreplace'); ?> <code>user_login</code> <?php _e('and', 'searchandreplace'); ?> <code>user_nicename</code> table: <code>_users</code></label></td>
							</tr>
							<tr class="form-invalid">
								<th>&nbsp;</th>
								<td colspan="2" style="text-align: center;">&nbsp;&nbsp; <a href="javascript:selectcb('replace', true);" title="<?php _e('Checkboxes to assign', 'searchandreplace'); ?>"><?php _e('all', 'searchandreplace'); ?></a> | <a href="javascript:selectcb('replace', false);" title="<?php _e('Checkboxes to unmask', 'searchandreplace'); ?>"><?php _e('none', 'searchandreplace'); ?></a></td>
								<td>&nbsp;</td>
							</tr>
						</table>

						<table summary="submit" class="form-table">
							<tr>
								<th><?php _e('Replace', 'searchandreplace'); ?></th>
								<td><input class="code" type="text" name="search_text" value="" size="80" /></td>
							</tr>
							<tr>
								<th><?php _e('with', 'searchandreplace'); ?></th>
								<td><input class="code" type="text" name="replace_text" value="" size="80" /></td>
							</tr>
						</table>
						<p class="submit">
							<input class="button" type="submit" value="<?php _e('Go', 'searchandreplace'); ?> &raquo;" />
							<input type="hidden" name="submitted" />
						</p>
					</form>

				</div>
			</div>
		</div>

		<div id="poststuff" class="dlm">
			<div class="postbox closed" >
				<h3><?php _e('Information on the plugin', 'searchandreplace') ?></h3>
				<div class="inside">
					<p><?php _e('&quot;Search and Replace&quot; originalplugin (en) created by <a href="http://thedeadone.net/">Mark Cunningham</a> and provided (comments) by durch <a href="http://www.gonahkar.com">Gonahkar</a>.<br />&quot;Search &amp; Replace&quot;, current version provided by <a href="http://bueltge.de">Frank Bueltge</a>.', 'searchandreplace'); ?></p>
					<p><?php _e('Further information: Visit the <a href="http://bueltge.de/wp-suchen-und-ersetzen-de-plugin/114/">plugin homepage</a> for further information or to grab the latest version of this plugin.', 'searchandreplace'); ?><br />&copy; Copyright 2006 - <?php echo date("Y"); ?> <a href="http://bueltge.de">Frank B&uuml;ltge</a> | <?php _e('You want to thank me? Visit my <a href="http://bueltge.de/wunschliste">wishlist</a>.', 'searchandreplace'); ?></p>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		<!--
		jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
		jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox.close-me').each(function(){
			jQuery(this).addClass("closed");
		});
		//-->
		</script>

</div>
<?php } ?>