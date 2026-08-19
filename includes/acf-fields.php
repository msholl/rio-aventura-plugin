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
					array(
						'key'         => 'field_exp_horarios',
						'label'       => __( 'Horários', 'conecta-experiencias' ),
						'name'        => 'horarios',
						'type'        => 'text',
						'placeholder' => __( 'Ex.: 8h, 11h, 16h', 'conecta-experiencias' ),
					),
					array(
						'key'          => 'field_exp_incluso',
						'label'        => __( 'Incluso', 'conecta-experiencias' ),
						'name'         => 'incluso',
						'type'         => 'textarea',
						'rows'         => 5,
						'new_lines'    => 'br',
						'instructions' => __( 'Um item por linha — cada linha vira uma quebra (<br>) na exibição, sem lista com marcadores.', 'conecta-experiencias' ),
					),
					array(
						'key'          => 'field_exp_nao_incluso',
						'label'        => __( 'Não Incluso', 'conecta-experiencias' ),
						'name'         => 'nao_incluso',
						'type'         => 'textarea',
						'rows'         => 5,
						'new_lines'    => 'br',
						'instructions' => __( 'Um item por linha — cada linha vira uma quebra (<br>) na exibição, sem lista com marcadores.', 'conecta-experiencias' ),
					),
					array(
						'key'          => 'field_exp_levar_contigo',
						'label'        => __( 'Levar Contigo', 'conecta-experiencias' ),
						'name'         => 'levar_contigo',
						'type'         => 'textarea',
						'rows'         => 3,
						'new_lines'    => 'wpautop',
						'instructions' => __( 'Texto corrido — use separadores como "·" ou vírgula.', 'conecta-experiencias' ),
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
