import type { ComponentProps } from 'react';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

interface FieldProps {
    label: string;
    value: string | null | undefined;
    onChange: (valor: string) => void;
    error?: string;
    multiline?: boolean;
    className?: string;
}

/** Rótulo + campo + mensagem de erro, no padrão visual de todos os formulários. */
export default function Field({
    label,
    value,
    onChange,
    error,
    multiline,
    className,
    ...props
}: FieldProps & Omit<ComponentProps<'input'>, 'value' | 'onChange'>) {
    const id = props.id ?? `campo-${label}`;
    const comum = { id, value: value ?? '', 'aria-invalid': !!error };

    return (
        <div className={cn('grid gap-1.5', className)}>
            <Label htmlFor={id} className="text-xs font-bold uppercase tracking-wide text-muted-foreground">
                {label}
            </Label>
            {multiline ? (
                <Textarea {...comum} rows={4} onChange={(e) => onChange(e.target.value)} />
            ) : (
                <Input {...comum} {...props} onChange={(e) => onChange(e.target.value)} />
            )}
            {error && <span className="text-xs text-destructive">{error}</span>}
        </div>
    );
}
