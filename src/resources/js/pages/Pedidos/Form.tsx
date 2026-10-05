import { Head, Link, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

import Field from '@/components/Field';
import ExcluirPedido from '@/components/pedidos/ExcluirPedido';
import ItemFormCard from '@/components/pedidos/ItemFormCard';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import { rotas } from '@/lib/routes';
import type { Item, Pedido } from '@/types';

/** Item com chave local estável (não é enviada ao servidor). */
type ItemEditavel = Item & { _k: number };

const ITEM_VAZIO: Item = {
    item: null, material: null, denominacao: null, qtd: null, un: null, preco: null, vlr_tot: null, icms: null, ipi: null,
    dt_entrega: null, item_lei: null, tipo_manutencao: null, local_prestacao: null, desconto_absoluto: null,
    icms_monofasico: null, reducao_base_icms: null, base_inss: null, cidade_entrega: null, observacoes: null, fabricante: null,
    desenho_nesting: null, compra_mp: null, compra_insumo: null, usinagem: null, corte_dobra: null, solda: null,
    pintura: null, montagem: null, responsavel: null, status: 'andamento',
};

const CAMPOS: { nome: 'numero' | 'data_pedido' | 'cliente' | 'fornecedor' | 'valor'; rotulo: string; tipo?: string; placeholder?: string }[] = [
    { nome: 'numero', rotulo: 'Número do pedido', placeholder: 'Ex: 4502006271' },
    { nome: 'data_pedido', rotulo: 'Data do pedido', tipo: 'date' },
    { nome: 'cliente', rotulo: 'Cliente', placeholder: 'Nome do comprador' },
    { nome: 'fornecedor', rotulo: 'Fornecedor', placeholder: 'Nome da empresa vendedora' },
    { nome: 'valor', rotulo: 'Valor total (R$)', tipo: 'number', placeholder: '0,00' },
];

/** Criar (pedido = null) ou editar um pedido completo, com seus itens. */
export default function Form({ pedido, status }: { pedido: Pedido | null; status: Record<string, string> }) {
    const existe = pedido !== null;
    const proximaChave = useRef(0);
    const novoItem = (item: Item = ITEM_VAZIO): ItemEditavel => ({ ...item, _k: proximaChave.current++ });

    const form = useForm({
        numero: pedido?.numero ?? '',
        data_pedido: pedido?.data_pedido ?? '',
        cliente: pedido?.cliente ?? '',
        fornecedor: pedido?.fornecedor ?? '',
        valor: pedido?.valor ?? '',
        itens: (pedido ? (pedido.itens ?? []) : [ITEM_VAZIO]).map((i) => novoItem(i)),
    });
    const [minimizados, setMinimizados] = useState<Set<number>>(() => new Set(existe ? form.data.itens.map((i) => i._k) : [])); // editar um pedido: itens começam minimizados
    const voltar = existe ? rotas.ver(pedido.id) : rotas.index;
    const mensagens = Object.values(form.errors);

    const alterarItem = (chave: number, campo: keyof Item, valor: string) =>
        form.setData('itens', form.data.itens.map((i) => (i._k === chave ? { ...i, [campo]: valor } : i)));

    const alternar = (chave: number) =>
        setMinimizados((atual) => {
            const proximo = new Set(atual);
            if (!proximo.delete(chave)) proximo.add(chave);
            return proximo;
        });

    const enviar = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((dados) => ({ ...dados, itens: dados.itens.map(({ _k, ...resto }) => resto) }));
        if (existe) form.put(rotas.atualizar(pedido.id));
        else form.post(rotas.salvar);
    };

    return (
        <AppLayout>
            <Head title={existe ? 'Editar pedido' : 'Criar pedido'} />

            <Card>
                <CardContent className="grid gap-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <Button asChild variant="outline">
                            <Link href={voltar}>← Voltar</Link>
                        </Button>
                        {existe && <ExcluirPedido pedido={pedido} variant="destructive" />}
                    </div>

                    <h2 className="text-2xl font-extrabold">{existe ? `Editar pedido nº ${pedido.numero ?? pedido.id}` : 'Criar pedido manual'}</h2>

                    {mensagens.length > 0 && (
                        <div role="alert" className="rounded-xl border border-l-4 border-l-destructive p-4 text-sm text-destructive">
                            <strong>Verifique os dados:</strong>
                            <ul className="mt-2 ml-5 list-disc">
                                {mensagens.map((m, i) => (
                                    <li key={i}>{m}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <form onSubmit={enviar} className="grid gap-8">
                        <div className="grid gap-4 rounded-xl border bg-muted p-6 sm:grid-cols-2">
                            {CAMPOS.map((c) => (
                                <Field
                                    key={c.nome}
                                    id={c.nome}
                                    label={c.rotulo}
                                    type={c.tipo ?? 'text'}
                                    step={c.nome === 'valor' ? '0.01' : undefined}
                                    placeholder={c.placeholder}
                                    value={form.data[c.nome]}
                                    onChange={(v) => form.setData(c.nome, v)}
                                    error={form.errors[c.nome]}
                                />
                            ))}
                        </div>

                        <div className="grid gap-4">
                            <h3 className="font-extrabold text-foreground/80">Itens do pedido</h3>
                            {form.data.itens.map((item, i) => (
                                <ItemFormCard
                                    key={item._k}
                                    item={item}
                                    posicao={i + 1}
                                    minimizado={minimizados.has(item._k)}
                                    erros={form.errors}
                                    status={status}
                                    onChange={(campo, valor) => alterarItem(item._k, campo, valor)}
                                    onToggle={() => alternar(item._k)}
                                    onRemove={() => form.setData('itens', form.data.itens.filter((x) => x._k !== item._k))}
                                />
                            ))}
                        </div>

                        <div className="flex flex-wrap justify-between gap-3 border-t pt-6">
                            <Button type="button" variant="outline" onClick={() => form.setData('itens', [...form.data.itens, novoItem()])}>
                                + Adicionar item
                            </Button>
                            <div className="flex gap-3">
                                <Button asChild variant="outline">
                                    <Link href={voltar}>Cancelar</Link>
                                </Button>
                                <Button type="submit" disabled={form.processing}>
                                    {existe ? 'Salvar alterações' : 'Criar pedido'}
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
