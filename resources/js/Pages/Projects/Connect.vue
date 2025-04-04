<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    project: Object
});

const authMode = ref(props.project.db_type === 'sqlserver' ? 'windows' : 'sql');

const form = useForm({
    server: '',
    database: '',
    port: props.project.db_type === 'mysql' ? '3306' : props.project.db_type === 'pgsql' ? '5432' : '',
    authMode: authMode.value,
    username: '',
    password: '',
    description: ''
});

const showAuthFields = computed(() => {
    return props.project.db_type !== 'sqlserver' || (props.project.db_type === 'sqlserver' && authMode.value === 'sql');
});

const submit = () => {
    form.post(route('projects.handle-connect', props.project.id));
};

const getDbTypeName = (type) => {
    const types = {
        'mysql': 'MySQL',
        'sqlserver': 'SQL Server',
        'pgsql': 'PostgreSQL'
    };
    return types[type] || type;
};

const updateAuthMode = (value) => {
    form.authMode = value;
    authMode.value = value;
};
</script>

<template>
    <Head title="Connexion au projet" />
    
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Connexion au projet: {{ project.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="mb-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">Informations de connexion</h3>
                                <p class="text-sm text-gray-600">Type de base de données: {{ getDbTypeName(project.db_type) }}</p>
                            </div>
                            <Link
                                :href="route('projects.index')"
                                class="inline-flex items-center px-3 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Retour aux projets
                            </Link>
                        </div>
                        
                        <form @submit.prevent="submit" class="max-w-lg">
                            <div class="mb-4">
                                <InputLabel for="server" value="Serveur" />
                                <TextInput
                                    id="server"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.server"
                                    required
                                    placeholder="localhost ou adresse IP"
                                />
                                <InputError class="mt-2" :message="form.errors.server" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="database" value="Base de données" />
                                <TextInput
                                    id="database"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.database"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.database" />
                            </div>

                            <div v-if="project.db_type !== 'sqlserver'" class="mb-4">
                                <InputLabel for="port" value="Port" />
                                <TextInput
                                    id="port"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.port"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.port" />
                            </div>

                            <div v-if="project.db_type === 'sqlserver'" class="mb-4">
                                <InputLabel value="Mode d'authentification" />
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <input
                                            id="windows-auth"
                                            type="radio"
                                            value="windows"
                                            name="authMode"
                                            :checked="authMode === 'windows'"
                                            @change="updateAuthMode('windows')"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        />
                                        <label for="windows-auth" class="ml-2 block text-sm text-gray-900">
                                            Authentification Windows
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input
                                            id="sql-auth"
                                            type="radio"
                                            value="sql"
                                            name="authMode"
                                            :checked="authMode === 'sql'"
                                            @change="updateAuthMode('sql')"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        />
                                        <label for="sql-auth" class="ml-2 block text-sm text-gray-900">
                                            Authentification SQL Server
                                        </label>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.authMode" />
                            </div>

                            <div v-if="showAuthFields" class="mb-4">
                                <InputLabel for="username" value="Nom d'utilisateur" />
                                <TextInput
                                    id="username"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.username"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.username" />
                            </div>

                            <div v-if="showAuthFields" class="mb-4">
                                <InputLabel for="password" value="Mot de passe" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.password"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="description" value="Description (optionnelle)" />
                                <TextareaInput
                                    id="description"
                                    class="mt-1 block w-full"
                                    v-model="form.description"
                                    rows="4"
                                />
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <div class="flex items-center justify-end mt-6">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Se connecter
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>