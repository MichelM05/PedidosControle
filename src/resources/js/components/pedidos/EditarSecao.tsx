import { useForm } from '@inertiajs/react';

import Field from '@/components/Field';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { rotas } from '@/lib/routes';
import type { BlocoChave, Pedido, Rotulos } from '@/types';

export type Secao = 'resumo' | 'condicoes' | 'observacoes' | BlocoChave;

interface Campo {
    nome: string;
    rotulo: string;
    tipo?: 'text' | 'number' | 'date' | 'email' | 'textarea';
    step?: string;
}

const MONETARIOS = ['total_icms', 'total_ipi', 'total_produtos'];

function campos(secao: Secao, rotulos: Rotulos): Campo[] {
    switch (secao) {
        case 'resumo':
            return [
                { nome: 'numero', rotulo: 'Número do pedido' },
                { nome: 'data_pedido', rotulo: 'Data do pedido', tipo: 'date' },
                { nome: 'valor', rotulo: 'Valor total (R$)', tipo: 'number', step: '0.01' },
                { nome: 'cliente', rotulo: 'Cliente' },
                { nome: 'fornecedor', rotulo: 'Fornecedor' },
            ];
        case 'condicoes':
            return Object.entries(rotulos.condicoes).map(([nome, rotulo]) => ({
                nome,
                rotulo,
                ...(MONETARIOS.includes(nome) && { tipo: 'number' as const, step: '0.01' }),
                ...(nome === 'contato_email' && { tipo: 'email' as const }),
            }));
        case 'observacoes':
            return [{ nome: 'observacoes', rotulo: 'Observações', tipo: 'textarea' }];
        default:
            return [
                { nome: 'nome', rotulo: 'Nome' },
                { nome: 'endereco', rotulo: 'Endereço (uma linha por linha)', tipo: 'textarea' },
                { nome: 'cnpj', rotulo: 'CNPJ' },
                { nome: 'ie', rotulo: 'IE' },
                { nome: 'fone', rotulo: 'Fone' },
            ];
    }
}

function titulo(secao: Secao, rotulos: Rotulos): string {
    if (secao === 'resumo') return 'Dados do pedido';
    if (secao === 'condicoes') return 'Condições do pedido';
    if (secao === 'observacoes') return 'Observações';
    return rotulos.blocos[secao];
}

function valoresIniciais(secao: Secao, pedido: Pedido): Record<string, string> {
    const extras = pedido.dados_extras ?? {};
    const texto = (v: unknown) => (v === null || v === undefined ? '' : String(v));

    if (secao === 'resumo') {
        return {
            numero: texto(pedido.numero),
            data_pedido: texto(pedido.data_pedido),
            valor: texto(pedido.valor),
            cliente: texto(pedido.cliente),
            fornecedor: texto(pedido.fornecedor),
        };
    }
    if (secao === 'condicoes' || secao === 'observacoes') {
        return Object.fromEntries(Object.entries(extras).filter(([, v]) => typeof v === 'string').map(([k, v]) => [k, texto(v)]));
    }

    const bloco = extras.blocos?.[secao] ?? {};
    const nomeBase = secao === 'fornecedor' ? pedido.fornecedor : secao === 'faturamento' ? pedido.cliente : null;

    return {
        nome: texto(bloco.nome ?? nomeBase),
        endereco: (bloco.endereco ?? []).join('\n'),
        cnpj: texto(bloco.cnpj),
        ie: texto(bloco.ie),
        fone: texto(bloco.fone),
    };
}

interface Props {
    secao: Secao;
    pedido: Pedido;
    rotulos: Rotulos;
    onClose: () => void;
}

/** Modal de edição de uma seção do pedido (sem os itens). Use `key={secao}` para reiniciar os valores. */
export default function EditarSecao({ secao, pedido, rotulos, onClose }: Props) {
    const lista = campos(secao, rotulos);
    const base = valoresIniciais(secao, pedido);
    const form = useForm<Record<string, string>>({ secao, ...Object.fromEntries(lista.map((c) => [c.nome, base[c.nome] ?? ''])) });

    return (
        <Dialog open onOpenChange={(aberto) => !aberto && onClose()}>
            <DialogContent className="max-w-xl">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.patch(rotas.dados(pedido.id), { preserveScroll: true, onSuccess: onClose });
                    }}
                    className="grid gap-4"
                >
                    <DialogHeader>
                        <DialogTitle>{titulo(secao, rotulos)}</DialogTitle>
                        <DialogDescription className="sr-only">Edite os campos e salve.</DialogDescription>
                    </DialogHeader>

                    <div className="grid max-h-[60vh] gap-3 overflow-y-auto p-1 sm:grid-cols-2">
                        {lista.map((c) => (
                            <Field
                                key={c.nome}
                                id={`${secao}-${c.nome}`}
                                label={c.rotulo}
                                type={c.tipo === 'textarea' ? undefined : c.tipo}
                                step={c.step}
                                multiline={c.tipo === 'textarea'}
                                className={c.tipo === 'textarea' ? 'sm:col-span-2' : undefined}
                                value={form.data[c.nome]}
                                onChange={(v) => form.setData(c.nome, v)}
                                error={form.errors[c.nome]}
                            />
                        ))}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Salvar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
