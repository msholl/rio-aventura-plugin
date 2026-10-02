# Rio Aventura — Experiências

Plugin WordPress que fornece a **estrutura de dados** das experiências de turismo
da agência: um Custom Post Type, uma taxonomia e os campos customizados (ACF).
Independente de tema — a estrutura sobrevive a troca de tema.

> Além dos dados, o plugin entrega telas prontas (desde a 1.7.0): a listagem
> `[experiencias]`, com filtro por categoria e por destaque, a página completa
> da experiência em `/experiencias/{slug}/` (com galeria de fotos e vídeos) e
> dois blocos para a home, `[experiencias_categorias]` e
> `[experiencias_destaque]`. Os carrosséis e cards montados no Elementor
> continuam funcionando — as abordagens convivem.

## O que ele registra

| Item | Slug interno | Detalhes |
|------|--------------|----------|
| CPT | `experiencia` | `public`, `has_archive`, REST habilitado, suporta título/editor/imagem destacada/resumo. URLs em `/experiencias/{slug}`. Ícone `dashicons-palmtree`. |
| Taxonomia | `categoria_experiencia` | Hierárquica (vocabulário controlado, tipo categoria), vinculada ao CPT, REST habilitado, coluna no admin. URLs em `/categoria/{termo}`. Sem termos semeados — cadastrados no admin. |
| Grupo ACF | `Detalhes da Experiência` | Interruptor `destaque` (true/false) e campos de texto livre `preco`, `duracao`, `dificuldade`, `distancia`, `horarios` e textareas `incluso`, `nao_incluso` (um item por linha, exibidos com `<br>`) e `levar_contigo` (texto corrido). Vinculado a `post_type == experiencia`. |
| Grupo ACF | `Estilo da Categoria` | Campo `cor` (Color Picker) no **termo** da taxonomia. Disponibiliza a cor da categoria como dado, para uso opcional no card via Elementor. |
| Shortcode | `[cor_categoria]` | Devolve o hex da cor da categoria do post atual do loop (campo `cor` do termo). Atributos: `fallback` (default `#1D9E75`) e `term_id` (força um termo específico). Saída sempre um hex válido. |
| Shortcode | `[experiencias]` | Grade de todas as experiências com filtro por categoria. Ver "Listagem e página da experiência". |
| Shortcode | `[experiencia_detalhe]` | Página completa de uma experiência (estilo "single product"). |
| Shortcode | `[experiencias_categorias]` | Cartões das categorias que levam à listagem já filtrada. Feito para a home. |
| Shortcode | `[experiencias_destaque]` | Cards das experiências marcadas como destaque. Feito para a home. |
| Template | `/experiencias/{slug}/` | Usa o `[experiencia_detalhe]` automaticamente, salvo se houver template Single do Elementor Pro ou `single-experiencia.php` no tema. |
| Meta box | `Galeria de fotos e vídeos` | Fotos, vídeos MP4 e links do YouTube/Vimeo do carrossel da experiência (meta `galeria`). |
| Colunas no admin | `Foto` e `★` | Miniatura da imagem destacada (com placeholder quando falta) e estrela das experiências em destaque. Só no admin. |
| Dynamic Tag | `Cor da Categoria` | Tag nativa do Elementor (categoria COLOR), grupo "Experiência". Mesma regra do shortcode, direto no seletor de cor de qualquer elemento (fundo, texto, borda). |

### Mapeamento dos requisitos
- **Foto** → imagem destacada (suporte a `thumbnail`).
- **Nome** → `post_title`.
- **Descrição** → corpo do editor (Post Content).
- **Link para a experiência completa** → permalink do próprio CPT (Single no Elementor).
- **Categoria** → taxonomia `categoria_experiencia` (alimenta o filtro nativo do Elementor).
- **Preço / Duração / Dificuldade / Distância / Horários** → campos ACF de texto.
- **Incluso / Não Incluso** → textareas ACF, um item por linha (`new_lines => 'br'`
  faz cada linha virar `<br>`, sem `<ul>`/marcadores).
- **Levar Contigo** → textarea ACF de texto corrido (`new_lines => 'wpautop'`);
  o editor usa separadores como "·" ou vírgula.
- **Cor da categoria** → campo ACF `cor` no termo (dado para uso opcional no card).

