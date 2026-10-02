<?php
/**
 * Página da experiência [experiencia_detalhe] — estilo "single product".
 *
 * Variáveis: WP_Post $post, WP_Term|null $categoria, string $whatsapp_url,
 * WP_Post[] $relacionadas.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

$preco       = conecta_exp_preco( $post->ID );
$incluso     = conecta_exp_campo_lista( 'incluso', $post->ID );
$nao_incluso = conecta_exp_campo_lista( 'nao_incluso', $post->ID );
$levar       = conecta_exp_campo( 'levar_contigo', $post->ID );
$listagem    = conecta_exp_url_listagem();

$ficha = array_filter(
	array(
		__( 'Duración', 'conecta-experiencias' )   => conecta_exp_campo( 'duracao', $post->ID ),
		__( 'Dificultad', 'conecta-experiencias' ) => conecta_exp_campo( 'dificuldade', $post->ID ),
		__( 'Distancia', 'conecta-experiencias' )  => conecta_exp_campo( 'distancia', $post->ID ),
		__( 'Horarios', 'conecta-experiencias' )   => conecta_exp_campo( 'horarios', $post->ID ),
	),
	'strlen'
);

$foto    = get_the_post_thumbnail_url( $post, 'full' );
$galeria = conecta_exp_galeria_itens( $post->ID );

// the_content usa o post global (e o Elementor também); ajusta durante o render.
$post_anterior   = $GLOBALS['post'] ?? null;
$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
setup_postdata( $post );
$conteudo = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
$GLOBALS['post'] = $post_anterior; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
if ( $post_anterior ) {
	setup_postdata( $post_anterior );
}
?>
<div class="cxp cxp-detalhe" style="<?php echo esc_attr( conecta_exp_estilo_categoria( $categoria ) ); ?>">

	<header class="cxp-hero<?php echo $foto ? '' : ' cxp-hero--sem-foto'; ?>"<?php echo $foto ? ' style="--cxp-hero-img:url(' . esc_url( $foto ) . ')"' : ''; ?>>
		<div class="cxp-hero__conteudo">
			<nav class="cxp-trilha" aria-label="<?php esc_attr_e( 'Ruta de navegación', 'conecta-experiencias' ); ?>">
				<a href="<?php echo esc_url( $listagem ); ?>"><?php esc_html_e( 'Experiencias', 'conecta-experiencias' ); ?></a>
				<?php if ( $categoria ) : ?>
					<span aria-hidden="true">/</span>
					<a href="<?php echo esc_url( add_query_arg( 'categoria', $categoria->slug, $listagem ) ); ?>"><?php echo esc_html( $categoria->name ); ?></a>
				<?php endif; ?>
			</nav>
			<?php if ( $categoria ) : ?>
				<span class="cxp-badge"><?php echo esc_html( $categoria->name ); ?></span>
			<?php endif; ?>
			<h1 class="cxp-hero__titulo"><?php echo esc_html( get_the_title( $post ) ); ?></h1>
			<?php if ( has_excerpt( $post ) ) : ?>
				<p class="cxp-hero__resumo"><?php echo esc_html( get_the_excerpt( $post ) ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<div class="cxp-detalhe__layout">
		<div class="cxp-detalhe__principal">
			<?php
			// Com uma foto só ela já está no topo; o carrossel entra a partir de dois itens.
			if ( count( $galeria ) > 1 ) {
				conecta_exp_template(
					'galeria',
					array(
						'post'  => $post,
						'itens' => $galeria,
					)
				);
			}
			?>

			<?php if ( trim( wp_strip_all_tags( $conteudo ) ) ) : ?>
				<section class="cxp-secao" aria-labelledby="cxp-sobre">
					<h2 id="cxp-sobre" class="cxp-secao__titulo"><?php esc_html_e( 'Sobre la experiencia', 'conecta-experiencias' ); ?></h2>
					<div class="cxp-texto"><?php echo $conteudo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- saída de the_content. ?></div>
				</section>
			<?php endif; ?>

			<?php if ( $incluso || $nao_incluso ) : ?>
				<section class="cxp-secao cxp-incluso" aria-label="<?php esc_attr_e( 'Qué incluye', 'conecta-experiencias' ); ?>">
					<?php if ( $incluso ) : ?>
						<div class="cxp-incluso__col">
							<h2 class="cxp-secao__titulo"><?php esc_html_e( 'Incluye', 'conecta-experiencias' ); ?></h2>
							<ul class="cxp-lista cxp-lista--sim">
								<?php foreach ( $incluso as $item ) : ?>
									<li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg><?php echo esc_html( $item ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
					<?php if ( $nao_incluso ) : ?>
						<div class="cxp-incluso__col">
							<h2 class="cxp-secao__titulo"><?php esc_html_e( 'No incluye', 'conecta-experiencias' ); ?></h2>
							<ul class="cxp-lista cxp-lista--nao">
								<?php foreach ( $nao_incluso as $item ) : ?>
									<li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M7 7l10 10M17 7L7 17"/></svg><?php echo esc_html( $item ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( $levar ) : ?>
				<section class="cxp-secao cxp-levar" aria-labelledby="cxp-levar">
					<h2 id="cxp-levar" class="cxp-secao__titulo"><?php esc_html_e( 'Qué llevar', 'conecta-experiencias' ); ?></h2>
					<div class="cxp-texto"><?php echo wp_kses_post( wpautop( $levar ) ); ?></div>
				</section>
			<?php endif; ?>
		</div>

		<aside class="cxp-reserva" aria-label="<?php esc_attr_e( 'Reserva', 'conecta-experiencias' ); ?>">
			<div class="cxp-reserva__caixa">
				<?php if ( $preco ) : ?>
					<p class="cxp-preco cxp-preco--grande">
						<span class="cxp-preco__valor"><?php echo esc_html( $preco ); ?></span>
					</p>
				<?php endif; ?>

				<?php if ( $ficha ) : ?>
					<dl class="cxp-ficha">
						<?php foreach ( $ficha as $rotulo => $valor ) : ?>
							<div class="cxp-ficha__item">
								<dt><?php echo esc_html( $rotulo ); ?></dt>
								<dd><?php echo esc_html( $valor ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>

				<a class="cxp-botao cxp-botao--whatsapp" href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener">
					<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3z"/><path d="M8.8 8.6c.2-.5.5-.5.8-.5h.5c.2 0 .4 0 .6.5l.7 1.7c.1.2 0 .4-.1.6l-.5.6c-.1.1-.2.3 0 .5.6 1 1.4 1.8 2.5 2.4.2.1.4.1.5-.1l.7-.8c.2-.2.4-.2.6-.1l1.6.8c.2.1.4.2.4.4 0 .8-.6 1.6-1.4 1.8-.7.2-1.6.1-3.2-.7-2-1-3.4-3-3.6-3.3-.3-.4-.9-1.4-.9-2.4 0-.6.3-1 .5-1.3z" class="cxp-preenchido"/></svg>
					<?php esc_html_e( 'Reservar por WhatsApp', 'conecta-experiencias' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(abre en una nueva pestaña)', 'conecta-experiencias' ); ?></span>
				</a>
				<p class="cxp-reserva__nota"><?php esc_html_e( 'Respondemos por WhatsApp para confirmar fecha, horario y disponibilidad.', 'conecta-experiencias' ); ?></p>
			</div>
		</aside>
	</div>

	<?php if ( $relacionadas ) : ?>
		<section class="cxp-relacionadas" aria-labelledby="cxp-relacionadas">
			<h2 id="cxp-relacionadas" class="cxp-relacionadas__titulo">
				<?php
				// O nome da categoria fica fora da string traduzível: assim o
				// TranslatePress traduz o nome pelo dicionário, como no resto do site.
				if ( $categoria ) {
					echo esc_html__( 'Más experiencias en', 'conecta-experiencias' ) . ' <span>' . esc_html( $categoria->name ) . '</span>';
				} else {
					esc_html_e( 'Más experiencias', 'conecta-experiencias' );
				}
				?>
			</h2>
			<div class="cxp-grade" style="--cxp-colunas:3">
				<?php
				foreach ( $relacionadas as $relacionada ) {
					conecta_exp_template(
						'card',
						array(
							'post'       => $relacionada,
							'titulo_tag' => 'h3',
						)
					);
				}
				?>
			</div>
			<p class="cxp-relacionadas__todas">
				<a class="cxp-botao cxp-botao--secundario" href="<?php echo esc_url( $categoria ? add_query_arg( 'categoria', $categoria->slug, $listagem ) : $listagem ); ?>"><?php esc_html_e( 'Ver todas', 'conecta-experiencias' ); ?></a>
			</p>
		</section>
	<?php endif; ?>

	<div class="cxp-barra-reserva">
		<?php if ( $preco ) : ?>
			<p class="cxp-preco">
				<span class="cxp-preco__valor"><?php echo esc_html( $preco ); ?></span>
			</p>
		<?php endif; ?>
		<a class="cxp-botao cxp-botao--whatsapp" href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Reservar', 'conecta-experiencias' ); ?><span class="screen-reader-text"><?php esc_html_e( '(abre en una nueva pestaña)', 'conecta-experiencias' ); ?></span></a>
	</div>
</div>
