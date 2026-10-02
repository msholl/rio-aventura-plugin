<?php
/**
 * Listagem [experiencias]: filtro por categoria + grade de cards.
 *
 * Todos os cards são renderizados; os de outras categorias saem com
 * `hidden`. Assim o JS troca o filtro sem recarregar, e sem JS os links
 * ?categoria={slug} fazem o mesmo pelo servidor.
 *
 * Variáveis: WP_Post[] $experiencias, WP_Term[] $categorias (por slug),
 * int $destaques (quantas em destaque), string $ativa, bool $mostrar_filtro, int $colunas, string $titulo_tag.
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

$base_url = remove_query_arg( 'categoria' );
$visiveis = 0;
?>
<div id="<?php echo esc_attr( CONECTA_EXP_ANCORA_LISTAGEM ); ?>" class="cxp cxp-listagem" style="--cxp-colunas:<?php echo (int) $colunas; ?>" data-cxp-listagem>
	<?php if ( $mostrar_filtro ) : ?>
		<nav class="cxp-filtro" aria-label="<?php esc_attr_e( 'Filtrar por categoría', 'conecta-experiencias' ); ?>">
			<?php if ( $destaques ) : ?>
				<a class="cxp-filtro__item cxp-filtro__item--destaque" href="<?php echo esc_url( add_query_arg( 'categoria', CONECTA_EXP_FILTRO_DESTAQUE, $base_url ) ); ?>" data-categoria="<?php echo esc_attr( CONECTA_EXP_FILTRO_DESTAQUE ); ?>"<?php echo CONECTA_EXP_FILTRO_DESTAQUE === $ativa ? ' aria-current="true"' : ''; ?>>
					<svg class="cxp-filtro__estrela" aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/></svg>
					<?php esc_html_e( 'Destacados', 'conecta-experiencias' ); ?>
					<span class="cxp-filtro__qtd"><?php echo (int) $destaques; ?></span>
				</a>
			<?php endif; ?>
			<a class="cxp-filtro__item" href="<?php echo esc_url( $base_url ); ?>" data-categoria=""<?php echo '' === $ativa ? ' aria-current="true"' : ''; ?>>
				<?php esc_html_e( 'Todas', 'conecta-experiencias' ); ?>
				<span class="cxp-filtro__qtd"><?php echo (int) count( $experiencias ); ?></span>
			</a>
			<?php foreach ( $categorias as $term ) : ?>
				<?php
				$qtd = 0;
				foreach ( $experiencias as $exp ) {
					$qtd += has_term( $term->term_id, Conecta_Exp_Taxonomy::TAXONOMY, $exp ) ? 1 : 0;
				}
				?>
				<a class="cxp-filtro__item" href="<?php echo esc_url( add_query_arg( 'categoria', $term->slug, $base_url ) ); ?>" data-categoria="<?php echo esc_attr( $term->slug ); ?>" style="<?php echo esc_attr( conecta_exp_estilo_categoria( $term ) ); ?>"<?php echo $ativa === $term->slug ? ' aria-current="true"' : ''; ?>>
					<span class="cxp-filtro__cor" aria-hidden="true"></span>
					<?php echo esc_html( $term->name ); ?>
					<span class="cxp-filtro__qtd"><?php echo (int) $qtd; ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $experiencias ) : ?>
		<div class="cxp-grade">
			<?php
			foreach ( $experiencias as $post ) {
				if ( CONECTA_EXP_FILTRO_DESTAQUE === $ativa ) {
					$oculto = ! conecta_exp_destaque( $post->ID );
				} else {
					$oculto = $ativa && ! has_term( $ativa, Conecta_Exp_Taxonomy::TAXONOMY, $post );
				}
				$visiveis += $oculto ? 0 : 1;
				conecta_exp_template(
					'card',
					array(
						'post'       => $post,
						'titulo_tag' => $titulo_tag,
						'oculto'     => $oculto,
					)
				);
			}
			?>
		</div>
		<p class="cxp-listagem__status" role="status" data-cxp-status
			<?php /* translators: %d: quantidade de experiências. */ ?>
			data-singular="<?php echo esc_attr( _n( '%d experiencia', '%d experiencias', 1, 'conecta-experiencias' ) ); ?>"
			data-plural="<?php echo esc_attr( _n( '%d experiencia', '%d experiencias', 2, 'conecta-experiencias' ) ); ?>">
			<?php
			/* translators: %d: quantidade de experiências. */
			echo esc_html( sprintf( _n( '%d experiencia', '%d experiencias', $visiveis, 'conecta-experiencias' ), $visiveis ) );
			?>
		</p>
	<?php else : ?>
		<p class="cxp-vazio"><?php esc_html_e( 'No hay experiencias disponibles en este momento.', 'conecta-experiencias' ); ?></p>
	<?php endif; ?>
</div>
