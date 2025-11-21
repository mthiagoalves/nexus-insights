<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import DashboardChart from '@/components/DashboardChart.vue';
import { ref, computed } from 'vue';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface MetricsProps {
    totalSpend: number;
    sessions: number;
    conversions: number;
    revenue: number;
}

interface WorkspaceSummary {
    id: number;
    name: string;
    slug: string;
}

type SeriesPoint = { date: string; spend: number; revenue: number; impressions?: number; clicks?: number };

const page = usePage();

const metrics = computed(() => page.props.metrics as MetricsProps);
const currentWorkspace = computed(
    () => page.props.currentWorkspace as WorkspaceSummary | null,
);

// series + filters vindos do backend (DashboardController deve enviar 'series' e 'filters')
const series = computed(() => (page.props.series ?? []) as SeriesPoint[]);

type PageFilters = { from?: string; to?: string } | undefined;

const pageFilters = page.props.filters as PageFilters;

const filters = ref<{ from: string; to: string }>({
    from: pageFilters?.from ?? '',
    to: pageFilters?.to ?? '',
});



const applying = ref(false);

const applyFilters = async () => {
    // garante formato YYYY-MM-DD ou vazio
    const params = new URLSearchParams();
    if (filters.value.from) params.set('from', filters.value.from);
    if (filters.value.to) params.set('to', filters.value.to);

    applying.value = true;
    try {
        // Faz uma navegação Inertia para atualizar os props do backend
        await router.get(`/dashboard?${params.toString()}`, {
            preserveState: false,
            preserveScroll: true,
        });
    } finally {
        applying.value = false;
    }
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header com filtros -->
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-white">Visão geral</h2>
                    <p class="text-sm text-sidebar-text/80">Painel com métricas agregadas por período</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="date" v-model="filters.from"
                        class="rounded-md border bg-sidebar-input px-3 py-2 text-sm text-white"
                        :max="filters.to || undefined" />
                    <input type="date" v-model="filters.to"
                        class="rounded-md border bg-sidebar-input px-3 py-2 text-sm text-white"
                        :min="filters.from || undefined" />
                    <button @click="applyFilters"
                        class="ml-2 inline-flex items-center rounded-md bg-[#05b7c3] px-3 py-2 text-sm font-semibold text-white"
                        :class="{ 'opacity-60 pointer-events-none': applying }">
                        {{ applying ? 'Aplicando...' : 'Aplicar' }}
                    </button>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <PlaceholderPattern />
                    <p class="text-xs font-semibold text-white uppercase">
                        Investimento total (ads)
                    </p>
                    <p class="mt-2 text-2xl font-bold text-white">
                        R$ {{ metrics.totalSpend.toFixed(2) }}
                    </p>
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <PlaceholderPattern />
                    <p class="text-xs font-semibold text-white uppercase">
                        Sessões na página de vendas
                    </p>
                    <p class="mt-2 text-2xl font-bold text-white">
                        {{ metrics.sessions }}
                    </p>
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <PlaceholderPattern />
                    <p class="text-xs font-semibold text-white uppercase">
                        Conversões
                    </p>
                    <p class="mt-2 text-2xl font-bold text-white">
                        {{ metrics.conversions }}
                    </p>
                </div>
            </div>

            <!-- Chart + receita -->
            <div class="grid gap-4 md:grid-cols-">
                <div
                    class="relative col-span-2 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4 bg-gradient-to-b from-white/3 to-transparent">
                    <div class="mb-2">
                        <p class="text-xs font-semibold text-white uppercase">Gostos × Receita (por dia)</p>
                        <p class="text-sm text-sidebar-text/80">Período: {{ filters.from || '—' }} → {{ filters.to ||
                            '—' }}</p>
                    </div>

                    <DashboardChart :series="series" />
                </div>

                <div
                    class="relative rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4 flex flex-col justify-between">
                    <div>
                        <p class="text-xs font-semibold text-white uppercase">
                            Receita total
                        </p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600">
                            R$ {{ metrics.revenue.toFixed(2) }}
                        </p>
                    </div>

                    <div class="mt-6 text-sm text-sidebar-text/80">
                        <p><strong>Workspace:</strong> {{ currentWorkspace ? currentWorkspace.name : '—' }}</p>
                        <p class="mt-2">Dados atualizados via sincronização de métricas da Meta.</p>
                    </div>
                </div>
            </div>

            <!-- espaço futuro: resumo por campanha -->
            <div
                class="relative min-h-[20vh] rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                <PlaceholderPattern />
                <p class="text-xs font-semibold text-white uppercase">Resumo por campanha</p>
                <p class="mt-2 text-sm text-sidebar-text/80">Em breve: tabela com CTR, CPC, CPA, ROAS por campanha.</p>
            </div>
        </div>
    </AppLayout>
</template>