## Requisitos

- WordPress 6.0+
- PHP 8.1+
- [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) (free é suficiente)
- Elementor Pro (para os templates; não exigido pelo plugin em si)

O CPT e a taxonomia funcionam sem o ACF. Se o ACF estiver inativo, os campos
customizados não aparecem e um aviso é exibido no admin.

## Instalação

1. Copie a pasta `rio-aventura-plugin` para `wp-content/plugins/`.
2. Ative **Advanced Custom Fields**.
3. Ative **Rio Aventura — Experiências**.

Na ativação o plugin registra a estrutura e atualiza as regras de rewrite
(permalinks). Nenhuma experiência ou categoria é criada — o conteúdo vive no
banco e é cadastrado no admin.

## Estrutura

```
rio-aventura-plugin/
├── rio-aventura-plugin.php    # header + bootstrap, ativação/desativação, i18n, aviso de ACF
├── includes/
│   ├── class-cpt.php          # CPT "experiencia"
│   ├── class-taxonomy.php     # taxonomia "categoria_experiencia"
│   ├── acf-fields.php         # campos da experiência + cor do termo
│   ├── shortcode-cor-categoria.php  # resolução da cor + shortcode [cor_categoria]
│   ├── elementor-dynamic-tags.php   # Dynamic Tag "Cor da Categoria" (COLOR)
│   ├── galeria.php            # meta box "Galeria de fotos e vídeos" + leitura dos itens
│   ├── front.php              # shortcodes do front, template do single, helpers
│   ├── translatepress.php     # bandeira da Espanha para o idioma es_AR
│   ├── admin-columns.php      # colunas "Foto" e "★" na listagem (só carregado no admin)
│   └── class-importer.php     # importador CSV (só carregado no admin)
├── templates/                 # HTML do front (sobrescrevível pelo tema)
│   ├── listagem.php
│   ├── card.php
│   ├── detalhe.php
│   ├── galeria.php
│   ├── categorias.php
│   ├── destaques.php
│   └── single-experiencia.php
├── languages/                 # traduções pt_BR / en_US do front (.po/.mo/.l10n.php)
├── assets/
│   ├── experiencias.css
│   ├── experiencias.js        # filtro sem recarregar + carrossel da galeria
│   ├── galeria-admin.js       # meta box da galeria
│   └── simbolo.svg            # símbolo da marca (placeholder sem foto)
└── README.md
```

## Listagem no admin

**Experiências → Todas as Experiências.** A listagem ganha duas colunas:

- **Foto**, entre a checkbox e o título, com a miniatura da imagem destacada.
  Sem imagem, mostra um placeholder — quadrado cinza com borda tracejada e o
  ícone `dashicons-palmtree`, o mesmo do menu do CPT. Célula vazia se
  confundiria com falha de carregamento, e a ausência de foto é exatamente o
  que se quer enxergar ao varrer a lista antes de publicar.
- **★**, logo depois do título, marcando as experiências em destaque.

Notas de implementação:

- A imagem é pedida em `array( 120, 120 )` e exibida a 60px, para não borrar em
  tela de alta densidade. `object-fit: cover` mantém o quadrado.
- O CSS é inline e impresso só nessa tela (`get_current_screen()`).
- Os ícones são `aria-hidden`; o estado é anunciado por `screen-reader-text`.
- As colunas são inseridas reconstruindo o array, preservando a ordem das
  demais — colunas de outros plugins continuam onde estavam.

## Importador CSV

**Experiências → Importar CSV.** Serve para o cadastro inicial em lote e para
atualizações posteriores em massa (reajuste de preços, troca de horários).

Só CSV: ler `.xlsx` exigiria o PhpSpreadsheet como dependência. O caminho é
montar a planilha no Excel/Google Sheets e usar **Salvar como → CSV**. O parser
tolera o CSV brasileiro — separador `;` ou `,` (detectado sozinho), acentuação
Windows-1252 e o BOM que o Excel escreve.

O fluxo tem duas etapas: o arquivo é enviado, você vê a **pré-visualização**
(quais linhas serão criadas, atualizadas ou rejeitadas) e só então confirma.
Nada é gravado antes disso. O CSV fica em `uploads/conecta-exp-import/`
(protegido por `.htaccess`) e é apagado ao fim da importação.

