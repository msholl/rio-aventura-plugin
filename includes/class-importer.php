<?php
/**
 * Importador de Experiências via CSV.
 *
 * Fluxo em duas etapas: o arquivo é enviado, pré-visualizado (o que será criado,
 * atualizado ou rejeitado) e só então importado. O arquivo fica em um diretório
 * privado dentro de uploads e é apagado ao final.
 *
 * Só CSV: ler .xlsx exigiria PhpSpreadsheet como dependência. O caminho é
 * "Salvar como → CSV" no Excel/Google Sheets. O parser tolera o CSV brasileiro
 * (separador ";", acentuação Windows-1252 e BOM do Excel).
 *
 * @package ConectaExperiencias
 */

defined( 'ABSPATH' ) || exit;

/**
 * Página de importação e rotina de ingestão do CSV.
 */
class Conecta_Exp_Importer {

	/**
	 * Slug da página de admin.
	 */
	const MENU_SLUG = 'conecta-exp-importar';

	/**
	 * Metas do usuário que sustentam o fluxo de duas etapas.
	 *
	 * Meta de usuário em vez de transient de propósito: com object cache
	 * persistente (Redis/Memcached) um transient pode ser despejado entre o
	 * upload e a confirmação, e o import morria com "sessão expirou".
	 */
	const META_PENDENTE = '_conecta_exp_import_pendente';

	/**
	 * Meta com o relatório da última importação, apagada depois de exibida.
	 */
	const META_RESULTADO = '_conecta_exp_import_resultado';

	/**
	 * Meta com a mensagem de erro a exibir após um redirecionamento.
	 */
	const META_ERRO = '_conecta_exp_import_erro';

	/**
	 * Prazo do arquivo enviado que ainda aguarda confirmação.
	 */
	const PRAZO_PENDENTE = HOUR_IN_SECONDS;

	/**
	 * Subpasta (dentro de wp-content/uploads) onde o CSV fica até ser processado.
	 */
	const UPLOAD_SUBDIR = 'conecta-exp-import';

	/**
	 * Registra a página e os handlers de formulário.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_post_conecta_exp_import_upload', array( __CLASS__, 'handle_upload' ) );
		add_action( 'admin_post_conecta_exp_import_run', array( __CLASS__, 'handle_run' ) );
		add_action( 'admin_post_conecta_exp_import_modelo', array( __CLASS__, 'download_template' ) );
	}

	/**
	 * Capacidade exigida para importar.
	 *
	 * Importar cria posts e pode baixar imagens de fora, por isso o padrão é
	 * restritivo. Filtrável para liberar a editores, se o cliente precisar.
	 *
	 * @return string
	 */
	private static function capability() {
		return (string) apply_filters( 'conecta_exp_import_capability', 'manage_options' );
	}

