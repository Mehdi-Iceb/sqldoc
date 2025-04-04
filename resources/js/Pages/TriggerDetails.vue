<template>
    <AuthenticatedLayout>
      <template #header>
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-800">
            <span class="text-gray-500 font-normal">Trigger :</span> 
            {{ triggerName }}
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
  
          <!-- État de succès -->
          <div v-else class="space-y-8">
  
            <!-- Description du trigger -->
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
                  placeholder="Description du trigger (usage, comportement, impact...)"
                ></textarea>
              </div>
            </div>
  
            <!-- Informations générales -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Informations générales</h3>
              </div>
              <div class="p-6 grid grid-cols-2 gap-6">
                <div>
                  <p class="text-sm text-gray-600">Table</p>
                  <p class="mt-1 font-medium">{{ triggerDetails.table_name }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Schéma</p>
                  <p class="mt-1 font-medium">{{ triggerDetails.schema }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Type</p>
                  <p class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ triggerDetails.trigger_type }}
                    </span>
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Événement</p>
                  <p class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {{ triggerDetails.trigger_event }}
                    </span>
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">État</p>
                  <p class="mt-1">
                    <span :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      triggerDetails.is_disabled 
                        ? 'bg-red-100 text-red-800' 
                        : 'bg-green-100 text-green-800'
                    ]">
                      {{ triggerDetails.is_disabled ? 'Désactivé' : 'Activé' }}
                    </span>
                  </p>
                </div>
                <div v-if="triggerDetails.create_date">
                  <p class="text-sm text-gray-600">Date de création</p>
                  <p class="mt-1 font-medium">{{ formatDate(triggerDetails.create_date) }}</p>
                </div>
              </div>
            </div>
  
            <!-- Définition SQL -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Définition SQL</h3>
              </div>
              <div class="p-6">
                <pre class="whitespace-pre-wrap text-sm font-mono bg-gray-50 p-4 rounded-lg text-gray-600">{{ triggerDetails.definition }}</pre>
              </div>
            </div>
  
            <!-- Bouton pour sauvegarder toutes les informations -->
            <div class="flex justify-end mt-6">
                <button 
                    @click="saveAll"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="saving"
                >
                {{ saving ? 'Enregistrement...' : 'Enregistrer la description' }}
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
  
  const form = ref({
    description: ''
  })
  
  const props = defineProps({
    triggerName: {
      type: String,
      required: true
    }
  })
  
  const loading = ref(true)
  const error = ref(null)
  const saving = ref(false)
  const triggerDetails = ref({
    table_name: '',
    schema: '',
    trigger_type: '',
    trigger_event: '',
    is_disabled: false,
    definition: '',
    create_date: null,
    description: ''
  })
  
  const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString()
  }
  
  // Sauvegarder uniquement la description
  const saveDescription = async () => {
    try {
      saving.value = true
      
      // Appel à l'API pour sauvegarder uniquement la description
      const response = await axios.post(`/trigger/${props.triggerName}/description`, {
        description: form.value.description
      })
      
      if (response.data.success) {
        // Message de succès
        alert('Description du trigger enregistrée avec succès')
        
        // Mise à jour locale
        triggerDetails.value.description = form.value.description
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
  
  // Sauvegarder toutes les informations
  const saveAll = async () => {
    try {
        saving.value = true
        
        // Préparer les données à envoyer (seulement la description)
        const triggerData = {
        description: form.value.description,
        language: 'fr'
        }
        
        // Appel à l'API
        const response = await axios.post(`/trigger/${props.triggerName}/save-all`, triggerData)
        
        if (response.data.success) {
        // Message de succès
        alert('Description du trigger enregistrée avec succès')
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
  
  onMounted(async () => {
    try {
      const response = await axios.get(`/trigger/${encodeURIComponent(props.triggerName)}/details`)
      triggerDetails.value = response.data
      // Initialiser la description avec celle du trigger si disponible
      form.value.description = response.data.description || ''
    } catch (err) {
      console.error('Erreur lors du chargement des détails du trigger:', err)
      error.value = err.response?.data?.error || 'Erreur lors du chargement des détails'
    } finally {
      loading.value = false
    }
  })
  </script>