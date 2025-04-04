<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FunctionDescription;
use App\Models\FuncInformation;
use App\Models\FuncParameter;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class FunctionController extends Controller
{
    /**
     * Récupère les détails d'une fonction
     */
    public function apiDetails($functionName)
    {
        try {
            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('API - Récupération des détails pour functionName: ' . $functionName . ', dbId: ' . $dbId);
            
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description de la fonction
            $functionDesc = FunctionDescription::where('dbid', $dbId)
                ->where('functionname', $functionName)
                ->first();

            if (!$functionDesc) {
                return response()->json(['error' => 'Fonction non trouvée'], 404);
            }

            // Récupérer les informations de la fonction
            $functionInfo = FuncInformation::where('id_func', $functionDesc->id)->first();
            
            // Récupérer les paramètres de la fonction
            $parameters = FuncParameter::where('id_func', $functionDesc->id)
                ->get()
                ->map(function ($param) {
                    return [
                        'parameter_id' => $param->id,
                        'parameter_name' => $param->name,
                        'data_type' => $param->type,
                        'is_output' => $param->output === 'OUTPUT',
                        'description' => $param->description ?? null
                    ];
                });

            // Construire la réponse
            return response()->json([
                'name' => $functionDesc->functionname,
                'description' => $functionDesc->description,
                'function_type' => $functionInfo ? $functionInfo->type : null,
                'return_type' => $functionInfo ? $functionInfo->return_type : null,
                'create_date' => $functionInfo ? $functionInfo->creation_date : null,
                'modify_date' => $functionInfo ? $functionInfo->last_change_date : null,
                'parameters' => $parameters,
                'definition' => $functionInfo && $functionInfo->definition ? $functionInfo->definition : null
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans FunctionController::apiDetails', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Erreur lors de la récupération des détails de la fonction: ' . $e->getMessage()], 500);
        }
    }

// méthode pour le rendu Inertia
    public function details($functionName)
    {
        try {
            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('Récupération des détails pour functionName: ' . $functionName . ', dbId: ' . $dbId);
            
            if (!$dbId) {
                return Inertia::render('FunctionDetails', [
                    'functionName' => $functionName,
                    'functionDetails' => [
                        'name' => $functionName,
                        'description' => '',
                        'function_type' => null,
                        'return_type' => null,
                        'create_date' => null,
                        'modify_date' => null,
                        'parameters' => [],
                        'definition' => null
                    ],
                    'error' => 'Aucune base de données sélectionnée'
                ]);
            }

            // Récupérer la description de la fonction
            $functionDesc = FunctionDescription::where('dbid', $dbId)
                ->where('functionname', $functionName)
                ->first();

            if (!$functionDesc) {
                return Inertia::render('FunctionDetails', [
                    'functionName' => $functionName,
                    'functionDetails' => [
                        'name' => $functionName,
                        'description' => '',
                        'function_type' => null,
                        'return_type' => null,
                        'create_date' => null,
                        'modify_date' => null,
                        'parameters' => [],
                        'definition' => null
                    ],
                    'error' => 'Fonction non trouvée'
                ]);
            }

            // Récupérer les informations de la fonction
            $functionInfo = FuncInformation::where('id_func', $functionDesc->id)->first();
            
            // Récupérer les paramètres de la fonction
            $parameters = FuncParameter::where('id_func', $functionDesc->id)
                ->get()
                ->map(function ($param) {
                    return [
                        'parameter_id' => $param->id,
                        'parameter_name' => $param->name,
                        'data_type' => $param->type,
                        'is_output' => $param->output === 'OUTPUT',
                        'description' => $param->description ?? null
                    ];
                });

            return Inertia::render('FunctionDetails', [
                'functionName' => $functionName,
                'functionDetails' => [
                    'name' => $functionDesc->functionname,
                    'description' => $functionDesc->description,
                    'function_type' => $functionInfo ? $functionInfo->type : null,
                    'return_type' => $functionInfo ? $functionInfo->return_type : null,
                    'create_date' => $functionInfo ? $functionInfo->creation_date : null,
                    'modify_date' => $functionInfo ? $functionInfo->last_change_date : null,
                    'parameters' => $parameters,
                    'definition' => $functionInfo && $functionInfo->definition ? $functionInfo->definition : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans FunctionController::details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('FunctionDetails', [
                'functionName' => $functionName,
                'functionDetails' => [
                    'name' => $functionName,
                    'description' => '',
                    'function_type' => null,
                    'return_type' => null,
                    'create_date' => null,
                    'modify_date' => null,
                    'parameters' => [],
                    'definition' => null
                ],
                'error' => 'Erreur lors de la récupération des détails de la fonction: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sauvegarde la description d'une fonction
     */
    public function saveDescription(Request $request, $functionName)
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

            // Récupérer la description de la fonction
            $functionDesc = FunctionDescription::where('dbid', $dbId)
                ->where('functionname', $functionName)
                ->first();

            if (!$functionDesc) {
                return response()->json(['error' => 'Fonction non trouvée'], 404);
            }

            // Mettre à jour la description
            $functionDesc->description = $validated['description'];
            $functionDesc->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la sauvegarde de la description: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sauvegarde la description d'un paramètre de fonction
     */
    public function saveParameterDescription(Request $request, $parameterId)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string'
            ]);

            // Récupérer le paramètre
            $parameter = FuncParameter::find($parameterId);

            if (!$parameter) {
                return response()->json(['error' => 'Paramètre non trouvé'], 404);
            }

            // Mettre à jour la description
            $parameter->description = $validated['description'];
            $parameter->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la sauvegarde de la description du paramètre: ' . $e->getMessage()], 500);
        }
    }
}