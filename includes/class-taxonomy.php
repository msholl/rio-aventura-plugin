<?php
/**
 * Registro da taxonomia "Categoria" das experiências.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

/**
 * Encapsula o registro da taxonomia `categoria_experiencia`.
 *
 * Hierárquica (comportamento tipo categoria) para servir como vocabulário
 * controlado e alimentar o widget nativo Taxonomy Filter do Elementor na
 * página de Experiências. Os termos são cadastrados no admin — nada é
 * semeado via código.
 */
class Conecta_Exp_Taxonomy {

	/**
	 * Slug interno da taxonomia.
	 *
	 * @var string
	 */
	const TAXONOMY = 'categoria_experiencia';

	/**
	 * Registra a taxonomia e a vincula ao CPT de experiências.
	 *
	 * Idempotente: o WordPress sobrescreve o registro anterior se a função
	 * for chamada mais de uma vez na mesma requisição.
	 */
	public static function register() {
		$labels = array(
			'name'                       => _x( 'Categorias', 'Nome geral da taxonomia', 'conecta-experiencias' ),
			'singular_name'              => _x( 'Categoria', 'Nome singular da taxonomia', 'conecta-experiencias' ),
			'menu_name'                  => __( 'Categorias', 'conecta-experiencias' ),
			'all_items'                  => __( 'Todas as Categorias', 'conecta-experiencias' ),
			'edit_item'                  => __( 'Editar Categoria', 'conecta-experiencias' ),
			'view_item'                  => __( 'Ver Categoria', 'conecta-experiencias' ),
			'update_item'                => __( 'Atualizar Categoria', 'conecta-experiencias' ),
			'add_new_item'               => __( 'Adicionar nova Categoria', 'conecta-experiencias' ),
			'new_item_name'              => __( 'Nome da nova Categoria', 'conecta-experiencias' ),
			'parent_item'                => __( 'Categoria superior', 'conecta-experiencias' ),
			'parent_item_colon'          => __( 'Categoria superior:', 'conecta-experiencias' ),
			'search_items'               => __( 'Buscar Categorias', 'conecta-experiencias' ),
			'popular_items'              => __( 'Categorias populares', 'conecta-experiencias' ),
			'separate_items_with_commas' => __( 'Separe as categorias por vírgula', 'conecta-experiencias' ),
			'add_or_remove_items'        => __( 'Adicionar ou remover categorias', 'conecta-experiencias' ),
			'choose_from_most_used'      => __( 'Escolher entre as mais usadas', 'conecta-experiencias' ),
			'not_found'                  => __( 'Nenhuma categoria encontrada.', 'conecta-experiencias' ),
			'no_terms'                   => __( 'Nenhuma categoria', 'conecta-experiencias' ),
			'back_to_items'              => __( '← Voltar para Categorias', 'conecta-experiencias' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => true,
			'query_var'         => true,
			'rewrite'           => array(
				'slug'         => 'categoria',
				'with_front'   => false,
				'hierarchical' => true,
			),
		);

		register_taxonomy( self::TAXONOMY, array( Conecta_Exp_CPT::POST_TYPE ), $args );
	}
}
