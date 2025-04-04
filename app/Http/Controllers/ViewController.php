<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ViewDescription;
use App\Models\ViewInformation;
use App\Models\ViewColumn;
use Inertia\Inertia;

class ViewController extends Controller
{
    /**
     * Affiche les détails d'une vue spécifique
     */
    public function details($viewName)
    {
        try {
            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('Récupération des détails pour viewName: ' . $viewName . ', dbId: ' . $dbId);
            
            if (!$dbId) {
                return Inertia::render('ViewDetails', [
                    'viewName' => $viewName,
                    'viewDetails' => [
                        'description' => '',
                        'columns' => [],
                        'definition' => null,
                        'create_date' => null,
                        'modify_date' => null,
                        'schema_name' => null,
                    ],
                    'error' => 'Aucune base de données sélectionnée'
                ]);
            }

            // Récupérer la description de la vue
            $viewDesc = ViewDescription::where('dbid', $dbId)
                ->where('viewname', $viewName)
                ->first();

            if (!$viewDesc) {
                return Inertia::render('ViewDetails', [
                    'viewName' => $viewName,
                    'viewDetails' => [
                        'description' => '',
                        'columns' => [],
                        'definition' => null,
                        'create_date' => null,
                        'modify_date' => null,
                        'schema_name' => null,
                    ],
                    'error' => 'Vue non trouvée'
                ]);
            }

            // Récupérer les informations de la vue
            $viewInfo = ViewInformation::where('id_view', $viewDesc->id)->first();
            
            // Récupérer les colonnes de la vue
            $columns = ViewColumn::where('id_view', $viewDesc->id)
                ->get()
                ->map(function ($column) {
                    return [
                        'column_name' => $column->name,
                        'data_type' => $column->type,
                        'is_nullable' => $column->nullable == 1,
                        'description' => $column->description ?? null
                    ];
                });

            return Inertia::render('ViewDetails', [
                'viewName' => $viewName,
                'viewDetails' => [
                    'description' => $viewDesc->description,
                    'columns' => $columns,
                    'definition' => $viewInfo ? $viewInfo->definition : null,
                    'create_date' => $viewInfo ? $viewInfo->creation_date : null,
                    'modify_date' => $viewInfo ? $viewInfo->last_change_date : null,
                    'schema_name' => $viewInfo ? $viewInfo->schema_name : null,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur dans ViewController::details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('ViewDetails', [
                'viewName' => $viewName,
                'viewDetails' => [
                    'description' => '',
                    'columns' => [],
                    'definition' => null,
                    'create_date' => null,
                    'modify_date' => null,
                    'schema_name' => null,
                ],
                'error' => 'Erreur lors de la récupération des détails de la vue: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sauvegarde la description de la vue
     */
    public function saveDescription(Request $request, $viewName)
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

            // Récupérer la description de la vue
            $viewDesc = ViewDescription::where('dbid', $dbId)
                ->where('viewname', $viewName)
                ->first();

            if (!$viewDesc) {
                return response()->json(['error' => 'Vue non trouvée'], 404);
            }

            // Mettre à jour la description de la vue
            $viewDesc->description = $validated['description'];
            $viewDesc->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la sauvegarde de la description: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sauvegarde la description d'une colonne de la vue
     */
    public function saveColumnDescription(Request $request, $viewName, $columnName)
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

            // Récupérer la description de la vue
            $viewDesc = ViewDescription::where('dbid', $dbId)
                ->where('viewname', $viewName)
                ->first();

            if (!$viewDesc) {
                return response()->json(['error' => 'Vue non trouvée'], 404);
            }

            // Mettre à jour la description de la colonne
            $column = ViewColumn::where('id_view', $viewDesc->id)
                ->where('name', $columnName)
                ->first();

            if (!$column) {
                return response()->json(['error' => 'Colonne non trouvée'], 404);
            }

            $column->description = $validated['description'];
            $column->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la sauvegarde de la description de la colonne: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sauvegarde toutes les informations de la vue (descriptions uniquement)
     */
    public function saveAll(Request $request, $viewName)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string',
                'columns' => 'required|array',
                'columns.*.column_name' => 'required|string',
                'columns.*.description' => 'nullable|string'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description de la vue
            $viewDesc = ViewDescription::where('dbid', $dbId)
                ->where('viewname', $viewName)
                ->first();

            if (!$viewDesc) {
                return response()->json(['error' => 'Vue non trouvée'], 404);
            }

            // Mettre à jour la description de la vue
            $viewDesc->description = $validated['description'];
            $viewDesc->save();

            // Mettre à jour les descriptions des colonnes
            foreach ($validated['columns'] as $columnData) {
                $column = ViewColumn::where('id_view', $viewDesc->id)
                    ->where('name', $columnData['column_name'])
                    ->first();

                if ($column) {
                    $column->description = $columnData['description'];
                    $column->save();
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la sauvegarde de toutes les informations: ' . $e->getMessage()], 500);
        }
    }
}
