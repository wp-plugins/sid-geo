<?php
/*
Plugin Name: Sidney's Geo Stuff
Plugin URI: http://www.sidney.ws4f.us/tag/wordpress
Description: Do some fun stuff with coordinates
Version: pre-alpha
Author: Fabian "Sidney" Winter
Author URI: http://www.sidney.ws4f.us
*/

/**
* ==============================================================================
* Shows the form for the "write" page
*/
function sid_geo_admin_form(){
	global $post, $table_prefix, $wpdb;
	
	echo('<fieldset id="geodiv">');
	echo('<legend>(Sid\'s) Location</legend>');
	
	$locs = $wpdb->get_results('SELECT loc, geo_ID FROM '.$table_prefix.'sid_geo WHERE post="1" ORDER BY loc DESC',ARRAY_A);

	echo('<select name="sid_geo_loc[]" multiple="multiple" size="5">');
	
	$postloc=array();
	if($post)
	{
		$locations = $wpdb->get_results('SELECT geo_id FROM '.$table_prefix.'sid_post2geo WHERE post_id="'.$post.'" AND type="post"',ARRAY_A);
		if($locations)
		{
			foreach($locations as $locations)
			{
				$postloc[] = $locations['geo_id'];
			};
		};
	};
	
	foreach ($locs as $loc)
	{
		if(in_array($loc['geo_ID'],$postloc)){
			echo('<option value="'.$loc['geo_ID'].'" selected="selected">'.$loc['loc'].'</option>');
		}
		else
		{
			echo('<option value="'.$loc['geo_ID'].'">'.$loc['loc'].'</option>');			
		};
	};
	echo('</select>');
	echo('</fieldset>');
}

/**
* ===========================================================================
* Saves the post tags to DB
*/
function sid_geo_save($postID){
	global $wpdb, $table_prefix;
	
	$wpdb->query('DELETE FROM '.$table_prefix.'sid_post2geo WHERE post_id="'.$postID.'"');
	
	foreach($_POST['sid_geo_loc'] AS $location)
	{
		$wpdb->query('INSERT INTO '.$table_prefix.'sid_post2geo SET post_id="'.$postID.'", geo_id="'.$location.'", type="post"');
	};
}

