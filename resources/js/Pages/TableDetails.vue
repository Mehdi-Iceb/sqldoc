<template>
    <AuthenticatedLayout>
      <!-- Header -->
      <template #header>
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-800">
            <span class="text-gray-500 font-normal">Table :</span> 
            {{ tableName }}
          </h2>
        </div>
      </template>
  
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
          <!-- Loading state -->
          <div v-if="loading" class="bg-white rounded-lg shadow-sm p-6">
            <div class="animate-pulse space-y-4">
              <div class="h-4 bg-gray-200 rounded w-1/4"></div>
              <div class="space-y-3">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded"></div>
              </div>
            </div>
          </div>
  
          <!-- Error state -->
          <div v-else-if="error" 
               class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <div class="text-red-700">{{ error }}</div>
            </div>
          </div>
  
          <!-- Success state -->
          <div v-else class="space-y-8">
  
            <!-- Description de la table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-medium text-gray-900">Description de la table</h3>
                  <button 
                    @click="saveTableStructure" 
                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="saving"
                  >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer les descriptions' }}
                  </button>
                </div>
              </div>
              <div class="p-6">
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Description optionnelle (usage, environnement, contenu...)"
                ></textarea>
              </div>
            </div>
            
            <!-- Structure de la table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"/>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900">Structure de la table</h3>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr class="bg-gray-50">
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Colonne
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nullable
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Clé
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Description
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Valeur possible
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="column in tableDetails.columns" 
                        :key="column.column_name"
                        class="hover:bg-gray-50 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ column.column_name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <span class="font-mono">{{ formatDataType(column) }}</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          column.is_nullable 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-red-100 text-red-800'
                        ]">
                          {{ column.is_nullable ? 'Oui' : 'Non' }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex gap-1">
                          <span v-if="column.is_primary_key" 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            PK
                          </span>
                          <span v-if="column.is_foreign_key"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                            </svg>
                            FK
                          </span>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-500">
                        <div class="flex items-center space-x-2">
                          <span v-if="!editingDescription[column.column_name]">
                            {{ column.description || '-' }}
                          </span>
                          <input
                            v-else
                            v-model="editingDescriptionValue"
                            type="text"
                            class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                            @keyup.enter="saveDescription(column.column_name)"
                            @keyup.esc="cancelEdit('description', column.column_name)"
                          >
                          <button
                            v-if="!editingDescription[column.column_name]"
                            @click="startEdit('description', column.column_name, column.description)"
                            class="p-1 text-gray-400 hover:text-gray-600"
                            title="Modifier la description"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                          </button>
                          <div v-else class="flex space-x-1">
                            <button
                              @click="saveDescription(column.column_name)"
                              class="p-1 text-green-600 hover:text-green-700"
                              title="Sauvegarder"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                              </svg>
                            </button>
                            <button
                              @click="cancelEdit('description', column.column_name)"
                              class="p-1 text-red-600 hover:text-red-700"
                              title="Annuler"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                              </svg>
                            </button>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-500">
                        <div class="flex items-center space-x-2">
                          <span v-if="!editingPossibleValues[column.column_name]">
                            {{ column.possible_values || '-' }}
                          </span>
                          <input
                            v-else
                            v-model="editingPossibleValuesValue"
                            type="text"
                            class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                            @keyup.enter="savePossibleValues(column.column_name)"
                            @keyup.esc="cancelEdit('possibleValues', column.column_name)"
                            placeholder="exemple of possible value"
                          >
                          <button
                            v-if="!editingPossibleValues[column.column_name]"
                            @click="startEdit('possibleValues', column.column_name, column.possible_values)"
                            class="p-1 text-gray-400 hover:text-gray-600"
                            title="Modifier les valeurs possibles"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                          </button>
                          <div v-else class="flex space-x-1">
                            <button
                              @click="savePossibleValues(column.column_name)"
                              class="p-1 text-green-600 hover:text-green-700"
                              title="Sauvegarder"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                              </svg>
                            </button>
                            <button
                              @click="cancelEdit('possibleValues', column.column_name)"
                              class="p-1 text-red-600 hover:text-red-700"
                              title="Annuler"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                              </svg>
                            </button>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
  
            <!-- Index -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900">Index</h3>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr class="bg-gray-50">
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nom
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Colonnes
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Propriétés
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="index in tableDetails.indexes" 
                        :key="index.index_name"
                        class="hover:bg-gray-50 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ index.index_name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ index.index_type }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <span class="font-mono">{{ index.columns }}</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex gap-2">
                          <span v-if="index.is_primary_key" 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Clé Primaire
                          </span>
                          <span v-if="index.is_unique" 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Unique
                          </span>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!tableDetails.indexes?.length">
                      <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                        Aucun index trouvé
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
  
            <!-- Relations -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900">Relations</h3>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr class="bg-gray-50">
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Contrainte
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Colonne
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Table référencée
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Colonne référencée
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="relation in tableDetails.relations" 
                        :key="relation.constraint_name"
                        class="hover:bg-gray-50 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ relation.constraint_name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ relation.column_name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <Link 
                          :href="route('table.details', { tableName: relation.referenced_table })"
                          class="text-blue-600 hover:text-blue-900 font-medium hover:underline"
                        >
                          {{ relation.referenced_table }}
                        </Link>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ relation.referenced_column }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div class="space-y-1 text-xs">
                          <div class="px-2 py-1 bg-gray-100 rounded">
                            ON DELETE {{ relation.delete_rule }}
                          </div>
                          <div class="px-2 py-1 bg-gray-100 rounded">
                            ON UPDATE {{ relation.update_rule }}
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!tableDetails.relations?.length">
                      <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                        Aucune relation trouvée
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
  import { ref, onMounted } from 'vue'
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
  import { Link } from '@inertiajs/vue3'
  
  const props = defineProps({
    tableName: {
      type: String,
      required: true
    }
  });
  
  // États
  const loading = ref(true);
  const error = ref(null);
  const saving = ref(false);
  const tableDetails = ref({
    description: '',
    columns: [],
    indexes: [],
    relations: []
  });
  const form = ref({
    description: ''
  });
  
  // États pour l'édition
  const editingDescription = ref({});
  const editingDescriptionValue = ref('');
  const editingPossibleValues = ref({});
  const editingPossibleValuesValue = ref('');
  
  // Chargement des détails de la table
  onMounted(async () => {
    try {
      console.log('Chargement des détails pour:', props.tableName);
      const response = await axios.get(`/table/${encodeURIComponent(props.tableName)}/details`);
      console.log('Réponse reçue:', response.data);
      
      tableDetails.value = response.data;
      form.value.description = response.data.description || '';
      
    } catch (err) {
      console.error('Erreur complète:', err);
      error.value = `Erreur: ${err.response?.data?.error || err.message}`;
    } finally {
      loading.value = false;
    }
  });
  
  // Formatage du type de données
  const formatDataType = (column) => {
    let type = column.data_type;
    
    if (['varchar', 'nvarchar', 'char', 'nchar'].includes(type.toLowerCase())) {
      if (column.max_length) {
        type += `(${column.max_length === -1 ? 'MAX' : column.max_length})`;
      }
    } else if (['decimal', 'numeric'].includes(type.toLowerCase())) {
      if (column.precision && column.scale !== undefined) {
        type += `(${column.precision},${column.scale})`;
      }
    }
    
    return type;
  };
  
  // Fonction pour sauvegarder toute la structure
  const saveTableStructure = async () => {
  try {
    saving.value = true;
    
    // Préparer les données à envoyer - uniquement les descriptions et valeurs possibles
    const tableData = {
      description: form.value.description,
      language: 'fr',
      columns: tableDetails.value.columns.map(column => ({
        column: column.column_name,
        description: column.description || null,
        rangevalues: column.possible_values || null
      }))
      // Nous ne modifions pas les index et relations, 
      // donc nous ne les incluons pas dans la sauvegarde
    };
    
    // Appel à l'API
    const response = await axios.post(`/table/${props.tableName}/save-structure`, tableData);
    
    if (response.data.success) {
      alert('Descriptions et valeurs possibles enregistrées avec succès');
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde');
    }
    
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error);
        alert('Erreur lors de la sauvegarde des descriptions et valeurs possibles');
    } finally {
        saving.value = false;
    }
  
    };

  
  // Fonction générique pour démarrer l'édition
  const startEdit = (type, columnName, currentValue) => {
    if (type === 'description') {
      editingDescription.value = { [columnName]: true };
      editingDescriptionValue.value = currentValue || '';
    } else if (type === 'possibleValues') {
      editingPossibleValues.value = { [columnName]: true };
      editingPossibleValuesValue.value = currentValue || '';
    }
  };
  
  // Fonction générique pour annuler l'édition
  const cancelEdit = (type, columnName) => {
    if (type === 'description') {
      editingDescription.value = { [columnName]: false };
      editingDescriptionValue.value = '';
    } else if (type === 'possibleValues') {
      editingPossibleValues.value = { [columnName]: false };
      editingPossibleValuesValue.value = '';
    }
  };
  
  // Fonction pour sauvegarder la description
  const saveDescription = async (columnName) => {
    try {
      const response = await axios.post(`/table/${props.tableName}/column/${columnName}/description`, {
        description: editingDescriptionValue.value
      });
      
      if (response.data.success) {
        // Mise à jour de la description dans les données locales
        const column = tableDetails.value.columns.find(c => c.column_name === columnName);
        if (column) {
          column.description = editingDescriptionValue.value;
        }
        
        // Réinitialise l'état d'édition
        cancelEdit('description', columnName);
      } else {
        throw new Error(response.data.error || 'Erreur lors de la sauvegarde de la description');
      }
    } catch (error) {
      console.error('Erreur lors de la mise à jour de la description:', error);
      alert('Erreur lors de la sauvegarde de la description');
    }
  };
  
  // Fonction pour sauvegarder les valeurs possibles
  const savePossibleValues = async (columnName) => {
    try {
      const response = await axios.post(`/table/${props.tableName}/column/${columnName}/possible-values`, {
        possible_values: editingPossibleValuesValue.value
      });
      
      if (response.data.success) {
        // Mise à jour des valeurs possibles dans les données locales
        const column = tableDetails.value.columns.find(c => c.column_name === columnName);
        if (column) {
          column.possible_values = editingPossibleValuesValue.value;
        }
        
        // Réinitialise l'état d'édition
        cancelEdit('possibleValues', columnName);
      } else {
        throw new Error(response.data.error || 'Erreur lors de la sauvegarde des valeurs possibles');
      }
    } catch (error) {
      console.error('Erreur lors de la mise à jour des valeurs possibles:', error);
      alert('Erreur lors de la sauvegarde des valeurs possibles');
    }
  };
  </script>