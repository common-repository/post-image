<?php
/*
 * Plugin Name:   Post Image
 * Version:       1.0
 * Plugin URI:    http://wordpress.org/extend/plugins/post-image/
 * Description:   This plugin gives you the flexibility to upload images and icons from the plugin configutation page and able to view the uploaded images with the detail information of the image.The align position of the image in a post can also be set.Then the image can be automatically set from the add new post section without any complexity. Adjust your settings <a href="options-general.php?page=PostImage">here</a>.
 * Author:        MaxBlogPress
 * Author URI:    http://www.maxblogpress.com
 *
 * License:       GNU General Public License
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * Copyright (C) 2007 www.maxblogpress.com
 *
 */
$mbppi_path      = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
$mbppi_path      = str_replace('\\','/',$mbppi_path);
$mbppi_dir       = substr($mban_path,0,strrpos($mbppi_path,'/'));
$mbppi_siteurl   = get_bloginfo('wpurl');
$mbppi_siteurl   = (strpos($mbppi_siteurl,'http://') === false) ? get_bloginfo('siteurl') : $mbppi_siteurl;
$mbppi_fullpath  = $mbppi_siteurl.'/wp-content/plugins/'.$mbppi_dir.'';
$mbppi_fullpath  = $mbppi_fullpath.'post-image/';
$mbppi_abspath   = str_replace("\\","/",ABSPATH); 

define('MBP_PI_ABSPATH', $mbppi_path);
define('MBP_PI_LIBPATH', $mbppi_fullpath);
define('MBP_PI_SITEURL', $mbppi_siteurl);
define('MBP_PI_NAME', 'Post Image');
define('MBP_PI_VERSION', '1.0');  
define('MBP_PI_LIBPATH', $mbppi_fullpath);

define('WPIDIR', dirname(__FILE__) . '/');
define('WPIURL', get_option('siteurl'));
define('IMGROOT', WPIDIR . 'img/');
define('WPICSS', WPIURL . '/wp-content/plugins/post-image/css/');
define('WPIIMG', WPIURL . '/wp-content/plugins/post-image/img/');
define("MBP_PI_IMGDIR", $mbppi_abspath . '/wp-content/plugins/post-image/img/');

class MPPostImage {
	var $version = '1.0';
	
	# is full-uninstall?
	var $full_uninstall = false;
	
	# __construct()
	function MPPostImage() {
		global $wpdb, $wp_version;
		
		#create pic upload directory
		if (!file_exists(MBP_PI_IMGDIR)) {
			mkdir(MBP_PI_IMGDIR);
			@chmod(MBP_PI_IMGDIR,0666);
		}		
		
		# table names init
		$this -> db = array(
			'posts_pictures' => $wpdb -> prefix . 'wpi_posts_pictures'
		);
		
		# is installed?
		$this -> installed = get_option('wpi_version') == $this -> version;
		
		# actions
		add_action('admin_head', array( &$this, 'ecf_add_meta_box' ) );  // menu support 
		add_action('activate_post-image/post-image.php', array(&$this, 'install'));		// install
		add_action('deactivate_post-image/post-image.php', array(&$this, 'uninstall'));	// uninstall
		add_action('wp_head', array(&$this, 'style'));										// load style
		//add_action('dbx_post_sidebar', array(&$this, 'dropdown'));							// dropdown
		
		add_action('publish_post', array(&$this, 'save'));									// publish
		add_action('save_post', array(&$this, 'save'));		
										// save
		add_action('delete_post', array(&$this, 'delete'));									// delete
		add_action('admin_menu', array(&$this, 'adminMenu'));								// adminMenu
		
		# filters
		add_filter('the_content', array(&$this, 'the_content'));							// content
	}
  
    function ecf_add_meta_box(){
		global $wp_version;
		if ( $wp_version > 2.1 &&  $wp_version < 2.7 ) { 
			add_action( 'dbx_post_advanced', array( &$this, 'insert_gui' ) );
			if( $wp_version >= 2.5 &&  $wp_version <= 2.6 ) { 
				add_action( 'edit_post_form', array( &$this, 'insert_gui' ) );		
			} else {
				add_action( 'dbx_post_advanced', array( &$this, 'insert_gui' ) );		
			}
		}
	
		if( $wp_version > 2.6 ) {   
			add_meta_box('easy-cf', 'Select Image', array(&$this, 'insert_gui') , 'post', 'advanced');			
			
		}			
	}
  
