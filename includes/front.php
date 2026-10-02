<?php
/**
 * Exibição no front: listagem [experiencias], página da experiência
 * [experiencia_detalhe] e template automático de /experiencias/{slug}.
 *
 * O HTML vive em templates/ e pode ser sobrescrito pelo tema copiando o
 * arquivo para {tema}/rio-aventura/{nome}.php. As classes CSS usam o
 * prefixo `cxp-` para não colidir com Elementor/tema.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

/**
 * Número de WhatsApp (só dígitos, com DDI) usado nos botões de reserva.
 *
 * @return string
 */
function conecta_exp_whatsapp_numero() {
	$numero = apply_filters( 'conecta_exp_whatsapp_numero', '5521990853118' );
	return preg_replace( '/\D+/', '', (string) $numero );
}

/**
 * Link do WhatsApp com a mensagem de reserva da experiência.
 *
 * @param WP_Post|int $post   Experiência.
 * @param string      $numero Número opcional; vazio usa o padrão.
 * @return string URL do wa.me.
 */
function conecta_exp_whatsapp_url( $post, $numero = '' ) {
	$post   = get_post( $post );
	$numero = $numero ? preg_replace( '/\D+/', '', $numero ) : conecta_exp_whatsapp_numero();

	// post_title cru: get_the_title() recebe marcação do TranslatePress nos
	// idiomas traduzidos, que iria parar dentro da mensagem.
	/* translators: %s: nome da experiência. */
	$mensagem = sprintf( __( '¡Hola! Vi en el sitio la experiencia "%s" y quiero reservarla.', 'conecta-experiencias' ), wp_strip_all_tags( $post->post_title ) );
	$mensagem = apply_filters( 'conecta_exp_whatsapp_mensagem', $mensagem, $post );

	return 'https://wa.me/' . $numero . '?text=' . rawurlencode( $mensagem );
}

/**
 * Lê um campo ACF sem formatação, com fallback para post meta quando o ACF
 * está inativo (o importador grava no mesmo meta_key).
 *
 * @param string $campo   Nome do campo.
 * @param int    $post_id ID da experiência.
 * @return string
 */
function conecta_exp_campo( $campo, $post_id ) {
	$valor = function_exists( 'get_field' ) ? get_field( $campo, $post_id, false ) : get_post_meta( $post_id, $campo, true );
	return is_string( $valor ) ? trim( $valor ) : '';
}

/**
 * Se a experiência está marcada como destaque (campo ACF true/false).
 *
 * @param int $post_id ID da experiência.
 * @return bool
 */
function conecta_exp_destaque( $post_id ) {
	return (bool) get_post_meta( $post_id, 'destaque', true );
}

/**
 * Slug do filtro "Destacados" na listagem (?categoria=destacados). Não é uma
 * categoria de verdade: os cards em destaque recebem esse slug a mais em
 * data-categorias, e o JS do filtro trata igual às categorias.
 */
const CONECTA_EXP_FILTRO_DESTAQUE = 'destacados';

/**
 * Âncora do início da listagem (filtro + cards). Links de fora, como os
 * botões de categoria da home, usam #experiencias para abrir já nos cards.
 */
const CONECTA_EXP_ANCORA_LISTAGEM = 'experiencias';

/**
 * Textarea de "um item por linha" → array de itens não vazios.
 *
 * @param string $campo   Nome do campo.
 * @param int    $post_id ID da experiência.
 * @return string[]
 */
function conecta_exp_campo_lista( $campo, $post_id ) {
	$linhas = preg_split( '/\r\n|\r|\n/', conecta_exp_campo( $campo, $post_id ) );
	return array_values( array_filter( array_map( 'trim', $linhas ), 'strlen' ) );
}

/**
 * Preço pronto para exibir. O campo é texto livre: alguns vêm como
 * "230,00", outros já com símbolo — só prefixa "R$" quando começa com número.
 *
 * @param int $post_id ID da experiência.
 * @return string
 */
function conecta_exp_preco( $post_id ) {
	$preco = conecta_exp_campo( 'preco', $post_id );
	if ( '' === $preco ) {
		return '';
	}
	return preg_match( '/^\d/', $preco ) ? 'R$ ' . $preco : $preco;
}

/**
 * Primeira categoria da experiência (mesma regra de conecta_exp_cor_categoria_valor()).
 *
 * @param int $post_id ID da experiência.
 * @return WP_Term|null
 */
function conecta_exp_categoria_principal( $post_id ) {
	$terms = get_the_terms( $post_id, Conecta_Exp_Taxonomy::TAXONOMY );
	return ( empty( $terms ) || is_wp_error( $terms ) ) ? null : $terms[0];
}

