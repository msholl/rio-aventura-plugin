<?php
/**
 * Registro do Custom Post Type "Experiência".
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

/**
 * Encapsula o registro do CPT `experiencia`.
 */
class Conecta_Exp_CPT {

	/**
	 * Slug interno do post type.
	 *
	 * @var string
	 */
	const POST_TYPE = 'experiencia';

	/**
	 * Registra o post type.
	 *
	 * Idempotente: o WordPress simplesmente sobrescreve o registro anterior
	 * se chamado mais de uma vez na mesma requisição.
	 */
	public static function register() {
		$labels = array(
			'name'                  => _x( 'Experiências', 'Nome geral do post type', 'conecta-experiencias' ),
			'singular_name'         => _x( 'Experiência', 'Nome singular do post type', 'conecta-experiencias' ),
			'menu_name'             => _x( 'Experiências', 'Texto do menu admin', 'conecta-experiencias' ),
			'name_admin_bar'        => _x( 'Experiência', 'Item da barra admin', 'conecta-experiencias' ),
			'add_new'               => __( 'Adicionar nova', 'conecta-experiencias' ),
			'add_new_item'          => __( 'Adicionar nova Experiência', 'conecta-experiencias' ),
			'new_item'              => __( 'Nova Experiência', 'conecta-experiencias' ),
			'edit_item'             => __( 'Editar Experiência', 'conecta-experiencias' ),
			'view_item'             => __( 'Ver Experiência', 'conecta-experiencias' ),
			'view_items'            => __( 'Ver Experiências', 'conecta-experiencias' ),
			'all_items'             => __( 'Todas as Experiências', 'conecta-experiencias' ),
			'search_items'          => __( 'Buscar Experiências', 'conecta-experiencias' ),
			'parent_item_colon'     => __( 'Experiência superior:', 'conecta-experiencias' ),
			'not_found'             => __( 'Nenhuma experiência encontrada.', 'conecta-experiencias' ),
			'not_found_in_trash'    => __( 'Nenhuma experiência na lixeira.', 'conecta-experiencias' ),
			'featured_image'        => __( 'Foto da experiência', 'conecta-experiencias' ),
			'set_featured_image'    => __( 'Definir foto da experiência', 'conecta-experiencias' ),
			'remove_featured_image' => __( 'Remover foto da experiência', 'conecta-experiencias' ),
			'use_featured_image'    => __( 'Usar como foto da experiência', 'conecta-experiencias' ),
			'archives'              => __( 'Arquivo de Experiências', 'conecta-experiencias' ),
			'insert_into_item'      => __( 'Inserir na experiência', 'conecta-experiencias' ),
			'uploaded_to_this_item' => __( 'Enviado para esta experiência', 'conecta-experiencias' ),
			'filter_items_list'     => __( 'Filtrar lista de experiências', 'conecta-experiencias' ),
			'items_list_navigation' => __( 'Navegação da lista de experiências', 'conecta-experiencias' ),
			'items_list'            => __( 'Lista de experiências', 'conecta-experiencias' ),
			'item_published'        => __( 'Experiência publicada.', 'conecta-experiencias' ),
			'item_updated'          => __( 'Experiência atualizada.', 'conecta-experiencias' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'has_archive'        => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-palmtree',
			'menu_position'      => 20,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'taxonomies'         => array( Conecta_Exp_Taxonomy::TAXONOMY ),
			'rewrite'            => array(
				'slug'       => 'experiencias',
				'with_front' => false,
			),
			'hierarchical'       => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'capability_type'    => 'post',
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