  #template building function	
  function insert_gui() {  
    global $wp_version;
	//for showing image
	$js = "<script type=\"text/javascript\" src=\"" .  MBP_PI_LIBPATH  . "script.js\"></script>";
	echo $js;
	
	//for select box selected
	$query = "SELECT picture FROM wp_wpi_posts_pictures WHERE ID='" . $_GET['post'] . "'";
	$sql   = mysql_query($query);
	$rs	   = mysql_fetch_array($sql);
	 	
	if( $wp_version >= 2.1 &&  $wp_version < 2.5 ) {
	?>
		<div class='dbx-b-ox-wrapper'>
				<fieldset id='trackbacksdiv' class='dbx-box'>
				<div class='dbx-h-andle-wrapper'>
				<h3 class='dbx-handle'>Select Image</h3>
				</div>
			    <div class='dbx-c-ontent-wrapper'>
					<div class='dbx-content'>
							<?php if ($rs['picture'] != '') { ?>
								<div id="view_def_image">
								<img src="<?php echo MBP_PI_LIBPATH . "image.php?m=t&p=" . urlencode(WPIIMG) . "&f=" . urlencode($rs['picture']);?>" />
								</div>
							<?php } ?>
							
							<div id="view_image" style="display:none"></div>		
							<select id="select_picture" name="select_picture" onchange="showImage('<?php echo MBP_PI_LIBPATH;?>', '<?php echo WPIIMG; ?>')"> 
							<option value="0">Select Image</option>
							<?php
							if ($dir = opendir(IMGROOT)) { 
								while(false !== ($file = readdir($dir))) {
									if ($file != '.' && $file != '..') {
										$extension = strtolower(substr($file, strrpos($file, '.')+1));
										if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
										
											if ($file == $rs['picture']) {
												$selected = 'selected';
											} else {
												$selected = '';
											}
									
											echo '<option ' . $selected . ' value="' . $file . '">' . $file . '</option>' . "\n";
										}
									}
								}
								closedir($dir);
							}
							?>
					
						</select>				
					</div>
			       </div>
		           </fieldset>
		           </div>		
	<?php } ?>
	
	
	
	<?php
	if ( $wp_version >= 2.5 &&  $wp_version < 2.7 ) { ?>
		<div id='trackbacksdiv22' class='postbox closed'><h3>Select Image</h3><div class='inside'><p>
							<?php if ($rs['picture'] != '') { ?>
								<div id="view_def_image">
								<img src="<?php echo MBP_PI_LIBPATH . "image.php?m=t&p=" . urlencode(WPIIMG) . "&f=" . urlencode($rs['picture']);?>" />
								</div>
							<?php } ?>
							
							<div id="view_image" style="display:none"></div>											
						<select id="select_picture" name="select_picture" onchange="showImage('<?php echo MBP_PI_LIBPATH;?>', '<?php echo WPIIMG; ?>')"> 
							<option value="0">Select Image</option>
							<?php
							if ($dir = opendir(IMGROOT)) { 
								while(false !== ($file = readdir($dir))) {
									if ($file != '.' && $file != '..') {
										$extension = strtolower(substr($file, strrpos($file, '.')+1));
										if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
										
											if ($file == $rs['picture']) {
												$selected = 'selected';
											} else {
												$selected = '';
											}
									
											echo '<option ' . $selected . ' value="' . $file . '">' . $file . '</option>' . "\n";
										}
									}
								}
								closedir($dir);
							}
							?>
					