/**
 * Luminância relativa (WCAG) de uma cor hex.
 *
 * @param string $hex Cor "#rrggbb".
 * @return float
 */
function conecta_exp_luminancia( $hex ) {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	$canais = array_map(
		function ( $c ) {
			$c = hexdec( $c ) / 255;
			return $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
		},
		str_split( $hex, 2 )
	);
	return 0.2126 * $canais[0] + 0.7152 * $canais[1] + 0.0722 * $canais[2];
}

/**
 * Cor de texto (clara ou escura) com mais contraste sobre a cor dada —
 * as cores de categoria são escolhidas no admin e podem ser claras ou escuras.
 *
 * @param string $fundo Cor hex de fundo.
 * @return string
 */
function conecta_exp_cor_texto_sobre( $fundo ) {
	$l       = conecta_exp_luminancia( $fundo );
	$escuro  = '#132110';
	$l_claro = 1.0;
	$l_esc   = conecta_exp_luminancia( $escuro );

	$contraste_claro = ( $l_claro + 0.05 ) / ( $l + 0.05 );
	$contraste_esc   = ( $l + 0.05 ) / ( $l_esc + 0.05 );

	return $contraste_claro >= $contraste_esc ? '#ffffff' : $escuro;
}

/**
 * Variáveis CSS de cor da categoria para o atributo style.
 *
 * @param WP_Term|null $term Categoria.
 * @return string
 */
function conecta_exp_estilo_categoria( $term ) {
	$cor = conecta_exp_cor_categoria_valor( '#008B72', $term ? $term->term_id : 0 );
	return sprintf( '--cxp-cor:%s;--cxp-cor-texto:%s;', $cor, conecta_exp_cor_texto_sobre( $cor ) );
}

/**
 * Resumo curto para o card: o excerpt, ou o começo do conteúdo.
 *
 * @param WP_Post $post Experiência.
 * @return string
 */
function conecta_exp_resumo( $post ) {
	if ( has_excerpt( $post ) ) {
		return get_the_excerpt( $post );
	}
	return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 24 );
}

/**
 * URL da página que contém o shortcode [experiencias] — destino do
 * "voltar" e dos links de categoria na página da experiência.
 *
 * Procurada no conteúdo e nos dados do Elementor e guardada em transient
 * (limpo ao salvar uma página). Sem página, cai no arquivo do CPT.
 *
 * @return string
 */
function conecta_exp_url_listagem() {
	$page_id = get_transient( 'conecta_exp_pagina_listagem' );

	if ( false === $page_id ) {
		global $wpdb;
		// "[experiencias]" ou "[experiencias atributo=…]" — sem pegar os
		// outros shortcodes com o mesmo prefixo, como [experiencias_categorias].
		$sem_atributos = '%' . $wpdb->esc_like( '[experiencias]' ) . '%';
		$com_atributos = '%' . $wpdb->esc_like( '[experiencias ' ) . '%';
		$page_id       = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_data'
				WHERE p.post_type = 'page' AND p.post_status = 'publish'
				AND ( p.post_content LIKE %s OR p.post_content LIKE %s OR m.meta_value LIKE %s OR m.meta_value LIKE %s )
				ORDER BY p.ID ASC LIMIT 1",
				$sem_atributos,
				$com_atributos,
				$sem_atributos,
				$com_atributos
			)
		);
		set_transient( 'conecta_exp_pagina_listagem', $page_id, DAY_IN_SECONDS );
	}

	$url = $page_id ? get_permalink( $page_id ) : get_post_type_archive_link( Conecta_Exp_CPT::POST_TYPE );
	return apply_filters( 'conecta_exp_url_listagem', $url );
}

add_action(
	'save_post_page',
	function () {
		delete_transient( 'conecta_exp_pagina_listagem' );
	}
);

/**
 * Inclui um template do plugin, permitindo override pelo tema em
 * {tema}/rio-aventura/{nome}.php.
 *
 * @param string $nome Nome do template sem extensão.
 * @param array  $vars Variáveis disponíveis no template.
 */
function conecta_exp_template( $nome, array $vars = array() ) {
	$arquivo = locate_template( 'rio-aventura/' . $nome . '.php' );
	if ( ! $arquivo ) {
		$arquivo = CONECTA_EXP_PATH . 'templates/' . $nome . '.php';
	}
	// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- variáveis locais do template.
	extract( $vars, EXTR_SKIP );
	include $arquivo;
}

