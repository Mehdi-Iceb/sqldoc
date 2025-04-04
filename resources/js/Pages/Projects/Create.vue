<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    dbTypes: Object
});

const form = useForm({
    name: '',
    db_type: ''
});

const submit = () => {
    form.post(route('projects.store'));
};
</script>

<template>
    <Head title="Nouveau projet" />
    
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Créer un nouveau projet
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="max-w-lg mx-auto">
                        <div class="mb-6">
                            <InputLabel for="name" value="Nom du projet" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="mb-6">
                            <InputLabel for="db_type" value="Type de base de données" />
                            <div class="mt-1">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div 
                                        v-for="(label, value) in dbTypes" 
                                        :key="value"
                                        class="border rounded-lg p-4 cursor-pointer"
                                        :class="form.db_type === value ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                                        @click="form.db_type = value"
                                    >
                                        <div class="flex items-center justify-center">
                                            <input
                                                type="radio"
                                                :id="value"
                                                :value="value"
                                                v-model="form.db_type"
                                                class="hidden"
                                            />
                                            <label :for="value" class="text-center w-full cursor-pointer">
                                                <span class="block font-medium text-gray-900">{{ label }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.db_type" />
                        </div>

                        <div class="flex items-center justify-end mt-8 space-x-4">
                            <Link
                                :href="route('projects.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Créer le projet
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>