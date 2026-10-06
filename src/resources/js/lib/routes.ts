/** URLs da aplicação (espelham routes/web.php). */
export const rotas = {
    historico: '/historico',
    login: '/login',
    logout: '/logout',
    perfil: '/perfil',
    perfilSenha: '/perfil/senha',
    usuarios: '/usuarios',
    usuario: (id: number) => `/usuarios/${id}`,
    index: '/',
    upload: '/upload',
    criar: '/pedidos/create',
    salvar: '/pedidos',
    ver: (id: number) => `/pedidos/${id}`,
    editar: (id: number) => `/pedidos/${id}/edit`,
    atualizar: (id: number) => `/pedidos/${id}`,
    dados: (id: number) => `/pedidos/${id}/dados`,
    pdf: (id: number) => `/pedidos/${id}/pdf`,
    excluir: (id: number) => `/pedidos/${id}`,
    statusPedido: (id: number) => `/pedidos/${id}/status`,
    controle: '/controle',
    controleExportar: (ano?: number, modo: 'itens' | 'pedidos' = 'itens') => {
        const params = new URLSearchParams();
        if (ano) params.set('ano', String(ano));
        if (modo === 'pedidos') params.set('modo', 'pedidos');
        const query = params.toString();
        return query ? `/controle/exportar?${query}` : '/controle/exportar';
    },
    item: (id: number) => `/itens/${id}`,
    controleItem: (id: number) => `/controle/itens/${id}`,
};