						</select>			
		</p></div></div>
	<?php }
		if ($wp_version > 2.7) {
  	?>
  	
			<?php if ($rs['picture'] != '') { ?>
				<div id="view_def_image">
				<img src="<?php echo MBP_PI_LIBPATH . "image.php?m=t&p=" . urlencode(WPIIMG) . "&f=" . urlencode($rs['picture']);?>" />
				</div>
			<?php } ?>
			
			<div id="view_image" style="display:none"></div>								
			<select id="select_picture" name="select_picture" onchange="showImage('<?php echo MBP_PI_LIBPATH;?>', '<?php echo WPIIMG; ?>')"> 
				<option value="0">Select Image</option>
				<?php
				if ($dir = opendir(IMGROOT)) { 
					while(false !== ($file = readdir($dir))) {
						if ($file != '.' && $file != '..') {
							$extension = strtolower(substr($file, strrpos($file, '.')+1));
							if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
							
								if ($file == $rs['picture']) {
									$selected = 'selected';
								} else {
									$selected = '';
								}
						
								echo '<option ' . $selected . ' value="' . $file . '">' . $file . '</option>' . "\n";
							}
						}
					}
					closedir($dir);
				}
				?>
		
			</select>		
	
  <?php 
  	}
  } 	
	
	# install plugin
	function install() {
		global $wpdb;
		
		if (file_exists(ABSPATH . '/wp-admin/upgrade-functions.php'))
      		require_once(ABSPATH . '/wp-admin/upgrade-functions.php');
    	else
      		require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
			
		if (!$this -> installed) {
			# wp_wpi_posts_pictures
			dbDelta("CREATE TABLE `{$this -> db['posts_pictures']}` (
						`ID` BIGINT UNSIGNED NOT NULL DEFAULT 0,
						`picture` VARCHAR(255) NOT NULL,
						PRIMARY KEY(`ID`)
					)");
			# options
			add_option('wpi_version', $this -> version);
			add_option('wpi_position', 'right');
			$this -> installed = true;
		}
	}
	
	# uninstall plugin
	function uninstall() {
		global $wpdb;
		
		if ($this -> full_uninstall) {
			# delete tables
			foreach ($this -> db as $table) {
				$wpdb -> query("DROP TABLE `{$table}`");
			}
			# delete options
			delete_option('wpi_version');
			delete_option('wpi_position');
		}
	}
	
	# called before content is shown
	function the_content($content) {
		global $wpdb,$post;
		$postID = $post -> ID;
		if ($result = $wpdb -> get_results("SELECT * FROM `{$this -> db['posts_pictures']}` WHERE `ID`={$postID}")) {
			$position = strtolower(get_option('wpi_position'));
			$content = '<img class="wpi_img_' . $position . '" src="' . WPIIMG . $result[0] -> picture . '" title="' . $result[0] -> picture . '" />' . $content;
		}
		return $content;
	}
	
	# load style when page is loaded
	function style() {
		echo "
		<style>
		<!--
			.wpi_img_left,.wpi_img_right {
				margin-bottom:15px;
				background:#eee;
				padding:2px;
				border:1px solid #d0d0d0;
			}
			.wpi_img_left {
				margin-right:15px;
				float:left;
			}
			.wpi_img_right {
				margin-left:15px;
				float:right;
			}
			*+html .wpi_img_left {
				margin-top:20px;
			}
			*+html .wpi_img_right {
				margin-top:20px;
			}
		-->
		</style>
		";
	}
	
	# show dropdown for selectting a picture
	function dropdown() {
		
		//for showing image
		$js = "<script type=\"text/javascript\" src=\"" .  MBP_PI_LIBPATH  . "script.js\"></script>";
		echo $js;
		
		//for select box selected
		$query = "SELECT picture FROM wp_wpi_posts_pictures WHERE ID='" . $_GET['post'] . "'";
		$sql   = @mysql_query($query);
		$rs	   = @mysql_fetch_array($sql);
	?>
	

	<fieldset id="wpi_dropdown_pictures" class="dbx-box">
	<h3 class="dbx-handle">Select Picture</h3>';
	<a href="javascript:void(0)" onClick="showImage('<?php echo MBP_PI_LIBPATH;?>')">
		Show Image
	</a>	
	
		<div class="dbx-content">
				<p>
					<select id="select_picture" name="select_picture"> 
					<option value="0">Select Image</option>
		<?php
		if ($dir = opendir(IMGROOT)) { 
			while(false !== ($file = readdir($dir))) {
				if ($file != '.' && $file != '..') {
					$extension = strtolower(substr($file, strrpos($file, '.')+1));
					if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
					
						if ($file == $rs['picture']) {
							$selected = 'selected';
						} else {
							$selected = '';
						}
				
						echo '<option ' . $selected . ' value="' . $file . '">' . $file . '</option>' . "\n";
					}
				}
			}
			closedir($dir);
		}
		?>

					</select>
				</p>
			</div>
		</fieldset>
	<?php }
	
	# called when post is published or saved
	function save($postID) {
		global $wpdb;
		if ($_POST['select_picture'] == 0) {
			$query = "DELETE FROM `{$this -> db['posts_pictures']}` WHERE ID='" . $postID . "'";
			@mysql_query($query);
		} else {
			if (isset($_POST['select_picture']) && !empty($_POST['select_picture'])) {
				$is_exists = $wpdb -> get_var("SELECT COUNT(*) FROM `{$this -> db['posts_pictures']}` WHERE `ID`={$postID}");
					if ($is_exists) {
						$wpdb -> query("UPDATE `{$this -> db['posts_pictures']}` SET `picture`='{$_POST['select_picture']}' WHERE `ID`={$postID}");
					} else {
						$wpdb -> query("INSERT INTO `{$this -> db['posts_pictures']}` VALUES({$postID},'{$_POST['select_picture']}')");
					}
			}
		}	
	}
	
	# called when post is deleted
	function delete($postID) {
		global $wpdb;
		$wpdb -> query("DELETE FROM `{$this -> db['posts_pictures']}` WHERE `ID`={$postID}");
	}
	
	# adds the Post Image item to menu
	function adminMenu() {
		add_options_page('PostImage', 'Post Image', 1, 'PostImage', array(&$this, 'admin'));
	}
	
	function admin() {
		
	$mbp_pi_activate = get_option('mbp_pi_activate');
	$reg_msg = '';
	$mbp_pi_msg = '';
	$form_1 = 'mbp_pi_reg_form_1';
	$form_2 = 'mbp_pi_reg_form_2';
		// Activate the plugin if email already on list
	if ( trim($_GET['mbp_onlist']) == 1 ) {
		$mbp_pi_activate = 2;
		update_option('mbp_pi_activate', $mbp_pi_activate);
		$reg_msg = 'Thank you for registering the plugin. It has been activated'; 
	} 
	// If registration form is successfully submitted
	if ( ((trim($_GET['submit']) != '' && trim($_GET['from']) != '') || trim($_GET['submit_again']) != '') && $mbp_pi_activate != 2 ) { 
		update_option('mbp_pi_name', $_GET['name']);
		update_option('mbp_pi_email', $_GET['from']);
		$mbp_pi_activate = 1;
		update_option('mbp_pi_activate', $mbp_pi_activate);
	}
	if ( intval($mbp_pi_activate) == 0 ) { // First step of plugin registration
		global $userdata;
		$this->mbp_piRegisterStep1($form_1,$userdata);
	} else if ( intval($mbp_pi_activate) == 1 ) { // Second step of plugin registration
		$name  = get_option('mbp_pi_name');
		$email = get_option('mbp_pi_email');
		$this->mbp_piRegisterStep2($form_2,$name,$email);
	} else if ( intval($mbp_pi_activate) == 2 ) { // Options page
		if ( trim($reg_msg) != '' ) {
			echo '<div id="message" class="updated fade"><p><strong>'.$reg_msg.'</strong></p></div>';
		}		
		
		if (@$_GET['wpi'] == 'save') {
			$position = strtolower($_GET['position']);
			if ($position === 'left' || $position === 'right') {
				update_option('wpi_position', $position);
				echo '<div id="message" class="updated fade"><p>Position Configuration <strong>Saved</strong>.</p></div>';
			} else {
				echo '<div id="message" class="updated fade"><p>Error! Unkown Position.</p></div>';
			}
		}
		if (@$_POST['wpi'] == 'upload') {
			$file_size_max = 1000000;
			if ($_FILES['image']['size'] > $file_size_max) {
				echo '<div id="message" class="updated fade"><p>Error! Picture is too large, the max size is 1M.</p></div>';
			} else {
				$filename = $_FILES['image']['name'];
				$extension = strtolower(substr($filename, strrpos($filename, '.')+1));
				if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
					if (file_exists(IMGROOT . $_FILES['image']['name'])) {
						echo '<div id="message" class="updated fade"><p>Error! Picture is exist.</p></div>';
					} else {
						@move_uploaded_file($_FILES['image']['tmp_name'], IMGROOT . $_FILES['image']['name']);
						echo '<div id="message" class="updated fade"><p>Upload Picture <strong>Success</strong>.</p></div>';
					}
				} else {
					echo '<div id="message" class="updated fade"><p>Error! Picture must be jpg(jpeg), gif or png.</p></div>';
				}
			}
		}
		
	?>	
		<div class="wrap">
			<h2><?php echo MBP_PI_NAME.' '.MBP_PI_VERSION;?></h2><br/>
			<strong><img src="<?php echo MBP_AIT_LIBPATH;?>image/how.gif" border="0" align="absmiddle" /> <a href="http://wordpress.org/extend/plugins/post-image/other_notes/" target="_blank">How to use it</a>&nbsp;&nbsp;&nbsp;
					<img src="<?php echo MBP_AIT_LIBPATH;?>image/comment.gif" border="0" align="absmiddle" /> <a href="http://www.maxblogpress.com/forum/forumdisplay.php?f=31" target="_blank">Community</a></strong><br/>			
		<h2>Configuration</h2>	
			<form name="wpi_admin" method="get" action="">
				<table>
					<tbody>
						<tr>
	<?php	
		if (strtolower(get_option("wpi_position")) === 'right') {
	 ?>	
			<td><input name="position" type="radio" value="left" /></td>
			<td>Left-Top</td>
			<td><input name="position" type="radio" value="right" checked="checked" /></td>
			<td>Right-Top</td>
		
		<?php } else { ?>
			<td><input name="position" type="radio" value="left" checked="checked" /></td>
			<td>Left-Top</td>
			<td><input name="position" type="radio" value="right" /></td>
			<td>Right-Top</td>
		<?php } ?>

						</tr>
					</tbody>
				</table>
				<br />
				<p class="submit">
					<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
					<input type="hidden" name="wpi" value="save" />
					<input type="submit" value="Save Configuration >>" />
				</p>
			</form>
			<br />
			<form name="wpi_upload" method="post" enctype="multipart/form-data" action="">
				<table>
					<tbody>
						<tr>
							<th scope="row"><label for="upload">Picture</label></th>
							<td><input id="upload" type="file" name="image" style="width:600px;" /></td>
						</tr>
					</tbody>
				</table>
				<br />
				<p class="submit">
					<input type="hidden" name="page" value="<?php echo $_GET['page'];?>" />
					<input type="hidden" name="wpi" value="upload" />
					<input type="submit" value="Upload >>" />
				</p>
			</form>
		<b>Uploaded Pictures</b><br/>
		<ul style="display:block;width:600px;list-style:none">
		<?php
		if ($dir = opendir(IMGROOT)) {
			while(false !== ($file = readdir($dir))) {
				if ($file != '.' && $file != '..') {
					$extension = strtolower(substr($file, strrpos($file, '.')+1));
					if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'gif' || $extension === 'png') {
						$file_no[] = $file;
						list($width, $height, $type, $attr) = getimagesize(WPIIMG . $file);
						$image_info = "Width:" . $width . " " . 'Height:' . $height . " ";
						$file_size = filesize(MBP_PI_IMGDIR . $file);
						$file_size = number_format($file_size/1024) . " KB";
						$image_info.= "Size:" . $file_size;
						$image_path = MBP_PI_LIBPATH . "image.php?m=t&p=" . urlencode(WPIIMG) . "&f=" . urlencode($file);
		?>				
						<li style="float:left;padding:8px;height:100px;background-color:#CCCCCC">
							<img alt="<?php echo $image_info;?>" title="<?php echo $image_info;?>" src="<?php echo $image_path;?>"/>
							<br/>
							<a href="?page=PostImage&action=del&p=<?php echo $file;?>" onclick="return window.confirm('Are you sure to delete?')">Delete</a>
						</li>
						
						
		<?php			
					}
				}
			}
			closedir($dir);
		}
		if (sizeof($file_no) == 0) {
			echo 'No Image uploaded!';
		}
		?>
			</ul>
			<div class="clear"/>
		
		
