<script setup lang="ts">
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    workspace: { id: number; name: string; slug: string };
    campaigns: any; // paginated
    filters: { from: string; to: string };
}>();

const filters = ref({
    from: props.filters?.from ?? '',
    to: props.filters?.to ?? '',
});

const perPage = ref(props.campaigns.per_page ?? 15);

const applyFilters = () => {
    const params = new URLSearchParams();
    if (filters.value.from) params.set('from', filters.value.from);
    if (filters.value.to) params.set('to', filters.value.to);
    if (perPage.value) params.set('per_page', String(perPage.value));
    router.get(`/campaigns/metrics?${params.toString()}`, { preserveScroll: true });
};

const exportCsv = () => {
    // exporta a página atual (backend pode providenciar csv endpoint se quiser)
    const params = new URLSearchParams();
    if (filters.value.from) params.set('from', filters.value.from);
    if (filters.value.to) params.set('to', filters.value.to);
    if (perPage.value) params.set('per_page', String(perPage.value));
    window.open(`/campaigns/metrics/export?${params.toString()}`, '_blank');
};
</script>

<template>
    <AppLayout>

        <Head title="Campanhas — Resumo" />

        <PlaceholderPattern />
        <div class="max-w-full px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Resumo por campanha</h1>
                    <p class="text-sm text-gray-500">Métricas agregadas por campanha (período)</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="date" v-model="filters.from" class="border rounded px-2 py-1 text-sm" />
                    <input type="date" v-model="filters.to" class="border rounded px-2 py-1 text-sm" />
                    <select v-model="perPage" class="border rounded px-2 py-1 text-sm">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                    </select>
                    <button @click="applyFilters" class="px-3 py-1 bg-[#05b7c3] text-white rounded">Aplicar</button>
                    <button @click="exportCsv" class="px-3 py-1 bg-gray-200 text-gray-800 rounded">Exportar CSV</button>
                </div>
            </div>

            <div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-gray-100">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#e4e9e9] text-[#313740] text-left font-semibold">
                        <tr>
                            <th class="p-2 text-left">Campanha</th>
                            <th class="p-2 text-left">Conta</th>
                            <th class="p-2 text-right">Impr.</th>
                            <th class="p-2 text-right">Cliques</th>
                            <th class="p-2 text-right">Gastos</th>
                            <th class="p-2 text-right">CTR %</th>
                            <th class="p-2 text-right">CPC</th>
                            <th class="p-2 text-right">Conv.</th>
                            <th class="p-2 text-right">CPA</th>
                            <th class="p-2 text-right">Receita</th>
                            <th class="p-2 text-right">ROAS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="c in props.campaigns.data" :key="c.id"
                            class="hover:bg-[#e4e9e9] text-[#e5e9eb] bg-black hover:text-[#313740] ">
                            <td class="p-2">{{ c.campaign_name }}</td>
                            <td class="p-2">{{ c.account_name }}</td>
                            <td class="p-2 text-right">{{ c.impressions }}</td>
                            <td class="p-2 text-right">{{ c.clicks }}</td>
                            <td class="p-2 text-right text-[#e8ae2a]">R$ {{ Number(c.spend).toFixed(2) }}</td>
                            <td class="p-2 text-right">{{ c.ctr }}%</td>
                            <td class="p-2 text-right">R$ {{ Number(c.cpc).toFixed(2) }}</td>
                            <td class="p-2 text-right">{{ c.conversions }}</td>
                            <td class="p-2 text-right">R$ {{ Number(c.cpa).toFixed(2) }}</td>
                            <td class="p-2 text-right">R$ {{ Number(c.revenue).toFixed(2) }}</td>
                            <td class="p-2 text-right text-[#05b7c3]">{{ c.roas }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">Total: {{ props.campaigns.total }}</div>
                <div class="flex items-center gap-2">
                    <button :disabled="!props.campaigns.prev_page_url"
                        @click="() => router.get(props.campaigns.prev_page_url)"
                        class="px-3 py-1 border rounded">Anterior</button>
                    <span class="text-sm">Página {{ props.campaigns.current_page }} / {{ props.campaigns.last_page
                        }}</span>
                    <button :disabled="!props.campaigns.next_page_url"
                        @click="() => router.get(props.campaigns.next_page_url)"
                        class="px-3 py-1 border rounded">Próximo</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
