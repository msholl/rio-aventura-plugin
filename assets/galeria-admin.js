/**
 * Caixa "Galeria de fotos e vídeos" na edição da experiência: escolhe fotos e
 * vídeos na biblioteca de mídia, aceita links do YouTube/Vimeo, ordena
 * arrastando e grava a lista (JSON) no campo oculto.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $lista = $( '[data-cxp-galeria]' );
		var $campo = $( 'input[name="conecta_exp_galeria"]' );
		var frame;

		if ( ! $lista.length ) {
			return;
		}

		function itens() {
			return $lista.children( 'li' ).map( function () {
				var item = String( $( this ).attr( 'data-item' ) );
				return /^\d+$/.test( item ) ? parseInt( item, 10 ) : item;
			} ).get();
		}

		function sincronizar() {
			$campo.val( JSON.stringify( itens() ) );
		}

		function botaoRemover() {
			return $( '<button type="button" class="cxp-admin-galeria__remover">&times;</button>' ).attr( 'aria-label', conectaExpGaleria.remover );
		}

		$lista.sortable( { update: sincronizar } );

		$lista.on( 'click', '.cxp-admin-galeria__remover', function () {
			$( this ).closest( 'li' ).remove();
			sincronizar();
		} );

		$( '[data-cxp-galeria-adicionar]' ).on( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( {
					title: conectaExpGaleria.titulo,
					button: { text: conectaExpGaleria.botao },
					library: { type: [ 'image', 'video' ] },
					multiple: 'add'
				} );

				frame.on( 'select', function () {
					var existentes = itens();

					frame.state().get( 'selection' ).each( function ( anexo ) {
						var dados = anexo.toJSON();
						var $li;

						if ( existentes.indexOf( dados.id ) !== -1 ) {
							return;
						}

						$li = $( '<li>' ).attr( 'data-item', dados.id );
						if ( dados.type === 'video' ) {
							if ( dados.thumb && dados.thumb.src && dados.thumb.src !== dados.icon ) {
								$li.append( $( '<img>' ).attr( { src: dados.thumb.src, alt: '' } ) );
							} else {
								$li.append( '<span class="cxp-admin-galeria__video dashicons dashicons-video-alt3"></span>' );
							}
							$li.append( '<span class="cxp-admin-galeria__rotulo">MP4</span>' );
						} else {
							$li.append( $( '<img>' ).attr( {
								src: dados.sizes && dados.sizes.thumbnail ? dados.sizes.thumbnail.url : dados.url,
								alt: ''
							} ) );
						}
						$li.append( botaoRemover() ).appendTo( $lista );
					} );

					sincronizar();
				} );
			}

			frame.open();
		} );

		// Link do YouTube/Vimeo: o servidor valida e devolve a miniatura pronta.
		$( '[data-cxp-galeria-link]' ).on( 'click', function () {
			var url = window.prompt( conectaExpGaleria.pedirUrl );
			var $botao = $( this );

			if ( ! url ) {
				return;
			}
			url = url.trim();
			if ( itens().indexOf( url ) !== -1 ) {
				return;
			}

			$botao.prop( 'disabled', true );
			$.post( conectaExpGaleria.ajax, {
				action: 'conecta_exp_galeria_link',
				_ajax_nonce: conectaExpGaleria.nonce,
				url: url
			} ).done( function ( resposta ) {
				if ( resposta && resposta.success ) {
					$lista.append( resposta.data.html );
					sincronizar();
				} else {
					window.alert( resposta && resposta.data && resposta.data.mensagem ? resposta.data.mensagem : 'Erro' );
				}
			} ).always( function () {
				$botao.prop( 'disabled', false );
			} );
		} );
	} );
}( jQuery ) );
