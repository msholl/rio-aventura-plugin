<?php
/**
 * Galeria da experiência: caixa no admin para escolher fotos e vídeos
 * (biblioteca de mídia ou link do YouTube/Vimeo, arrastar para ordenar) e
 * leitura dos itens para o carrossel.
 *
 * Não usa o campo Galeria do ACF porque ele só existe no ACF Pro. O meta
 * `galeria` guarda a lista em ordem: IDs de anexo (foto ou vídeo MP4) e
 * URLs do YouTube/Vimeo.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reconhece um link do YouTube ou do Vimeo.
 *
 * @param string $url Link colado no admin.
 * @return array|null { provedor, id, embed (URL do player com autoplay) } ou null.
 */
function conecta_exp_video_externo( $url ) {
	$url = trim( (string) $url );

	if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m ) ) {
		return array(
			'provedor' => 'youtube',
			'id'       => $m[1],
			// youtube-nocookie: o YouTube só grava cookies se a pessoa der play.
			'embed'    => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0&playsinline=1',
		);
	}

	// vimeo.com/123, vimeo.com/123/hash (não listado), player.vimeo.com/video/123?h=hash.
	if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)(?:/([a-f0-9]+))?~i', $url, $m ) ) {
		$hash = ! empty( $m[2] ) ? $m[2] : '';
		if ( ! $hash && preg_match( '~[?&]h=([a-f0-9]+)~i', $url, $h ) ) {
			$hash = $h[1];
		}
		return array(
			'provedor' => 'vimeo',
			'id'       => $m[1],
			'embed'    => 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1' . ( $hash ? '&h=' . $hash : '' ),
		);
	}

	return null;
}

/**
 * Capa de um vídeo do YouTube/Vimeo, pelo oEmbed do provedor (cache de 1 semana).
 *
 * @param string $url    Link do vídeo.
 * @param array  $video  Retorno de conecta_exp_video_externo().
 * @return string URL da imagem, ou vazio.
 */
function conecta_exp_video_capa( $url, $video ) {
	if ( 'youtube' === $video['provedor'] ) {
		// URL fixa e pública; dispensa a chamada ao oEmbed.
		return 'https://i.ytimg.com/vi/' . $video['id'] . '/hqdefault.jpg';
	}

	$chave = 'conecta_exp_capa_' . md5( $url );
	$capa  = get_transient( $chave );
	if ( false === $capa ) {
		$dados = _wp_oembed_get_object()->get_data( $url, array( 'width' => 1280 ) );
		$capa  = $dados && ! empty( $dados->thumbnail_url ) ? (string) $dados->thumbnail_url : '';
		set_transient( $chave, $capa, $capa ? WEEK_IN_SECONDS : HOUR_IN_SECONDS );
	}
	return $capa;
}

/**
 * Itens do carrossel: a foto da experiência primeiro, depois os da galeria,
 * sem repetir e só o que ainda existe.
 *
 * @param int $post_id ID da experiência.
 * @return array[] Cada item: tipo ("imagem", "video" ou "externo") e os dados para exibir.
 */
function conecta_exp_galeria_itens( $post_id ) {
	$brutos = array_merge(
		array( (int) get_post_thumbnail_id( $post_id ) ),
		(array) get_post_meta( $post_id, 'galeria', true )
	);

	$itens = array();
	$vistos = array();
	foreach ( $brutos as $bruto ) {
		$chave = is_numeric( $bruto ) ? (int) $bruto : trim( (string) $bruto );
		if ( ! $chave || isset( $vistos[ $chave ] ) ) {
			continue;
		}
		$vistos[ $chave ] = true;

		if ( is_int( $chave ) ) {
			if ( wp_attachment_is_image( $chave ) ) {
				$itens[] = array(
					'tipo' => 'imagem',
					'id'   => $chave,
				);
			} elseif ( wp_attachment_is( 'video', $chave ) ) {
				$itens[] = array(
					'tipo'  => 'video',
					'id'    => $chave,
					'src'   => wp_get_attachment_url( $chave ),
					'mime'  => get_post_mime_type( $chave ),
					// Capa do vídeo, se o WordPress tiver uma (imagem destacada do anexo).
					'capa'  => (string) get_the_post_thumbnail_url( $chave, 'large' ),
					'thumb' => (string) get_the_post_thumbnail_url( $chave, 'thumbnail' ),
				);
			}
			continue;
		}

		$video = conecta_exp_video_externo( $chave );
		if ( $video ) {
			$capa  = conecta_exp_video_capa( $chave, $video );
			$item  = array(
				'tipo'         => 'externo',
				'url'          => $chave,
				'provedor'     => $video['provedor'],
				'embed'        => $video['embed'],
				'capa'         => $capa,
				'capa_reserva' => '',
				'thumb'        => $capa,
			);
			if ( 'youtube' === $video['provedor'] ) {
				// maxresdefault é 16:9 sem tarjas, mas nem todo vídeo tem; a
				// hqdefault (4:3 com tarjas pretas) fica de reserva.
				$base                 = 'https://i.ytimg.com/vi/' . $video['id'] . '/';
				$item['capa']         = $base . 'maxresdefault.jpg';
				$item['capa_reserva'] = $base . 'hqdefault.jpg';
				$item['thumb']        = $base . 'mqdefault.jpg';
			}
			$itens[] = $item;
		}
	}

	return $itens;
}