### Colunas

| Coluna | Destino | Observação |
|--------|---------|------------|
| `titulo` | `post_title` | **Obrigatória.** |
| `slug` | `post_name` | Opcional; sem ela o slug sai do título. |
| `status` | `post_status` | `publish`/`draft`; tem prioridade sobre a opção do formulário. |
| `resumo` | `post_excerpt` | |
| `conteudo` | `post_content` | Aceita HTML (`wp_kses_post`). |
| `categorias` | `categoria_experiencia` | Separadas por vírgula; termos novos são criados. |
| `preco`, `duracao`, `dificuldade`, `distancia`, `horarios` | campos ACF de texto | Já com unidade/símbolo, como no admin. |
| `incluso`, `nao_incluso` | textareas ACF | Um item por linha: separe por `\|` ou por quebra de linha dentro da célula. |
| `levar_contigo` | textarea ACF | Texto corrido. |
| `imagem` | imagem destacada | URL, ID de anexo ou nome de arquivo já na biblioteca. |

Os nomes do cabeçalho são normalizados antes de casar, então `Título`,
`titulo` e `TITULO` são equivalentes, e há sinônimos aceitos (`nome`,
`descricao`, `valor`, `foto`…). Colunas sem correspondência são listadas na
pré-visualização e ignoradas — a planilha do cliente pode ter colunas extras.
Colunas ausentes não são tocadas.

O botão **Baixar CSV modelo** entrega o cabeçalho completo com duas linhas de
exemplo, em UTF-8 com BOM para abrir certo no Excel pt-BR.

### Reimportação

Uma experiência já existente é reconhecida pelo `slug` e, na falta dele, pelo
título exato — então reimportar a mesma planilha **atualiza** em vez de
duplicar (desmarque "Atualizar experiências que já existem" para pular as que
já existem). Imagens baixadas de URL guardam a origem no meta
`_conecta_exp_source_url` e são reaproveitadas, sem duplicar mídia.

### Notas

- Acesso restrito a `manage_options`; ajustável pelo filtro
  `conecta_exp_import_capability`.
- Sem ACF ativo, os valores são gravados como post meta no formato que o ACF
  reconhece quando for ativado depois — a importação não quebra.
- Baixar imagens externas é a parte lenta. Se o host cortar por timeout,
  desmarque "Importar as imagens", importe os dados e rode de novo só com as
  imagens (as experiências já criadas serão atualizadas).

## Uso no Elementor

Com o ACF ativo, os campos `preco`, `duracao`, `dificuldade`, `distancia`,
`horarios`, `incluso`, `nao_incluso` e `levar_contigo` ficam disponíveis nos
**Dynamic Tags** do Elementor (grupo ACF). O campo `cor`
do termo fica disponível como Dynamic Tag de taxonomia/termo. A taxonomia
`categoria_experiencia` aparece no widget **Taxonomy Filter** e na Query do
Loop.

Como os valores dos campos já incluem unidade/símbolo (ex.: "R$ 180,00",
"533m"), **não** use Before/After na exibição — isso duplicaria o símbolo.

Os carrosséis por categoria da página atual são montados **manualmente no
Elementor** com esses dados; a alternativa pronta é o `[experiencias]`.

### Dynamic Tag "Cor da Categoria"

O dynamic tag "ACF Campo" do Elementor não lê campos de **termo**, então a cor
da categoria é exposta por uma tag nativa própria: em qualquer controle de COR
(fundo, cor do texto, borda), clique no ícone dinâmico e escolha **Cor da
Categoria** no grupo **Experiência**. O elemento assume a cor da categoria da
experiência atual. Por ser resolvida no render do PHP, funciona dentro de Loop
Carousel/Grid, inclusive nos slides clonados. Sem categoria/cor, usa o
fallback `#1D9E75`.

### Shortcode `[cor_categoria]`

A mesma cor também está disponível como shortcode, que devolve apenas o hex
(ex.: `#1D9E75`) — sem HTML. Use-o em contextos que aceitam shortcode para
colorir elementos do card via CSS. Tag e shortcode compartilham a mesma função
de resolução (`conecta_exp_cor_categoria_valor()`).