<div align="center" style="background-color:#f1f1f1; padding:5px 0px 5px 0px" >
<p align="center"><strong><?php echo MBP_PI_NAME.' '.MBP_PI_VERSION; ?> by <a href="http://www.maxblogpress.com" target="_blank">MaxBlogPress</a></strong></p>
<p align="center">This plugin is the result of <a href="http://www.maxblogpress.com/blog/219/maxblogpress-revived/" target="_blank">MaxBlogPress Revived</a> project.</p>
</div>			
		
		
		</div>
<?php		
		}
	}
	
	
// Srart Registration.

/**
 * Plugin registration form
 */
function mbp_piRegistrationForm($form_name, $submit_btn_txt='Register', $name, $email, $hide=0, $submit_again='') {
	$wp_url = get_bloginfo('wpurl');
	$wp_url = (strpos($wp_url,'http://') === false) ? get_bloginfo('siteurl') : $wp_url;
	$plugin_pg    = 'options-general.php';
	$thankyou_url = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'];
	$onlist_url   = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'].'&amp;mbp_onlist=1';
	if ( $hide == 1 ) $align_tbl = 'left';
	else $align_tbl = 'center';
	?>
	
	<?php if ( $submit_again != 1 ) { ?>
	<script><!--
	function trim(str){
		var n = str;
		while ( n.length>0 && n.charAt(0)==' ' ) 
			n = n.substring(1,n.length);
		while( n.length>0 && n.charAt(n.length-1)==' ' )	
			n = n.substring(0,n.length-1);
		return n;
	}
	function mbp_piValidateForm_0() {
		var name = document.<?php echo $form_name;?>.name;
		var email = document.<?php echo $form_name;?>.from;
		var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		var err = ''
		if ( trim(name.value) == '' )
			err += '- Name Required\n';
		if ( reg.test(email.value) == false )
			err += '- Valid Email Required\n';
		if ( err != '' ) {
			alert(err);
			return false;
		}
		return true;
	}
	//-->
	</script>
	<?php } ?>
	<table align="<?php echo $align_tbl;?>">
	<form name="<?php echo $form_name;?>" method="post" action="http://www.aweber.com/scripts/addlead.pl" <?php if($submit_again!=1){;?>onsubmit="return mbp_piValidateForm_0()"<?php }?>>
	 <input type="hidden" name="unit" value="maxbp-activate">
	 <input type="hidden" name="redirect" value="<?php echo $thankyou_url;?>">
	 <input type="hidden" name="meta_redirect_onlist" value="<?php echo $onlist_url;?>">
	 <input type="hidden" name="meta_adtracking" value="mr-post-image">
	 <input type="hidden" name="meta_message" value="1">
	 <input type="hidden" name="meta_required" value="from,name">
	 <input type="hidden" name="meta_forward_vars" value="1">	
	 <?php if ( $submit_again == 1 ) { ?> 	
	 <input type="hidden" name="submit_again" value="1">
	 <?php } ?>		 
	 <?php if ( $hide == 1 ) { ?> 
	 <input type="hidden" name="name" value="<?php echo $name;?>">
	 <input type="hidden" name="from" value="<?php echo $email;?>">
	 <?php } else { ?>
	 <tr><td>Name: </td><td><input type="text" name="name" value="<?php echo $name;?>" size="25" maxlength="150" /></td></tr>
	 <tr><td>Email: </td><td><input type="text" name="from" value="<?php echo $email;?>" size="25" maxlength="150" /></td></tr>
	 <?php } ?>
	 <tr><td>&nbsp;</td>
	 <td><input type="submit" name="submit" value="<?php echo $submit_btn_txt;?>" class="button" /></td>
	 </tr>
	 </form>

	
	</table>


	
	<?php
}

