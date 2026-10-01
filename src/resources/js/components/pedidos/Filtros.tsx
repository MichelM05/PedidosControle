import { router } from '@inertiajs/react';
import { Filter } from 'lucide-react';
import { useState } from 'react';

import Field from '@/components/Field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { rotas } from '@/lib/routes';

const CAMPOS = [
    { nome: 'numero', rotulo: 'Número' },
    { nome: 'cliente', rotulo: 'Cliente' },
    { nome: 'fornecedor', rotulo: 'Fornecedor' },
    { nome: 'data_inicio', rotulo: 'Data inicial', type: 'date' },
    { nome: 'data_fim', rotulo: 'Data final', type: 'date' },
    { nome: 'valor_min', rotulo: 'Valor mín. (R$)', placeholder: 'Ex: 1000,00' },
    { nome: 'valor_max', rotulo: 'Valor máx. (R$)', placeholder: 'Ex: 5000,00' },
] as const;

type Filtros = Record<(typeof CAMPOS)[number]['nome'], string>;

export default function Filtros({ filtros, erros }: { filtros: Partial<Filtros>; erros: Record<string, string> }) {
    const [valores, setValores] = useState<Partial<Filtros>>(filtros);

    const aplicar = (e: React.FormEvent) => {
        e.preventDefault();
        const preenchidos = Object.fromEntries(Object.entries(valores).filter(([, v]) => v));
        router.get(rotas.index, preenchidos, { preserveState: true });
    };

    const limpar = () => {
        setValores({});
        router.get(rotas.index);
    };

    return (
        <Card className="mb-8">
            <CardHeader>
                <CardTitle className="text-2xl font-extrabold">Filtros</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={aplicar} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {CAMPOS.map((c) => (
                        <Field
                            key={c.nome}
                            id={`filtro-${c.nome}`}
                            label={c.rotulo}
                            type={'type' in c ? c.type : 'text'}
                            placeholder={'placeholder' in c ? c.placeholder : undefined}
                            value={valores[c.nome]}
                            onChange={(v) => setValores((atual) => ({ ...atual, [c.nome]: v }))}
                            error={erros[c.nome]}
                        />
                    ))}
                    <div className="flex flex-wrap justify-end gap-3 border-t pt-4 sm:col-span-2 lg:col-span-4">
                        <Button type="submit">
                            <Filter /> Filtrar resultados
                        </Button>
                        <Button type="button" variant="outline" onClick={limpar}>
                            Limpar filtros
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
