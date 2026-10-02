<?php
/**
 * Carrossel de fotos e vídeos da página da experiência, com miniaturas e
 * ampliação das fotos.
 *
 * Variáveis: WP_Post $post, array[] $itens (de conecta_exp_galeria_itens()).
 *
 * Vídeo MP4 toca no próprio slide (controles nativos). YouTube/Vimeo mostram
 * só a capa; o player (iframe) entra quando a pessoa clica, para não pesar
 * no carregamento da página.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

$total  = count( $itens );
$titulo = wp_strip_all_tags( get_the_title( $post ) );
$play   = '<svg class="cxp-galeria__play" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11"/><path d="M10 8.5v7l6-3.5z"/></svg>';
?>
<section class="cxp-galeria" data-cxp-galeria aria-roledescription="<?php esc_attr_e( 'carrusel', 'conecta-experiencias' ); ?>" aria-label="<?php esc_attr_e( 'Fotos de la experiencia', 'conecta-experiencias' ); ?>">
	<div class="cxp-galeria__palco">
		<ul class="cxp-galeria__trilho" data-cxp-trilho>
			<?php foreach ( $itens as $i => $item ) : ?>
				<li class="cxp-galeria__slide cxp-galeria__slide--<?php echo esc_attr( $item['tipo'] ); ?>" aria-roledescription="<?php esc_attr_e( 'foto', 'conecta-experiencias' ); ?>" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' / ' . $total ); ?>">
					<?php if ( 'imagem' === $item['tipo'] ) : ?>
						<?php $alt = trim( (string) get_post_meta( $item['id'], '_wp_attachment_image_alt', true ) ); ?>
						<button type="button" class="cxp-galeria__ampliar" data-cxp-ampliar data-full="<?php echo esc_url( wp_get_attachment_image_url( $item['id'], 'full' ) ); ?>">
							<?php
							echo wp_get_attachment_image(
								$item['id'],
								'large',
								false,
								array(
									'alt'     => $alt ? $alt : $titulo,
									'sizes'   => '(min-width: 1024px) 760px, 100vw',
									'loading' => 0 === $i ? 'eager' : 'lazy',
								)
							);
							?>
							<span class="screen-reader-text"><?php esc_html_e( 'Ampliar foto', 'conecta-experiencias' ); ?></span>
						</button>
					<?php elseif ( 'video' === $item['tipo'] ) : ?>
						<video class="cxp-galeria__video" controls playsinline preload="metadata"<?php echo $item['capa'] ? ' poster="' . esc_url( $item['capa'] ) . '"' : ''; ?>>
							<source src="<?php echo esc_url( $item['src'] ); ?>" type="<?php echo esc_attr( $item['mime'] ); ?>">
						</video>
					<?php else : ?>
						<button type="button" class="cxp-galeria__externo" data-cxp-embed="<?php echo esc_url( $item['embed'] ); ?>">
							<?php if ( $item['capa'] && $item['capa_reserva'] ) : ?>
								<img src="<?php echo esc_url( $item['capa'] ); ?>" alt="" loading="lazy" data-reserva="<?php echo esc_url( $item['capa_reserva'] ); ?>" onerror="this.onerror=null;this.src=this.dataset.reserva;this.classList.add('cxp-galeria__capa-4x3')">
							<?php elseif ( $item['capa'] ) : ?>
								<img src="<?php echo esc_url( $item['capa'] ); ?>" alt="" loading="lazy">
							<?php endif; ?>
							<?php echo $play; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo. ?>
							<span class="screen-reader-text"><?php esc_html_e( 'Reproducir video', 'conecta-experiencias' ); ?></span>
						</button>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<button type="button" class="cxp-galeria__seta cxp-galeria__seta--anterior" data-cxp-anterior aria-label="<?php esc_attr_e( 'Foto anterior', 'conecta-experiencias' ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg>
		</button>
		<button type="button" class="cxp-galeria__seta cxp-galeria__seta--proxima" data-cxp-proxima aria-label="<?php esc_attr_e( 'Foto siguiente', 'conecta-experiencias' ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
		</button>
		<p class="cxp-galeria__contador" data-cxp-contador aria-live="polite">1 / <?php echo esc_html( $total ); ?></p>
	</div>

	<div class="cxp-galeria__miniaturas">
		<?php foreach ( $itens as $i => $item ) : ?>
			<button type="button" class="cxp-galeria__miniatura<?php echo 'imagem' !== $item['tipo'] ? ' cxp-galeria__miniatura--video' : ''; ?>" data-cxp-ir="<?php echo esc_attr( $i ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>>
				<?php if ( 'imagem' === $item['tipo'] ) : ?>
					<?php echo wp_get_attachment_image( $item['id'], 'thumbnail', false, array( 'alt' => '' ) ); ?>
				<?php elseif ( $item['thumb'] ) : ?>
					<img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="" loading="lazy">
				<?php else : ?>
					<span class="cxp-galeria__miniatura-vazia"></span>
				<?php endif; ?>
				<?php echo 'imagem' !== $item['tipo'] ? $play : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG fixo. ?>
				<span class="screen-reader-text">
					<?php
					echo esc_html(
						'imagem' === $item['tipo']
							/* translators: %d: número do item na galeria. */
							? sprintf( __( 'Ver foto %d', 'conecta-experiencias' ), $i + 1 )
							/* translators: %d: número do item na galeria. */
							: sprintf( __( 'Ver video %d', 'conecta-experiencias' ), $i + 1 )
					);
					?>
				</span>
			</button>
		<?php endforeach; ?>
	</div>

	<dialog class="cxp-lightbox" data-cxp-lightbox aria-label="<?php esc_attr_e( 'Fotos de la experiencia', 'conecta-experiencias' ); ?>">
		<img class="cxp-lightbox__img" src="" alt="" data-cxp-lightbox-img>
		<button type="button" class="cxp-lightbox__fechar" data-cxp-fechar aria-label="<?php esc_attr_e( 'Cerrar', 'conecta-experiencias' ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
		<button type="button" class="cxp-galeria__seta cxp-galeria__seta--anterior" data-cxp-anterior aria-label="<?php esc_attr_e( 'Foto anterior', 'conecta-experiencias' ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg>
		</button>
		<button type="button" class="cxp-galeria__seta cxp-galeria__seta--proxima" data-cxp-proxima aria-label="<?php esc_attr_e( 'Foto siguiente', 'conecta-experiencias' ); ?>">
			<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
		</button>
		<p class="cxp-galeria__contador" data-cxp-contador></p>
	</dialog>
</section>