/**
 * Register Plugin - Step 2
 */
function mbp_piRegisterStep2($form_name='frm2',$name,$email) {
	$msg = 'You have not clicked on the confirmation link yet. A confirmation email has been sent to you again. Please check your email and click on the confirmation link to activate the plugin.';
	if ( trim($_GET['submit_again']) != '' && $msg != '' ) {
		echo '<div id="message" class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
	}
	?>
	<style type="text/css">
	table, tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_PI_NAME.' '.MBP_PI_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff; text-align:left;">
	  <tr><td align="center"><h3>Almost Done....</h3></td></tr>
	  <tr><td><h3>Step 1:</h3></td></tr>
	  <tr><td>A confirmation email has been sent to your email "<?php echo $email;?>". You must click on the link inside the email to activate the plugin.</td></tr>
	  <tr><td><strong>The confirmation email will look like:</strong><br /><img src="http://www.maxblogpress.com/images/activate-plugin-email.jpg" vspace="4" border="0" /></td></tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr><td><h3>Step 2:</h3></td></tr>
	  <tr><td>Click on the button below to Verify and Activate the plugin.</td></tr>
	  <tr><td><?php $this->mbp_piRegistrationForm($form_name.'_0','Verify and Activate',$name,$email,$hide=1,$submit_again=1);?></td></tr>
	 </table>
	 </td></tr></table><br />
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding:8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding:8px; background-color:#ffffff; text-align:left;">
	   <tr><td><h3>Troubleshooting</h3></td></tr>
	   <tr><td><strong>The confirmation email is not there in my inbox!</strong></td></tr>
	   <tr><td>Dont panic! CHECK THE JUNK, spam or bulk folder of your email.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>It's not there in the junk folder either.</strong></td></tr>
	   <tr><td>Sometimes the confirmation email takes time to arrive. Please be patient. WAIT FOR 6 HOURS AT MOST. The confirmation email should be there by then.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>6 hours and yet no sign of a confirmation email!</strong></td></tr>
	   <tr><td>Please register again from below:</td></tr>
	   <tr><td><?php $this->mbp_piRegistrationForm($form_name,'Register Again',$name,$email,$hide=0,$submit_again=2);?></td></tr>
	   <tr><td><strong>Help! Still no confirmation email and I have already registered twice</strong></td></tr>
	   <tr><td>Okay, please register again from the form above using a DIFFERENT EMAIL ADDRESS this time.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr>
		 <td><strong>Why am I receiving an error similar to the one shown below?</strong><br />
			 <img src="http://www.maxblogpress.com/images/no-verification-error.jpg" border="0" vspace="8" /><br />
		   You get that kind of error when you click on &quot;Verify and Activate&quot; button or try to register again.<br />
		   <br />
		   This error means that you have already subscribed but have not yet clicked on the link inside confirmation email. In order to  avoid any spam complain we don't send repeated confirmation emails. If you have not recieved the confirmation email then you need to wait for 12 hours at least before requesting another confirmation email. </td>
	   </tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>But I've still got problems.</strong></td></tr>
	   <tr><td>Stay calm. <strong><a href="http://www.maxblogpress.com/contact-us/" target="_blank">Contact us</a></strong> about it and we will get to you ASAP.</td></tr>
	 </table>
	 </td></tr></table>
	 </center>		
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_PI_NAME.' '.MBP_PI_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}