- Dentro do Loop Item, lê o **primeiro** termo de `categoria_experiencia` do
  post atual e devolve o campo `cor` desse termo.
- `fallback="#123456"` — cor usada quando não há termo, cor ou contexto de
  post. Default `#1D9E75`.
- `term_id="42"` — força a leitura de um termo específico em vez do primeiro.
- A saída é sempre um hex válido (sanitizada com `sanitize_hex_color()`); sem
  ACF ativo, devolve o fallback sem erro.

## Listagem e página da experiência

### `[experiencias]`

Cole numa página (widget **Shortcode** do Elementor ou bloco Shortcode). Mostra
todas as experiências publicadas em cards (foto, categoria, título, duração,
dificuldade, resumo e preço) e, acima, os botões de filtro com a contagem por
categoria. O filtro troca os cards sem recarregar e atualiza a URL para
`?categoria={slug}` — esse link também funciona direto (e sem JavaScript).

| Atributo | Default | Uso |
|---|---|---|
| `categoria` | — | Slugs separados por vírgula: restringe a listagem a essas categorias. |
| `filtro` | `si` | `no` esconde os botões de filtro. |
| `limite` | `-1` | Máximo de experiências. |
| `colunas` | `3` | Colunas no desktop (1–4); 2 no tablet e 1 no celular. |
| `titulo_tag` | `h2` | Tag do título do card (`h2`, `h3`, `h4`). |

Ordem: **Atributos da página → Ordem** (`menu_order`) e depois título.

Havendo experiência marcada como **Destaque**, o primeiro botão do filtro é
**★ Destacados** (`?categoria=destacados`), antes de "Todas".

O início da listagem tem a âncora `#experiencias`: um link como
`/experiencia/?categoria=tours-clasicos#experiencias` abre a página já na
altura dos cards. No celular, a faixa de filtros rola até o botão ativo.

O cabeçalho do site é transparente e fica por cima do topo da página; ponha
um banner/hero ou um espaçador antes do shortcode.

### Página da experiência

`/experiencias/{slug}/` passa a ter hero com a foto, trilha (Experiencias /
Categoria), caixa de reserva com preço, ficha (duração, dificuldade,
distância, horários) e botão de WhatsApp, o conteúdo do editor, Incluye / No
incluye, Qué llevar e "Más experiencias en {categoria}". No celular, uma barra
fixa com preço e "Reservar" acompanha a rolagem. Campos vazios não aparecem.

A trilha e o "Ver todas" apontam para a página que contém `[experiencias]`
(detectada sozinha; filtro `conecta_exp_url_listagem` para forçar outra).

`[experiencia_detalhe]` renderiza a mesma tela em outro lugar — atributos
`id`, `whatsapp` e `relacionadas` (quantidade; `0` esconde).

### Galeria de fotos e vídeos

Na edição da experiência, a caixa **Galeria de fotos e vídeos** (lateral)
monta o carrossel que aparece acima de "Sobre la experiencia":

- **Adicionar fotos ou vídeos** abre a biblioteca de mídia (imagens e vídeos).
- **Adicionar link do YouTube/Vimeo** aceita `youtube.com/watch`, `youtu.be`,
  Shorts, `vimeo.com/{id}` e Vimeo não listado (`vimeo.com/{id}/{hash}`).
- Arraste para ordenar; o "×" remove.

A foto da experiência é sempre o primeiro item; o carrossel aparece a partir
de dois itens. Fotos abrem ampliadas (só as fotos entram na navegação
ampliada). Vídeo enviado ao site toca no slide com os controles nativos —
prefira clipes curtos (até ~30 s / 15 MB). YouTube/Vimeo mostram só a capa e
carregam o player no clique (YouTube no modo `youtube-nocookie`). Ao trocar de
slide, o vídeo em reprodução para.

O meta `galeria` guarda a lista em ordem: IDs de anexo e URLs de vídeo. A
leitura pronta para exibir é `conecta_exp_galeria_itens( $post_id )`.

### Destaques

O interruptor **Destaque** (grupo "Detalhes da Experiência") marca a
experiência para o filtro "Destacados" da listagem e para o
`[experiencias_destaque]`.

## Blocos para a home

### `[experiencias_categorias]`

