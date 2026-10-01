import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

import Field from '@/components/Field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout';
import * as f from '@/lib/format';
import { rotas } from '@/lib/routes';
import type { Usuario } from '@/types';

/** Formulário de criar/editar usuário (em modal). `usuario = null` cria; com usuário, edita. */
function FormUsuario({ usuario, onClose }: { usuario: Usuario | null; onClose: () => void }) {
    const form = useForm({
        name: usuario?.name ?? '',
        email: usuario?.email ?? '',
        password: '',
        is_admin: usuario?.is_admin ?? false,
        ativo: usuario?.ativo ?? true,
    });

    const enviar = (e: React.FormEvent) => {
        e.preventDefault();
        const opcoes = { preserveScroll: true, onSuccess: onClose };
        if (usuario) form.patch(rotas.usuario(usuario.id), opcoes);
        else form.post(rotas.usuarios, opcoes);
    };

    return (
        <Dialog open onOpenChange={(aberto) => !aberto && onClose()}>
            <DialogContent className="max-w-lg">
                <form onSubmit={enviar} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{usuario ? 'Editar usuário' : 'Novo usuário'}</DialogTitle>
                        <DialogDescription>{usuario ? 'Deixe a senha em branco para mantê-la.' : 'A pessoa entra com este e-mail e a senha definida aqui.'}</DialogDescription>
                    </DialogHeader>

                    <Field id="usuario-name" label="Nome" value={form.data.name} onChange={(v) => form.setData('name', v)} error={form.errors.name} />
                    <Field id="usuario-email" label="E-mail" type="email" value={form.data.email} onChange={(v) => form.setData('email', v)} error={form.errors.email} />
                    <Field
                        id="usuario-password"
                        label={usuario ? 'Nova senha (opcional)' : 'Senha (mínimo 8 caracteres)'}
                        type="password"
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(v) => form.setData('password', v)}
                        error={form.errors.password}
                    />

                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={form.data.is_admin} onChange={(e) => form.setData('is_admin', e.target.checked)} className="size-4 accent-primary" />
                        Administrador (pode gerenciar usuários)
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={form.data.ativo} onChange={(e) => form.setData('ativo', e.target.checked)} className="size-4 accent-primary" />
                        Ativo (usuário desativado não consegue entrar)
                    </label>
                    {form.errors.ativo && <p className="text-sm text-destructive">{form.errors.ativo}</p>}

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

export default function Index({ usuarios }: { usuarios: Usuario[] }) {
    // undefined = modal fechado; null = novo usuário; objeto = editando
    const [editando, setEditando] = useState<Usuario | null | undefined>(undefined);

    return (
        <AppLayout>
            <Head title="Usuários" />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-3">
                    <CardTitle className="text-2xl font-extrabold">Usuários</CardTitle>
                    <Button onClick={() => setEditando(null)}>+ Novo usuário</Button>
                </CardHeader>
                <CardContent>
                    <div className="rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/60 text-xs uppercase">
                                    <TableHead className="px-4 py-3">Nome</TableHead>
                                    <TableHead className="px-4 py-3">E-mail</TableHead>
                                    <TableHead className="px-4 py-3">Perfil</TableHead>
                                    <TableHead className="px-4 py-3">Situação</TableHead>
                                    <TableHead className="px-4 py-3">Criado em</TableHead>
                                    <TableHead className="px-4 py-3 text-right">Ações</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {usuarios.map((u) => (
                                    <TableRow key={u.id} className={u.ativo ? undefined : 'text-muted-foreground'}>
                                        <TableCell className="px-4 py-3 font-medium">{u.name}</TableCell>
                                        <TableCell className="px-4 py-3">{u.email}</TableCell>
                                        <TableCell className="px-4 py-3">{u.is_admin ? <Badge className="bg-sage text-ink">Administrador</Badge> : <Badge variant="outline">Usuário</Badge>}</TableCell>
                                        <TableCell className="px-4 py-3">{u.ativo ? 'Ativo' : <Badge variant="outline">Desativado</Badge>}</TableCell>
                                        <TableCell className="px-4 py-3">{f.data(u.created_at)}</TableCell>
                                        <TableCell className="px-4 py-3 text-right">
                                            <Button variant="outline" size="sm" onClick={() => setEditando(u)}>
                                                Editar
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <p className="mt-3 text-xs text-muted-foreground">Usuários não são apagados: desative para impedir o acesso.</p>
                </CardContent>
            </Card>

            {editando !== undefined && <FormUsuario key={editando?.id ?? 'novo'} usuario={editando} onClose={() => setEditando(undefined)} />}
        </AppLayout>
    );
}
