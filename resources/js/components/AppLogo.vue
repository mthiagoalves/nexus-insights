<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const page = usePage();

const currentWorkspace = computed(() => page.props.currentWorkspace as any || null);
const user = computed(() => page.props.auth.user as any || null);
</script>

<template>
    <div class="flex size-8 items-center justify-center rounded-md">
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div v-if="currentWorkspace" class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">  {{ (user as any).name }} - Workspace</span>
    </div>
</template>
