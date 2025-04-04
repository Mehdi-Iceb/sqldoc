<template>
    <AuthenticatedLayout>
      <template #header>
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-800">
            <span class="text-gray-500 font-normal">Vue :</span> 
            {{ viewName }}
          </h2>
          <button 
            @click="saveAll"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            :disabled="saving"
            >
            {{ saving ? 'Enregistrement...' : 'Enregistrer toutes les descriptions' }}
          </button>
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
          <div v-else>
  
            <!-- Description de la vue -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-medium text-gray-900">Description</h3>
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
  
            <!-- Informations de la vue -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Informations</h3>
              </div>
              <div class="p-6 grid grid-cols-2 gap-6">
                <div>
                  <p class="text-sm text-gray-600">Date de création</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ formatDate(viewDetails.create_date) }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Dernière modification</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ formatDate(viewDetails.modify_date) }}
                  </p>
                </div>
              </div>
            </div>
  
            <!-- Structure de la vue -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Structure</h3>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr class="bg-gray-50">
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Colonne
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Nullable
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Description
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="column in viewDetails.columns" 
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
                      <td class="px-6 py-4 text-sm text-gray-500">
                        <div class="flex items-center space-x-2">
                          <span v-if="!editingColumnName || editingColumnName !== column.column_name">
                            {{ column.description || '-' }}
                          </span>
                          <input
                            v-else
                            v-model="editingColumnDescription"
                            type="text"
                            class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                            @keyup.enter="saveColumnDescription(column.column_name)"
                            @keyup.esc="cancelEdit()"
                          >
                          <button
                            v-if="!editingColumnName || editingColumnName !== column.column_name"
                            @click="startEdit(column)"
                            class="p-1 text-gray-400 hover:text-gray-600"
                            title="Modifier la description"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                          </button>
                          <div v-else class="flex space-x-1">
                            <button
                              @click="saveColumnDescription(column.column_name)"
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
                    <tr v-if="!viewDetails.columns || viewDetails.columns.length === 0">
                      <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                        Aucune colonne trouvée
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
                <pre class="whitespace-pre-wrap text-sm text-gray-600 font-mono bg-gray-50 p-4 rounded-lg">{{ viewDetails.definition }}</pre>
              </div>
            </div>
  
            <!-- Bouton pour sauvegarder toutes les informations -->
            <div class="flex justify-end mt-6">
              <button 
                @click="saveAll"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :disabled="saving"
              >
                {{ saving ? 'Enregistrement...' : 'Enregistrer toutes les informations' }}
              </button>
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
  
  const form = ref({
    description: ''
  })
  
  const props = defineProps({
    viewName: {
      type: String,
      required: true
    }
  })
  
  const loading = ref(true)
  const error = ref(null)
  const saving = ref(false)
  const viewDetails = ref({
    columns: [],
    definition: '',
    create_date: null,
    modify_date: null
  })
  
  // Pour l'édition des descriptions de colonnes
  const editingColumnName = ref(null)
  const editingColumnDescription = ref('')
  
  const startEdit = (column) => {
    editingColumnName.value = column.column_name
    editingColumnDescription.value = column.description || ''
  }
  
  const cancelEdit = () => {
    editingColumnName.value = null
    editingColumnDescription.value = ''
  }
  
  // Fonction pour sauvegarder la description d'une colonne
  const saveColumnDescription = async (columnName) => {
    try {
      // Appel API pour sauvegarder la description de la colonne
      const response = await axios.post(`/view/${props.viewName}/column/${columnName}/description`, {
        description: editingColumnDescription.value
      })
      
      if (response.data.success) {
        // Mise à jour locale
        const column = viewDetails.value.columns.find(c => c.column_name === columnName)
        if (column) {
          column.description = editingColumnDescription.value
        }
        
        // Fin de l'édition
        cancelEdit()
      } else {
        throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
      }
    } catch (error) {
      console.error('Erreur lors de la sauvegarde de la description de la colonne:', error)
      alert('Erreur lors de la sauvegarde de la description de la colonne')
    }
  }
  
  // Fonction pour sauvegarder la description
  const saveDescription = async () => {
    try {
      saving.value = true
      
      // Appel API pour sauvegarder la description
      const response = await axios.post(`/view/${props.viewName}/description`, {
        description: form.value.description
      })
      
      if (response.data.success) {
        // Message de succès
        alert('Description de la vue enregistrée avec succès')
        
        // Mise à jour locale
        viewDetails.value.description = form.value.description
      } else {
        throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
      }
    } catch (error) {
      console.error('Erreur lors de la sauvegarde de la description:', error)
      alert('Erreur lors de la sauvegarde de la description')
    } finally {
      saving.value = false
    }
  }
  
  // Fonction pour sauvegarder toutes les informations
  const saveAll = async () => {
  try {
    saving.value = true
    
    // Préparer les données à envoyer (seulement les descriptions)
    const viewData = {
      description: form.value.description,
      
      // Colonnes de la vue (uniquement les noms et descriptions)
      columns: viewDetails.value.columns.map(column => ({
        column_name: column.column_name,
        description: column.description
      }))
    }
    
    // Appel à l'API
    const response = await axios.post(`/view/${props.viewName}/save-all`, viewData)
    
    if (response.data.success) {
      // Message de succès
      alert('Les descriptions de la vue ont été enregistrées avec succès')
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde des descriptions:', error)
        alert('Erreur lors de la sauvegarde des descriptions')
    } finally {
        saving.value = false
    }
  }
  
  const formatDataType = (column) => {
    let type = column.data_type
    if (['varchar', 'nvarchar', 'char', 'nchar'].includes(type.toLowerCase())) {
      if (column.max_length) {
        type += `(${column.max_length === -1 ? 'MAX' : column.max_length})`
      }
    } else if (['decimal', 'numeric'].includes(type.toLowerCase())) {
      if (column.precision && column.scale !== undefined) {
        type += `(${column.precision},${column.scale})`
      }
    }
    return type
  }
  
  const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleString()
  }
  
  onMounted(async () => {
    try {
      const response = await axios.get(`/view/${props.viewName}/details`)
      viewDetails.value = response.data
      form.value.description = response.data.description || ''
    } catch (err) {
      error.value = err.response?.data?.error || 'Erreur lors du chargement des détails de la vue'
    } finally {
      loading.value = false
    }
  })
  </script>