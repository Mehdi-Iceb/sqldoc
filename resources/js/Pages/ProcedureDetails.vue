<template>
    <AuthenticatedLayout>
      <template #header>
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-800">
            <span class="text-gray-500 font-normal">Procédure stockée :</span> 
            {{ procedureName }}
          </h2>
        </div>
      </template>
  
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
          <!-- État de chargement -->
          <div v-if="loading" class="bg-white rounded-lg shadow-sm p-6">
            <div class="animate-pulse space-y-4">
              <div class="h-4 bg-gray-200 rounded w-1/4"></div>
              <div class="space-y-3">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded"></div>
              </div>
            </div>
          </div>
  
          <!-- État d'erreur -->
          <div v-else-if="error" 
               class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <div class="text-red-700">{{ error }}</div>
            </div>
          </div>
  
          <!-- État succès -->
          <div v-else>
            
            <!-- Description de la procédure stockée -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-medium text-gray-900">Description</h3>
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
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Description de la procédure stockée (usage, paramètres, exemples...)"
                ></textarea>
              </div>
            </div>
  
            <!-- Informations de la procédure stockée -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Informations</h3>
              </div>
              <div class="p-6 grid grid-cols-2 gap-6">
                <div>
                  <p class="text-sm text-gray-600">Schéma</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ procedureDetails.schema || 'Non spécifié' }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Date de création</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ formatDate(procedureDetails.create_date) }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Dernière modification</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ formatDate(procedureDetails.modify_date) }}
                  </p>
                </div>
              </div>
            </div>
  
            <!-- Paramètres -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Paramètres</h3>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr class="bg-gray-50">
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nom
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Output
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Valeur par défaut
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="param in procedureDetails.parameters" 
                        :key="param.parameter_name"
                        class="hover:bg-gray-50 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ param.parameter_name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <span class="font-mono">{{ param.data_type }}</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span v-if="param.is_output" 
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          Output
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ param.default_value || '-' }}
                      </td>
                    </tr>
                    <tr v-if="!procedureDetails.parameters || procedureDetails.parameters.length === 0">
                      <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                        Aucun paramètre trouvé
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
  
            <!-- Définition SQL -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Définition SQL</h3>
              </div>
              <div class="p-6">
                <pre class="whitespace-pre-wrap text-sm text-gray-600 font-mono bg-gray-50 p-4 rounded-lg">{{ procedureDetails.definition }}</pre>
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
  
  const saving = ref(false)
  
  // Fonction pour sauvegarder la description
  const saveDescription = async () => {
    try {
      saving.value = true
      
      // Appel à l'API pour sauvegarder uniquement la description
      const response = await axios.post(`/procedure/${props.procedureName}/description`, {
        description: form.value.description
      })
      
      if (response.data.success) {
        // Message de succès
        alert('Description de la procédure stockée enregistrée avec succès')
        
        // Mise à jour locale
        procedureDetails.value.description = form.value.description
      } else {
        throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
      }
      
    } catch (error) {
      console.error('Erreur lors de la sauvegarde:', error)
      console.error('Détails:', error.response?.data)
      alert('Erreur lors de la sauvegarde de la description')
    } finally {
      saving.value = false
    }
  }
  
  // Fonction pour sauvegarder toutes les informations
  const saveAll = async () => {
    try {
        saving.value = true
        
        // Préparer les données à envoyer (seulement la description)
        const procedureData = {
        description: form.value.description
        }
        
        // Appel à l'API
        const response = await axios.post(`/procedure/${props.procedureName}/save-all`, procedureData)
        
        if (response.data.success) {
        // Message de succès
        alert('Description de la procédure stockée enregistrée avec succès')
        } else {
        throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
        }
        
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error)
        console.error('Détails:', error.response?.data)
        alert('Erreur lors de la sauvegarde de la description')
    } finally {
        saving.value = false
    }
  }
  
  const form = ref({
    description: ''
  })
  
  const props = defineProps({
    procedureName: {
      type: String,
      required: true
    }
  })
  
  const loading = ref(true)
  const error = ref(null)
  const procedureDetails = ref({
    parameters: [],
    definition: '',
    schema: '',
    create_date: null,
    modify_date: null,
    description: ''
  })
  
  const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleString()
  }
  
  onMounted(async () => {
    try {
      console.log('Chargement des détails pour:', props.procedureName);
      const response = await axios.get(`/api/procedure/${encodeURIComponent(props.procedureName)}/details`)
      console.log('Réponse reçue:', response.data);
      procedureDetails.value = response.data
      form.value.description = response.data.description || ''
    } catch (err) {
      error.value = err.response?.data?.error || 'Erreur lors du chargement des détails de la procédure'
    } finally {
      loading.value = false
    }
  })
  </script>