/**
 * Captura a saída de um template como string (para shortcodes).
 *
 * @param string $nome Nome do template.
 * @param array  $vars Variáveis.
 * @return string
 */
function conecta_exp_render( $nome, array $vars = array() ) {
	ob_start();
	conecta_exp_template( $nome, $vars );
	return ob_get_clean();
}

/**
 * Converte atributos "sim/si/yes/1/true" em booleano.
 *
 * @param mixed $valor Valor do atributo.
 * @return bool
 */
function conecta_exp_bool( $valor ) {
	return in_array( strtolower( (string) $valor ), array( '1', 'true', 'yes', 'sim', 'si', 'sí', 'on' ), true );
}

/**
 * CSS/JS do front. Registrados sempre; enfileirados só onde há uso.
 */
function conecta_exp_registrar_assets() {
	wp_register_style( 'conecta-exp-front', plugins_url( 'assets/experiencias.css', CONECTA_EXP_FILE ), array(), CONECTA_EXP_VERSION );
	wp_register_script( 'conecta-exp-front', plugins_url( 'assets/experiencias.js', CONECTA_EXP_FILE ), array(), CONECTA_EXP_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );

	// Enfileira já no <head> quando dá para saber de antemão, evitando o
	// "pisca" sem estilo de um enqueue tardio feito dentro do shortcode.
	if ( is_singular( Conecta_Exp_CPT::POST_TYPE ) ) {
		wp_enqueue_style( 'conecta-exp-front' );
		wp_enqueue_script( 'conecta-exp-front' );
		return;
	}

	$post = get_queried_object();
	if ( $post instanceof WP_Post ) {
		$elementor = (string) get_post_meta( $post->ID, '_elementor_data', true );
		if ( has_shortcode( $post->post_content, 'experiencias' ) || false !== strpos( $elementor, '[experiencias' ) || false !== strpos( $elementor, '[experiencia_detalhe' ) ) {
			wp_enqueue_style( 'conecta-exp-front' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'conecta_exp_registrar_assets' );

/**
 * Shortcode [experiencias] — grade de experiências com filtro por categoria.
 *
 * Atributos:
 * - `categoria`:  slugs separados por vírgula; restringe a listagem a elas.
 * - `filtro`:     "si"/"no" — exibe os botões de filtro. Default "si".
 * - `limite`:     máximo de experiências. Default -1 (todas).
 * - `colunas`:    colunas no desktop (1–4). Default 3.
 * - `titulo_tag`: tag do título do card (h2–h4). Default "h2".
 *
 * O filtro funciona sem recarregar a página (JS) e também sem JS, pelo
 * parâmetro ?categoria={slug}, que serve de link direto para uma categoria.
 * Havendo experiência marcada como destaque, o filtro "Destacados"
 * (?categoria=destacados) vem antes de "Todas".
 *
 * @param array|string $atts Atributos.
 * @return string
 */
function conecta_exp_shortcode_listagem( $atts ) {
	$atts = shortcode_atts(
		array(
			'categoria'  => '',
			'filtro'     => 'si',
			'limite'     => -1,
			'colunas'    => 3,
			'titulo_tag' => 'h2',
		),
		$atts,
		'experiencias'
	);

	$restringir = array_filter( array_map( 'sanitize_title', explode( ',', $atts['categoria'] ) ) );

	$query_args = array(
		'post_type'      => Conecta_Exp_CPT::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['limite'],
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true,
	);
	if ( $restringir ) {
		$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => Conecta_Exp_Taxonomy::TAXONOMY,
				'field'    => 'slug',
				'terms'    => $restringir,
			),
		);
	}
	$experiencias = get_posts( apply_filters( 'conecta_exp_listagem_query_args', $query_args, $atts ) );

	// Categorias do filtro: só as que têm experiência na listagem atual.
	$categorias = array();
	foreach ( $experiencias as $exp ) {
		foreach ( (array) get_the_terms( $exp, Conecta_Exp_Taxonomy::TAXONOMY ) as $term ) {
			if ( $term instanceof WP_Term ) {
				$categorias[ $term->slug ] = $term;
			}
		}
	}
	uasort(
		$categorias,
		function ( $a, $b ) {
			return strcasecmp( $a->name, $b->name );
		}
	);

	$destaques = count( array_filter( wp_list_pluck( $experiencias, 'ID' ), 'conecta_exp_destaque' ) );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtro público só de leitura.
	$ativa = isset( $_GET['categoria'] ) ? sanitize_title( wp_unslash( $_GET['categoria'] ) ) : '';
	if ( ! isset( $categorias[ $ativa ] ) && ! ( CONECTA_EXP_FILTRO_DESTAQUE === $ativa && $destaques ) ) {
		$ativa = '';
	}

	$tag = in_array( $atts['titulo_tag'], array( 'h2', 'h3', 'h4' ), true ) ? $atts['titulo_tag'] : 'h2';

	wp_enqueue_style( 'conecta-exp-front' );
	wp_enqueue_script( 'conecta-exp-front' );

	return conecta_exp_render(
		'listagem',
		array(
			'experiencias' => $experiencias,
			'categorias'   => $categorias,
			'destaques'    => $destaques,
			'ativa'        => $ativa,
			'mostrar_filtro' => conecta_exp_bool( $atts['filtro'] ) && ( count( $categorias ) > 1 || $destaques ),
			'colunas'      => max( 1, min( 4, (int) $atts['colunas'] ) ),
			'titulo_tag'   => $tag,
		)
	);
}
add_shortcode( 'experiencias', 'conecta_exp_shortcode_listagem' );

/**
 * Shortcode [experiencias_categorias] — botões das categorias que levam à
 * página de experiências já filtrada (?categoria={slug}). Feito para a home.
 *
 * Atributos:
 * - `categorias`: slugs separados por vírgula, na ordem desejada. Default:
 *                 todas as categorias com experiência publicada, em ordem alfabética.
 * - `limite`:     máximo de botões. Default 4.
 * - `alinhamento`: "centro" ou "esquerda". Default "centro".
 * - `titulo`:      título acima dos botões. Default "Explora por categoría";
 *                  titulo="" esconde.
 *
 * @param array|string $atts Atributos.
 * @return string
 */
function conecta_exp_shortcode_categorias( $atts ) {
	$atts = shortcode_atts(
		array(
			'categorias'  => '',
			'limite'      => 4,
			'alinhamento' => 'centro',
			'titulo'      => __( 'Explora por categoría', 'conecta-experiencias' ),
		),
		$atts,
		'experiencias_categorias'
	);

	$slugs = array_filter( array_map( 'sanitize_title', explode( ',', $atts['categorias'] ) ) );
	$args  = array(
		'taxonomy'   => Conecta_Exp_Taxonomy::TAXONOMY,
		'hide_empty' => true,
	);
	if ( $slugs ) {
		$args['slug']    = $slugs;
		$args['orderby'] = 'slug__in';
	} else {
		$args['orderby'] = 'name';
	}

	$categorias = get_terms( $args );
	if ( is_wp_error( $categorias ) || ! $categorias ) {
		return '';
	}
	if ( (int) $atts['limite'] > 0 ) {
		$categorias = array_slice( $categorias, 0, (int) $atts['limite'] );
	}

	wp_enqueue_style( 'conecta-exp-front' );

	return conecta_exp_render(
		'categorias',
		array(
			'categorias'  => $categorias,
			'listagem'    => conecta_exp_url_listagem(),
			'alinhamento' => 'esquerda' === $atts['alinhamento'] ? 'esquerda' : 'centro',
			'titulo'      => trim( $atts['titulo'] ),
		)
	);
}
add_shortcode( 'experiencias_categorias', 'conecta_exp_shortcode_categorias' );

/**
 * Shortcode [experiencias_destaque] — cards das experiências marcadas como
 * destaque, com link para a listagem completa. Feito para a home.
 *
 * Atributos:
 * - `limite`:  quantos cards. Default -1 (todas as marcadas como destaque).
 * - `colunas`: colunas no desktop (1–4). Default: uma por card, até 4.
 *
 * Até 1024px os cards viram uma faixa com rolagem horizontal.
 * - `titulo`:  título acima dos cards. Default "Experiencias destacadas"; titulo="" esconde.
 *
 * @param array|string $atts Atributos.
 * @return string
 */
function conecta_exp_shortcode_destaque( $atts ) {
	$atts = shortcode_atts(
		array(
			'limite'  => -1,
			'colunas' => 0,
			'titulo'  => __( 'Experiencias destacadas', 'conecta-experiencias' ),
		),
		$atts,
		'experiencias_destaque'
	);

	$experiencias = get_posts(
		array(
			'post_type'      => Conecta_Exp_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['limite'],
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => 'destaque',
					'value' => '1',
				),
			),
		)
	);
	if ( ! $experiencias ) {
		return '';
	}

	wp_enqueue_style( 'conecta-exp-front' );

	return conecta_exp_render(
		'destaques',
		array(
			'experiencias' => $experiencias,
			'colunas'      => max( 1, min( 4, (int) $atts['colunas'] ? (int) $atts['colunas'] : count( $experiencias ) ) ),
			'titulo'       => trim( $atts['titulo'] ),
			'listagem'     => conecta_exp_url_listagem(),
		)
	);
}
add_shortcode( 'experiencias_destaque', 'conecta_exp_shortcode_destaque' );