/**
 * Register Plugin - Step 1
 */
function mbp_piRegisterStep1($form_name='frm1',$userdata) {
	$name  = trim($userdata->first_name.' '.$userdata->last_name);
	$email = trim($userdata->user_email);
	?>
	<style type="text/css">
	tabled , tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_PI_NAME.' '.MBP_PI_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:2px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	  <tr><td align="center">
		<table width="548" align="center" cellpadding="3" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff;">
		  <tr><td align="center"><h3>Please register the plugin to activate it. (Registration is free)</h3></td></tr>
		  <tr><td align="left">In addition you'll receive complimentary subscription to MaxBlogPress Newsletter which will give you many tips and tricks to attract lots of visitors to your blog.</td></tr>
		  <tr><td align="center"><strong>Fill the form below to register the plugin:</strong></td></tr>
		  <tr><td align="center"><?php $this->mbp_piRegistrationForm($form_name,'Register',$name,$email);?></td></tr>
		  <tr><td align="center"><font size="1">[ Your contact information will be handled with the strictest confidence <br />and will never be sold or shared with third parties ]</font></td></tr>
		</table>
	  </td></tr></table>
	 </center>
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_PI_NAME.' '.MBP_PI_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}	
	
}// Registration

if ($_GET['action'] == 'del' && $_GET['p'] != '') {
	$pic   = $_GET['p'];
	$query = "DELETE FROM wp_wpi_posts_pictures WHERE picture='" . $_GET['p'] . "'";
	@mysql_query($query);
	@unlink(MBP_PI_IMGDIR . $pic);
	echo "<script>window.location.href='?page=PostImage'</script>";
}

$wpposticon = & new MPPostImage();
?>