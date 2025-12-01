<?php
require_once 'dbConnections/security.php' ;

session_start();

// Used to generate a 5 random character for CAPTCHA code
$code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 5);

// Store the code in session
$_SESSION['captcha'] = $code;

// Createing an image for the CAPTCHA
header('Content-type: image/png');
$image = imagecreatetruecolor(120, 40);

// Colors for the CAPTCHA white background and black text
$bg = imagecolorallocate($image, 255, 255, 255);
$fg = imagecolorallocate($image, 0, 0, 0);

// Fill background and add CAPTCHA text
imagefill($image, 0, 0, $bg);
imagestring($image, 5, 20, 10, $code, $fg);

// Output the image 
imagepng($image);
//free the memory
imagedestroy($image);