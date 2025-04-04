<template>
  <div>
    <div class="flex h-screen bg-gray-100">
      <Navigation :databaseStructure="databaseStructure"/>
      <NavigationMobile />

      <div class="flex flex-col flex-1 w-full">
        <TopMenu />

        <main class="h-full overflow-y-auto">
          <div class="container px-6 mx-auto grid">
            <h2 class="my-6 text-2xl font-semibold text-gray-700">
              <slot name="header" />
            </h2>

            <slot />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Navigation from './Navigation.vue';
import TopMenu from "./TopMenu.vue";
import NavigationMobile from './NavigationMobile.vue';
import axios from 'axios';

const databaseStructure = ref(null)

onMounted(async () => {
  try {
    console.log('Chargement de la structure...');
    const response = await axios.get('/database-structure');
    console.log('Données reçues:', response.data);
    databaseStructure.value = response.data;
    console.log("Structure chargée dans layout:", databaseStructure.value);
  } catch (error) {
    console.error('Erreur détaillée:', error.response?.data?.error);
    console.error('Erreur complète:', error);
  }
});

</script>