Cartões das categorias com experiência publicada (nome, quantidade e seta, na
cor da categoria), cada um levando a `/{listagem}/?categoria={slug}#experiencias`.

| Atributo | Default | Uso |
|---|---|---|
| `categorias` | todas, em ordem alfabética | Slugs separados por vírgula, na ordem desejada. |
| `limite` | `4` | Máximo de cartões. |
| `alinhamento` | `centro` | `esquerda` alinha à esquerda. |
| `titulo` | "Explora por categoría" | Título acima; `titulo=""` esconde. |

### `[experiencias_destaque]`

Cards das experiências marcadas como destaque, com o botão "Ver todas las
experiencias". Até 1024px os cards viram uma faixa com rolagem horizontal.
Sem nenhum destaque, não imprime nada.

| Atributo | Default | Uso |
|---|---|---|
| `limite` | `-1` (todas) | Quantos cards. |
| `colunas` | um por card, até 4 | Colunas no desktop. |
| `titulo` | "Experiencias destacadas" | Título acima; `titulo=""` esconde. |

### Personalização

- WhatsApp: filtro `conecta_exp_whatsapp_numero` (default `5521990853118`) e
  `conecta_exp_whatsapp_mensagem` (texto da mensagem).
- Consulta da listagem: filtro `conecta_exp_listagem_query_args`.
- Desligar o template automático do single: `add_filter( 'conecta_exp_usar_template_single', '__return_false' );`
- HTML: copie um arquivo de `templates/` para `{tema}/rio-aventura/` e edite.
- Os textos do front (botões, rótulos, títulos de seção) estão em espanhol,
  o idioma padrão do site, e as traduções vêm com o plugin em `languages/`
  (`pt_BR` e `en_US`). O TranslatePress troca o idioma e o WordPress usa
  esses arquivos — não é preciso traduzi-los no editor do TranslatePress
  (uma tradução feita lá tem prioridade). Conteúdo da experiência e nomes de
  categoria continuam sendo traduzidos pelo dicionário do TranslatePress.
- Ao mudar um texto do front: `wp i18n make-pot . languages/conecta-experiencias.pot --exclude=languages`,
  edite os `.po` e rode `wp i18n make-mo languages && wp i18n make-php languages`.

## Integrações

- **TranslatePress:** o espanhol do site está cadastrado como `es_AR`; trocar o
  idioma apagaria a ligação com as traduções, então só a bandeira é trocada
  para a da Espanha (`trp_flag_html` / `trp_flag_file_name`). Outros pares em
  `conecta_exp_bandeiras_trocadas`.
- **Jetpack:** os "Posts relacionados" são desligados na página da experiência
  (`jetpack_relatedposts_filter_enabled_for_request`), que já tem o bloco
  próprio de relacionadas.

## Notas técnicas

- Prefixo de código `conecta_` / text domain `conecta-experiencias`.
- Registros idempotentes; ativar/desativar não gera erros de PHP.
- `flush_rewrite_rules()` rodado na ativação e na desativação.
- Caso edite slugs de rewrite, revisite **Configurações → Links Permanentes**
  e salve para forçar um novo flush.

## Histórico

| Versão | Mudança |
|---|---|
| 1.13.0 | Galeria aceita vídeo da biblioteca e links do YouTube/Vimeo. |
| 1.12.x | Cartões de categoria na home; cor de fundo dos cards; título dos destaques maior; relacionados do Jetpack desligados nas experiências. |
| 1.11.x | `[experiencias_destaque]`; faixa com rolagem horizontal no celular; âncora `#experiencias`. |
| 1.10.x | `[experiencias_categorias]`. |
| 1.9.0 | Campo Destaque, filtro "Destacados" e coluna ★ no admin. |
| 1.8.x | Galeria de fotos na página da experiência; bandeira da Espanha no TranslatePress. |
| 1.7.x | `[experiencias]`, página da experiência, traduções pt/en, placeholder sem foto. |
| 1.6.0 | Coluna "Foto" no admin. |

## Evolução

Se no futuro for necessário **filtrar por dificuldade** (como hoje por
categoria), converta `dificuldade` de campo ACF para taxonomia — mesmo padrão
de `categoria_experiencia` —, pois o filtro nativo do Elementor só opera sobre
taxonomias.
