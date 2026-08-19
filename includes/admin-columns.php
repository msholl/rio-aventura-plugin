<?php
/**
 * Coluna "Foto" na listagem de Experiências no admin.
 *
 * Mostra a miniatura da imagem destacada. Quando a experiência ainda não tem
 * foto, exibe um placeholder em vez de deixar a célula vazia — assim a falta
 * de imagem salta aos olhos ao varrer a lista, que é justamente quando ela
 * precisa ser notada.
 *
 * Só toca no admin: não registra nada no front e não altera a consulta.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

/**
 * Chave da coluna. Prefixada para não colidir com colunas de outros plugins.
 */
const CONECTA_EXP_COLUNA_FOTO = 'conecta_exp_foto';

/**
 * Insere a coluna logo após a checkbox, antes do título.
 *
 * Percorre e reconstrói o array para preservar a ordem das demais colunas —
 * outros plugins podem ter adicionado as suas.
 *
 * @param array $colunas Colunas registradas para a listagem.
 * @return array
 */
function conecta_exp_admin_colunas( $colunas ) {
	$novas = array();

	foreach ( $colunas as $chave => $rotulo ) {
		$novas[ $chave ] = $rotulo;

		if ( 'cb' === $chave ) {
			$novas[ CONECTA_EXP_COLUNA_FOTO ] = __( 'Foto', 'conecta-experiencias' );
		}
	}

	// Sem checkbox (ex.: usuário sem permissão de edição em massa), entra na frente.
	if ( ! isset( $novas[ CONECTA_EXP_COLUNA_FOTO ] ) ) {
		$novas = array( CONECTA_EXP_COLUNA_FOTO => __( 'Foto', 'conecta-experiencias' ) ) + $novas;
	}

	return $novas;
}
add_filter( 'manage_' . Conecta_Exp_CPT::POST_TYPE . '_posts_columns', 'conecta_exp_admin_colunas' );

/**
 * Renderiza a célula: miniatura ou placeholder.
 *
 * @param string $coluna  Chave da coluna sendo renderizada.
 * @param int    $post_id ID da experiência da linha.
 */
function conecta_exp_admin_coluna_conteudo( $coluna, $post_id ) {
	if ( CONECTA_EXP_COLUNA_FOTO !== $coluna ) {
		return;
	}

	if ( has_post_thumbnail( $post_id ) ) {
		echo get_the_post_thumbnail(
			$post_id,
			array( 120, 120 ),
			array(
				'class'   => 'conecta-exp-foto',
				'loading' => 'lazy',
				'alt'     => '',
			)
		);
		return;
	}

	printf(
		'<span class="conecta-exp-foto conecta-exp-foto--vazia"><span class="dashicons dashicons-palmtree" aria-hidden="true"></span><span class="screen-reader-text">%s</span></span>',
		esc_html__( 'Sem foto', 'conecta-experiencias' )
	);
}
add_action( 'manage_' . Conecta_Exp_CPT::POST_TYPE . '_posts_custom_column', 'conecta_exp_admin_coluna_conteudo', 10, 2 );

/**
 * CSS da coluna, inline e só na tela da listagem.
 *
 * São poucas regras e valem para uma única tela — não justifica enfileirar um
 * arquivo e uma requisição a mais.
 */
function conecta_exp_admin_coluna_estilo() {
	$tela = get_current_screen();

	if ( ! $tela || 'edit-' . Conecta_Exp_CPT::POST_TYPE !== $tela->id ) {
		return;
	}
	?>
	<style id="conecta-exp-coluna-foto">
		.wp-list-table .column-<?php echo esc_html( CONECTA_EXP_COLUNA_FOTO ); ?> {
			width: 76px;
		}
		.conecta-exp-foto {
			display: block;
			width: 60px;
			height: 60px;
			border-radius: 3px;
		}
		img.conecta-exp-foto {
			object-fit: cover;
			background: #f0f0f1;
		}
		.conecta-exp-foto--vazia {
			display: flex;
			align-items: center;
			justify-content: center;
			background: #f0f0f1;
			border: 1px dashed #c3c4c7;
			color: #a7aaad;
		}
		.conecta-exp-foto--vazia .dashicons {
			width: 24px;
			height: 24px;
			font-size: 24px;
		}
		/* Nas larguras em que o WP empilha as colunas, o rótulo "Foto" reaparece. */
		@media screen and (max-width: 782px) {
			.wp-list-table .column-<?php echo esc_html( CONECTA_EXP_COLUNA_FOTO ); ?> {
				width: auto;
			}
		}
	</style>
	<?php
}
add_action( 'admin_head', 'conecta_exp_admin_coluna_estilo' );
