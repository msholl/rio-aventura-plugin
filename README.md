# Rio Aventura — Experiências

Plugin WordPress que fornece a **estrutura de dados** das experiências de turismo
da agência: um Custom Post Type, uma taxonomia e os campos customizados (ACF).
Independente de tema — a estrutura sobrevive a troca de tema.

> Este plugin cuida **apenas** dos dados — não renderiza nada, não tem shortcode
> e não escreve em nenhuma página. Toda a apresentação (card, carrosséis por
> categoria, Loop Grid, Taxonomy Filter e Single) é montada na interface do
> **Elementor Pro** e **não** faz parte deste plugin.

## O que ele registra

| Item | Slug interno | Detalhes |
|------|--------------|----------|
| CPT | `experiencia` | `public`, `has_archive`, REST habilitado, suporta título/editor/imagem destacada/resumo. URLs em `/experiencias/{slug}`. Ícone `dashicons-palmtree`. |
| Taxonomia | `categoria_experiencia` | Hierárquica (vocabulário controlado, tipo categoria), vinculada ao CPT, REST habilitado, coluna no admin. URLs em `/categoria/{termo}`. Sem termos semeados — cadastrados no admin. |
| Grupo ACF | `Detalhes da Experiência` | Campos `preco`, `duracao`, `dificuldade`, `distancia` — todos **texto livre**. Vinculado a `post_type == experiencia`. |
| Grupo ACF | `Estilo da Categoria` | Campo `cor` (Color Picker) no **termo** da taxonomia. Disponibiliza a cor da categoria como dado, para uso opcional no card via Elementor. |

### Mapeamento dos requisitos
- **Foto** → imagem destacada (suporte a `thumbnail`).
- **Nome** → `post_title`.
- **Descrição** → corpo do editor (Post Content).
- **Link para a experiência completa** → permalink do próprio CPT (Single no Elementor).
- **Categoria** → taxonomia `categoria_experiencia` (alimenta o filtro nativo do Elementor).
- **Preço / Duração / Dificuldade / Distância** → campos ACF de texto.
- **Cor da categoria** → campo ACF `cor` no termo (dado para uso opcional no card).

## Requisitos

- WordPress 6.0+
- PHP 8.1+
- [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) (free é suficiente)
- Elementor Pro (para os templates; não exigido pelo plugin em si)

O CPT e a taxonomia funcionam sem o ACF. Se o ACF estiver inativo, os quatro
campos não aparecem e um aviso é exibido no admin.

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
│   └── acf-fields.php         # campos da experiência + cor do termo
└── README.md
```

## Uso no Elementor

Com o ACF ativo, os campos `preco`, `duracao`, `dificuldade` e `distancia`
ficam disponíveis nos **Dynamic Tags** do Elementor (grupo ACF). O campo `cor`
do termo fica disponível como Dynamic Tag de taxonomia/termo. A taxonomia
`categoria_experiencia` aparece no widget **Taxonomy Filter** e na Query do
Loop.

Como os valores dos campos já incluem unidade/símbolo (ex.: "R$ 180,00",
"533m"), **não** use Before/After na exibição — isso duplicaria o símbolo.

O card e os carrosséis por categoria são montados **manualmente no Elementor** —
este plugin apenas fornece os dados (incluindo a `cor` de cada categoria).

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
