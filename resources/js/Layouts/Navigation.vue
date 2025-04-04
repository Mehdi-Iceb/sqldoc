<template>
  <aside class="z-20 hidden w-64 overflow-y-auto bg-blue-500 md:block flex-shrink-0">
    <!-- <div v-if="!databaseStructure" class="py-4 text-red-800 text-center">
      <svg aria-hidden="true" class="inline w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-red-500" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
      </svg>
    </div> -->
    <div class="py-4 text-white">
      <Link class="ml-6 text-lg font-bold text-gray-200" :href="route('projects.index')">
        SQL-INFO 2025 
      </Link>

      <ul class="mt-6">
        <!-- <li class="relative px-6 py-3">
          <NavLink :href="route('users.index')" :active="route().current('users.index')">
            <template #icon>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                   xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
            </template>
            Users
          </NavLink>
        </li> -->
        
        <li class="relative px-6 py-3">
          <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
            <template #icon>
              <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round"
                   stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
            </template>
            Dashboard
          </NavLink>
        </li>

        <li class="relative px-6 py-3">
          <NavLink :href="route('projects.index')" :active="route().current('projects.index')">
            <template #icon>
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
              </svg>
            </template>
            My project
          </NavLink>
        </li>
      </ul>

      <!-- Barre de recherche -->
      <div class="px-6 mt-4">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Rechercher..."
          class="w-full px-3 py-2 text-sm text-gray-900 bg-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600"
        />
      </div>

      <!-- Filtres -->
      <div class="px-6 mt-2 flex flex-wrap gap-2">
        <button
          v-for="filter in filters"
          :key="filter.type"
          @click="toggleFilter(filter.type)"
          :class="[
            'px-2 py-1 text-xs rounded-full transition-colors',
            activeFilters.includes(filter.type)
              ? 'bg-blue-700 text-white'
              : 'bg-gray-200 text-gray-800'
          ]"
        >
          {{ filter.label }} ({{ getFilterCount(filter.type) }})
        </button>
      </div>

      <!-- Section Tables -->
      <div v-if="databaseStructure && shouldShowSection('tables')" class="px-6 py-3">
        <button @click="toggleSection('tables')" class="flex items-center w-full">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path :d="isOpen.tables ? 'M19 9l-7 7-7-7' : 'M9 5l7 7-7 7'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Tables ({{ filteredTables.length }})
        </button>
        <ul v-if="isOpen.tables" class="pl-4 mt-2">
          <li v-for="table in filteredTables" :key="table.id" class="py-1 hover:text-white cursor-pointer text-sm">
            <Link 
              :href="route('table.details', { tableName: table.name })" 
              class="flex items-center text-gray-200 hover:text-white"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z" 
                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
              </svg>
              {{ table.name }}
              <span v-if="table.matchedColumn" class="ml-2 text-xs bg-blue-600 px-2 py-0.5 rounded">
                <template v-if="table.matchReason === 'primaryKey'">PK: </template>
                <template v-else-if="table.matchReason === 'foreignKey'">FK: </template>
                <template v-else-if="table.matchReason === 'column'">Col: </template>
                {{ table.matchedColumn }}
              </span>
            </Link>
          </li>
          <li v-if="filteredTables.length === 0" class="py-1 text-gray-400 italic text-sm">
            Aucune table trouvée
          </li>
        </ul>
      </div>

      <!-- Section Vues -->
      <div v-if="databaseStructure && shouldShowSection('views')" class="px-6 py-3">
        <button @click="toggleSection('views')" class="flex items-center w-full">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path :d="isOpen.views ? 'M19 9l-7 7-7-7' : 'M9 5l7 7-7 7'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Vues ({{ filteredViews.length }})
        </button>
        <ul v-if="isOpen.views" class="pl-4 mt-2">
          <li v-for="view in filteredViews" :key="view.id" class="py-1 hover:text-white cursor-pointer text-sm">
            <Link 
              :href="route('view.details', { viewName: view.name })" 
              class="flex items-center text-gray-200 hover:text-white"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" 
                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
              </svg>
              {{ view.name }}
            </Link>
          </li>
          <li v-if="filteredViews.length === 0" class="py-1 text-gray-400 italic text-sm">
            Aucune vue trouvée
          </li>
        </ul>
      </div>

      <!-- Section Fonctions -->
      <div v-if="databaseStructure && shouldShowSection('functions')" class="px-6 py-3">
        <button @click="toggleSection('functions')" class="flex items-center w-full">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path :d="isOpen.functions ? 'M19 9l-7 7-7-7' : 'M9 5l7 7-7 7'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Fonctions ({{ filteredFunctions.length }})
        </button>
        <ul v-if="isOpen.functions" class="pl-4 mt-2">
          <li v-for="func in filteredFunctions" :key="func.id" class="py-1 hover:text-white cursor-pointer text-sm">
            <Link 
              :href="route('function.details', { functionName: func.name })" 
              class="flex items-center text-gray-200 hover:text-white"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M10 20l4-16m4 4l4 4-4 4M4 4l4 4-4 4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
              </svg>
              {{ func.name }}
            </Link>
          </li>
          <li v-if="filteredFunctions.length === 0" class="py-1 text-gray-400 italic text-sm">
            Aucune fonction trouvée
          </li>
        </ul>
      </div>

      <!-- Section Procédures -->
      <div v-if="databaseStructure && shouldShowSection('procedures')" class="px-6 py-3">
        <button @click="toggleSection('procedures')" class="flex items-center w-full">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path :d="isOpen.procedures ? 'M19 9l-7 7-7-7' : 'M9 5l7 7-7 7'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Procédures ({{ filteredProcedures.length }})
        </button>
        <ul v-if="isOpen.procedures" class="pl-4 mt-2">
          <li v-for="proc in filteredProcedures" :key="proc.id" class="py-1 hover:text-white cursor-pointer text-sm">
            <Link 
              :href="route('procedure.details', { procedureName: proc.name })" 
              class="flex items-center text-gray-200 hover:text-white"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" 
                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
              </svg>
              {{ proc.name }}
            </Link>
          </li>
          <li v-if="filteredProcedures.length === 0" class="py-1 text-gray-400 italic text-sm">
            Aucune procédure trouvée
          </li>
        </ul>
      </div>

      <!-- Section Triggers -->
      <div v-if="databaseStructure && shouldShowSection('triggers')" class="px-6 py-3">
        <button @click="toggleSection('triggers')" class="flex items-center w-full">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path :d="isOpen.triggers ? 'M19 9l-7 7-7-7' : 'M9 5l7 7-7 7'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
          </svg>
          Triggers ({{ filteredTriggers.length }})
        </button>
        <ul v-if="isOpen.triggers" class="pl-4 mt-2">
          <li v-for="trigger in filteredTriggers" :key="trigger.id" class="py-1 hover:text-white cursor-pointer text-sm">
            <Link 
              :href="route('trigger.details', { triggerName: trigger.name })" 
              class="flex items-center text-gray-200 hover:text-white"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
              </svg>
              {{ trigger.name }}
            </Link>
          </li>
          <li v-if="filteredTriggers.length === 0" class="py-1 text-gray-400 italic text-sm">
            Aucun trigger trouvé
          </li>
        </ul>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import NavLink from '@/Components/NavLink.vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

