<template>
    <Head title="Projets" />
    
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Projets
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-end mb-6">
                    <Link
                        :href="route('projects.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Créer un nouveau projet
                    </Link>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div v-if="projects.length === 0" class="text-center py-8">
                        <h3 class="text-lg font-medium text-gray-900">Vous n'avez pas encore de projets</h3>
                        <p class="mt-2 text-gray-600">Commencez par créer un nouveau projet en cliquant sur le bouton ci-dessus.</p>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div 
                            v-for="project in projects" 
                            :key="project.id"
                            class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <div class="p-4 border-b bg-gray-50">
                                <h3 class="text-lg font-semibold text-gray-800">{{ project.name }}</h3>
                                <p class="text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ getBdTypeName(project.db_type) }}
                                    </span>
                                </p>
                            </div>
                            <div class="p-4">
                                <div class="flex justify-end space-x-2">
                                    <Link
                                        :href="route('projects.open', project.id)"
                                        class="inline-flex items-center px-3 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Ouvrir
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    projects: Array
});

// Fonction pour obtenir le nom formaté du type de base de données
const getBdTypeName = (type) => {
    const types = {
        'mysql': 'MySQL',
        'sqlserver': 'SQL Server',
        'pgsql': 'PostgreSQL'
    };
    return types[type] || type;
};
</script>