import type { ReactNode } from 'react';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

interface ConfirmDialogProps {
    /** Botão que abre a confirmação. */
    children: ReactNode;
    title: string;
    description: string;
    confirmLabel?: string;
    onConfirm: () => void;
}

/** Modal único de confirmação para ações destrutivas. */
export default function ConfirmDialog({ children, title, description, confirmLabel = 'Confirmar', onConfirm }: ConfirmDialogProps) {
    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>{description}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancelar</AlertDialogCancel>
                    <AlertDialogAction className="bg-destructive text-white hover:bg-red-800" onClick={onConfirm}>
                        {confirmLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
