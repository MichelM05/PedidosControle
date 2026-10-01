import { Link, useForm } from '@inertiajs/react';
import { FileText, Plus, X } from 'lucide-react';
import { useRef } from 'react';

import { Button } from '@/components/ui/button';
import { rotas } from '@/lib/routes';

/** Card de importação do PDF (e atalho para criar pedido manual). */
export default function UploadCard() {
    const entrada = useRef<HTMLInputElement>(null);
    const form = useForm<{ pdf: File | null }>({ pdf: null });

    const limpar = () => {
        form.setData('pdf', null);
        if (entrada.current) entrada.current.value = '';
    };

    return (
        <section className="relative mb-8 overflow-hidden rounded-2xl bg-primary p-6 text-primary-foreground sm:p-8">
            <FileText className="absolute top-4 right-4 hidden size-24 text-white/10 sm:block" aria-hidden />

            <h2 className="mb-2 text-2xl font-extrabold">Importar pedido em PDF</h2>
            <p className="mb-4 max-w-3xl text-sm text-zinc-300">
                A importação extrai do PDF: número do pedido, data, cliente, fornecedor, valor total, endereços, condições comerciais e
                itens detalhados (quantidade, preço, impostos, data de entrega, item de lei, tipo de manutenção e local da prestação).
            </p>

            <form
                className="grid max-w-xl gap-4 rounded-2xl border border-white/20 bg-white/10 p-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(rotas.upload, { forceFormData: true });
                }}
            >
                <div className="flex flex-wrap items-center gap-3">
                    <Button asChild variant="secondary" size="lg" className="cursor-pointer bg-white text-zinc-900 hover:bg-zinc-100">
                        <label htmlFor="pdf">
                            <Plus /> {form.data.pdf ? 'Trocar arquivo' : 'Selecione o PDF'}
                        </label>
                    </Button>
                    <input
                        ref={entrada}
                        id="pdf"
                        type="file"
                        accept=".pdf,application/pdf"
                        className="sr-only"
                        onChange={(e) => form.setData('pdf', e.target.files?.[0] ?? null)}
                    />
                    <div className="min-w-0 text-sm">
                        <div className="flex items-center gap-2 font-bold">
                            <span className="truncate">{form.data.pdf ? form.data.pdf.name : 'Nenhum arquivo selecionado'}</span>
                            {form.data.pdf && (
                                <button type="button" onClick={limpar} aria-label="Remover arquivo" className="rounded-full bg-white/20 p-0.5 hover:bg-destructive">
                                    <X className="size-4" />
                                </button>
                            )}
                        </div>
                        <span className="text-xs text-zinc-400">No momento aceitamos apenas arquivos .PDF</span>
                    </div>
                </div>

                {form.errors.pdf && <p className="text-sm font-semibold text-orange-300">{form.errors.pdf}</p>}

                <div className="flex flex-wrap gap-3">
                    <Button type="submit" disabled={!form.data.pdf || form.processing} className="bg-brand text-zinc-900 hover:bg-brand-light">
                        {form.processing ? 'Processando...' : 'Processar PDF'}
                    </Button>
                    <Button asChild variant="outline" className="border-zinc-500 bg-transparent text-white hover:bg-white/10 hover:text-white">
                        <Link href={rotas.criar}>Criar pedido manual</Link>
                    </Button>
                </div>
            </form>
        </section>
    );
}
