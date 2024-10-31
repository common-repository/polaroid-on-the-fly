<?php
/*
Plugin Name: Polaroid on the Fly
Plugin URI: http://sivel.net/wordpress/
Description: Creates polaroids of images on the fly for thumbnails in posts. Support for lightbox included. Built on modified Polaroid-o-nizer v0.7.2 sources. Once enabled, go to "Options" and select "Polaroid on the Fly".
Version: 0.7
Author: Matt Martz
Author URI: http://sivel.net

        Copyright (c) 2008 Matt Martz (http://sivel.net)
        Polaroid on the Fly is released under the GNU General Public License (GPL)
	http://www.gnu.org/licenses/gpl-2.0.txt

	Polaroid-o-nizer is licensed under the GNU General Public License (GPL)
	http://www.gnu.org/licenses/gpl-2.0.txt
*/

// Initialize Globabl variables
// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
        define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
        define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
// Guess the location
$plugin_path = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__));
$plugin_url = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));

### Create Text Domain For Translations
load_plugin_textdomain('polaroid-on-the-fly', 'wp-content/plugins/polaroid-on-the-fly');

### Function: potf Option Menu
add_action('admin_menu', 'potf_menu');
function potf_menu() {
        if (function_exists('add_options_page')) {
                add_options_page(__('Polaroid on the Fly', 'polaroid-on-the-fly'), __('Polaroid on the Fly', 'polaroid-on-the-fly'), 'manage_options', 'polaroid-on-the-fly/polaroid-on-the-fly.php', 'potf_options');
        }
}

