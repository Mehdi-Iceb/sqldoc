<template>
  <AuthenticatedLayout>
    <div class="space-y-8">
      <!-- Description de la fonction -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Description de la fonction</h3>
            <button 
              @click="saveDescription"
              class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="saving"
            >
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </div>
        <div class="p-6">
          <textarea
            v-model="descriptionText"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Description optionnelle (usage, entrées/sorties, règles métier...)"
          ></textarea>
        </div>
      </div>
      
      <!-- Méta-informations -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900">Informations</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <h4 class="text-sm font-semibold text-gray-500 mb-1">Nom</h4>
              <div class="bg-gray-50 p-3 rounded">
                <p class="text-gray-800">{{ functionData.name || 'Non spécifié' }}</p>
              </div>
            </div>
            <div>
              <h4 class="text-sm font-semibold text-gray-500 mb-1">Type</h4>
              <div class="bg-gray-50 p-3 rounded">
                <p class="text-gray-800">{{ functionData.function_type || 'Non spécifié' }}</p>
              </div>
            </div>
            <div>
              <h4 class="text-sm font-semibold text-gray-500 mb-1">Type de retour</h4>
              <div class="bg-gray-50 p-3 rounded">
                <p class="text-gray-800">{{ functionData.return_type || 'Non spécifié' }}</p>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <h4 class="text-sm font-semibold text-gray-500 mb-1">Date de création</h4>
              <div class="bg-gray-50 p-3 rounded">
                <p class="text-gray-800">{{ formatDate(functionData.create_date) }}</p>
              </div>
            </div>
            <div>
              <h4 class="text-sm font-semibold text-gray-500 mb-1">Dernière modification</h4>
              <div class="bg-gray-50 p-3 rounded">
                <p class="text-gray-800">{{ formatDate(functionData.modify_date) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Onglets -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <a 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm cursor-pointer'
            ]"
          >
            {{ tab.name }}
          </a>
        </nav>
      </div>
      
      <!-- Paramètres -->
      <div v-show="activeTab === 'parameters'" class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900">Paramètres</h3>
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
                  Sortie
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Description
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="param in functionData.parameters" 
                  :key="param.parameter_id"
                  class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ param.parameter_name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  <span class="font-mono">{{ param.data_type }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    param.is_output 
                      ? 'bg-blue-100 text-blue-800' 
                      : 'bg-gray-100 text-gray-800'
                  ]">
                    {{ param.is_output ? 'Oui' : 'Non' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                  <div class="flex items-center space-x-2">
                    <span v-if="!editingParamId || editingParamId !== param.parameter_id">
                      {{ param.description || '-' }}
                    </span>
                    <input
                      v-else
                      v-model="editingValue"
                      type="text"
                      class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                      @keyup.enter="saveParamDescription(param)"
                      @keyup.esc="cancelEdit()"
                    >
                    <button
                      v-if="!editingParamId || editingParamId !== param.parameter_id"
                      @click="startEdit(param)"
                      class="p-1 text-gray-400 hover:text-gray-600"
                      title="Modifier la description"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <div v-else class="flex space-x-1">
                      <button
                        @click="saveParamDescription(param)"
                        class="p-1 text-green-600 hover:text-green-700"
                        title="Sauvegarder"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                      </button>
                      <button
                        @click="cancelEdit()"
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
              <tr v-if="!functionData.parameters || functionData.parameters.length === 0">
                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                  Aucun paramètre trouvé
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Définition -->
      <div v-show="activeTab === 'definition'" class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900">Définition</h3>
          </div>
        </div>
        <div class="p-6">
          <div class="bg-gray-900 p-4 rounded overflow-auto max-h-96">
            <pre class="text-gray-200 text-sm whitespace-pre-wrap">{{ functionData.definition || 'Définition non disponible' }}</pre>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue';
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
  import { router } from '@inertiajs/vue3';
  
  const props = defineProps({
    functionName: {
      type: String,
      required: true
    }
  });
  
  // État pour les données de la fonction
  const functionData = ref({
    name: '',
    description: '',
    function_type: '',
    return_type: '',
    create_date: null,
    modify_date: null,
    parameters: [],
    definition: ''
  });
  
  // Pour la description de la fonction
  const descriptionText = ref('');
  const saving = ref(false);
  
  // Onglets
  const activeTab = ref('parameters');
  const tabs = [
    { id: 'parameters', name: 'Paramètres' },
    { id: 'definition', name: 'Définition' }
  ];
  
  // Pour l'édition des descriptions de paramètres
  const editingParamId = ref(null);
  const editingValue = ref('');
  
  // Charger les détails de la fonction
  onMounted(async () => {
    try {
      const response = await axios.get(`/function/${props.functionName}/details`);
      functionData.value = response.data;
      descriptionText.value = response.data.description || '';
    } catch (error) {
      console.error('Erreur lors du chargement des détails de la fonction:', error);
      alert('Erreur lors du chargement des détails de la fonction');
    }
  });
  
  const startEdit = (param) => {
    editingParamId.value = param.parameter_id;
    editingValue.value = param.description || '';
  };
  
  const cancelEdit = () => {
    editingParamId.value = null;
    editingValue.value = '';
  };
  
  const saveParamDescription = async (param) => {
    try {
      // Appel API pour sauvegarder la description du paramètre
      await axios.post(`/function-parameter/${parameterId}/update-description`, {
        description: editingValue.value
      });
      
      // Mise à jour locale
      param.description = editingValue.value;
      
      // Fin de l'édition
      cancelEdit();
    } catch (error) {
      console.error('Erreur lors de la sauvegarde de la description:', error);
      alert('Erreur lors de la sauvegarde de la description');
    }
  };
  
  const saveDescription = async () => {
    try {
      saving.value = true;
      
      // Appel API pour sauvegarder la description de la fonction
      await axios.post(`/function/${props.functionName}/description`, {
        description: descriptionText.value
      });
      
      // Mise à jour locale
      functionData.value.description = descriptionText.value;
      
      alert('Description enregistrée avec succès');
    } catch (error) {
      console.error('Erreur lors de la sauvegarde de la description:', error);
      alert('Erreur lors de la sauvegarde de la description');
    } finally {
      saving.value = false;
    }
  };
  
  // Formater la date
  const formatDate = (dateString) => {
    if (!dateString) return 'Non spécifiée';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };
  </script>