/**
* ===================================================================
* Displays and handles the "options" page
*/
function sid_geo_option_page($unused){
	global $table_prefix, $wpdb;
	
	if(isset($_GET['sid_geo_delete_id']))
	{
		$wpdb->query('DELETE FROM '.$table_prefix.'sid_geo WHERE geo_ID="'.$_GET['sid_geo_delete_id'].'"');
		$wpdb->query('DELETE FROM '.$table_prefix.'sid_post2geo WHERE geo_id="'.$_GET['sid_geo_delete_id'].'"');
	};
	
	if(isset($_POST['sid_geo_locs_submit']))
	{
		foreach($_POST['sid_geo_locations'] AS $id => $values)
		{
			if($values['loc'])
			{
				$wpdb->query('REPLACE INTO '.$table_prefix.'sid_geo SET geo_ID="'.$id.'", loc="'.$values['loc'].'", lat="'.$values['lat'].'", lon="'.$values['lon'].'", description="'.$values['description'].'", post="'.($values['post']?'1':'0').'", comment="'.($values['comment']?'1':'0').'"');
			};
		};
		
		update_option('sid_geo_comments_no',$_POST['sid_geo_comments_no']);
	};
	
	if(isset($_POST['sid_geo_redir_submit']))
	{
		update_option('sid_geo_redir_on',$_POST['sid_geo_redir_on']);
		update_option('sid_geo_redir_delta',$_POST['sid_geo_redir_delta']);
	};
	
	$locations = $wpdb->get_results('SELECT geo_ID, loc, lat, lon, description, post, comment FROM '.$table_prefix.'sid_geo')
	?>
	<div class=wrap>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<h2><?php _e('Sidney\'s Geostuff'); ?></h2>

		<table>
			<thead>
				<tr>
					<td><?php _e('Location (name)'); ?></td>
					<td><?php _e('Latitude'); ?></td>
					<td><?php _e('Longitude'); ?></td>
					<td><?php _e('Description'); ?></td>
					<td><?php _e('Accessible for posts'); ?></td>
					<td><?php _e('Accessible for comments'); ?></td>
					<td><?php _e('Options'); ?></td>
					<!--<td><?php _e('Number of posts'); ?></td>
					<td><?php _e('Number of comments'); ?></td>-->
				</tr>
			</thead>
			<tbody>
				<?php
				if($locations)
				{
					foreach($locations as $location)
					{
						echo('<tr>
							<td><input type="text" name="sid_geo_locations['.$location->geo_ID.'][loc]" size="20" maxlength="50" value="'.$location->loc.'" /></td>
							<td><input type="text" name="sid_geo_locations['.$location->geo_ID.'][lat]" size="7" value="'.$location->lat.'" /></td>
							<td><input type="text" name="sid_geo_locations['.$location->geo_ID.'][lon]" size="7" value="'.$location->lon.'" /></td>
							<td><textarea name="sid_geo_locations['.$location->geo_ID.'][description]">'.$location->description.'</textarea></td>
							<td><input type="checkbox" name="sid_geo_locations['.$location->geo_ID.'][post]"'.($location->post?' checked="checked"':'').' /></td>
							<td><input type="checkbox" name="sid_geo_locations['.$location->geo_ID.'][comment]"'.($location->comment?' checked="checked"':'').' /></td>
							<td><a href="'.add_query_arg('sid_geo_delete_id',$location->geo_ID).'">'.__('Delete').'</a></td>
						</tr>');
						$max = $location->geo_ID;
					};
				};
				$max += 1;
				?>
				<tr id="new_row">
					<td><input type="text" name="sid_geo_locations[<?php echo($max); ?>][loc]" size="20" maxlength="50" value="" /></td>
					<td><input type="text" name="sid_geo_locations[<?php echo($max); ?>][lat]" size="7" value="" /></td>
					<td><input type="text" name="sid_geo_locations[<?php echo($max); ?>][lon]" size="7" value="" /></td>
					<td><textarea name="sid_geo_locations[<?php echo($max); ?>][description]"></textarea></td>
					<td><input type="checkbox" name="sid_geo_locations[<?php echo($max); ?>][post]" /></td>
					<td><input type="checkbox" name="sid_geo_locations[<?php echo($max); ?>][comment]" /></td>
					<td />
				</tr>
			</tbody>
		</table>
		
		<label for="sid_geo_comments_no"><?php _e('Allow a "No Location" field for comments'); ?></label>
			<input type="checkbox" name="sid_geo_comments_no" id="sid_geo_comments_no" <?php echo(get_option('sid_geo_comments_no')==true?' checked="checked"':''); ?> />
		
		<div class="submit"><input type="submit" name="sid_geo_locs_submit" value="<?php _e('Update/Add locations'); ?>" /></div>
				
		<fieldset>
			<legend><php _e('/geo/-Redirect'); ?></php></legend>
			<label for="sid_geo_redir_on"><?php _e('Enable /geo/-redirect'); ?></label>
				<input type="checkbox" name="sid_geo_redir_on" id="sid_geo_redir_on" <?php echo(get_option('sid_geo_redir_on')==true?' checked="checked"':''); ?> /><br />
			<label for="sid_geo_redir_delta"><?php _e('Allowed blur for lat+lon (geo/lat:x+lon:y)'); ?></label>
				<input type="text" size="2" name="sid_geo_redir_delta" id="sid_geo_redir_delta" value="<?php echo(get_option('sid_geo_redir_delta')); ?>" />
			<div class="submit"><input type="submit" name="sid_geo_redir_submit" value="<?php _e('Save'); ?>" /></div>
		</fieldset>
		
		</form>
	</div>
	<?php
}

/**
* ====================================================================
* Installing functions
*/
function sid_geo_install () {
	global $table_prefix, $wpdb;

	$result = mysql_list_tables(DB_NAME);
	$tables = array();

	while ($row = mysql_fetch_row($result)) { $tables[] = $row[0]; }
  
	$first_install = false;
  
	if (!in_array($table_prefix.'sid_geo', $tables)) {
		$first_install = true;
	}

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

	$sql = 'CREATE TABLE `'.$table_prefix.'sid_geo` (
		`geo_ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
		`loc` VARCHAR( 50 ) NOT NULL ,
		`lat` FLOAT NOT NULL ,
		`lon` FLOAT NOT NULL ,
		`description` longtext,
		`post` ENUM( \'0\', \'1\' ) NOT NULL ,
		`comment` ENUM( \'0\', \'1\' ) NOT NULL ,
		PRIMARY KEY ( `geo_ID` )
		)';

	maybe_create_table($table_prefix.'sid_geo',$sql);
	
	// Relation DB
	$sql = 'CREATE TABLE `'.$table_prefix.'sid_post2geo` (
		`rel_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
		`post_id` BIGINT UNSIGNED NOT NULL ,
		`geo_id` BIGINT UNSIGNED NOT NULL ,
		`type` ENUM( \'post\', \'comment\' ) NOT NULL, 
		PRIMARY KEY ( `rel_id` )
		)';
		
	maybe_create_table($table_prefix.'sid_post2geo',$sql);
		
	// First entry
	if($first_install==true)
	{	
		$sql = 'INSERT INTO '.$table_prefix.'sid_geo ( `geo_ID` , `loc` , `lat` , `lon` , `post` , `comment` )
			VALUES (
			\'1\', \'My girl\\\'s Heart\', \'42\', \'42\', \'1\', \'0\'
			)';
		dbDelta($sql);
		add_option('sid_geo_redir_on','1');
		add_option('sid_geo_redir_delta','5');
		add_option('sid_geo_comments_no','1');
	};
}

function sid_geo_prepare_locs(){
	global $post, $sid_geo_locs, $sid_geo_pointer, $wpdb, $table_prefix;
	$sid_geo_locs = $wpdb->get_results('SELECT lat, lon, loc, description FROM '.$table_prefix.'sid_geo g INNER JOIN '.$table_prefix.'sid_post2geo p2g ON (p2g.geo_id=g.geo_ID) WHERE p2g.post_id="'.$post->ID.'" AND type="post"',ARRAY_A);
	$sid_geo_pointer = 0;
}

function sid_geo_next_loc(){
	global $sid_geo_pointer, $sid_geo_locs;
	$sid_geo_pointer ++;
	if($sid_geo_pointer > count($sid_geo_locs))
	{
		$sid_geo_pointer = count($sid_geo_locs);
	};
}

function sid_geo_have_locs(){
	global $post, $sid_geo_locs, $sid_geo_pointer, $wpdb, $table_prefix;

	if(!isset($sid_geo_locs))
	{
		$sid_geo_locs = $wpdb->get_results('SELECT lat, lon, loc, description FROM '.$table_prefix.'sid_geo g INNER JOIN '.$table_prefix.'sid_post2geo p2g ON (p2g.geo_id=g.geo_ID) WHERE p2g.post_id="'.$post->ID.'" AND type="post"',ARRAY_A);
		$sid_geo_pointer = -1;
	};

	$sid_geo_pointer++;
	if($sid_geo_pointer > (count($sid_geo_locs)-1))
	{
		unset($GLOBALS['sid_geo_locs']);
		unset($GLOBALS['sid_geo_pointer']);
		return false;
	} else {
		return true;
	};
}

function sid_geo_get_lon(){
	global $sid_geo_locs, $sid_geo_pointer;
	return $sid_geo_locs[$sid_geo_pointer]['lon'];
}

function sid_geo_the_lon(){
	echo(sid_geo_get_lon());
}

function sid_geo_get_lat(){
	global $sid_geo_locs, $sid_geo_pointer;
	return $sid_geo_locs[$sid_geo_pointer]['lat'];
}

function sid_geo_the_lat(){
	echo(sid_geo_get_lat());
}

function sid_geo_get_loc(){
	global $sid_geo_locs, $sid_geo_pointer;
	return $sid_geo_locs[$sid_geo_pointer]['loc'];
}

function sid_geo_the_loc(){
	echo(sid_geo_get_loc());
}

function sid_geo_get_description(){
	global $sid_geo_locs, $sid_geo_pointer;
	return $sid_geo_locs[$sid_geo_pointer]['description'];
}

function sid_geo_the_descrition(){
	echo(sid_geo_get_description);
}

function sid_geo_the_map($service){
	echo(sid_geo_get_map($service));
}

function sid_geo_get_map($service){
	global $post, $sid_geo_locs, $sid_geo_pointer;
	$mapurls = array (
		'AcmeMap' => 'http://www.acme.com/mapper?lat=$lat&amp;long=$lon&amp;scale=11&amp;theme=Image&amp;width=3&amp;height=2&amp;dot=Yes',
		'GeoURL' => 'http://geourl.org/near/?lat=$lat&amp;lon=$lon&amp;dist=500',
		'GeoCache' => 'http://www.geocaching.com/seek/nearest.aspx?origin_lat=$lat&amp;origin_long=$lon&amp;dist=5',
		'MapQuest' => 'http://www.mapquest.com/maps/map.adp?latlongtype=decimal&amp;latitude=$lat&amp;longitude=$lon',
		'SideBit' => 'http://www.sidebit.com/ProjectGeoURLMap.php?lat=$lat&amp;lon=$lon',
		'DegreeConfluence' => 'http://confluence.org/confluence.php?lat=$lat&amp;lon=$lon',
		'TopoZone' => 'http://www.topozone.com/map.asp?lat=$lat&amp;lon=$lon',
		'FindU' => 'http://www.findu.com/cgi-bin/near.cgi?lat=$lat&amp;lon=$lon&amp;scale=100000&amp;zoom=50&amp;type=1&amp;icon=0&amp;&amp;scriptfile=http://mapserver.maptech.com/api/espn/index.cfm',
		'MapTech' => 'http://mapserver.maptech.com/api/espn/index.cfm?lat=$lat&amp;lon=$lon',
		'GoogleMaps' => 'http://maps.google.com/maps?ll=$lat%2C$lon&amp;spn=0.015839,0.032747',
        );

	$services = array_keys($mapurls);

	if(!in_array($service,$services))
	{
		return false;
	} else {
		$url = str_replace('$lat',urlencode($sid_geo_locs[$sid_geo_pointer]['lat']),$mapurls[$service]);
		$url = str_replace('$lon',urlencode($sid_geo_locs[$sid_geo_pointer]['lon']),$url);
		$url = str_replace('$loc',urlencode($sid_geo_locs[$sid_geo_pointer]['loc']),$url);
		$url = str_replace('$desc',urlencode($sid_geo_locs[$sid_geo_pointer]['desc']),$url);
		return $url;
	};
}

function sid_geo_options() {
    if (function_exists('add_options_page')) {
		add_options_page('Geostuff', 'Geostuff', 10, basename(__FILE__), 'sid_geo_option_page');
    }
}

function sid_geo_template_redirect() {
	if(file_exists(TEMPLATEPATH.'/geo.php'))
	{
		$template = (TEMPLATEPATH . '/geo.php');
	} else {
		$template = get_category_template();
	};
	
	include $template;
}

function sid_geo_rewrite_rules(&$rules) {
	$rules["^geo/?(.+)"] = "/index.php?geo=$1 [QSA]";

	return $rules;
}

function sid_geo_filter_query_vars($query_vars) {  
	$query_vars[] = 'geo';  
	return $query_vars;  
}

function sid_geo_parse_query(){
	if (!empty($GLOBALS['geo']))
	{
		global $wp_query;
		$wp_query->is_single = false;
		$wp_query->is_page = false;
		$wp_query->is_archive = true;
		$wp_query->is_search = false;
		$wp_query->is_home = false;

		add_filter('posts_join', 'sid_geo_posts_join');
		add_filter('posts_where', 'sid_geo_posts_where');
		add_action('template_redirect', 'sid_geo_template_redirect');
	};
}

function sid_geo_posts_join($join){
	global $table_prefix, $wpdb;
	
	$join .= 'LEFT JOIN '.$table_prefix.'sid_post2geo p2g ON ('.$wpdb->posts.'.ID=p2g.post_id) LEFT JOIN '.$table_prefix.'sid_geo g ON (p2g.geo_id=g.geo_ID)';
	return $join;
}

function sid_geo_posts_where($where){
	if(eregi('lat:([0-9.]+) lon:([0-9.]+)',$GLOBALS['geo'],$coords))
	{
		$delta = get_option('sid_geo_redir_delta');
		$where .= ' AND (g.lat BETWEEN '.($coords[1]-$delta).' AND '.($coords[1]+$delta).') AND (g.lon BETWEEN '.($coords[2]-$delta).' AND '.($coords[2]+$delta).')';
	} else {
		$where .= ' AND g.loc="'.urldecode($GLOBALS['geo']).'" ';
	};
	
	return $where;
}

function sid_geo_header(){
	global $wpdb, $table_prefix, $wp_query;
	
	$locs = $wpdb->get_results('SELECT g.lat, g.lon FROM '.$table_prefix.'sid_post2geo p2g INNER JOIN '.$table_prefix.'sid_geo g ON(p2g.geo_id=g.geo_ID) WHERE p2g.post_id="'.$wp_query->post->ID.'" LIMIT 1',ARRAY_A);
	echo('<meta name="ICBM" content="'.$locs[0]['lat'].', '.$locs[0]['lon'].'" />'."\n");
	echo('<meta name="geo.position" content="'.$locs[0]['lat'].';'.$locs[0]['lon'].'" />'."\n");
}

function sid_geo_comment_form(){
	global $wpdb, $table_prefix;

	$locs = $wpdb->get_results('SELECT loc, geo_ID FROM '.$table_prefix.'sid_geo WHERE comment="1"',ARRAY_A);

	if(count($locs)>0)
	{
		echo('<select name="sid_geo">');
		if(get_option('sid_geo_comments_no')==true)
		{
			echo('<option value="-1">'.__('Nowhere').'</option>');
		};
		foreach($locs AS $loc)
		{
			echo('<option value="'.$loc['geo_ID'].'">'.$loc['loc'].'</option>');
		};

		echo('</select>');
	};
}

function sid_geo_comment_save($comment_id){
	global $wpdb, $table_prefix;
	if($_POST['sid_geo']!=-1)
	{
		$wpdb->query('INSERT INTO '.$table_prefix.'sid_post2geo SET geo_id="'.$_POST['sid_geo'].'", type="comment", post_id="'.$comment_id.'"');
	};
}

function sid_geo_comment_loc(){
	global $wpdb, $table_prefix, $comment;
	
	$loc = $wpdb->get_results('SELECT g.loc FROM '.$table_prefix.'sid_post2geo p2g INNER JOIN '.$table_prefix.'sid_geo g ON (p2g.geo_id=g.geo_ID) WHERE (p2g.type="comment") AND (p2g.post_id="'.$comment->comment_ID.'")',ARRAY_A);
	return $loc[0]['loc'];
}

// Install
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
   add_action('init', 'sid_geo_install');
}


// Add the form stuff
//add_action('simple_edit_form', 'sid_lang_admin_form');
add_action('edit_form_advanced', 'sid_geo_admin_form');

// Save changes to tagsadd_action('publish_post', 'sid_geo_save');add_action('edit_post', 'sid_geo_save');
add_action('save_post', 'sid_geo_save');

// Add Options page
add_action('admin_menu', 'sid_geo_options');

// Add Js to pages
//add_action('wp-head','sid_geo_show_js');

// Add stuff for /geo/loc
if(get_option('sid_geo_redir_on')==1)
{
	add_filter('rewrite_rules_array', 'sid_geo_rewrite_rules');
	add_filter('query_vars', 'sid_geo_filter_query_vars');
	add_action('parse_query', 'sid_geo_parse_query');
};

add_action('wp-head','sid_geo_header');

add_action('comment_form','sid_geo_comment_form');
add_action('comment_post','sid_geo_comment_save');

?>