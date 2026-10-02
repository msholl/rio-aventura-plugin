<?php
/**
 * Card de uma experiência (listagem e "relacionadas").
 *
 * Mostra só o resumo — ficha completa, incluso/não incluso e o que levar
 * ficam na página da experiência.
 *
 * Variáveis: WP_Post $post, string $titulo_tag, bool $oculto.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

$oculto    = ! empty( $oculto );
$categoria = conecta_exp_categoria_principal( $post->ID );
$termos    = get_the_terms( $post->ID, Conecta_Exp_Taxonomy::TAXONOMY );
$slugs     = is_array( $termos ) ? wp_list_pluck( $termos, 'slug' ) : array();
if ( conecta_exp_destaque( $post->ID ) ) {
	$slugs[] = CONECTA_EXP_FILTRO_DESTAQUE;
}
$preco     = conecta_exp_preco( $post->ID );
$duracao   = conecta_exp_campo( 'duracao', $post->ID );
$dificuldade = conecta_exp_campo( 'dificuldade', $post->ID );
$link      = get_permalink( $post );
?>
<article class="cxp-card" style="<?php echo esc_attr( conecta_exp_estilo_categoria( $categoria ) ); ?>" data-categorias="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>"<?php echo $oculto ? ' hidden' : ''; ?>>
	<div class="cxp-card__media">
		<?php if ( has_post_thumbnail( $post ) ) : ?>
			<?php echo get_the_post_thumbnail( $post, 'medium_large', array( 'class' => 'cxp-card__img', 'loading' => 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<span class="cxp-card__img cxp-card__img--vazia" aria-hidden="true"></span>
		<?php endif; ?>
		<?php if ( $categoria ) : ?>
			<span class="cxp-badge"><?php echo esc_html( $categoria->name ); ?></span>
		<?php endif; ?>
	</div>

	<div class="cxp-card__corpo">
		<<?php echo tag_escape( $titulo_tag ); ?> class="cxp-card__titulo">
			<a class="cxp-card__link" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a>
		</<?php echo tag_escape( $titulo_tag ); ?>>

		<?php if ( $duracao || $dificuldade ) : ?>
			<ul class="cxp-chips">
				<?php if ( $duracao ) : ?>
					<li class="cxp-chip"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?php echo esc_html( $duracao ); ?></li>
				<?php endif; ?>
				<?php if ( $dificuldade ) : ?>
					<li class="cxp-chip"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 20l6-10 4 6 3-4 5 8z"/></svg><?php echo esc_html( $dificuldade ); ?></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<p class="cxp-card__resumo"><?php echo esc_html( conecta_exp_resumo( $post ) ); ?></p>

		<div class="cxp-card__rodape">
			<?php if ( $preco ) : ?>
				<p class="cxp-preco">
					<span class="cxp-preco__valor"><?php echo esc_html( $preco ); ?></span>
				</p>
			<?php endif; ?>
			<span class="cxp-botao cxp-botao--card" aria-hidden="true"><?php esc_html_e( 'Ver detalles', 'conecta-experiencias' ); ?></span>
		</div>
	</div>
</article>