/**
 * Valida a lista vinda do admin: IDs de foto/vídeo e links do YouTube/Vimeo.
 *
 * @param array $lista Itens brutos.
 * @return array
 */
function conecta_exp_galeria_sanitizar( $lista ) {
	$limpa = array();
	foreach ( (array) $lista as $item ) {
		if ( is_numeric( $item ) ) {
			$id = absint( $item );
			if ( $id && ( wp_attachment_is_image( $id ) || wp_attachment_is( 'video', $id ) ) ) {
				$limpa[] = $id;
			}
		} elseif ( is_string( $item ) && conecta_exp_video_externo( $item ) ) {
			$limpa[] = esc_url_raw( trim( $item ) );
		}
	}
	return array_values( array_unique( $limpa, SORT_REGULAR ) );
}

/**
 * Registra a caixa "Galeria de fotos e vídeos" na tela de edição.
 */
function conecta_exp_galeria_metabox() {
	add_meta_box(
		'conecta-exp-galeria',
		__( 'Galeria de fotos e vídeos', 'conecta-experiencias' ),
		'conecta_exp_galeria_metabox_html',
		Conecta_Exp_CPT::POST_TYPE,
		'side',
		'low'
	);
}
add_action( 'add_meta_boxes', 'conecta_exp_galeria_metabox' );

/**
 * Miniatura de um item na caixa do admin.
 *
 * @param int|string $item ID do anexo ou link do vídeo.
 */
function conecta_exp_galeria_admin_item( $item ) {
	$rotulo = '';
	if ( is_numeric( $item ) ) {
		if ( wp_attachment_is_image( $item ) ) {
			$miniatura = wp_get_attachment_image( $item, 'thumbnail' );
		} else {
			$capa      = get_the_post_thumbnail_url( $item, 'thumbnail' );
			$miniatura = $capa ? '<img src="' . esc_url( $capa ) . '" alt="">' : '<span class="cxp-admin-galeria__video dashicons dashicons-video-alt3"></span>';
			$rotulo    = 'MP4';
		}
	} else {
		$video     = conecta_exp_video_externo( $item );
		$capa      = conecta_exp_video_capa( $item, $video );
		$miniatura = $capa ? '<img src="' . esc_url( $capa ) . '" alt="">' : '<span class="cxp-admin-galeria__video dashicons dashicons-video-alt3"></span>';
		$rotulo    = 'youtube' === $video['provedor'] ? 'YouTube' : 'Vimeo';
	}
	?>
	<li data-item="<?php echo esc_attr( $item ); ?>">
		<?php echo $miniatura; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- montado acima com esc_url/wp_get_attachment_image. ?>
		<?php if ( $rotulo ) : ?>
			<span class="cxp-admin-galeria__rotulo"><?php echo esc_html( $rotulo ); ?></span>
		<?php endif; ?>
		<button type="button" class="cxp-admin-galeria__remover" aria-label="<?php esc_attr_e( 'Remover', 'conecta-experiencias' ); ?>">&times;</button>
	</li>
	<?php
}

/**
 * HTML da caixa.
 *
 * @param WP_Post $post Experiência.
 */
function conecta_exp_galeria_metabox_html( $post ) {
	$itens = conecta_exp_galeria_sanitizar( (array) get_post_meta( $post->ID, 'galeria', true ) );
	wp_nonce_field( 'conecta_exp_galeria', 'conecta_exp_galeria_nonce' );
	?>
	<p class="description"><?php esc_html_e( 'Itens do carrossel da página da experiência, depois da foto da experiência. Arraste para mudar a ordem.', 'conecta-experiencias' ); ?></p>
	<ul class="cxp-admin-galeria" data-cxp-galeria>
		<?php array_map( 'conecta_exp_galeria_admin_item', $itens ); ?>
	</ul>
	<input type="hidden" name="conecta_exp_galeria" value="<?php echo esc_attr( wp_json_encode( $itens ) ); ?>">
	<p class="cxp-admin-galeria__botoes">
		<button type="button" class="button" data-cxp-galeria-adicionar><?php esc_html_e( 'Adicionar fotos ou vídeos', 'conecta-experiencias' ); ?></button>
		<button type="button" class="button" data-cxp-galeria-link><?php esc_html_e( 'Adicionar link do YouTube/Vimeo', 'conecta-experiencias' ); ?></button>
	</p>
	<p class="description"><?php esc_html_e( 'Vídeos enviados ao site: prefira clipes curtos (até ~30 s e 15 MB). Para vídeos maiores, use um link do YouTube ou Vimeo.', 'conecta-experiencias' ); ?></p>
	<?php
}

