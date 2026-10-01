/** URLs da aplicação (espelham routes/web.php). */
export const rotas = {
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
};
