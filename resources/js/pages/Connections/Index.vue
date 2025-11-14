<script setup lang="ts">
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';

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
            <PlaceholderPattern />
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8 space-y-8">

                <div>
                    <h1 class="text-2xl font-bold text-[#e5e9eb]">
                        Conexões
                    </h1>
                    <p class="mt-1 text-sm text-[#e5e9eb]">
                        Gerencie as integrações do workspace
                        <span class="font-semibold text-[#05b7c3]">
                            "{{ props.workspace.name }}"
                        </span>
                        com plataformas externas.
                    </p>
                </div>
                <div class="rounded-xl shadow-sm border border-gray-100 p-4">
                    <h2 class="text-sm font-semibold text-[#05b7c3]">
                        Plataformas disponíveis
                    </h2>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="provider in props.availableProviders" :key="provider.key"
                            class="border border-dashed border-gray-200 rounded-lg p-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-[#e5e9eb]">
                                    {{ provider.name }}
                                </p>
                                <p class="text-xs text-[#e5e9eb] mt-1">
                                    Clique em conectar para autorizar o Nexus Insights a acessar os dados.
                                </p>
                            </div>
                            <a v-if="provider.key === 'meta'"
                                class="inline-flex items-center rounded-md border border-indigo-600 hover:border-b-emerald-600 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-emerald-600 hover:bg-emerald-200 cursor-pointer"
                                href="/auth/meta/redirect">
                                Conectar
                            </a>
                            <a v-else
                                class="inline-flex items-center rounded-md border border-indigo-600 hover:border-b-emerald-600 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-emerald-600 hover:bg-emerald-200 cursor-pointer">
                                Conectar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Conexões ativas -->
                <div class="rounded-xl shadow-sm border border-gray-100 p-4">
                    <h2 class="text-sm font-semibold text-[#05b7c3]">
                        Conexões ativas
                    </h2>

                    <div v-if="props.connections.length === 0" class="mt-3 text-sm text-[#e5e9eb]">
                        Nenhuma conexão ativa ainda. Conecte uma conta para começar a puxar métricas.
                    </div>

                    <div v-else class="mt-4 space-y-2">
                        <div v-for="conn in props.connections" :key="conn.id"
                            class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                            <div>
                                <p class="text-sm font-medium text-[e5e9eb]">
                                    {{ conn.name || conn.provider }}
                                </p>
                                <p class="text-xs text-[#e5e9eb]">
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
