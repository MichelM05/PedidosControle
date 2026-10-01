import { Head, useForm, usePage } from '@inertiajs/react';

import Field from '@/components/Field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout';
import { rotas } from '@/lib/routes';
import type { PageProps } from '@/types';

/** Meu perfil: o usuário troca nome, e-mail e senha. */
export default function Edit() {
    const { user } = usePage<PageProps>().props.auth;
    const dados = useForm({ name: user?.name ?? '', email: user?.email ?? '' });
    const senha = useForm({ senha_atual: '', password: '', password_confirmation: '' });

    return (
        <AppLayout>
            <Head title="Meu perfil" />

            <div className="mx-auto grid max-w-2xl gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-2xl font-extrabold">Meu perfil</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                dados.patch(rotas.perfil, { preserveScroll: true });
                            }}
                            className="grid gap-4"
                        >
                            <Field id="perfil-name" label="Nome" value={dados.data.name} onChange={(v) => dados.setData('name', v)} error={dados.errors.name} />
                            <Field id="perfil-email" label="E-mail" type="email" value={dados.data.email} onChange={(v) => dados.setData('email', v)} error={dados.errors.email} />
                            <div>
                                <Button type="submit" disabled={dados.processing}>
                                    Salvar dados
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-xl font-extrabold">Alterar senha</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                senha.put(rotas.perfilSenha, { preserveScroll: true, onSuccess: () => senha.reset() });
                            }}
                            className="grid gap-4"
                        >
                            <Field id="senha-atual" label="Senha atual" type="password" autoComplete="current-password" value={senha.data.senha_atual} onChange={(v) => senha.setData('senha_atual', v)} error={senha.errors.senha_atual} />
                            <Field id="senha-nova" label="Nova senha (mínimo 8 caracteres)" type="password" autoComplete="new-password" value={senha.data.password} onChange={(v) => senha.setData('password', v)} error={senha.errors.password} />
                            <Field id="senha-confirma" label="Repita a nova senha" type="password" autoComplete="new-password" value={senha.data.password_confirmation} onChange={(v) => senha.setData('password_confirmation', v)} />
                            <div>
                                <Button type="submit" disabled={senha.processing}>
                                    Alterar senha
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
