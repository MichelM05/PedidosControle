import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';

// App pequeno: carrega todas as páginas de uma vez (sem divisão em chunks por rota).
const paginas = import.meta.glob<{ default: ComponentType }>('./pages/**/*.tsx', { eager: true });

createInertiaApp({
    resolve: (nome) => paginas[`./pages/${nome}.tsx`],
    progress: { color: '#f56218' },
});
