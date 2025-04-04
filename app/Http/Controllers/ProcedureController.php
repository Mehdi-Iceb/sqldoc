<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PsDescription;
use App\Models\PsInformation;
use App\Models\PsParameter;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProcedureController extends Controller
{
    /**
     * Récupère les détails d'une procédure stockée
     */
    public function apiDetails($procedureName)
{
    try {
        // Obtenir l'ID de la base de données actuelle depuis la session
        $dbId = session('current_db_id');
        Log::info('API - Récupération des détails pour procedureName: ' . $procedureName . ', dbId: ' . $dbId);
        
        if (!$dbId) {
            return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
        }

        // Récupérer la description de la procédure
        $procedureDesc = PsDescription::where('dbid', $dbId)
            ->where('psname', $procedureName)
            ->first();

        if (!$procedureDesc) {
            return response()->json(['error' => 'Procédure stockée non trouvée'], 404);
        }

        // Récupérer les informations de la procédure
        $procedureInfo = PsInformation::where('id_ps', $procedureDesc->id)->first();
        
        // Récupérer les paramètres de la procédure
        $parameters = PsParameter::where('id_ps', $procedureDesc->id)
            ->get()
            ->map(function ($param) {
                return [
                    'parameter_id' => $param->id,
                    'parameter_name' => $param->name,
                    'data_type' => $param->type,
                    'is_output' => $param->output === 'OUTPUT',
                    'default_value' => $param->default_value,
                    'description' => $param->description
                ];
            });

        // Construire la réponse
        return response()->json([
            'name' => $procedureDesc->psname,
            'description' => $procedureDesc->description,
            'schema' => $procedureInfo ? $procedureInfo->schema : null,
            'create_date' => $procedureInfo ? $procedureInfo->creation_date : null,
            'modify_date' => $procedureInfo ? $procedureInfo->last_change_date : null,
            'parameters' => $parameters,
            'definition' => $procedureInfo ? $procedureInfo->definition : null
        ]);

    } catch (\Exception $e) {
        Log::error('Erreur dans ProcedureController::apiDetails', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'Erreur lors de la récupération des détails de la procédure stockée: ' . $e->getMessage()
        ], 500);
    }
}

// méthode pour le rendu Inertia
    public function details($procedureName)
    {
        try {
            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('Récupération des détails pour procedureName: ' . $procedureName . ', dbId: ' . $dbId);
            
            if (!$dbId) {
                return Inertia::render('ProcedureDetails', [
                    'procedureName' => $procedureName,
                    'procedureDetails' => [
                        'name' => $procedureName,
                        'description' => '',
                        'schema' => null,
                        'create_date' => null,
                        'modify_date' => null,
                        'parameters' => [],
                        'definition' => null
                    ],
                    'error' => 'Aucune base de données sélectionnée'
                ]);
            }

            // Récupérer la description de la procédure
            $procedureDesc = PsDescription::where('dbid', $dbId)
                ->where('psname', $procedureName)
                ->first();

            if (!$procedureDesc) {
                return Inertia::render('ProcedureDetails', [
                    'procedureName' => $procedureName,
                    'procedureDetails' => [
                        'name' => $procedureName,
                        'description' => '',
                        'schema' => null,
                        'create_date' => null,
                        'modify_date' => null,
                        'parameters' => [],
                        'definition' => null
                    ],
                    'error' => 'Procédure stockée non trouvée'
                ]);
            }

            // Récupérer les informations de la procédure
            $procedureInfo = PsInformation::where('id_ps', $procedureDesc->id)->first();
            
            // Récupérer les paramètres de la procédure
            $parameters = PsParameter::where('id_ps', $procedureDesc->id)
                ->get()
                ->map(function ($param) {
                    return [
                        'parameter_id' => $param->id,
                        'parameter_name' => $param->name,
                        'data_type' => $param->type,
                        'is_output' => $param->output === 'OUTPUT',
                        'default_value' => $param->default_value,
                        'description' => $param->description
                    ];
                });

            return Inertia::render('ProcedureDetails', [
                'procedureName' => $procedureName,
                'procedureDetails' => [
                    'name' => $procedureDesc->psname,
                    'description' => $procedureDesc->description,
                    'schema' => $procedureInfo ? $procedureInfo->schema : null,
                    'create_date' => $procedureInfo ? $procedureInfo->creation_date : null,
                    'modify_date' => $procedureInfo ? $procedureInfo->last_change_date : null,
                    'parameters' => $parameters,
                    'definition' => $procedureInfo ? $procedureInfo->definition : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans ProcedureController::details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('ProcedureDetails', [
                'procedureName' => $procedureName,
                'procedureDetails' => [
                    'name' => $procedureName,
                    'description' => '',
                    'schema' => null,
                    'create_date' => null,
                    'modify_date' => null,
                    'parameters' => [],
                    'definition' => null
                ],
                'error' => 'Erreur lors de la récupération des détails de la procédure stockée: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sauvegarde la description d'une procédure stockée
     */
    public function saveDescription(Request $request, $procedureName)
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

            // Récupérer la description de la procédure
            $procedureDesc = PsDescription::where('dbid', $dbId)
                ->where('psname', $procedureName)
                ->first();

            if (!$procedureDesc) {
                return response()->json(['error' => 'Procédure stockée non trouvée'], 404);
            }

            // Mettre à jour la description
            $procedureDesc->description = $validated['description'];
            $procedureDesc->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la sauvegarde de la description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarde la description d'un paramètre de procédure
     */
    public function saveParameterDescription(Request $request, $parameterId)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string'
            ]);

            // Récupérer le paramètre
            $parameter = PsParameter::find($parameterId);

            if (!$parameter) {
                return response()->json(['error' => 'Paramètre non trouvé'], 404);
            }

            // Mettre à jour la description
            $parameter->description = $validated['description'];
            $parameter->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la sauvegarde de la description du paramètre: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sauvegarde toutes les informations d'une procédure stockée (uniquement la description)
     */
    public function saveAll(Request $request, $procedureName)
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

            // Récupérer la description de la procédure
            $procedureDesc = PsDescription::where('dbid', $dbId)
                ->where('psname', $procedureName)
                ->first();

            if (!$procedureDesc) {
                return response()->json(['error' => 'Procédure stockée non trouvée'], 404);
            }

            // Mettre à jour la description
            $procedureDesc->description = $validated['description'];
            $procedureDesc->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la sauvegarde des informations: ' . $e->getMessage()
            ], 500);
        }
    }
}