	/**
	 * Adiciona a página como submenu de Experiências.
	 */
	public static function register_page() {
		add_submenu_page(
			'edit.php?post_type=' . Conecta_Exp_CPT::POST_TYPE,
			__( 'Importar Experiências (CSV)', 'conecta-experiencias' ),
			__( 'Importar CSV', 'conecta-experiencias' ),
			self::capability(),
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/* ---------------------------------------------------------------------
	 * Definição das colunas
	 * ------------------------------------------------------------------ */

	/**
	 * Colunas reconhecidas: chave canônica => rótulos aceitos no cabeçalho.
	 *
	 * O cabeçalho é normalizado (sem acento, minúsculo, `_` no lugar de espaço)
	 * antes da comparação, então "Não Incluso" casa com `nao_incluso`.
	 *
	 * @return array<string, array<int, string>>
	 */
	private static function columns() {
		return array(
			'titulo'        => array( 'titulo', 'title', 'nome', 'experiencia' ),
			'slug'          => array( 'slug', 'permalink', 'url_amigavel' ),
			'status'        => array( 'status', 'situacao' ),
			'resumo'        => array( 'resumo', 'excerpt', 'descricao_curta', 'subtitulo' ),
			'conteudo'      => array( 'conteudo', 'content', 'descricao', 'texto', 'descricao_longa' ),
			'categorias'    => array( 'categorias', 'categoria', 'category', 'categories' ),
			'preco'         => array( 'preco', 'valor', 'price' ),
			'duracao'       => array( 'duracao', 'duration', 'tempo' ),
			'dificuldade'   => array( 'dificuldade', 'nivel', 'difficulty' ),
			'distancia'     => array( 'distancia', 'distance' ),
			'horarios'      => array( 'horarios', 'horario', 'saidas' ),
			'incluso'       => array( 'incluso', 'inclui', 'o_que_esta_incluso' ),
			'nao_incluso'   => array( 'nao_incluso', 'nao_inclui', 'o_que_nao_esta_incluso' ),
			'levar_contigo' => array( 'levar_contigo', 'levar', 'o_que_levar' ),
			'imagem'        => array( 'imagem', 'foto', 'imagem_destacada', 'featured_image', 'thumbnail' ),
		);
	}

	/**
	 * Campos ACF: chave canônica da coluna => key do campo.
	 *
	 * @return array<string, string>
	 */
	private static function acf_fields() {
		return array(
			'preco'         => 'field_exp_preco',
			'duracao'       => 'field_exp_duracao',
			'dificuldade'   => 'field_exp_dificuldade',
			'distancia'     => 'field_exp_distancia',
			'horarios'      => 'field_exp_horarios',
			'incluso'       => 'field_exp_incluso',
			'nao_incluso'   => 'field_exp_nao_incluso',
			'levar_contigo' => 'field_exp_levar_contigo',
		);
	}

	/* ---------------------------------------------------------------------
	 * Leitura do CSV
	 * ------------------------------------------------------------------ */

	/**
	 * Normaliza um rótulo de cabeçalho para comparação.
	 *
	 * @param string $label Rótulo cru.
	 * @return string
	 */
	private static function normalize_header( $label ) {
		$label = remove_accents( (string) $label );
		$label = strtolower( trim( $label ) );
		$label = preg_replace( '/[^a-z0-9]+/', '_', $label );

		return trim( (string) $label, '_' );
	}

	/**
	 * Converte para UTF-8 quando o arquivo veio em Windows-1252 (Excel pt-BR).
	 *
	 * @param string $value Valor cru.
	 * @return string
	 */
	private static function to_utf8( $value ) {
		$value = (string) $value;

		if ( '' === $value || mb_check_encoding( $value, 'UTF-8' ) ) {
			return $value;
		}

		return (string) mb_convert_encoding( $value, 'UTF-8', 'Windows-1252' );
	}

	/**
	 * Descobre o separador olhando a linha de cabeçalho.
	 *
	 * @param string $line Primeira linha do arquivo.
	 * @return string
	 */
	private static function detect_delimiter( $line ) {
		$candidates = array( ';' => 0, ',' => 0, "\t" => 0 );

		foreach ( array_keys( $candidates ) as $char ) {
			$candidates[ $char ] = substr_count( $line, $char );
		}

		arsort( $candidates );
		$best = array_key_first( $candidates );

		return $candidates[ $best ] > 0 ? $best : ';';
	}

	/**
	 * Lê o CSV e devolve as linhas já mapeadas para as chaves canônicas.
	 *
	 * @param string $path Caminho absoluto do arquivo.
	 * @return array{rows: array<int, array<string, string>>, unknown: array<int, string>}|WP_Error
	 */
	private static function parse_file( $path ) {
		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! $handle ) {
			return new WP_Error( 'conecta_exp_read', __( 'Não foi possível abrir o arquivo enviado.', 'conecta-experiencias' ) );
		}

		// BOM do Excel: descarta se estiver presente.
		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		$position   = ftell( $handle );
		$first_line = (string) fgets( $handle );
		$delimiter  = self::detect_delimiter( $first_line );
		fseek( $handle, $position );

		$header = fgetcsv( $handle, 0, $delimiter, '"', '' );

		if ( ! is_array( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return new WP_Error( 'conecta_exp_header', __( 'O arquivo está vazio ou não tem linha de cabeçalho.', 'conecta-experiencias' ) );
		}

		$columns = self::columns();
		$map     = array();
		$unknown = array();

		foreach ( $header as $index => $label ) {
			$normalized = self::normalize_header( self::to_utf8( $label ) );

			if ( '' === $normalized ) {
				continue;
			}

			$matched = '';
			foreach ( $columns as $key => $aliases ) {
				if ( in_array( $normalized, $aliases, true ) ) {
					$matched = $key;
					break;
				}
			}

			if ( '' === $matched ) {
				$unknown[] = self::to_utf8( $label );
				continue;
			}

			$map[ $index ] = $matched;
		}

		if ( ! in_array( 'titulo', $map, true ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			return new WP_Error(
				'conecta_exp_titulo',
				__( 'O cabeçalho não tem a coluna "titulo". Ela é obrigatória — baixe o modelo para conferir os nomes esperados.', 'conecta-experiencias' )
			);
		}

		$rows = array();

		while ( false !== ( $data = fgetcsv( $handle, 0, $delimiter, '"', '' ) ) ) {
			if ( ! is_array( $data ) ) {
				continue;
			}

			$row      = array();
			$has_data = false;

			foreach ( $map as $index => $key ) {
				$value = isset( $data[ $index ] ) ? trim( self::to_utf8( $data[ $index ] ) ) : '';

				if ( '' !== $value ) {
					$has_data = true;
				}

				$row[ $key ] = $value;
			}

			if ( ! $has_data ) {
				continue;
			}

			$rows[] = $row;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return array(
			'rows'    => $rows,
			'unknown' => $unknown,
		);
	}

	/**
	 * Normaliza listas multilinha: aceita "|" como separador além da quebra real.
	 *
	 * @param string $value Valor cru da célula.
	 * @return string
	 */
	private static function normalize_lines( $value ) {
		$value = str_replace( array( "\r\n", "\r" ), "\n", $value );
		$value = str_replace( '|', "\n", $value );
		$lines = array_filter( array_map( 'trim', explode( "\n", $value ) ), 'strlen' );

		return implode( "\n", $lines );
	}

	/* ---------------------------------------------------------------------
	 * Importação
	 * ------------------------------------------------------------------ */

	/**
	 * Procura uma experiência já existente pelo slug ou pelo título exato.
	 *
	 * @param array<string, string> $row Linha já mapeada.
	 * @return int ID do post existente, ou 0.
	 */
	private static function find_existing( array $row ) {
		$slug = ! empty( $row['slug'] ) ? sanitize_title( $row['slug'] ) : sanitize_title( $row['titulo'] );

		if ( $slug ) {
			$post = get_page_by_path( $slug, OBJECT, Conecta_Exp_CPT::POST_TYPE );

			if ( $post instanceof WP_Post ) {
				return (int) $post->ID;
			}
		}

		$query = new WP_Query(
			array(
				'post_type'              => Conecta_Exp_CPT::POST_TYPE,
				'title'                  => $row['titulo'],
				'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return $query->posts ? (int) $query->posts[0] : 0;
	}

	/**
	 * Grava um campo ACF, com fallback para post meta se o ACF estiver inativo.
	 *
	 * @param int    $post_id ID do post.
	 * @param string $key     Key do campo ACF.
	 * @param string $name    Nome do campo (meta_key).
	 * @param string $value   Valor.
	 */
	private static function set_field( $post_id, $key, $name, $value ) {
		if ( function_exists( 'update_field' ) ) {
			update_field( $key, $value, $post_id );
			return;
		}

		// Sem ACF: grava o meta e a referência da key, que é o formato que o
		// ACF espera encontrar quando for ativado depois.
		update_post_meta( $post_id, $name, $value );
		update_post_meta( $post_id, '_' . $name, $key );
	}

	/**
	 * Resolve as categorias da linha, criando os termos que ainda não existem.
	 *
	 * @param string $raw Lista separada por vírgula, ponto e vírgula ou "|".
	 * @return array<int, int> IDs dos termos.
	 */
	private static function resolve_terms( $raw ) {
		$names = preg_split( '/[,;|]/', $raw );
		$ids   = array();

		foreach ( (array) $names as $name ) {
			$name = trim( (string) $name );

			if ( '' === $name ) {
				continue;
			}

			$term = get_term_by( 'name', $name, Conecta_Exp_Taxonomy::TAXONOMY );

			if ( ! $term ) {
				$term = get_term_by( 'slug', sanitize_title( $name ), Conecta_Exp_Taxonomy::TAXONOMY );
			}

			if ( $term instanceof WP_Term ) {
				$ids[] = (int) $term->term_id;
				continue;
			}

			$created = wp_insert_term( $name, Conecta_Exp_Taxonomy::TAXONOMY );

			if ( ! is_wp_error( $created ) ) {
				$ids[] = (int) $created['term_id'];
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Define a imagem destacada a partir de um ID de anexo, URL ou nome de arquivo.
	 *
	 * URLs já baixadas em uma importação anterior são reaproveitadas pelo meta
	 * `_conecta_exp_source_url`, então reimportar não duplica a mídia.
	 *
	 * @param int    $post_id ID do post.
	 * @param string $value   Valor da coluna imagem.
	 * @return true|WP_Error
	 */
	private static function set_thumbnail( $post_id, $value ) {
		if ( ctype_digit( $value ) ) {
			$attachment_id = (int) $value;

			if ( 'attachment' !== get_post_type( $attachment_id ) ) {
				return new WP_Error( 'conecta_exp_img', __( 'ID de imagem inexistente.', 'conecta-experiencias' ) );
			}

			set_post_thumbnail( $post_id, $attachment_id );
			return true;
		}

		if ( ! preg_match( '#^https?://#i', $value ) ) {
			// Nome de arquivo solto: tenta achar na biblioteca de mídia.
			$attachment_id = attachment_url_to_postid( trailingslashit( wp_upload_dir()['baseurl'] ) . ltrim( $value, '/' ) );

			if ( ! $attachment_id ) {
				return new WP_Error( 'conecta_exp_img', __( 'Imagem não encontrada na biblioteca de mídia.', 'conecta-experiencias' ) );
			}

			set_post_thumbnail( $post_id, $attachment_id );
			return true;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_conecta_exp_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $value, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		if ( $existing ) {
			set_post_thumbnail( $post_id, (int) $existing[0] );
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $value, $post_id, null, 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		update_post_meta( $attachment_id, '_conecta_exp_source_url', $value );
		set_post_thumbnail( $post_id, (int) $attachment_id );

		return true;
	}

	/**
	 * Importa uma linha.
	 *
	 * @param array<string, string> $row     Linha mapeada.
	 * @param array<string, mixed>  $options Opções do formulário.
	 * @return array{status: string, message: string, post_id: int, titulo: string}
	 */
	private static function import_row( array $row, array $options ) {
		$titulo = sanitize_text_field( $row['titulo'] ?? '' );

		if ( '' === $titulo ) {
			return array(
				'status'  => 'erro',
				'message' => __( 'Linha sem título.', 'conecta-experiencias' ),
				'post_id' => 0,
				'titulo'  => '',
			);
		}

		$existing = self::find_existing( $row );

		if ( $existing && empty( $options['update'] ) ) {
			return array(
				'status'  => 'ignorado',
				'message' => __( 'Já existe e a atualização está desmarcada.', 'conecta-experiencias' ),
				'post_id' => $existing,
				'titulo'  => $titulo,
			);
		}

		$status = ! empty( $row['status'] ) ? sanitize_key( $row['status'] ) : $options['status'];

		if ( ! in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ) {
			$status = $options['status'];
		}

		$postarr = array(
			'post_type'   => Conecta_Exp_CPT::POST_TYPE,
			'post_title'  => $titulo,
			'post_status' => $status,
		);

		if ( isset( $row['conteudo'] ) ) {
			$postarr['post_content'] = wp_kses_post( $row['conteudo'] );
		}

		if ( isset( $row['resumo'] ) ) {
			$postarr['post_excerpt'] = sanitize_textarea_field( $row['resumo'] );
		}

		if ( ! empty( $row['slug'] ) ) {
			$postarr['post_name'] = sanitize_title( $row['slug'] );
		}

		if ( $existing ) {
			$postarr['ID'] = $existing;
			$post_id       = wp_update_post( $postarr, true );
			$action        = 'atualizado';
		} else {
			$post_id = wp_insert_post( $postarr, true );
			$action  = 'criado';
		}

		if ( is_wp_error( $post_id ) ) {
			return array(
				'status'  => 'erro',
				'message' => $post_id->get_error_message(),
				'post_id' => 0,
				'titulo'  => $titulo,
			);
		}

		$post_id = (int) $post_id;
		$notes   = array();

		foreach ( self::acf_fields() as $column => $field_key ) {
			if ( ! isset( $row[ $column ] ) ) {
				continue;
			}

			$value = in_array( $column, array( 'incluso', 'nao_incluso', 'levar_contigo' ), true )
				? sanitize_textarea_field( self::normalize_lines( $row[ $column ] ) )
				: sanitize_text_field( $row[ $column ] );

			self::set_field( $post_id, $field_key, $column, $value );
		}

		if ( ! empty( $row['categorias'] ) ) {
			$term_ids = self::resolve_terms( $row['categorias'] );

			if ( $term_ids ) {
				wp_set_object_terms( $post_id, $term_ids, Conecta_Exp_Taxonomy::TAXONOMY, false );
			}
		}

		if ( ! empty( $row['imagem'] ) && ! empty( $options['images'] ) ) {
			$result = self::set_thumbnail( $post_id, $row['imagem'] );

			if ( is_wp_error( $result ) ) {
				$notes[] = sprintf(
					/* translators: %s: mensagem de erro da imagem. */
					__( 'imagem não importada (%s)', 'conecta-experiencias' ),
					$result->get_error_message()
				);
			}
		}

		return array(
			'status'  => $action,
			'message' => implode( '; ', $notes ),
			'post_id' => $post_id,
			'titulo'  => $titulo,
		);
	}

	/* ---------------------------------------------------------------------
	 * Handlers
	 * ------------------------------------------------------------------ */

	/**
	 * Gera um token só com minúsculas e dígitos.
	 *
	 * Precisa sobreviver a `sanitize_key()` na volta — ela força minúsculas, e
	 * um token com maiúsculas deixaria de casar com o que foi gravado.
	 *
	 * @return string
	 */
	private static function novo_token() {
		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * Recupera o upload aguardando confirmação, validando token, prazo e arquivo.
	 *
	 * Cada motivo de falha tem sua própria mensagem: "expirou" genérico esconde
	 * o que de fato aconteceu.
	 *
	 * @param string $token Token vindo da URL ou do formulário.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function pendente( $token ) {
		$pendente = get_user_meta( get_current_user_id(), self::META_PENDENTE, true );

		if ( ! is_array( $pendente ) || empty( $pendente['token'] ) ) {
			return new WP_Error(
				'conecta_exp_sem_upload',
				__( 'Nenhum arquivo aguardando importação. Envie o CSV novamente.', 'conecta-experiencias' )
			);
		}

		if ( ! hash_equals( (string) $pendente['token'], (string) $token ) ) {
			return new WP_Error(
				'conecta_exp_token',
				__( 'Este endereço não corresponde ao último arquivo enviado. Envie o CSV novamente.', 'conecta-experiencias' )
			);
		}

		if ( time() - (int) $pendente['tempo'] > self::PRAZO_PENDENTE ) {
			self::limpa_pendente();
			return new WP_Error(
				'conecta_exp_prazo',
				__( 'O arquivo enviado passou de 1 hora sem ser confirmado e foi descartado. Envie o CSV novamente.', 'conecta-experiencias' )
			);
		}

		if ( ! file_exists( $pendente['path'] ) ) {
			self::limpa_pendente();
			return new WP_Error(
				'conecta_exp_arquivo',
				__( 'O arquivo enviado não está mais no servidor — a pasta de uploads pode ter sido limpa. Envie o CSV novamente.', 'conecta-experiencias' )
			);
		}

		return $pendente;
	}

	/**
	 * Descarta o upload pendente (arquivo e registro).
	 */
	private static function limpa_pendente() {
		$pendente = get_user_meta( get_current_user_id(), self::META_PENDENTE, true );

		if ( is_array( $pendente ) && ! empty( $pendente['path'] ) && file_exists( $pendente['path'] ) ) {
			wp_delete_file( $pendente['path'] );
		}

		delete_user_meta( get_current_user_id(), self::META_PENDENTE );
	}

	/**
	 * Diretório privado onde o CSV aguarda o processamento.
	 *
	 * @return string|WP_Error
	 */
	private static function import_dir() {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) ) {
			return new WP_Error( 'conecta_exp_uploads', $uploads['error'] );
		}

		$dir = trailingslashit( $uploads['basedir'] ) . self::UPLOAD_SUBDIR;

		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'conecta_exp_uploads', __( 'Não foi possível criar a pasta temporária de importação.', 'conecta-experiencias' ) );
		}

		// Bloqueia listagem e acesso direto ao CSV enviado.
		if ( ! file_exists( $dir . '/index.html' ) ) {
			file_put_contents( $dir . '/index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		return $dir;
	}

	/**
	 * Recebe o upload, guarda o arquivo e redireciona para a pré-visualização.
	 */
	public static function handle_upload() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'Sem permissão para importar.', 'conecta-experiencias' ) );
		}

		check_admin_referer( 'conecta_exp_import_upload' );

		if ( empty( $_FILES['arquivo']['name'] ) || ! isset( $_FILES['arquivo']['tmp_name'] ) ) {
			self::redirect_with_error( __( 'Selecione um arquivo CSV.', 'conecta-experiencias' ) );
		}

		if ( ! empty( $_FILES['arquivo']['error'] ) ) {
			self::redirect_with_error( __( 'Falha no upload do arquivo.', 'conecta-experiencias' ) );
		}

		$name      = sanitize_file_name( wp_unslash( $_FILES['arquivo']['name'] ) );
		$extension = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );

		if ( ! in_array( $extension, array( 'csv', 'txt' ), true ) ) {
			self::redirect_with_error( __( 'Formato não suportado. Salve a planilha como CSV (arquivo .csv) e envie novamente.', 'conecta-experiencias' ) );
		}

		$dir = self::import_dir();

		if ( is_wp_error( $dir ) ) {
			self::redirect_with_error( $dir->get_error_message() );
		}

		// Um envio novo invalida o anterior, sem deixar CSV órfão no disco.
		self::limpa_pendente();

		$token = self::novo_token();
		$path  = trailingslashit( $dir ) . $token . '.csv';
		$tmp   = sanitize_text_field( wp_unslash( $_FILES['arquivo']['tmp_name'] ) );

		if ( ! is_uploaded_file( $tmp ) || ! move_uploaded_file( $tmp, $path ) ) {
			self::redirect_with_error( __( 'Não foi possível salvar o arquivo enviado.', 'conecta-experiencias' ) );
		}

		update_user_meta(
			get_current_user_id(),
			self::META_PENDENTE,
			array(
				'token' => $token,
				'path'  => $path,
				'tempo' => time(),
			)
		);

		wp_safe_redirect( add_query_arg( 'token', $token, self::page_url() ) );
		exit;
	}

	/**
	 * Executa a importação a partir do arquivo já enviado.
	 */
	public static function handle_run() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'Sem permissão para importar.', 'conecta-experiencias' ) );
		}

		check_admin_referer( 'conecta_exp_import_run' );

		$token    = isset( $_POST['token'] ) ? sanitize_key( wp_unslash( $_POST['token'] ) ) : '';
		$pendente = self::pendente( $token );

		if ( is_wp_error( $pendente ) ) {
			self::redirect_with_error( $pendente->get_error_message() );
		}

		$parsed = self::parse_file( $pendente['path'] );

		if ( is_wp_error( $parsed ) ) {
			self::redirect_with_error( $parsed->get_error_message() );
		}

		$options = array(
			'status' => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'draft',
			'update' => ! empty( $_POST['atualizar'] ),
			'images' => ! empty( $_POST['imagens'] ),
		);

		if ( ! in_array( $options['status'], array( 'publish', 'draft', 'pending', 'private' ), true ) ) {
			$options['status'] = 'draft';
		}

		// Baixar imagens é o passo lento; afrouxa o limite quando o host permite.
		if ( $options['images'] && function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		$results = array();

		foreach ( $parsed['rows'] as $row ) {
			$results[] = self::import_row( $row, $options );
		}

		self::limpa_pendente();

		$token_resultado = self::novo_token();
		update_user_meta(
			get_current_user_id(),
			self::META_RESULTADO,
			array(
				'token'     => $token_resultado,
				'resultado' => $results,
			)
		);

		wp_safe_redirect( add_query_arg( 'resultado', $token_resultado, self::page_url() ) );
		exit;
	}

	/**
	 * Entrega um CSV de exemplo com o cabeçalho esperado.
	 */
	public static function download_template() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'Sem permissão.', 'conecta-experiencias' ) );
		}

		check_admin_referer( 'conecta_exp_import_modelo' );

		$header = array_keys( self::columns() );

		$exemplos = array(
			array(
				'Rafting no Rio Paraíba',
				'rafting-rio-paraiba',
				'publish',
				'Descida de 8 km com corredeiras nível III.',
				'<p>Uma descida guiada com equipe credenciada e todo o equipamento de segurança.</p>',
				'Aventura, Água',
				'R$ 180,00',
				'3 horas',
				'Moderado',
				'8km',
				'8h, 11h, 16h',
				"Guia credenciado|Equipamento de segurança|Seguro",
				"Alimentação|Transporte até o ponto de encontro",
				'Roupa de banho · protetor solar · tênis fechado',
				'https://exemplo.com.br/fotos/rafting.jpg',
			),
			array(
				'Trilha da Cachoeira Véu de Noiva',
				'',
				'draft',
				'Caminhada leve até a queda d’água.',
				'<p>Trilha de baixa dificuldade, ideal para famílias.</p>',
				'Trilhas',
				'R$ 90,00',
				'2 horas',
				'Fácil',
				'533m',
				'9h, 14h',
				"Guia local|Água mineral",
				"Lanche",
				'Repelente · boné · garrafa de água',
				'',
			),
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="modelo-experiencias.csv"' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		// BOM para o Excel pt-BR abrir com acentuação correta.
		echo "\xEF\xBB\xBF";

		fputcsv( $out, $header, ';', '"', '' );

		foreach ( $exemplos as $linha ) {
			fputcsv( $out, $linha, ';', '"', '' );
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Interface
	 * ------------------------------------------------------------------ */

	/**
	 * URL da página de importação.
	 *
	 * @return string
	 */
	private static function page_url() {
		return admin_url( 'edit.php?post_type=' . Conecta_Exp_CPT::POST_TYPE . '&page=' . self::MENU_SLUG );
	}

	/**
	 * Redireciona de volta para a página com uma mensagem de erro.
	 *
	 * @param string $message Mensagem.
	 */
	private static function redirect_with_error( $message ) {
		update_user_meta( get_current_user_id(), self::META_ERRO, $message );
		wp_safe_redirect( add_query_arg( 'erro', '1', self::page_url() ) );
		exit;
	}

	/**
	 * Renderiza a página do importador.
	 */
	public static function render_page() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'Sem permissão para importar.', 'conecta-experiencias' ) );
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'Importar Experiências (CSV)', 'conecta-experiencias' ) . '</h1>';

		if ( isset( $_GET['erro'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$message = get_user_meta( get_current_user_id(), self::META_ERRO, true );
			delete_user_meta( get_current_user_id(), self::META_ERRO );

			if ( $message ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
			}
		}

		$resultado = isset( $_GET['resultado'] ) ? sanitize_key( wp_unslash( $_GET['resultado'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $resultado ) {
			self::render_results( $resultado );
		}

		$token = isset( $_GET['token'] ) ? sanitize_key( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $token ) {
			self::render_preview( $token );
		} else {
			self::render_upload_form();
		}

		echo '</div>';
	}

	/**
	 * Formulário de envio do arquivo.
	 */
	private static function render_upload_form() {
		$columns = array_keys( self::columns() );
		?>
		<p><?php esc_html_e( 'Monte a planilha no Excel ou Google Sheets, exporte como CSV e envie aqui. Nada é gravado antes da pré-visualização.', 'conecta-experiencias' ); ?></p>

		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'conecta_exp_import_upload' ); ?>
			<input type="hidden" name="action" value="conecta_exp_import_upload">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="conecta-exp-arquivo"><?php esc_html_e( 'Arquivo CSV', 'conecta-experiencias' ); ?></label></th>
					<td><input type="file" id="conecta-exp-arquivo" name="arquivo" accept=".csv,text/csv" required></td>
				</tr>
			</table>
			<?php submit_button( __( 'Enviar e pré-visualizar', 'conecta-experiencias' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Colunas reconhecidas', 'conecta-experiencias' ); ?></h2>
		<p><code><?php echo esc_html( implode( ' · ', $columns ) ); ?></code></p>
		<ul class="ul-disc">
			<li><?php esc_html_e( '"titulo" é a única obrigatória. As demais podem faltar — o que não vier na planilha não é tocado.', 'conecta-experiencias' ); ?></li>
			<li><?php esc_html_e( 'Em "incluso" e "nao_incluso", separe os itens por "|" ou por quebra de linha dentro da célula.', 'conecta-experiencias' ); ?></li>
			<li><?php esc_html_e( 'Em "categorias", separe por vírgula. Categorias que ainda não existem são criadas automaticamente.', 'conecta-experiencias' ); ?></li>
			<li><?php esc_html_e( '"imagem" aceita a URL da foto, o ID de um anexo ou o nome do arquivo já enviado à biblioteca de mídia.', 'conecta-experiencias' ); ?></li>
			<li><?php esc_html_e( 'Uma experiência já existente é reconhecida pelo slug e, na falta dele, pelo título exato.', 'conecta-experiencias' ); ?></li>
		</ul>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'conecta_exp_import_modelo' ); ?>
			<input type="hidden" name="action" value="conecta_exp_import_modelo">
			<?php submit_button( __( 'Baixar CSV modelo', 'conecta-experiencias' ), 'secondary' ); ?>
		</form>
		<?php
	}

	/**
	 * Tabela de pré-visualização e opções da importação.
	 *
	 * @param string $token Token do arquivo enviado.
	 */
	private static function render_preview( $token ) {
		$pendente = self::pendente( $token );

		if ( is_wp_error( $pendente ) ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $pendente->get_error_message() ) );
			self::render_upload_form();
			return;
		}

		$parsed = self::parse_file( $pendente['path'] );

		if ( is_wp_error( $parsed ) ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $parsed->get_error_message() ) );
			self::render_upload_form();
			return;
		}

		if ( $parsed['unknown'] ) {
			printf(
				'<div class="notice notice-warning"><p>%s <code>%s</code></p></div>',
				esc_html__( 'Colunas ignoradas por não terem correspondência:', 'conecta-experiencias' ),
				esc_html( implode( ', ', $parsed['unknown'] ) )
			);
		}

		if ( ! $parsed['rows'] ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'Nenhuma linha com conteúdo foi encontrada no arquivo.', 'conecta-experiencias' )
			);
			self::render_upload_form();
			return;
		}

		echo '<h2>' . esc_html__( 'Pré-visualização', 'conecta-experiencias' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>#</th><th>' . esc_html__( 'Título', 'conecta-experiencias' ) . '</th>';
		echo '<th>' . esc_html__( 'Ação', 'conecta-experiencias' ) . '</th>';
		echo '<th>' . esc_html__( 'Categorias', 'conecta-experiencias' ) . '</th>';
		echo '<th>' . esc_html__( 'Preço', 'conecta-experiencias' ) . '</th>';
		echo '<th>' . esc_html__( 'Imagem', 'conecta-experiencias' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $parsed['rows'] as $index => $row ) {
			if ( empty( $row['titulo'] ) ) {
				$acao = __( 'Erro: sem título', 'conecta-experiencias' );
			} elseif ( self::find_existing( $row ) ) {
				$acao = __( 'Atualizar existente', 'conecta-experiencias' );
			} else {
				$acao = __( 'Criar', 'conecta-experiencias' );
			}

			printf(
				'<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				(int) $index + 1,
				esc_html( $row['titulo'] ?? '' ),
				esc_html( $acao ),
				esc_html( $row['categorias'] ?? '' ),
				esc_html( $row['preco'] ?? '' ),
				empty( $row['imagem'] ) ? '—' : esc_html__( 'sim', 'conecta-experiencias' )
			);
		}

		echo '</tbody></table>';
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'conecta_exp_import_run' ); ?>
			<input type="hidden" name="action" value="conecta_exp_import_run">
			<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="conecta-exp-status"><?php esc_html_e( 'Status dos itens', 'conecta-experiencias' ); ?></label></th>
					<td>
						<select id="conecta-exp-status" name="status">
							<option value="draft"><?php esc_html_e( 'Rascunho (revisar antes de publicar)', 'conecta-experiencias' ); ?></option>
							<option value="publish"><?php esc_html_e( 'Publicado', 'conecta-experiencias' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'A coluna "status" da planilha, quando preenchida, tem prioridade sobre esta opção.', 'conecta-experiencias' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Opções', 'conecta-experiencias' ); ?></th>
					<td>
						<label><input type="checkbox" name="atualizar" value="1" checked> <?php esc_html_e( 'Atualizar experiências que já existem', 'conecta-experiencias' ); ?></label><br>
						<label><input type="checkbox" name="imagens" value="1" checked> <?php esc_html_e( 'Importar as imagens da coluna "imagem"', 'conecta-experiencias' ); ?></label>
						<p class="description"><?php esc_html_e( 'Baixar imagens de URLs externas é a parte lenta. Se der timeout, desmarque, importe os dados e rode de novo só com as imagens.', 'conecta-experiencias' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Importar agora', 'conecta-experiencias' ) ); ?>
		</form>
		<p><a href="<?php echo esc_url( self::page_url() ); ?>"><?php esc_html_e( '← Cancelar e enviar outro arquivo', 'conecta-experiencias' ); ?></a></p>
		<?php
	}

	/**
	 * Relatório pós-importação.
	 *
	 * @param string $token Token do resultado.
	 */
	private static function render_results( $token ) {
		$guardado = get_user_meta( get_current_user_id(), self::META_RESULTADO, true );

		if ( ! is_array( $guardado ) || empty( $guardado['token'] ) || ! hash_equals( (string) $guardado['token'], (string) $token ) ) {
			return;
		}

		$results = $guardado['resultado'];
		delete_user_meta( get_current_user_id(), self::META_RESULTADO );

		if ( ! is_array( $results ) ) {
			return;
		}

		$counts = array_count_values( wp_list_pluck( $results, 'status' ) );

		printf(
			'<div class="notice notice-success"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: criados, 2: atualizados, 3: ignorados, 4: erros. */
					__( 'Importação concluída: %1$d criada(s), %2$d atualizada(s), %3$d ignorada(s), %4$d com erro.', 'conecta-experiencias' ),
					$counts['criado'] ?? 0,
					$counts['atualizado'] ?? 0,
					$counts['ignorado'] ?? 0,
					$counts['erro'] ?? 0
				)
			)
		);

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Título', 'conecta-experiencias' ) . '</th>';
		echo '<th>' . esc_html__( 'Resultado', 'conecta-experiencias' ) . '</th>';
		echo '<th>' . esc_html__( 'Observação', 'conecta-experiencias' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $results as $result ) {
			$titulo = $result['titulo'];

			if ( $result['post_id'] ) {
				$titulo = sprintf(
					'<a href="%s">%s</a>',
					esc_url( (string) get_edit_post_link( $result['post_id'] ) ),
					esc_html( $titulo )
				);
			} else {
				$titulo = esc_html( $titulo );
			}

			printf(
				'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
				$titulo, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $result['status'] ),
				esc_html( $result['message'] )
			);
		}

		echo '</tbody></table><hr>';
	}
}

Conecta_Exp_Importer::init();
