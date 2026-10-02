<?php
/**
 * Botões das categorias [experiencias_categorias]: cada um abre a página de
 * experiências já filtrada na categoria.
 *
 * Variáveis: WP_Term[] $categorias, string $listagem (URL da listagem),
 * string $alinhamento ("centro" ou "esquerda"), string $titulo (pode ser vazio).
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="cxp cxp-categorias<?php echo 'centro' === $alinhamento ? ' cxp-categorias--centro' : ''; ?>">
	<?php if ( '' !== $titulo ) : ?>
		<h3 class="cxp-categorias__titulo"><?php echo esc_html( $titulo ); ?></h3>
	<?php endif; ?>
	<nav class="cxp-categorias__lista" aria-label="<?php echo esc_attr( '' !== $titulo ? $titulo : __( 'Categorías de experiencias', 'conecta-experiencias' ) ); ?>">
		<?php foreach ( $categorias as $term ) : ?>
			<a class="cxp-categorias__item" href="<?php echo esc_url( add_query_arg( 'categoria', $term->slug, $listagem ) . '#' . CONECTA_EXP_ANCORA_LISTAGEM ); ?>" style="<?php echo esc_attr( conecta_exp_estilo_categoria( $term ) ); ?>">
				<span class="cxp-categorias__texto">
					<span class="cxp-categorias__nome"><?php echo esc_html( $term->name ); ?></span>
					<span class="cxp-categorias__qtd">
						<?php
						/* translators: %d: quantidade de experiências. */
						echo esc_html( sprintf( _n( '%d experiencia', '%d experiencias', $term->count, 'conecta-experiencias' ), $term->count ) );
						?>
					</span>
				</span>
				<svg class="cxp-categorias__seta" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
			</a>
		<?php endforeach; ?>
	</nav>
</div>