// États pour les sections dépliables
const isOpen = ref({
  tables: false,
  views: false,
  functions: false,
  procedures: false,
  triggers: false,
});



// État pour les données de la base de données
const databaseStructure = ref(null);

// État pour la recherche
const searchQuery = ref('');
const activeFilters = ref(['tables', 'views', 'functions', 'procedures', 'triggers']);

// État pour stocker les colonnes de tables (initialement vide)
const tableColumns = ref({});

// Configuration des filtres
const filters = [
  { type: 'tables', label: 'Tables' },
  { type: 'views', label: 'Vues' },
  { type: 'functions', label: 'Fonctions' },
  { type: 'procedures', label: 'Procédures' },
  { type: 'triggers', label: 'Triggers' }
];

// Fonction pour basculer les sections
const toggleSection = (section) => {
  isOpen.value[section] = !isOpen.value[section];
};

// Fonction pour basculer les filtres
const toggleFilter = (filterType) => {
  const index = activeFilters.value.indexOf(filterType);
  if (index === -1) {
    activeFilters.value.push(filterType);
  } else {
    activeFilters.value.splice(index, 1);
  }
};

// Fonction pour vérifier si une section doit être affichée
const shouldShowSection = (section) => {
  return activeFilters.value.includes(section);
};

// Fonction pour obtenir le nombre d'éléments pour un type
const getFilterCount = (type) => {
  return databaseStructure.value?.[type]?.length || 0;
};

