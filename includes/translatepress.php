<?php
/**
 * Ajustes do TranslatePress.
 *
 * O espanhol do site está cadastrado como es_AR. Trocar o idioma para es_ES
 * nas configurações do TranslatePress apagaria a ligação com as traduções já
 * feitas, então só a bandeira é trocada: es_AR passa a mostrar a da Espanha.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

/**
 * Idiomas cuja bandeira é trocada: código do idioma => código da bandeira.
 *
 * @return array<string,string>
 */
function conecta_exp_bandeiras_trocadas() {
	return apply_filters( 'conecta_exp_bandeiras_trocadas', array( 'es_AR' => 'es_ES' ) );
}

/**
 * Seletor de idioma atual (v2, bandeiras SVG em assets/flags/{4x3|1x1}/).
 * Troca só o nome do arquivo e mantém a proporção escolhida no seletor.
 *
 * @param string $html          <img> da bandeira.
 * @param string $language_code Código do idioma.
 * @return string
 */
function conecta_exp_bandeira_svg( $html, $language_code ) {
	$trocas = conecta_exp_bandeiras_trocadas();
	if ( isset( $trocas[ $language_code ] ) ) {
		$html = str_replace( '/' . $language_code . '.svg', '/' . $trocas[ $language_code ] . '.svg', $html );
	}
	return $html;
}
add_filter( 'trp_flag_html', 'conecta_exp_bandeira_svg', 10, 2 );

/**
 * Seletor antigo e editor de traduções (bandeiras PNG em assets/images/flags/).
 *
 * @param string $arquivo       Nome do arquivo da bandeira.
 * @param string $language_code Código do idioma.
 * @return string
 */
function conecta_exp_bandeira_png( $arquivo, $language_code ) {
	$trocas = conecta_exp_bandeiras_trocadas();
	return isset( $trocas[ $language_code ] ) ? $trocas[ $language_code ] . '.png' : $arquivo;
}
add_filter( 'trp_flag_file_name', 'conecta_exp_bandeira_png', 10, 2 );
