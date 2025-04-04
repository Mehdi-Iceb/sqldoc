<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TriggerDescription;
use App\Models\TriggerInformation;

class TriggerController extends Controller
{
    /**
     * Récupère les détails d'un trigger
     */
    public function apiDetails($triggerName)
    {
        try {
            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('API - Récupération des détails pour triggerName: ' . $triggerName . ', dbId: ' . $dbId);
            
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description du trigger
            $triggerDesc = TriggerDescription::where('dbid', $dbId)
                ->where('triggername', $triggerName)
                ->first();

            if (!$triggerDesc) {
                return response()->json(['error' => 'Trigger non trouvé'], 404);
            }

            // Récupérer les informations du trigger
            $triggerInfo = TriggerInformation::where('id_trigger', $triggerDesc->id)->first();

            if (!$triggerInfo) {
                return response()->json(['error' => 'Informations du trigger non trouvées'], 404);
            }

            // Construire la réponse
            return response()->json([
                'name' => $triggerDesc->triggername,
                'description' => $triggerDesc->description,
                'table_name' => $triggerInfo->table,
                'schema' => $triggerInfo->schema ?? null,
                'trigger_type' => $triggerInfo->type,
                'trigger_event' => $triggerInfo->event,
                'is_disabled' => $triggerInfo->state === 0 || $triggerInfo->state === 'DISABLED',
                'definition' => $triggerInfo->definition,
                'create_date' => $triggerInfo->creation_date
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans TriggerController::apiDetails', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Erreur lors de la récupération des détails du trigger: ' . $e->getMessage()], 500);
        }
    }

// méthode pour le rendu Inertia
    public function details($triggerName)
    {
        try {
            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('Récupération des détails pour triggerName: ' . $triggerName . ', dbId: ' . $dbId);
            
            if (!$dbId) {
                return Inertia::render('TriggerDetails', [
                    'triggerName' => $triggerName,
                    'triggerDetails' => [
                        'name' => $triggerName,
                        'description' => '',
                        'table_name' => '',
                        'schema' => null,
                        'trigger_type' => '',
                        'trigger_event' => '',
                        'is_disabled' => false,
                        'definition' => '',
                        'create_date' => null
                    ],
                    'error' => 'Aucune base de données sélectionnée'
                ]);
            }

            // Récupérer la description du trigger
            $triggerDesc = TriggerDescription::where('dbid', $dbId)
                ->where('triggername', $triggerName)
                ->first();

            if (!$triggerDesc) {
                return Inertia::render('TriggerDetails', [
                    'triggerName' => $triggerName,
                    'triggerDetails' => [
                        'name' => $triggerName,
                        'description' => '',
                        'table_name' => '',
                        'schema' => null,
                        'trigger_type' => '',
                        'trigger_event' => '',
                        'is_disabled' => false,
                        'definition' => '',
                        'create_date' => null
                    ],
                    'error' => 'Trigger non trouvé'
                ]);
            }

            // Récupérer les informations du trigger
            $triggerInfo = TriggerInformation::where('id_trigger', $triggerDesc->id)->first();

            if (!$triggerInfo) {
                return Inertia::render('TriggerDetails', [
                    'triggerName' => $triggerName,
                    'triggerDetails' => [
                        'name' => $triggerDesc->triggername,
                        'description' => $triggerDesc->description,
                        'table_name' => '',
                        'schema' => null,
                        'trigger_type' => '',
                        'trigger_event' => '',
                        'is_disabled' => false,
                        'definition' => '',
                        'create_date' => null
                    ],
                    'error' => 'Informations du trigger non trouvées'
                ]);
            }

            return Inertia::render('TriggerDetails', [
                'triggerName' => $triggerName,
                'triggerDetails' => [
                    'name' => $triggerDesc->triggername,
                    'description' => $triggerDesc->description,
                    'table_name' => $triggerInfo->table,
                    'schema' => $triggerInfo->schema ?? null,
                    'trigger_type' => $triggerInfo->type,
                    'trigger_event' => $triggerInfo->event,
                    'is_disabled' => $triggerInfo->state === 0 || $triggerInfo->state === 'DISABLED',
                    'definition' => $triggerInfo->definition,
                    'create_date' => $triggerInfo->creation_date
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans TriggerController::details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('TriggerDetails', [
                'triggerName' => $triggerName,
                'triggerDetails' => [
                    'name' => $triggerName,
                    'description' => '',
                    'table_name' => '',
                    'schema' => null,
                    'trigger_type' => '',
                    'trigger_event' => '',
                    'is_disabled' => false,
                    'definition' => '',
                    'create_date' => null
                ],
                'error' => 'Erreur lors de la récupération des détails du trigger: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sauvegarde la description d'un trigger
     */
    public function saveDescription(Request $request, $triggerName)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description du trigger
            $triggerDesc = TriggerDescription::where('dbid', $dbId)
                ->where('triggername', $triggerName)
                ->first();

            if (!$triggerDesc) {
                return response()->json(['error' => 'Trigger non trouvé'], 404);
            }

            // Mettre à jour la description
            $triggerDesc->description = $validated['description'];
            $triggerDesc->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la sauvegarde de la description: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sauvegarde toutes les informations d'un trigger (uniquement la description)
     */
    public function saveAll(Request $request, $triggerName)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string',
                'language' => 'nullable|string|max:3'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description du trigger
            $triggerDesc = TriggerDescription::where('dbid', $dbId)
                ->where('triggername', $triggerName)
                ->first();

            if (!$triggerDesc) {
                return response()->json(['error' => 'Trigger non trouvé'], 404);
            }

            // Mettre à jour la description et éventuellement la langue
            $triggerDesc->description = $validated['description'];
            if (isset($validated['language'])) {
                $triggerDesc->language = $validated['language'];
            }
            $triggerDesc->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la sauvegarde des informations: ' . $e->getMessage()], 500);
        }
    }
}
