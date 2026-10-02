<?php
/**
 * Template de /experiencias/{slug}/ fornecido pelo plugin.
 *
 * Um template Single do Elementor Pro com condição para Experiências tem
 * prioridade; sem ele, a página é montada pelo [experiencia_detalhe].
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'single' ) ) {
	while ( have_posts() ) {
		the_post();
		echo '<main id="content" class="cxp-main">';
		echo conecta_exp_shortcode_detalhe( array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escapado no template.
		echo '</main>';
	}
}

get_footer();