### Function: Options Page
function potf_options() {
        global $wpdb;
        $text = '';
        $potf_options = array();
        $potf_options = get_option('potf_options');
        if ($_POST['Submit']) {
		if (!$_POST['default']) {
	                $potf_options['secure'] = intval($_POST['secure']);
        	        $potf_options['tinyurl'] = intval($_POST['tinyurl']);
			$potf_options['lightbox'] = intval($_POST['lightbox']);
			$potf_options['caption'] = intval($_POST['caption']);
			$potf_options['encode'] = intval($_POST['encode']);
			$potf_options['target'] = intval($_POST['target']);
			$potf_options['height'] = intval($_POST['height']);
			$potf_options['width'] = intval($_POST['width']);
                	$potf_options['bg'] = trim($_POST['bg']);
	        } else {
		        $potf_options['secure'] = 1;
		        $potf_options['tinyurl'] = 0;
        		$potf_options['lightbox'] = 0;
	        	$potf_options['caption'] = 0;
	        	$potf_options['encode'] = 1;
		        $potf_options['target'] = 0;
        		$potf_options['height'] = 150;
		        $potf_options['width'] = 123;
        		$potf_options['bg'] = '255,255,255';
		        $potf_options['default'] = 0;
			$text = '<font color="green">'.__('Polaroid on the Fly Options Defaulted', 'polaroid-on-the-fly').'</font>';
		}
                $update_potf_options = update_option('potf_options', $potf_options);
                if (($update_potf_options) && (empty($text))) {
                        $text = '<font color="green">'.__('Polaroid on the Fly Options Updated', 'polaroid-on-the-fly').'</font>';
                }
                if (empty($text)) {
                        $text = '<font color="red">'.__('No Polaroid on the Fly Option Updated', 'polaroid-on-the-fly').'</font>';
                }

	}
?>
<?php if (!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>

<?php 
	if (!function_exists('gd_info')) {
		echo '<div id="message" class="updated fade"><p><font color="red">PHP GD Module Not Found. Polaroid on the Fly will not work. See <a href="http://us.php.net/gd#image.installation" target="_blank">http://us.php.net/gd</a> for installation information.</font></p></div>';
	} else { 
		if (!function_exists('imagecreatefromgif')) {
			echo '<div id="message" class="updated fade"><p><font color="red">PHP GD Module Found. However, it does not include support for GIF.  You will not be able to use a GIF for the source image. See <a href="http://us.php.net/gd#id2947667" target="_blank">http://us.php.net/gd</a> for more information.</font></p></div>';
		}
	} 
?>

<!--potf Options -->
<div class="wrap">
        <h2><?php _e('Polaroid on the Fly Options', 'polaroid-on-the-fly'); ?></h2>
        <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
                <table width="100%" cellspacing="3" cellpadding="3" border="0">
                        <tr>
                                <td valign="top"><strong><?php _e('Enable Security:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <select name="secure">
                                                <option value="0"<?php selected('0', $potf_options['secure']); ?>><?php _e('No', 'polaroid-on-the-fly'); ?></option>
                                                <option value="1"<?php selected('1', $potf_options['secure']); ?>><?php _e('Yes', 'polaroid-on-the-fly'); ?></option>
                                        </select>
                                        <br /><?php _e('Protect this plugin from being used by remote users/websites.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td valign="top"><strong><?php _e('Use Tinyurl.com:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <select name="tinyurl">
                                                <option value="0"<?php selected('0', $potf_options['tinyurl']); ?>><?php _e('No', 'polaroid-on-the-fly'); ?></option>
                                                <option value="1"<?php selected('1', $potf_options['tinyurl']); ?>><?php _e('Yes', 'polaroid-on-the-fly'); ?></option>
                                        </select>
                                        <br /><?php _e('Use tinyurl.com to hide the location of the image.  If fopen is not enabled in php on your server set this to \'No\'.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td valign="top"><strong><?php _e('Use Lightbox:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <select name="lightbox">
                                                <option value="0"<?php selected('0', $potf_options['lightbox']); ?>><?php _e('No', 'polaroid-on-the-fly'); ?></option>
                                                <option value="1"<?php selected('1', $potf_options['lightbox']); ?>><?php _e('Yes', 'polaroid-on-the-fly'); ?></option>
                                        </select>
                                        <br /><?php _e('Use Lightbox JS to display the image.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td valign="top"><strong><?php _e('Use Image Captions:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <select name="caption">
                                                <option value="0"<?php selected('0', $potf_options['caption']); ?>><?php _e('No', 'polaroid-on-the-fly'); ?></option>
                                                <option value="1"<?php selected('1', $potf_options['caption']); ?>><?php _e('Yes', 'polaroid-on-the-fly'); ?></option>
                                        </select>
                                        <br /><?php _e('Display image captions below picture on polaroid. This uses the alt text from the img tag.  NOTE: Because of the size all Captions will be truncated at 8 characters.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td valign="top"><strong><?php _e('Encode URL:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <select name="encode">
                                                <option value="0"<?php selected('0', $potf_options['encode']); ?>><?php _e('No', 'polaroid-on-the-fly'); ?></option>
                                                <option value="1"<?php selected('1', $potf_options['encode']); ?>><?php _e('Yes', 'polaroid-on-the-fly'); ?></option>
                                        </select>
                                        <br /><?php _e('Encode the URL containing options for the polaroid thumbnail passed to the Polaroid-o-nizer script.  If set to \'Yes\' this will help make your page valid XHTML 1.0 Transitional.  If set to \'No\' the page will not validate.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
			<tr>
                                <td valign="top"><strong><?php _e('Window Target:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <select name="target">
                                                <option value="0"<?php selected('0', $potf_options['target']); ?>><?php _e('None', 'polaroid-on-the-fly'); ?></option>
                                                <option value="1"<?php selected('1', $potf_options['target']); ?>><?php _e('New', 'polaroid-on-the-fly'); ?></option>
						<option value="2"<?php selected('2', $potf_options['target']); ?>><?php _e('Self', 'polaroid-on-the-fly'); ?></option>
                                        </select>
                                        <br /><?php _e('How the image will be displayed.  Whether in the current page or a new page. If Lightbox is enabled above this is automatically set to "None".', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
			<tr>
                                <td valign="top"><strong><?php _e('Height:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <input type="text" name="height" size="4" value="<?php echo htmlentities($potf_options['height']); ?>" />
                                        <br /><?php _e('Height of the thumbnail in px.  Default is 150.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
			<tr>
                                <td valign="top"><strong><?php _e('Width:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <input type="text" name="width" size="4" value="<?php echo htmlentities($potf_options['width']); ?>" />
                                        <br /><?php _e('Width of the thumbnail in px.  Default is 123.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td valign="top"><strong><?php _e('Background Color:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <input type="text" name="bg" size="60" value="<?php echo htmlentities($potf_options['bg']); ?>" />
                                        <br /><?php _e('This is the background color of your blog in RGB format.  See http://www.w3schools.com/html/html_colors.asp for help.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
			<tr>
                                <td valign="top"><strong><?php _e('Reset to Defaults:', 'polaroid-on-the-fly'); ?></strong></td>
                                <td>
                                        <select name="default">
                                                <option value="0"<?php selected('0', $potf_options['default']); ?>><?php _e('No', 'polaroid-on-the-fly'); ?></option>
                                                <option value="1"<?php selected('1', $potf_options['default']); ?>><?php _e('Yes', 'polaroid-on-the-fly'); ?></option>
                                        </select>
                                        <br /><?php _e('Reset all Polaroid on the Fly options to their defaults.', 'polaroid-on-the-fly'); ?>
                                </td>
                        </tr>
                        <tr>
                                <td width="100%" colspan="2" align="center"><input type="submit" name="Submit" class="button" value="<?php _e('Update Options', 'polaroid-on-the-fly'); ?>" />&nbsp;&nbsp;<input type="button" name="cancel" value="<?php _e('Cancel', 'polaroid-on-the-fly'); ?>" class="button" onclick="javascript:history.go(-1)" /></td>
                        </tr>
                </table>

        </form>
<?php
}

### Function: Get potf Option
function get_potf_option($option) {
        $potf_options = get_option('potf_options');
        return $potf_options[$option];
}

### Function: potf Init
add_action('activate_polaroid-on-the-fly/polaroid-on-the-fly.php', 'potf_init');
function potf_init() {
        global $wpdb;
        include_once(ABSPATH.'/wp-admin/upgrade-functions.php');
        // Delete Options First
        potf_delete();
        // Add Options
        $potf_options = array();
        $potf_options['secure'] = 1;
        $potf_options['tinyurl'] = 0;
	$potf_options['lightbox'] = 0;
	$potf_options['caption'] = 0;
	$potf_options['encode'] = 1;
	$potf_options['target'] = 0;
	$potf_options['height'] = 150;
	$potf_options['width'] = 123;
        $potf_options['bg'] = '255,255,255';
	$potf_options['default'] = 0;
        add_option('potf_options', $potf_options, 'Polaroid on the Fly Options');
}

### Function: delete options
function potf_delete() {
	delete_option('potf_options');
}

// Begin function to replace text
function polaroid_replace($potf_page_content) {

	// Import Global Variables
	GLOBAL $plugin_url;

	// Define Search Paramenters
	$search = '/(<p\>)?<img(.*)?src\=[\'|"](.*)\.(jpg|jpeg|gif|png)(\?.*)?[\'|"]\ (.*)?rel\=[\'|"]polaroid[\'|"](.*)((alt\=[\'|"](.*)[\'|"]))?(.*)?(\/)?\>(<\/p\>)?/i';

	if (get_potf_option('target') == 1) {
		$target = ' target="_blank"';
	} elseif (get_potf_option('target') == 2) {
		$target = ' target="_self"';
	} else {
		$target = '';
	}

        if ((get_potf_option('lightbox') == 1) && (get_potf_option('target') == 1)) {
                $lightbox = 'lightbox';
		$target = '';
        } elseif (get_potf_option('lightbox') == 1) { 
		$lightbox = 'lightbox';
	} else {
                $lightbox = '';
        }

	// Check the page content for matches
	if (preg_match_all($search, $potf_page_content, $matches, PREG_SET_ORDER)) {

		// Loop through each match
        	foreach ($matches as $match) :

			// Grab and format originally passed image url
                	preg_match('/src\=[\'|"](.*)\.(jpg|jpeg|gif|png)(\?.*)?[\'|"]\ /i',$match[0],$url_results);
	                $url_orig = preg_replace('/(src\=)?[\'|"](\ )?/i', '', $url_results[0]);

			// Grab and format orignally passed alt text
        	        preg_match('/alt\=[\'|"](.*)[\'|"]/i',$match[0],$alt_results);
	                $alt_orig = preg_replace('/(alt\=)?[\'|"]/i', '', $alt_results[0]);
        	        $alt_nolight = preg_replace('/\[(.*)\]/i', '', $alt_orig);

			// Grab album name
			preg_match('/\[(.*)\]/i', $alt_orig, $album_results);
			$album = $album_results[0];		
	
			// Run tinyurl.com api and grab the shortened url
			if (get_potf_option('tinyurl') == 1) {
		                $url_handle = fopen("http://tinyurl.com/api-create.php?url=" . $url_orig, "r");
        		        $url = fread($url_handle, 1024*1024);
                		fclose($url_handle);
			} else {
				$url = $url_orig;
			}

			if (get_potf_option('encode') == 1) {
				// Create encoded part of URL for final polaroid image link
				if (get_potf_option('caption') == 1) {
					$encoded = base64_encode(get_potf_option('bg') . "||" . $url . "||0||0||0||" . substr_replace($alt_nolight, '', 8, 1024));
				} else {
			                $encoded = base64_encode(get_potf_option('bg') . "||" . $url . "||0||0||0||");
				}
				// Define Repalce Paramenters
	        	        $replace = '<a href="' . $url . '" rel="' . $lightbox . $album . '" title="' . $alt_nolight . '"' . $target  . '><img style="border: none;" src="' . $plugin_url . '/gen-polaroid.php?img=' . $encoded . '" alt="' . $alt_nolight . '" height="' . get_potf_option('height') . '" width="' . get_potf_option('width') . '" /></a>';
			} else {
				// Create non-encoded part of URL for final polaroid image link
				if (get_potf_option('caption') == 1) {
					$notencoded = 'bg=' . get_potf_option('bg') . '&photo=' . $url . '&x=0&y=0&angle=0&text=' . substr_replace($alt_nolight, '', 8, 1024);
				} else {
					$notencoded = 'bg=' . get_potf_option('bg') . '&photo=' . $url . '&x=0&y=0&angle=0&text=';
				}
				// Define Repalce Paramenters
				$replace = '<a href="' . $url . '" rel="' . $lightbox . $album . '" title="' . $alt_nolight . '"' . $target  . '><img style="border: none;" src="' . $plugin_url . '/gen-polaroid.php?' . $notencoded . '" alt="' . $alt_nolight . '" height="' . get_potf_option('height') . '" width="' . get_potf_option('width') . '" /></a>';
			}

			// Make replacements
			$potf_page_content = str_replace($match[0], $replace, $potf_page_content);

	        endforeach;
	}
	
	// Return the modified page content
	return $potf_page_content;
}

function potf_css() {
	global $plugin_url;
	echo '<link rel="stylesheet" type="text/css" href="' . $plugin_url . '/potf.css" />' . "\n";
}

// Add filter to Wordpress to make substitutions from above function
//add_action('wp_head', 'potf_css');
add_filter('the_content', 'polaroid_replace');
?>
