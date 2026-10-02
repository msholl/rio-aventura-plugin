/**
 * Filtro por categoria da listagem [experiencias], sem recarregar a página,
 * e carrossel de fotos da página da experiência.
 *
 * Os links do filtro já funcionam sozinhos (?categoria={slug}, filtrado no
 * servidor); aqui eles só são interceptados para esconder/mostrar os cards
 * já renderizados e atualizar a URL, para o link continuar compartilhável.
 */
( function () {
	'use strict';

	function iniciar( listagem ) {
		var itens = listagem.querySelectorAll( '.cxp-filtro__item' );
		var cards = listagem.querySelectorAll( '.cxp-card' );
		var status = listagem.querySelector( '[data-cxp-status]' );

		function aplicar( slug ) {
			var visiveis = 0;

			cards.forEach( function ( card ) {
				var mostra = ! slug || ( ' ' + card.dataset.categorias + ' ' ).indexOf( ' ' + slug + ' ' ) !== -1;
				card.hidden = ! mostra;
				visiveis += mostra ? 1 : 0;
			} );

			itens.forEach( function ( item ) {
				if ( item.dataset.categoria === slug ) {
					item.setAttribute( 'aria-current', 'true' );
				} else {
					item.removeAttribute( 'aria-current' );
				}
			} );

			if ( status ) {
				var modelo = visiveis === 1 ? status.dataset.singular : status.dataset.plural;
				status.textContent = modelo.replace( '%d', visiveis );
			}
		}

		// No celular o filtro rola na horizontal: ao abrir já filtrado
		// (?categoria=…), mostra o botão ativo em vez do começo da faixa.
		var filtro = listagem.querySelector( '.cxp-filtro' );
		var ativo = listagem.querySelector( '.cxp-filtro__item[aria-current="true"]' );
		if ( filtro && ativo && filtro.scrollWidth > filtro.clientWidth ) {
			filtro.scrollLeft = ativo.offsetLeft - filtro.offsetLeft - 16;
		}

		itens.forEach( function ( item ) {
			item.addEventListener( 'click', function ( evento ) {
				if ( evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.button !== 0 ) {
					return;
				}
				evento.preventDefault();
				aplicar( item.dataset.categoria );
				if ( window.history && window.history.replaceState ) {
					window.history.replaceState( null, '', item.href );
				}
			} );
		} );
	}

	// Ao voltar pelo histórico (bfcache) o navegador devolve o foco ao card
	// clicado; tira esse foco para não parecer que o card está selecionado.
	window.addEventListener( 'pageshow', function ( evento ) {
		var ativo = document.activeElement;
		if ( evento.persisted && ativo && ativo.closest && ativo.closest( '.cxp-card' ) ) {
			ativo.blur();
		}
	} );

	/**
	 * Carrossel da página da experiência (fotos e vídeos). O trilho rola com
	 * scroll-snap (o arrastar no celular é nativo); setas e miniaturas só
	 * mandam rolar, e o índice atual sai da posição da rolagem.
	 */
	function iniciarGaleria( galeria ) {
		var trilho = galeria.querySelector( '[data-cxp-trilho]' );
		var slides = trilho.children;
		var total = slides.length;
		var palco = galeria.querySelector( '.cxp-galeria__palco' );
		var miniaturas = galeria.querySelectorAll( '[data-cxp-ir]' );
		var lightbox = galeria.querySelector( '[data-cxp-lightbox]' );
		var imgGrande = lightbox && lightbox.querySelector( '[data-cxp-lightbox-img]' );
		var fotos = [];
		var atual = 0;
		var atualLightbox = 0;
		var quadro;
		var semAnimacao = window.matchMedia( '(prefers-reduced-motion: reduce)' );

		// Só os slides de foto entram na ampliação.
		Array.prototype.forEach.call( slides, function ( slide, i ) {
			if ( slide.querySelector( '[data-cxp-ampliar]' ) ) {
				fotos.push( i );
			}
		} );

		function limitar( i ) {
			return ( i + total ) % total;
		}

		function ir( i, suave ) {
			trilho.scrollTo( {
				left: slides[ limitar( i ) ].offsetLeft,
				behavior: suave === false || semAnimacao.matches ? 'auto' : 'smooth'
			} );
		}

		// Ao sair de um slide, o vídeo dele para: MP4 pausa e o player do
		// YouTube/Vimeo volta a ser só a capa (é o único jeito de parar um iframe).
		function pararVideo( slide ) {
			var video = slide.querySelector( 'video' );
			var player = slide.querySelector( 'iframe' );
			if ( video && ! video.paused ) {
				video.pause();
			}
			if ( player && player.cxpCapa ) {
				player.replaceWith( player.cxpCapa );
			}
		}

		function marcar( i ) {
			if ( slides[ atual ] && atual !== i ) {
				pararVideo( slides[ atual ] );
			}
			atual = i;
			palco.querySelector( '[data-cxp-contador]' ).textContent = ( i + 1 ) + ' / ' + total;
			miniaturas.forEach( function ( miniatura, j ) {
				if ( j === i ) {
					miniatura.setAttribute( 'aria-current', 'true' );
					miniatura.scrollIntoView( { block: 'nearest', inline: 'nearest' } );
				} else {
					miniatura.removeAttribute( 'aria-current' );
				}
			} );
		}

		trilho.addEventListener( 'scroll', function () {
			cancelAnimationFrame( quadro );
			quadro = requestAnimationFrame( function () {
				var i = Math.round( trilho.scrollLeft / trilho.clientWidth );
				if ( i !== atual && i >= 0 && i < total ) {
					marcar( i );
				}
			} );
		}, { passive: true } );

		palco.querySelector( '[data-cxp-anterior]' ).addEventListener( 'click', function () {
			ir( atual - 1 );
		} );
		palco.querySelector( '[data-cxp-proxima]' ).addEventListener( 'click', function () {
			ir( atual + 1 );
		} );
		miniaturas.forEach( function ( miniatura ) {
			miniatura.addEventListener( 'click', function () {
				ir( parseInt( miniatura.dataset.cxpIr, 10 ) );
			} );
		} );

		palco.addEventListener( 'keydown', function ( evento ) {
			// Dentro do player, as setas avançam/voltam o vídeo.
			if ( evento.target.tagName === 'VIDEO' ) {
				return;
			}
			if ( evento.key === 'ArrowLeft' || evento.key === 'ArrowRight' ) {
				evento.preventDefault();
				ir( atual + ( evento.key === 'ArrowLeft' ? -1 : 1 ) );
			}
		} );

		// YouTube/Vimeo: troca a capa pelo player só no clique.
		trilho.querySelectorAll( '[data-cxp-embed]' ).forEach( function ( capa ) {
			capa.addEventListener( 'click', function () {
				var player = document.createElement( 'iframe' );
				player.className = 'cxp-galeria__iframe';
				player.src = capa.dataset.cxpEmbed;
				player.title = capa.textContent.trim();
				player.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
				player.allowFullscreen = true;
				player.cxpCapa = capa;
				capa.replaceWith( player );
				player.focus();
			} );
		} );

		if ( ! lightbox || typeof lightbox.showModal !== 'function' || ! fotos.length ) {
			return;
		}

		// k = posição na lista de fotos (não no carrossel todo).
		function mostrarGrande( k ) {
			var botao = slides[ fotos[ k ] ].querySelector( '[data-cxp-ampliar]' );
			atualLightbox = k;
			imgGrande.src = botao.dataset.full;
			imgGrande.alt = botao.querySelector( 'img' ).alt;
			lightbox.querySelector( '[data-cxp-contador]' ).textContent = ( k + 1 ) + ' / ' + fotos.length;
		}

		function proximaFoto( passo ) {
			mostrarGrande( ( atualLightbox + passo + fotos.length ) % fotos.length );
		}

		lightbox.classList.toggle( 'cxp-lightbox--uma', fotos.length < 2 );

		trilho.querySelectorAll( '[data-cxp-ampliar]' ).forEach( function ( botao ) {
			botao.addEventListener( 'click', function () {
				mostrarGrande( fotos.indexOf( Array.prototype.indexOf.call( slides, botao.closest( '.cxp-galeria__slide' ) ) ) );
				lightbox.showModal();
			} );
		} );

		lightbox.querySelector( '[data-cxp-anterior]' ).addEventListener( 'click', function () {
			proximaFoto( -1 );
		} );
		lightbox.querySelector( '[data-cxp-proxima]' ).addEventListener( 'click', function () {
			proximaFoto( 1 );
		} );
		lightbox.querySelector( '[data-cxp-fechar]' ).addEventListener( 'click', function () {
			lightbox.close();
		} );
		lightbox.addEventListener( 'keydown', function ( evento ) {
			if ( evento.key === 'ArrowLeft' || evento.key === 'ArrowRight' ) {
				evento.preventDefault();
				proximaFoto( evento.key === 'ArrowLeft' ? -1 : 1 );
			}
		} );
		// Clique no fundo escuro (fora da foto e dos botões) fecha.
		lightbox.addEventListener( 'click', function ( evento ) {
			if ( evento.target === lightbox ) {
				lightbox.close();
			}
		} );
		// Ao fechar, o carrossel fica na foto que estava aberta.
		lightbox.addEventListener( 'close', function () {
			ir( fotos[ atualLightbox ], false );
		} );
	}

	function carregar() {
		document.querySelectorAll( '[data-cxp-listagem]' ).forEach( iniciar );
		document.querySelectorAll( '[data-cxp-galeria]' ).forEach( iniciarGaleria );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', carregar );
	} else {
		carregar();
	}
}() );
