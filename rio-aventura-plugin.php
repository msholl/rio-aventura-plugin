<?php
/**
 * Plugin Name:       Rio Aventura — Experiências
 * Plugin URI:        https://conecta-soft.com.br/
 * Description:       Estrutura de dados das experiências de turismo: CPT "Experiência", taxonomia "Categorias" (com cor por termo) e campos ACF (preço, duração, dificuldade, distância). Independente de tema, pronto para os Dynamic Tags do Elementor.
 * Version:           1.0.4
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Matheus Sholl Schneider
 * Author URI:        https://conecta-soft.com.br/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       conecta-experiencias
 * Domain Path:       /languages
 *
 * @package ConectaExperiencias
 */

// Impede o acesso direto ao arquivo.
defined( 'ABSPATH' ) || exit;

define( 'CONECTA_EXP_VERSION', '1.0.4' );
define( 'CONECTA_EXP_FILE', __FILE__ );
define( 'CONECTA_EXP_PATH', plugin_dir_path( __FILE__ ) );

// Carrega os módulos responsáveis por cada parte da estrutura.
require_once CONECTA_EXP_PATH . 'includes/class-taxonomy.php';
require_once CONECTA_EXP_PATH . 'includes/class-cpt.php';
require_once CONECTA_EXP_PATH . 'includes/acf-fields.php';

/**
 * Carrega o text domain para tradução.
 */
function conecta_exp_load_textdomain() {
	load_plugin_textdomain(
		'conecta-experiencias',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'conecta_exp_load_textdomain' );

/**
 * Inicializa o registro da estrutura (taxonomia + CPT).
 *
 * A taxonomia é registrada antes do CPT para que o vínculo declarado em
 * `taxonomies` do CPT funcione. O registro é idempotente: rodar várias vezes
 * não duplica nada.
 */
function conecta_exp_init() {
	Conecta_Exp_Taxonomy::register();
	Conecta_Exp_CPT::register();
}
add_action( 'init', 'conecta_exp_init' );

/**
 * Ativação: registra a estrutura e atualiza as regras de rewrite.
 *
 * Registrar a taxonomia e o CPT antes do flush garante que as URLs
 * (/experiencias/{slug} e /categoria/{termo}) funcionem de imediato.
 */
function conecta_exp_activate() {
	Conecta_Exp_Taxonomy::register();
	Conecta_Exp_CPT::register();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'conecta_exp_activate' );

/**
 * Desativação: limpa as regras de rewrite.
 */
function conecta_exp_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'conecta_exp_deactivate' );

/**
 * Admin notice avisando que o ACF é necessário para os campos customizados.
 */
function conecta_exp_acf_notice() {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__(
			'Rio Aventura — Experiências: o plugin Advanced Custom Fields (ACF) está inativo. O CPT e a taxonomia Categorias funcionam normalmente, mas os campos (Preço, Duração, Dificuldade e Distância) só aparecem com o ACF ativo.',
			'conecta-experiencias'
		)
	);
}
add_action( 'admin_notices', 'conecta_exp_acf_notice' );
