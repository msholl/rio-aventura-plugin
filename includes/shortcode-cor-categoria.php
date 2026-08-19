<?php
/**
 * Cor da categoria da experiência: resolução do valor + shortcode [cor_categoria].
 *
 * A cor vive no campo ACF `cor` (color_picker) do TERMO da taxonomia
 * `categoria_experiencia`. A função `conecta_exp_cor_categoria_valor()` resolve
 * o hex e é compartilhada pelo shortcode e pelo Dynamic Tag nativo do Elementor
 * (includes/elementor-dynamic-tags.php) — regra única, sem duplicação.
 *
 * Só devolve o dado — não renderiza HTML, não gera CSS, não monta layout.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve a cor da categoria do post atual (ou de um termo específico).
 *
 * @param string $fallback Cor usada quando não há termo/cor. Default "#1D9E75".
 * @param int    $term_id  Força a leitura de um termo específico em vez do
 *                         primeiro termo do post atual do loop.
 * @return string Cor hex válida (ex.: "#1D9E75") — nunca vazio.
 */
function conecta_exp_cor_categoria_valor( $fallback = '#1D9E75', $term_id = 0 ) {
	$fallback = sanitize_hex_color( $fallback );
	$fallback = $fallback ? $fallback : '#1D9E75';

	if ( ! $term_id ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $fallback;
		}

		$terms = get_the_terms( $post_id, Conecta_Exp_Taxonomy::TAXONOMY );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $fallback;
		}

		// Primeiro termo na ordem padrão do WordPress (alfabética por nome).
		$term_id = $terms[0]->term_id;
	}

	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}

	// O WP_Term é o identificador mais robusto para o ACF ler campo de termo
	// (equivale ao formato "{taxonomy}_{term_id}").
	$term = get_term( absint( $term_id ), Conecta_Exp_Taxonomy::TAXONOMY );
	if ( ! $term instanceof WP_Term ) {
		return $fallback;
	}

	$cor = get_field( 'cor', $term );
	$cor = is_string( $cor ) ? sanitize_hex_color( $cor ) : '';

	return $cor ? $cor : $fallback;
}

/**
 * Shortcode [cor_categoria] — devolve o hex da cor da categoria.
 *
 * Atributos:
 * - `fallback`: cor usada quando não há termo/cor. Default "#1D9E75".
 * - `term_id`:  força a leitura de um termo específico em vez do primeiro
 *               termo do post atual.
 *
 * @param array|string $atts Atributos do shortcode.
 * @return string Cor hex válida — nunca vazio.
 */
function conecta_exp_cor_categoria_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'fallback' => '#1D9E75',
			'term_id'  => '',
		),
		$atts,
		'cor_categoria'
	);

	return conecta_exp_cor_categoria_valor( $atts['fallback'], absint( $atts['term_id'] ) );
}
add_shortcode( 'cor_categoria', 'conecta_exp_cor_categoria_shortcode' );
