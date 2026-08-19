# Rio Aventura — Experiências

Plugin WordPress que fornece a **estrutura de dados** das experiências de turismo
da agência: um Custom Post Type, uma taxonomia e os campos customizados (ACF).
Independente de tema — a estrutura sobrevive a troca de tema.

> Este plugin cuida **apenas** dos dados — não renderiza HTML de layout e não
> escreve em nenhuma página. O único shortcode, `[cor_categoria]`, devolve um
> valor (o hex da cor da categoria), não markup. Toda a apresentação (card,
> carrosséis por categoria, Loop Grid, Taxonomy Filter e Single) é montada na
> interface do **Elementor Pro** e **não** faz parte deste plugin.

## O que ele registra

| Item | Slug interno | Detalhes |
|------|--------------|----------|
| CPT | `experiencia` | `public`, `has_archive`, REST habilitado, suporta título/editor/imagem destacada/resumo. URLs em `/experiencias/{slug}`. Ícone `dashicons-palmtree`. |
| Taxonomia | `categoria_experiencia` | Hierárquica (vocabulário controlado, tipo categoria), vinculada ao CPT, REST habilitado, coluna no admin. URLs em `/categoria/{termo}`. Sem termos semeados — cadastrados no admin. |
| Grupo ACF | `Detalhes da Experiência` | Campos de texto livre `preco`, `duracao`, `dificuldade`, `distancia`, `horarios` e textareas `incluso`, `nao_incluso` (um item por linha, exibidos com `<br>`) e `levar_contigo` (texto corrido). Vinculado a `post_type == experiencia`. |
| Grupo ACF | `Estilo da Categoria` | Campo `cor` (Color Picker) no **termo** da taxonomia. Disponibiliza a cor da categoria como dado, para uso opcional no card via Elementor. |
| Shortcode | `[cor_categoria]` | Devolve o hex da cor da categoria do post atual do loop (campo `cor` do termo). Atributos: `fallback` (default `#1D9E75`) e `term_id` (força um termo específico). Saída sempre um hex válido. |
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
│   └── class-importer.php     # importador CSV (só carregado no admin)
└── README.md
```

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

O card e os carrosséis por categoria são montados **manualmente no Elementor** —
este plugin apenas fornece os dados (incluindo a `cor` de cada categoria).

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

## Notas técnicas

- Prefixo de código `conecta_` / text domain `conecta-experiencias`.
- Registros idempotentes; ativar/desativar não gera erros de PHP.
- `flush_rewrite_rules()` rodado na ativação e na desativação.
- Caso edite slugs de rewrite, revisite **Configurações → Links Permanentes**
  e salve para forçar um novo flush.

## Evolução

Se no futuro for necessário **filtrar por dificuldade** (como hoje por
categoria), converta `dificuldade` de campo ACF para taxonomia — mesmo padrão
de `categoria_experiencia` —, pois o filtro nativo do Elementor só opera sobre
taxonomias.
