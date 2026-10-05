export type BlocoChave = 'fornecedor' | 'faturamento' | 'local' | 'cobranca';

export interface Bloco {
    titulo?: string;
    nome?: string;
    endereco?: string[];
    cnpj?: string;
    ie?: string;
    fone?: string;
}

/** Cabeçalho do PDF guardado em pedidos.dados_extras (veja docs/arquitetura.md). */
export interface DadosExtras {
    cond_pgto?: string;
    frete?: string;
    moeda?: string;
    comprador?: string;
    contato_nome?: string;
    contato_email?: string;
    total_icms?: string;
    total_ipi?: string;
    total_produtos?: string;
    observacoes?: string;
    diferenca_aceita?: string;
    blocos?: Partial<Record<BlocoChave, Bloco>>;
}

export interface Item {
    id?: number;
    item: string | null;
    material: string | null;
    denominacao: string | null;
    qtd: string | null;
    un: string | null;
    preco: string | null;
    vlr_tot: string | null;
    icms: string | null;
    ipi: string | null;
    dt_entrega: string | null;
    item_lei: string | null;
    tipo_manutencao: string | null;
    local_prestacao: string | null;
    desconto_absoluto: string | null;
    icms_monofasico: string | null;
    reducao_base_icms: string | null;
    base_inss: string | null;
    cidade_entrega: string | null;
    // Controle de produção (planilha de controle)
    desenho_nesting: string | null;
    compra_mp: string | null;
    compra_insumo: string | null;
    usinagem: string | null;
    corte_dobra: string | null;
    solda: string | null;
    pintura: string | null;
    montagem: string | null;
    responsavel: string | null;
    status: string | null;
}

export interface Pedido {
    id: number;
    numero: string | null;
    data_pedido: string | null;
    cliente: string | null;
    fornecedor: string | null;
    valor: string | null;
    itens_count?: number;
    /** Situação do pedido pelos status dos itens e entrega mais próxima dos itens em andamento (só na lista). */
    status_geral?: string;
    proxima_entrega?: string | null;
    /** Data de entrega mostrada na lista: a mais próxima dos itens em andamento ou, sem nenhum, a última. */
    data_entrega?: string | null;
    itens?: Item[];
    dados_extras?: DadosExtras;
    texto_bruto?: string | null;
    tem_pdf?: boolean;
}

export interface Paginador<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface Rotulos {
    blocos: Record<BlocoChave, string>;
    condicoes: Record<string, string>;
}

/** Props compartilhadas por todas as páginas (HandleInertiaRequests). */
export interface Usuario {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    ativo?: boolean;
    created_at?: string;
}

export interface PageProps {
    auth: { user: Usuario | null };
    flash: { success?: string | null };
    errors: Record<string, string>;
    [key: string]: unknown;
}

/** Coluna da grade de controle (vem de App\\Support\\ColunasControle). */
export interface ColunaControle {
    chave: string;
    titulo: string;
    tela: string;
    largura: number;
    cor: string;
    alinha: 'left' | 'center';
    oculta: boolean;
    tipo: 'texto' | 'numero' | 'data' | 'etapa' | 'status';
}

/** Uma linha da grade: um item de pedido com os dados do pedido. */
export interface LinhaControle {
    id: number;
    pedido_id: number;
    cliente: string | null;
    numero: string | null;
    denominacao: string | null;
    qtd: number | null;
    dt_entrega: string | null;
    cidade_entrega: string | null;
    desenho_nesting: string | null;
    compra_mp: string | null;
    compra_insumo: string | null;
    usinagem: string | null;
    corte_dobra: string | null;
    solda: string | null;
    pintura: string | null;
    montagem: string | null;
    responsavel: string | null;
    status: string;
    [chave: string]: string | number | null;
}

/** Uma alteração do histórico (um campo alterado, ou a criação/exclusão). */
export interface RegistroHistorico {
    id: number;
    acao: 'criou' | 'editou' | 'excluiu';
    campo: string | null;
    valor_anterior: string | null;
    valor_novo: string | null;
    item_id: number | null;
    item_descricao: string | null;
}

/** Alterações salvas juntas: quem fez, quando, em qual pedido. */
export interface GrupoHistorico {
    lote: string;
    quando: string | null;
    usuario: string;
    pedido_id: number | null;
    pedido_numero: string | null;
    origem: string | null;
    registros: RegistroHistorico[];
}
