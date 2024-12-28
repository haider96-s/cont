<?php
/**
 * WP File Download Single Template
 *
 * @package    WP File Download
 * @subpackage Inject file in wordpress search result
 * @since      11.2017
 */
if (locate_template('template-parts/head.php') !== '') {
    get_template_part('template-parts/head');
}
if (locate_template('header.php') !== '') {
    get_header();
}
?>

<?php
// Start the Loop.
while (have_posts()) :
    the_post();
    wpfdTheContent();
endwhile;
?>

<?php
if (locate_template('footer.php') !== '') {
    get_footer();
}
if (has_action('wp_footer')) {
    wp_footer();
}