/**
 * Salva a ordem/seleção da galeria.
 *
 * @param int $post_id ID da experiência.
 */
function conecta_exp_galeria_salvar( $post_id ) {
	if ( ! isset( $_POST['conecta_exp_galeria_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['conecta_exp_galeria_nonce'] ), 'conecta_exp_galeria' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON validado item a item em conecta_exp_galeria_sanitizar().
	$valor = isset( $_POST['conecta_exp_galeria'] ) ? json_decode( wp_unslash( $_POST['conecta_exp_galeria'] ), true ) : array();
	$itens = conecta_exp_galeria_sanitizar( is_array( $valor ) ? $valor : array() );

	if ( $itens ) {
		update_post_meta( $post_id, 'galeria', $itens );
	} else {
		delete_post_meta( $post_id, 'galeria' );
	}
}
add_action( 'save_post_' . Conecta_Exp_CPT::POST_TYPE, 'conecta_exp_galeria_salvar' );

/**
 * AJAX do admin: valida um link colado e devolve a miniatura para a caixa.
 */
function conecta_exp_galeria_ajax_link() {
	check_ajax_referer( 'conecta_exp_galeria_link' );
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( null, 403 );
	}

	$url   = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
	$video = conecta_exp_video_externo( $url );
	if ( ! $video ) {
		wp_send_json_error( array( 'mensagem' => __( 'Link não reconhecido. Use um link do YouTube ou do Vimeo.', 'conecta-experiencias' ) ) );
	}

	ob_start();
	conecta_exp_galeria_admin_item( $url );
	wp_send_json_success( array( 'html' => ob_get_clean() ) );
}
add_action( 'wp_ajax_conecta_exp_galeria_link', 'conecta_exp_galeria_ajax_link' );

/**
 * Script e estilo da caixa, só na edição de experiência.
 *
 * @param string $hook Tela atual.
 */
function conecta_exp_galeria_admin_assets( $hook ) {
	$tela = get_current_screen();
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $tela || Conecta_Exp_CPT::POST_TYPE !== $tela->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'conecta-exp-galeria-admin', plugins_url( 'assets/galeria-admin.js', CONECTA_EXP_FILE ), array( 'jquery', 'jquery-ui-sortable' ), CONECTA_EXP_VERSION, true );
	wp_localize_script(
		'conecta-exp-galeria-admin',
		'conectaExpGaleria',
		array(
			'titulo'   => __( 'Fotos e vídeos da galeria', 'conecta-experiencias' ),
			'botao'    => __( 'Adicionar à galeria', 'conecta-experiencias' ),
			'remover'  => __( 'Remover', 'conecta-experiencias' ),
			'pedirUrl' => __( 'Cole o link do vídeo no YouTube ou no Vimeo:', 'conecta-experiencias' ),
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'conecta_exp_galeria_link' ),
		)
	);
	wp_add_inline_style(
		'wp-admin',
		'.cxp-admin-galeria{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin:8px 0}'
		. '.cxp-admin-galeria li{position:relative;margin:0;cursor:move}'
		. '.cxp-admin-galeria img,.cxp-admin-galeria__video{display:block;width:100%;height:auto;aspect-ratio:1;object-fit:cover;border-radius:3px}'
		. '.cxp-admin-galeria__video{display:flex;align-items:center;justify-content:center;background:#1d2327;color:#fff;font-size:28px}'
		. '.cxp-admin-galeria__rotulo{position:absolute;left:3px;bottom:3px;padding:0 4px;border-radius:2px;background:rgba(0,0,0,.75);color:#fff;font-size:10px;line-height:16px}'
		. '.cxp-admin-galeria__remover{position:absolute;top:2px;right:2px;width:22px;height:22px;padding:0;border:0;border-radius:50%;background:#b32d2e;color:#fff;font-size:16px;line-height:22px;cursor:pointer}'
		. '.cxp-admin-galeria .ui-sortable-placeholder{visibility:visible!important;border:2px dashed #c3c4c7}'
		. '.cxp-admin-galeria__botoes{display:flex;flex-direction:column;gap:6px}.cxp-admin-galeria__botoes .button{text-align:center}'
	);
}
add_action( 'admin_enqueue_scripts', 'conecta_exp_galeria_admin_assets' );
