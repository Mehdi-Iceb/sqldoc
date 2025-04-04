<template>
	<AuthenticatedLayout>
	  <template #header>
		<h2 class="text-xl font-semibold leading-tight text-gray-800">
		  Tableau de bord - Documentation de base de données
		</h2>
	  </template>
  
	  <div class="py-12">
		<div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
		  <!-- État de chargement -->
		  <div v-if="loading" class="space-y-6">
			<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
			  <div v-for="i in 8" :key="i" class="animate-pulse bg-white p-6 rounded-lg shadow-sm">
				<div class="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
				<div class="h-8 bg-gray-200 rounded w-1/4"></div>
			  </div>
			</div>
		  </div>
  
		  <!-- État d'erreur -->
		  <div v-else-if="error" class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
			<div class="flex items-center">
			  <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
				<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
			  </svg>
			  <div class="text-red-700">{{ error }}</div>
			</div>
		  </div>
  
		  <!-- Contenu du dashboard -->
		  <div v-else class="space-y-6">
			<!-- Informations de la base de données -->
			<div class="bg-white overflow-hidden shadow-sm rounded-lg">
			  <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
				<h3 class="text-lg font-medium text-gray-900">Information base de données</h3>
			  </div>
			  <div class="px-6 py-4">
				<p class="text-lg font-semibold text-gray-800">{{ dashboardData.database_name }}</p>
				<p v-if="dashboardData.database_description" class="mt-2 text-gray-600">
				  {{ dashboardData.database_description }}
				</p>
			  </div>
			</div>
  
			<!-- Statistiques globales -->
			<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
			  <!-- Objets de base -->
			  <StatCard title="Tables" :count="dashboardData.tables_count" color="blue" icon="table" />
			  <StatCard title="Vues" :count="dashboardData.views_count" color="green" icon="view" />
			  <StatCard title="Procédures stockées" :count="dashboardData.procedures_count" color="purple" icon="procedure" />
			  <StatCard title="Fonctions" :count="dashboardData.functions_count" color="indigo" icon="function" />
			  <StatCard title="Triggers" :count="dashboardData.triggers_count" color="red" icon="trigger" />
			  <StatCard title="Colonnes au total" :count="dashboardData.columns_count" color="yellow" icon="column" />
			  <StatCard title="Clés primaires" :count="dashboardData.primary_keys_count" color="amber" icon="key" />
			  <StatCard title="Clés étrangères" :count="dashboardData.foreign_keys_count" color="orange" icon="link" />
			</div>
  
			<!-- Taux de documentation -->
			<div class="bg-white overflow-hidden shadow-sm rounded-lg">
			  <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
				<h3 class="text-lg font-medium text-gray-900">État de la documentation</h3>
			  </div>
			  <div class="px-6 py-4">
				<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
				  <!-- Documentation des tables -->
				  <div>
					<h4 class="text-sm font-medium text-gray-600 mb-2">Tables documentées</h4>
					<div class="flex items-center">
					  <div class="flex-1 mr-4">
						<div class="h-2 bg-gray-200 rounded-full">
						  <div 
							class="h-2 bg-blue-500 rounded-full" 
							:style="`width: ${getPercentage(dashboardData.documented_tables_count, dashboardData.tables_count)}%`"
						  ></div>
						</div>
					  </div>
					  <div class="text-sm text-gray-600 whitespace-nowrap">
						{{ dashboardData.documented_tables_count }} / {{ dashboardData.tables_count }}
						({{ getPercentage(dashboardData.documented_tables_count, dashboardData.tables_count) }}%)
					  </div>
					</div>
				  </div>
  
				  <!-- Documentation des colonnes -->
				  <div>
					<h4 class="text-sm font-medium text-gray-600 mb-2">Colonnes documentées</h4>
					<div class="flex items-center">
					  <div class="flex-1 mr-4">
						<div class="h-2 bg-gray-200 rounded-full">
						  <div 
							class="h-2 bg-yellow-500 rounded-full" 
							:style="`width: ${getPercentage(dashboardData.documented_columns_count, dashboardData.columns_count)}%`"
						  ></div>
						</div>
					  </div>
					  <div class="text-sm text-gray-600 whitespace-nowrap">
						{{ dashboardData.documented_columns_count }} / {{ dashboardData.columns_count }}
						({{ getPercentage(dashboardData.documented_columns_count, dashboardData.columns_count) }}%)
					  </div>
					</div>
				  </div>
  
				  <!-- Documentation des vues -->
				  <div>
					<h4 class="text-sm font-medium text-gray-600 mb-2">Vues documentées</h4>
					<div class="flex items-center">
					  <div class="flex-1 mr-4">
						<div class="h-2 bg-gray-200 rounded-full">
						  <div 
							class="h-2 bg-green-500 rounded-full" 
							:style="`width: ${getPercentage(dashboardData.documented_views_count, dashboardData.views_count)}%`"
						  ></div>
						</div>
					  </div>
					  <div class="text-sm text-gray-600 whitespace-nowrap">
						{{ dashboardData.documented_views_count }} / {{ dashboardData.views_count }}
						({{ getPercentage(dashboardData.documented_views_count, dashboardData.views_count) }}%)
					  </div>
					</div>
				  </div>
  
				  <!-- Documentation des procédures stockées -->
				  <div>
					<h4 class="text-sm font-medium text-gray-600 mb-2">Procédures stockées documentées</h4>
					<div class="flex items-center">
					  <div class="flex-1 mr-4">
						<div class="h-2 bg-gray-200 rounded-full">
						  <div 
							class="h-2 bg-purple-500 rounded-full" 
							:style="`width: ${getPercentage(dashboardData.documented_procedures_count, dashboardData.procedures_count)}%`"
						  ></div>
						</div>
					  </div>
					  <div class="text-sm text-gray-600 whitespace-nowrap">
						{{ dashboardData.documented_procedures_count }} / {{ dashboardData.procedures_count }}
						({{ getPercentage(dashboardData.documented_procedures_count, dashboardData.procedures_count) }}%)
					  </div>
					</div>
				  </div>
  
				  <!-- Documentation des fonctions -->
				  <div>
					<h4 class="text-sm font-medium text-gray-600 mb-2">Fonctions documentées</h4>
					<div class="flex items-center">
					  <div class="flex-1 mr-4">
						<div class="h-2 bg-gray-200 rounded-full">
						  <div 
							class="h-2 bg-indigo-500 rounded-full" 
							:style="`width: ${getPercentage(dashboardData.documented_functions_count, dashboardData.functions_count)}%`"
						  ></div>
						</div>
					  </div>
					  <div class="text-sm text-gray-600 whitespace-nowrap">
						{{ dashboardData.documented_functions_count }} / {{ dashboardData.functions_count }}
						({{ getPercentage(dashboardData.documented_functions_count, dashboardData.functions_count) }}%)
					  </div>
					</div>
				  </div>
  
				  <!-- Documentation des triggers -->
				  <div>
					<h4 class="text-sm font-medium text-gray-600 mb-2">Triggers documentés</h4>
					<div class="flex items-center">
					  <div class="flex-1 mr-4">
						<div class="h-2 bg-gray-200 rounded-full">
						  <div 
							class="h-2 bg-red-500 rounded-full" 
							:style="`width: ${getPercentage(dashboardData.documented_triggers_count, dashboardData.triggers_count)}%`"
						  ></div>
						</div>
					  </div>
					  <div class="text-sm text-gray-600 whitespace-nowrap">
						{{ dashboardData.documented_triggers_count }} / {{ dashboardData.triggers_count }}
						({{ getPercentage(dashboardData.documented_triggers_count, dashboardData.triggers_count) }}%)
					  </div>
					</div>
				  </div>
				</div>
			  </div>
			</div>
  
			<!-- Tables les plus référencées -->
			<div class="bg-white overflow-hidden shadow-sm rounded-lg">
			  <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
				<h3 class="text-lg font-medium text-gray-900">Tables les plus référencées</h3>
			  </div>
			  <div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-gray-200">
				  <thead class="bg-gray-50">
					<tr>
					  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
						Table
					  </th>
					  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
						Nombre de références
					  </th>
					  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
						État de documentation
					  </th>
					</tr>
				  </thead>
				  <tbody class="bg-white divide-y divide-gray-200">
					<tr v-for="table in dashboardData.most_referenced_tables.slice(0, 5)" :key="table.id" class="hover:bg-gray-50">
					  <td class="px-6 py-4 whitespace-nowrap">
						<Link :href="route('table.details', { tableName: table.name })" class="text-blue-600 hover:text-blue-900">
						  {{ table.name }}
						</Link>
					  </td>
					  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
						{{ table.references_count }}
					  </td>
					  <td class="px-6 py-4 whitespace-nowrap">
						<span :class="[
						  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
						  table.is_documented ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
						]">
						  {{ table.is_documented ? 'Documentée' : 'Non documentée' }}
						</span>
					  </td>
					</tr>
					<tr v-if="!dashboardData.most_referenced_tables.length">
					  <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
						Aucune table référencée trouvée
					  </td>
					</tr>
				  </tbody>
				</table>
			  </div>
			</div>
		  </div>
		</div>
	  </div>
	</AuthenticatedLayout>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import { Link } from '@inertiajs/vue3'
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
  import StatCard from '@/Components/StatCard.vue';
  
  const loading = ref(true);
  const error = ref(null);
  const dashboardData = ref({
	database_name: '',
	database_description: '',
	tables_count: 0,
	views_count: 0,
	procedures_count: 0,
	functions_count: 0,
	triggers_count: 0,
	columns_count: 0,
	primary_keys_count: 0,
	foreign_keys_count: 0,
	documented_tables_count: 0,
	documented_columns_count: 0,
	documented_views_count: 0,
	documented_procedures_count: 0,
	documented_functions_count: 0,
	documented_triggers_count: 0,
	most_referenced_tables: []
  });
  
  // Calcul du pourcentage avec gestion des divisions par zéro
  const getPercentage = (numerator, denominator) => {
	if (!denominator) return 0;
	return Math.round((numerator / denominator) * 100);
  };
  
  // Charger les données du dashboard
  onMounted(async () => {
	try {
	  const response = await axios.get('/dashboard-data');
	  dashboardData.value = response.data;
	  loading.value = false;
	} catch (err) {
	  console.error('Erreur lors du chargement des données du dashboard:', err);
	  error.value = err.response?.data?.error || 'Erreur lors du chargement des données';
	  loading.value = false;
	}
  });
</script>
  