/**
 * Shortcode [experiencia_detalhe] — página completa da experiência, no
 * estilo "single product": foto, preço, ficha técnica, incluso/não incluso,
 * o que levar, botão de reserva e experiências da mesma categoria.
 *
 * Usado automaticamente em /experiencias/{slug}/; também pode ir num
 * template Single do Elementor ou numa página com `id`.
 *
 * Atributos:
 * - `id`:           ID da experiência. Default: a experiência atual.
 * - `whatsapp`:     número para a reserva. Default: filtro conecta_exp_whatsapp_numero.
 * - `relacionadas`: quantas experiências da mesma categoria mostrar. Default 3; 0 esconde.
 *
 * @param array|string $atts Atributos.
 * @return string
 */
function conecta_exp_shortcode_detalhe( $atts ) {
	static $renderizando = false;

	$atts = shortcode_atts(
		array(
			'id'           => 0,
			'whatsapp'     => '',
			'relacionadas' => 3,
		),
		$atts,
		'experiencia_detalhe'
	);

	$post = get_post( $atts['id'] ? absint( $atts['id'] ) : get_the_ID() );

	// O conteúdo da experiência passa por the_content; se o próprio shortcode
	// estiver nele, a trava evita recursão infinita.
	if ( $renderizando || ! $post || Conecta_Exp_CPT::POST_TYPE !== $post->post_type ) {
		return '';
	}
	$renderizando = true;

	$relacionadas = array();
	$categoria    = conecta_exp_categoria_principal( $post->ID );
	if ( $categoria && (int) $atts['relacionadas'] > 0 ) {
		$relacionadas = get_posts(
			array(
				'post_type'      => Conecta_Exp_CPT::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $atts['relacionadas'],
				'post__not_in'   => array( $post->ID ),
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => Conecta_Exp_Taxonomy::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => $categoria->term_id,
					),
				),
			)
		);
	}

	wp_enqueue_style( 'conecta-exp-front' );
	wp_enqueue_script( 'conecta-exp-front' );

	$html = conecta_exp_render(
		'detalhe',
		array(
			'post'         => $post,
			'categoria'    => $categoria,
			'whatsapp_url' => conecta_exp_whatsapp_url( $post, $atts['whatsapp'] ),
			'relacionadas' => $relacionadas,
		)
	);

	$renderizando = false;
	return $html;
}
add_shortcode( 'experiencia_detalhe', 'conecta_exp_shortcode_detalhe' );

