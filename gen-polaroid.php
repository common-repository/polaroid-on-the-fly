<?php
/*
Copyright (C) 2004-2007 The Polaroid-o-nizer Team

Copyright (c) 2007 Matt Martz (http://sivel.net)

This file was intended for distribution with the Polaroid
on the Fly Wordpress plugin by Matt Martz.  See
polaroid-on-the-fly.php for addition information.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

define("VERSION", "0.7.2_potf");
define("CREATIONDATE", "October 20th, 2007");
header("PoN-Version: " . VERSION);
error_reporting(0);

$root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root.'/wp-load.php')) {
	// WP 2.6
	require_once($root.'/wp-load.php');
} else {
	// Before 2.6
	require_once($root.'/wp-config.php');
}

// Pre-2.6 compatibility
if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
// Guess the location
$plugin_path = WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__));
$plugin_url = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__));

if (get_potf_option('secure') == 1) {
	if ($_SERVER['QUERY_STRING'] == "") {
	        exit("This page cannot be called in this manner.");
	}

	$items = split('//',$_SERVER['HTTP_REFERER']);
	$site = split('/',$items[1]);
	if ($site[0] != $_SERVER["HTTP_HOST"]) {
        	exit("You are trying to access this page from an illegal referrer ($site[0])");
	}
}

class Polaroid
{
	var $errmsg = array();
	var $photo;
	var $text;

	function Polaroid()
	{
		global $data;
		if (empty($data['bg']))
		{
			$data['bg'] = '255,255,255';
		}
		else
		{
			$bg = explode(",", $data['bg']);
			for ($i = 0; $i < 3; $i++)
			{
				if ($bg[$i] < 0 || $bg[$i] > 255 || !is_numeric($bg[$i]))
				{
					$data['bg'] = '255,255,255';
					$data['photo'] = $plugin_url . 'bg.jpg';
				}
			}
		}

		$data['x'] = !empty($data['x']) ? $data['x'] : 0;
		$data['y'] = !empty($data['y']) ? $data['y'] : 0;

		if ((isset($data['x']) && !is_numeric($data['x'])) || (isset($data['y']) && !is_numeric($data['y'])))
		{
			$this->errmsg['xy'] = "Incorrect x and/or y coordinates:";
		}

		$data['angle'] = !empty($data['angle']) || $data['angle'] == "0" ? $data['angle'] : 15;

		if ($data['angle'] < 0 || $data['angle'] > 360 || !is_numeric($data['angle']))
		{
			$this->errmsg['angle'] = "Incorrect rotation angle:";
		}

		$this->photo = str_replace(" ", "%20", $data['photo']);
		$info = @getimagesize($this->photo);
		if(!$info)
		{
			$data['photo'] = $plugin_url . 'url.jpg';
		}
		elseif(!in_array($info[2], array(1, 2, 3)))
		{
			$data['photo'] = $plugin_url . 'filetype.jpg';
		}
		elseif (($info[0] - $data['x']) < 200 || ($info[1] - $data['y']) < 200)
		{
			$data['photo'] = $plugin_url . 'minres.jpg';
		}
		elseif ($info[0] >= 2000 || $info[1] >= 2000)
		{
			$data['photo'] = $plugin_url . 'maxres.jpg';
		}
		
		$text = trim(strip_tags(stripslashes(str_replace("_", " ", $data['text']))));

		$this->text = trim(strip_tags(stripslashes(str_replace(" ", "_", $data['text']))));
	}

	function CreatePolaroid()
	{
		global $data;
		$text = trim(strip_tags(stripslashes(str_replace("_", " ", $data['text']))));

		$polaroid = imagecreatetruecolor(246, 300);

		$bg = explode(",", $data['bg']);
		$bg = imagecolorallocate($polaroid, $bg[0], $bg[1], $bg[2]);
		imagefill($polaroid, 0, 0, $bg);

		$photo = str_replace(" ", "%20", $data['photo']);
		$info = getimagesize($photo);

		$scale = round(($info[0] > $info[1]) ? (200 / $info[1]) : (200 / $info[0]), 4);

		if ($info[2] == 1)
		{
			$photo = imagecreatefromgif(stripslashes($photo));
		}
		elseif ($info[2] == 2)
		{
			$photo = imagecreatefromjpeg(stripslashes($photo));
		}
		elseif ($info[2] == 3)
		{
			$photo = imagecreatefrompng(stripslashes($photo));
		}

		$tmp = imagecreatetruecolor(200, 200);
		imagecopyresampled($tmp, $photo, 0, 0, $data['x'], $data['y'], floor($info[0] * $scale), floor($info[1] * $scale), $info[0], $info[1]);

		imagecopy($polaroid, $tmp, 20, 18, 0, 0, 200, 200);

		$frame = imagecreatefrompng("frame.png");
		imagecopy($polaroid, $frame, 0, 0, 0, 0, 245, 301);

		$text = wordwrap($text, 25, "||", 1);
		$text = explode("||", $text);

		$black = imagecolorallocate($polaroid, 0, 0, 0);

		$text_pos_y = array(235, 250, 265);
		for ($i = 0; $i < 3; $i++)
		{
			$width = imagettfbbox(10, 0, "./annifont.ttf", $text[$i]);
			$text_pos_x = (196 - $width[2])/2;
			imagettftext($polaroid, 18, 0, $text_pos_x, $text_pos_y[$i]+10, $black, "./annifont.ttf", $text[$i]);
		}

		if (function_exists('imagerotate'))
			$polaroid = imagerotate($polaroid, $data['angle'], $bg);

		header("Content-type: image/png");
		imagepng($polaroid , "", 2);
		imagedestroy($polaroid);
		exit;
	}
}

if (isset($_GET['img']) && !empty($_GET['img']))
{
	$input = explode("||", base64_decode($_GET['img']));
	$_GET['bg'] = $input[0];
	$_GET['photo'] = $input[1];
	$_GET['x'] = $input[2];
	$_GET['y'] = $input[3];
	$_GET['angle'] = $input[4];
	$_GET['text'] = $input[5];
}

if ($_SERVER['REQUEST_METHOD'] == "POST" || !empty($_GET))
{
	$data = array_merge($_GET, $_POST);
	$polaroid = new Polaroid;

	$polaroid->CreatePolaroid();
}
?>
