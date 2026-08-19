<?php
/**
 * Dynamic Tag nativo do Elementor "Cor da Categoria" (categoria COLOR).
 *
 * Expõe a cor da categoria da experiência atual direto no seletor de cor de
 * qualquer elemento (fundo, texto, borda), sem HTML/script no card. Resolvido
 * no render do PHP, funciona também dentro de Loop Carousel/Grid, inclusive
 * nos slides clonados.
 *
 * Reaproveita `conecta_exp_cor_categoria_valor()` — a mesma regra do shortcode
 * [cor_categoria], que continua disponível.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'elementor/dynamic_tags/register',
	function ( $manager ) {
		// Grupo "Experiência" no seletor de tags dinâmicas.
		$manager->register_group(
			'experiencia',
			array( 'title' => __( 'Experiência', 'conecta-experiencias' ) )
		);

		/**
		 * Tag "Cor da Categoria": devolve o hex da cor do termo da experiência.
		 */
		class Conecta_Exp_Tag_Cor_Categoria extends \Elementor\Core\DynamicTags\Tag {

			/**
			 * Nome interno da tag.
			 *
			 * @return string
			 */
			public function get_name() {
				return 'conecta-cor-categoria';
			}

			/**
			 * Título exibido no seletor.
			 *
			 * @return string
			 */
			public function get_title() {
				return __( 'Cor da Categoria', 'conecta-experiencias' );
			}

			/**
			 * Grupo da tag no seletor.
			 *
			 * @return string
			 */
			public function get_group() {
				return 'experiencia';
			}

			/**
			 * Categorias de controle que aceitam a tag (controles de COR).
			 *
			 * @return array
			 */
			public function get_categories() {
				return array( \Elementor\Modules\DynamicTags\Module::COLOR_CATEGORY );
			}

			/**
			 * Imprime o valor da tag (hex sempre válido, com fallback).
			 */
			public function render() {
				echo esc_html( conecta_exp_cor_categoria_valor() );
			}
		}

		$manager->register( new Conecta_Exp_Tag_Cor_Categoria() );
	}
);
