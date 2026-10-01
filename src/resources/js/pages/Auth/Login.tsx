import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/Field';
import { Button } from '@/components/ui/button';
import GuestLayout from '@/layouts/GuestLayout';
import { rotas } from '@/lib/routes';
import { cn } from '@/lib/utils';

type Modo = 'entrar' | 'criar';

interface Props {
    /** Cadastro aberto (REGISTRO_ABERTO): mostra a aba "Criar conta". */
    registroAberto: boolean;
    /** Ainda não há usuários: a primeira conta criada será administradora. */
    primeiroAcesso: boolean;
}

/** Tela de entrada: "Entrar" com e-mail e senha e, se o cadastro estiver aberto, "Criar conta". */
export default function Login({ registroAberto, primeiroAcesso }: Props) {
    const [modo, setModo] = useState<Modo>(primeiroAcesso && registroAberto ? 'criar' : 'entrar');
    const entrar = useForm({ email: '', password: '', remember: false });
    const criar = useForm({ name: '', email: '', password: '', password_confirmation: '' });

    const abas: [Modo, string][] = [['entrar', 'Entrar'], ...(registroAberto ? ([['criar', 'Criar conta']] as [Modo, string][]) : [])];

    return (
        <GuestLayout>
            <Head title={modo === 'entrar' ? 'Entrar' : 'Criar conta'} />

            {abas.length > 1 && (
                <div role="tablist" className="mb-6 grid grid-cols-2 gap-1 rounded-xl bg-muted p-1">
                    {abas.map(([chave, rotulo]) => (
                        <button
                            key={chave}
                            type="button"
                            role="tab"
                            aria-selected={modo === chave}
                            onClick={() => setModo(chave)}
                            className={cn('rounded-lg px-4 py-2 text-sm font-bold transition-colors', modo === chave ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:bg-accent')}
                        >
                            {rotulo}
                        </button>
                    ))}
                </div>
            )}

            {modo === 'entrar' ? (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        entrar.post(rotas.login, { onFinish: () => entrar.reset('password') });
                    }}
                    className="grid gap-4"
                >
                    <div>
                        <h1 className="text-2xl font-extrabold">Entrar</h1>
                        <p className="text-sm text-muted-foreground">Use seu e-mail e senha para acessar o sistema.</p>
                    </div>
                    <Field id="email" label="E-mail" type="email" autoComplete="username" autoFocus value={entrar.data.email} onChange={(v) => entrar.setData('email', v)} error={entrar.errors.email} />
                    <Field id="password" label="Senha" type="password" autoComplete="current-password" value={entrar.data.password} onChange={(v) => entrar.setData('password', v)} error={entrar.errors.password} />
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={entrar.data.remember} onChange={(e) => entrar.setData('remember', e.target.checked)} className="size-4 accent-primary" />
                        Lembrar-me neste computador
                    </label>
                    <Button type="submit" size="lg" disabled={entrar.processing} className="mt-2">
                        {entrar.processing ? 'Entrando...' : 'Entrar'}
                    </Button>
                </form>
            ) : (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        criar.post(rotas.registro, { onFinish: () => criar.reset('password', 'password_confirmation') });
                    }}
                    className="grid gap-4"
                >
                    <div>
                        <h1 className="text-2xl font-extrabold">Criar conta</h1>
                        <p className="text-sm text-muted-foreground">
                            {primeiroAcesso ? 'Ainda não há contas: a primeira que você criar será a administradora.' : 'Preencha os dados para criar sua conta e já entrar no sistema.'}
                        </p>
                    </div>
                    <Field id="registro-name" label="Nome" autoComplete="name" autoFocus value={criar.data.name} onChange={(v) => criar.setData('name', v)} error={criar.errors.name} />
                    <Field id="registro-email" label="E-mail" type="email" autoComplete="username" value={criar.data.email} onChange={(v) => criar.setData('email', v)} error={criar.errors.email} />
                    <Field id="registro-password" label="Senha (mínimo 8 caracteres)" type="password" autoComplete="new-password" value={criar.data.password} onChange={(v) => criar.setData('password', v)} error={criar.errors.password} />
                    <Field id="registro-confirma" label="Repita a senha" type="password" autoComplete="new-password" value={criar.data.password_confirmation} onChange={(v) => criar.setData('password_confirmation', v)} />
                    <Button type="submit" size="lg" disabled={criar.processing} className="mt-2">
                        {criar.processing ? 'Criando...' : 'Criar conta'}
                    </Button>
                </form>
            )}
        </GuestLayout>
    );
}