/**
 * Template automático para /experiencias/{slug}/.
 *
 * O arquivo do plugin ainda chama elementor_theme_do_location( 'single' ):
 * se houver um template Single do Elementor Pro para experiências, ele
 * vence; senão, entra o [experiencia_detalhe]. Um single-experiencia.php no
 * tema também tem prioridade.
 *
 * @param string $template Template escolhido pelo WordPress.
 * @return string
 */
function conecta_exp_template_single( $template ) {
	if ( ! is_singular( Conecta_Exp_CPT::POST_TYPE ) ) {
		return $template;
	}
	if ( locate_template( 'single-' . Conecta_Exp_CPT::POST_TYPE . '.php' ) ) {
		return $template;
	}
	if ( ! apply_filters( 'conecta_exp_usar_template_single', true ) ) {
		return $template;
	}
	return CONECTA_EXP_PATH . 'templates/single-experiencia.php';
}
add_filter( 'template_include', 'conecta_exp_template_single', 5 );

/**
 * Desliga os "Posts relacionados" do Jetpack na página da experiência: ela já
 * tem o bloco próprio "Más experiencias en {categoria}", e o do Jetpack sai
 * com data e "Entrada similar", sem o visual do site. Só aparece com o
 * Jetpack conectado ao WordPress.com (em produção, não no local).
 *
 * @param bool $ativo Se o Jetpack mostraria os relacionados nesta página.
 * @return bool
 */
function conecta_exp_sem_relacionados_jetpack( $ativo ) {
	return is_singular( Conecta_Exp_CPT::POST_TYPE ) ? false : $ativo;
}
add_filter( 'jetpack_relatedposts_filter_enabled_for_request', 'conecta_exp_sem_relacionados_jetpack' );
