import { Head, useForm } from '@inertiajs/react';

import Field from '@/components/Field';
import { Button } from '@/components/ui/button';
import GuestLayout from '@/layouts/GuestLayout';
import { rotas } from '@/lib/routes';

/** Tela de entrada: e-mail e senha. Contas são criadas por um administrador (tela Usuários) ou pelo comando usuarios:criar. */
export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    return (
        <GuestLayout>
            <Head title="Entrar" />

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.post(rotas.login, { onFinish: () => form.reset('password') });
                }}
                className="grid gap-4"
            >
                <div>
                    <h1 className="text-2xl font-extrabold">Entrar</h1>
                    <p className="text-sm text-muted-foreground">Use seu e-mail e senha para acessar o sistema.</p>
                </div>
                <Field id="email" label="E-mail" type="email" autoComplete="username" autoFocus value={form.data.email} onChange={(v) => form.setData('email', v)} error={form.errors.email} />
                <Field id="password" label="Senha" type="password" autoComplete="current-password" value={form.data.password} onChange={(v) => form.setData('password', v)} error={form.errors.password} />
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} className="size-4 accent-primary" />
                    Lembrar-me neste computador
                </label>
                <Button type="submit" size="lg" disabled={form.processing} className="mt-2">
                    {form.processing ? 'Entrando...' : 'Entrar'}
                </Button>
                <p className="text-center text-xs text-muted-foreground">Não tem acesso? Peça a um administrador para criar sua conta.</p>
            </form>
        </GuestLayout>
    );
}
