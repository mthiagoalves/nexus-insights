<script setup lang="ts">
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

interface WorkspaceSummary {
    id: number;
    name: string;
    slug: string;
}

interface AdAccountSummary {
    id: number;
    name: string;
    provider: string;
    external_id: string;
}

interface CampaignItem {
    id: number;
    ad_account_id: number;
    provider: string;
    external_id: string;
    name: string;
    status: string;
    objective: string | null;
    daily_budget: string | null;
    created_at: string;
    ad_account?: AdAccountSummary;
}

const props = defineProps<{
    workspace: WorkspaceSummary;
    campaigns: CampaignItem[];
}>();
</script>

<template>
    <AppLayout>

        <Head title="Campanhas" />
        <PlaceholderPattern />

        <div class="py-8">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8 space-y-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-bold text-[#e5e9eb]">
                            Campanhas
                        </h1>
                        <p class="mt-1 text-sm text-[#e5e9eb]">
                            Visualize as campanhas de anúncios vinculadas ao workspace
                            <span class="font-semibold text-[#05b7c3]">
                                "{{ props.workspace.name }}"
                            </span>.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl shadow-sm border border-gray-100">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-[#05b7c3]">
                            Lista de campanhas
                        </h2>
                        <span class="text-xs text-[#e5e9eb]">
                            Total: {{ props.campaigns.length }}
                        </span>
                    </div>

                    <div v-if="props.campaigns.length === 0" class="p-4 text-sm text-[#e5e9eb]">
                        Ainda não há campanhas cadastradas. Assim que conectar uma conta de anúncios e
                        sincronizar os dados, elas aparecerão aqui.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Campanha</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Conta</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Provedor</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Objetivo</th>
                                    <th class="px-4 py-2 text-right font-semibold text-gray-600">Budget diário</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Status</th>
                                    <th class="px-4 py-2 text-right font-semibold text-gray-600">Criada em</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="campaign in props.campaigns" :key="campaign.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-2">
                                        <div class="font-medium text-[#e5e9eb]">
                                            {{ campaign.name }}
                                        </div>
                                        <div class="text-xs text-[#e5e9eb]">
                                            ID externo: {{ campaign.external_id }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="text-gray-800">
                                            {{ campaign.ad_account?.name || '—' }}
                                        </div>
                                        <div class="text-xs text-[#e5e9eb]">
                                            {{ campaign.ad_account?.external_id || '' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-[#05b7c3]">
                                        {{ campaign.provider }}
                                    </td>
                                    <td class="px-4 py-2 text-[#05b7c3]">
                                        {{ campaign.objective || '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <span v-if="campaign.daily_budget">
                                            R$ {{ Number(campaign.daily_budget).toFixed(2) }}
                                        </span>
                                        <span v-else class="text-gray-400">
                                            —
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="{
                                                'bg-emerald-50 text-emerald-700': campaign.status === 'active',
                                                'bg-yellow-50 text-yellow-700': campaign.status === 'paused',
                                                'bg-gray-50 text-gray-600': campaign.status !== 'active' && campaign.status !== 'paused',
                                            }">
                                            {{ campaign.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs text-[#e5e9eb]">
                                        {{ new Date(campaign.created_at).toLocaleString() }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
