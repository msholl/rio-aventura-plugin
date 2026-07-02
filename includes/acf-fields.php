<?php
/**
 * Grupo de campos ACF "Detalhes da Experiência".
 *
 * Todos os campos são de texto livre para permitir a formatação manual já com
 * unidade/símbolo (ex.: "R$ 180,00", "533m", "0,5km"). Por isso a exibição no
 * Elementor NÃO deve usar Before/After, para não duplicar o símbolo.
 *
 * Registrado via código (acf_add_local_field_group) para ficar versionado e
 * independente do banco. A categoria NÃO é um campo aqui — é a taxonomia
 * `categoria_experiencia`, que alimenta o filtro nativo do Elementor.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_experiencia_detalhes',
				'title'    => __( 'Detalhes da Experiência', 'conecta-experiencias' ),
				'fields'   => array(
					array(
						'key'         => 'field_exp_preco',
						'label'       => __( 'Preço', 'conecta-experiencias' ),
						'name'        => 'preco',
						'type'        => 'text',
						'placeholder' => __( 'Ex.: R$ 180,00', 'conecta-experiencias' ),
					),
					array(
						'key'         => 'field_exp_duracao',
						'label'       => __( 'Duração', 'conecta-experiencias' ),
						'name'        => 'duracao',
						'type'        => 'text',
						'placeholder' => __( 'Ex.: 3 horas', 'conecta-experiencias' ),
					),
					array(
						'key'         => 'field_exp_dificuldade',
						'label'       => __( 'Dificuldade', 'conecta-experiencias' ),
						'name'        => 'dificuldade',
						'type'        => 'text',
						'placeholder' => __( 'Ex.: Moderado', 'conecta-experiencias' ),
					),
					array(
						'key'         => 'field_exp_distancia',
						'label'       => __( 'Distância', 'conecta-experiencias' ),
						'name'        => 'distancia',
						'type'        => 'text',
						'placeholder' => __( 'Ex.: 533m ou 0,5km', 'conecta-experiencias' ),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'experiencia',
						),
					),
				),
				'active'   => true,
			)
		);

		// Cor do termo da categoria: dado para uso opcional no card via Elementor.
		acf_add_local_field_group(
			array(
				'key'      => 'group_categoria_estilo',
				'title'    => __( 'Estilo da Categoria', 'conecta-experiencias' ),
				'fields'   => array(
					array(
						'key'           => 'field_cat_cor',
						'label'         => __( 'Cor', 'conecta-experiencias' ),
						'name'          => 'cor',
						'type'          => 'color_picker',
						'instructions'  => __( 'Cor da categoria, disponível como dado para uso opcional no card via Elementor.', 'conecta-experiencias' ),
						'enable_opacity' => 0,
						'return_format' => 'string',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => 'categoria_experiencia',
						),
					),
				),
				'active'   => true,
			)
		);
	}
);
