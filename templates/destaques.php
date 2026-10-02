<?php
/**
 * Cards das experiências em destaque [experiencias_destaque].
 *
 * Variáveis: WP_Post[] $experiencias, int $colunas, string $titulo (pode ser
 * vazio), string $listagem (URL da listagem).
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="cxp cxp-destaques"<?php echo '' !== $titulo ? ' aria-labelledby="cxp-destaques-titulo"' : ''; ?>>
	<?php if ( '' !== $titulo ) : ?>
		<h3 id="cxp-destaques-titulo" class="cxp-destaques__titulo"><?php echo esc_html( $titulo ); ?></h3>
	<?php endif; ?>
	<div class="cxp-grade cxp-destaques__trilho" style="--cxp-colunas:<?php echo (int) $colunas; ?>">
		<?php
		foreach ( $experiencias as $experiencia ) {
			conecta_exp_template(
				'card',
				array(
					'post'       => $experiencia,
					'titulo_tag' => '' !== $titulo ? 'h4' : 'h3',
				)
			);
		}
		?>
	</div>
	<p class="cxp-destaques__todas">
		<a class="cxp-botao cxp-botao--secundario" href="<?php echo esc_url( $listagem ); ?>"><?php esc_html_e( 'Ver todas las experiencias', 'conecta-experiencias' ); ?></a>
	</p>
</section>
