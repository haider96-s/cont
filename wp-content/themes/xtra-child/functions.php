<?php

/* ------------------------------------------------------------------------- *
 *  Custom functions
/* ------------------------------------------------------------------------- */

function xtra_child_theme() {

	wp_enqueue_style( 'xtra-child', get_stylesheet_directory_uri() . '/style.css', array( 'xtra' ), '' );

}
add_action( 'wp_enqueue_scripts', 'xtra_child_theme' );

// Add your custom functions here, or overwrite existing ones. Read more how to use:
// http://codex.wordpress.org/Child_Themes