// Tables filtrées par recherche
const filteredTables = computed(() => {
  if (!databaseStructure.value?.tables) return [];
  if (!searchQuery.value) return databaseStructure.value.tables;
  
  const query = searchQuery.value.toLowerCase();
  const results = [];
  
  // Recherche dans les noms de tables
  for (const table of databaseStructure.value.tables) {
    if (table.name.toLowerCase().includes(query)) {
      results.push(table);
      continue;
    }
    
    // Si les colonnes sont disponibles, recherche aussi dans les colonnes
    const columns = tableColumns.value[table.name];
    if (columns) {
      let foundInColumns = false;
      
      for (const column of columns) {
        if (column.column_name.toLowerCase().includes(query)) {
          // Ajouter la table avec l'information sur la colonne correspondante
          results.push({
            ...table,
            matchedColumn: column.column_name
          });
          foundInColumns = true;
          break;
        }
      }
      
      if (foundInColumns) continue;
    }
  }
  
  return results;
});

// Autres computed properties pour filtrer les vues, fonctions, etc.
const filteredViews = computed(() => {
  if (!databaseStructure.value?.views) return [];
  if (!searchQuery.value) return databaseStructure.value.views;
  
  const query = searchQuery.value.toLowerCase();
  return databaseStructure.value.views.filter(view => {
    return view.name.toLowerCase().includes(query);
  });
});

const filteredFunctions = computed(() => {
  if (!databaseStructure.value?.functions) return [];
  if (!searchQuery.value) return databaseStructure.value.functions;
  
  const query = searchQuery.value.toLowerCase();
  return databaseStructure.value.functions.filter(func => {
    return func.name.toLowerCase().includes(query);
  });
});

const filteredProcedures = computed(() => {
  if (!databaseStructure.value?.procedures) return [];
  if (!searchQuery.value) return databaseStructure.value.procedures;
  
  const query = searchQuery.value.toLowerCase();
  return databaseStructure.value.procedures.filter(proc => {
    return proc.name.toLowerCase().includes(query);
  });
});

const filteredTriggers = computed(() => {
  if (!databaseStructure.value?.triggers) return [];
  if (!searchQuery.value) return databaseStructure.value.triggers;
  
  const query = searchQuery.value.toLowerCase();
  return databaseStructure.value.triggers.filter(trigger => {
    return trigger.name.toLowerCase().includes(query);
  });
});


// const searchInTableColumns = async () => {
//   if (!searchQuery.value || searchQuery.value.length < 2) return;
//   if (!databaseStructure.value?.tables) return;
  
//   try {
//     // Ne chargez que pour les tables actuellement affichées
//     for (const table of databaseStructure.value.tables) {
//       if (!tableColumns.value[table.name]) {
//         await loadTableColumns(table.name);
//       }
//     }
//   } catch (error) {
//     console.error('Erreur lors de la recherche dans les colonnes:', error);
//   }
// };

const loadTableColumns = async (tableName) => {
  if (tableColumns.value[tableName]) return;
  
  try {
    console.log(`Chargement des colonnes pour ${tableName}...`);
    const response = await axios.get(`/api/table/${encodeURIComponent(tableName)}/details`);
    if (response.data && response.data.columns) {
      console.log(`Colonnes chargées pour ${tableName}`);
      tableColumns.value[tableName] = response.data.columns;
    }
  } catch (error) {
    console.error(`Erreur lors du chargement des colonnes pour ${tableName}:`, error);
  }
};

const preloadVisibleTablesColumns = async () => {
  if (!databaseStructure.value?.tables) return;
  
  // Si la recherche est active
  if (searchQuery.value && searchQuery.value.length > 2) {
    for (const table of databaseStructure.value.tables) {
      await loadTableColumns(table.name);
    }
  }
};

// Effet lorsque la recherche change
watch(searchQuery, () => {
  // Charge les colonnes uniquement si nécessaire
  if (searchQuery.value.length > 1) {
    preloadVisibleTablesColumns();
  }
}, { immediate: false });

// Récupérer la structure de la base de données au chargement
onMounted(async () => {
  try {
    console.log("Chargement de la structure de la base de données...");
    const response = await axios.get('/database-structure');
    console.log("Réponse reçue:", response);
    databaseStructure.value = response.data;
    console.log("Structure chargée:", databaseStructure.value);
    console.log("Tables:", databaseStructure.value?.tables);
    console.log("Vues:", databaseStructure.value?.views);
    console.log("Fonctions:", databaseStructure.value?.functions);
    console.log("Procédures:", databaseStructure.value?.procedures);
    console.log("Triggers:", databaseStructure.value?.triggers);
    
    // Ouvrir la section tables par défaut
    showingTables.value = true;
  } catch (error) {
    console.error('Erreur lors du chargement de la structure:', error);
  }
});
</script>
