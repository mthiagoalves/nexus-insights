<script setup lang="ts">
import App from '@/actions/App';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

interface WorkspaceSummary {
    id: number;
    name: string;
    slug: string;
}

interface Connection {
    id: number;
    provider: string;
    name: string | null;
    created_at: string;
    extra_data: Record<string, any> | null;
}

interface ProviderOption {
    key: string;
    name: string;
}

const props = defineProps<{
    workspace: WorkspaceSummary;
    connections: Connection[];
    availableProviders: ProviderOption[];
}>();
</script>

<template>
    <AppLayout>

        <Head title="Conexões" />

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Conexões
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Gerencie as integrações do workspace
                        <span class="font-semibold text-gray-700">
                            "{{ props.workspace.name }}"
                        </span>
                        com plataformas externas.
                    </p>
                </div>

                <!-- Plataformas disponíveis -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h2 class="text-sm font-semibold text-gray-700">
                        Plataformas disponíveis
                    </h2>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="provider in props.availableProviders" :key="provider.key"
                            class="border border-dashed border-gray-200 rounded-lg p-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ provider.name }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Clique em conectar para autorizar o Nexus Insights a acessar os dados.
                                </p>
                            </div>
                            <button type="button"
                                class="inline-flex items-center rounded-md border border-indigo-600 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:bg-indigo-50">
                                Conectar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Conexões ativas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h2 class="text-sm font-semibold text-gray-700">
                        Conexões ativas
                    </h2>

                    <div v-if="props.connections.length === 0" class="mt-3 text-sm text-gray-500">
                        Nenhuma conexão ativa ainda. Conecte uma conta para começar a puxar métricas.
                    </div>

                    <div v-else class="mt-4 space-y-2">
                        <div v-for="conn in props.connections" :key="conn.id"
                            class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ conn.name || conn.provider }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Provedor: {{ conn.provider }} • Desde: {{ new Date(conn.created_at).toLocaleString()
                                    }}
                                </p>
                            </div>
                            <button type="button" class="text-xs text-red-500 hover:text-red-600">
                                Desconectar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
