<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TableDescription;
use App\Models\TableStructure;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TableController extends Controller
{
    /**
     * Affiche les détails d'une table spécifique
     */
    public function details($tableName)
    {
        try {
            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            Log::info('Récupération des détails pour tableName: ' . $tableName . ', dbId: ' . $dbId);
        
            if (!$dbId) {
                if (request()->header('X-Inertia')) {
                    // Si c'est une requête Inertia, renvoyer une réponse Inertia
                    return Inertia::render('TableDetails', [
                        'tableName' => $tableName,
                        'tableDetails' => [
                            'description' => '',
                            'columns' => [],
                            'indexes' => [],
                            'relations' => []
                        ],
                        'error' => 'Aucune base de données sélectionnée'
                    ]);
                } else {
                    // Si c'est une requête AJAX normale, renvoyer une réponse JSON
                    return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
                }
            }

            // Récupérer la description de la table
            $tableDesc = TableDescription::where('dbid', $dbId)
                ->where('tablename', $tableName)
                ->first();

            if (!$tableDesc) {
                return response()->json(['error' => 'Table non trouvée'], 404);
            }

            // Récupérer les colonnes de la table
            $columns = TableStructure::where('id_table', $tableDesc->id)
                ->get()
                ->map(function ($column) {
                    return [
                        'column_name' => $column->column,
                        'data_type' => $column->type,
                        'is_nullable' => $column->nullable == 1,
                        'is_primary_key' => $column->key === 'PK',
                        'is_foreign_key' => $column->key === 'FK',
                        'description' => $column->description,
                        'possible_values' => $column->rangevalues
                    ];
                });

            // Récupérer les index de la table
            $indexes = DB::table('table_index')
                ->where('id_table', $tableDesc->id)
                ->get()
                ->map(function ($index) {
                    return [
                        'index_name' => $index->name,
                        'index_type' => $index->type,
                        'columns' => $index->column,
                        'is_primary_key' => strpos($index->properties, 'PRIMARY KEY') !== false,
                        'is_unique' => strpos($index->properties, 'UNIQUE') !== false
                    ];
                });

            // Récupérer les relations de la table
            $relations = DB::table('table_relations')
                ->where('id_table', $tableDesc->id)
                ->get()
                ->map(function ($relation) {
                    // Analyse de la chaîne d'action pour extraire les règles DELETE et UPDATE
                    $deleteRule = 'NO ACTION';
                    $updateRule = 'NO ACTION';
                    
                    if ($relation->action) {
                        if (preg_match('/ON DELETE (\w+( \w+)?)/', $relation->action, $deleteMatches)) {
                            $deleteRule = $deleteMatches[1];
                        }
                        if (preg_match('/ON UPDATE (\w+( \w+)?)/', $relation->action, $updateMatches)) {
                            $updateRule = $updateMatches[1];
                        }
                    }
                    
                    return [
                        'constraint_name' => $relation->constraints,
                        'column_name' => $relation->column,
                        'referenced_table' => $relation->referenced_table,
                        'referenced_column' => $relation->referenced_column,
                        'delete_rule' => $deleteRule,
                        'update_rule' => $updateRule
                    ];
                });

                if (request()->header('X-Inertia')) {
                    return Inertia::render('TableDetails', [
                        'tableName' => $tableName,
                        'tableDetails' => [
                            'description' => $tableDesc->description,
                            'columns' => $columns,
                            'indexes' => $indexes,
                            'relations' => $relations
                        ]
                    ]);
                } else {
                    return response()->json([
                        'description' => $tableDesc->description,
                        'columns' => $columns,
                        'indexes' => $indexes,
                        'relations' => $relations
                    ]);
                }
        
            } catch (\Exception $e) {
                Log::error('Erreur dans TableController::details', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                if (request()->header('X-Inertia')) {
                    return Inertia::render('TableDetails', [
                        'tableName' => $tableName,
                        'tableDetails' => [
                            'description' => '',
                            'columns' => [],
                            'indexes' => [],
                            'relations' => []
                        ],
                        'error' => 'Erreur lors de la récupération des détails de la table: ' . $e->getMessage()
                    ]);
                } else {
                    return response()->json(['error' => 'Erreur lors de la récupération des détails de la table: ' . $e->getMessage()], 500);
                }
            

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération des détails de la table: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sauvegarde la structure de la table (uniquement descriptions et valeurs possibles)
     */
    public function saveStructure(Request $request, $tableName)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'description' => 'nullable|string',
                'language' => 'required|string|size:2',
                'columns' => 'required|array',
                'columns.*.column' => 'required|string',
                'columns.*.description' => 'nullable|string',
                'columns.*.rangevalues' => 'nullable|string'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description de la table
            $tableDesc = TableDescription::where('dbid', $dbId)
                ->where('tablename', $tableName)
                ->first();

            if (!$tableDesc) {
                return response()->json(['error' => 'Table non trouvée'], 404);
            }

            // Mettre à jour la description de la table
            $tableDesc->description = $validated['description'];
            $tableDesc->language = $validated['language'];
            $tableDesc->save();

            // Mettre à jour les descriptions et valeurs possibles des colonnes
            foreach ($validated['columns'] as $columnData) {
                $column = TableStructure::where('id_table', $tableDesc->id)
                    ->where('column', $columnData['column'])
                    ->first();

                if ($column) {
                    $column->description = $columnData['description'];
                    $column->rangevalues = $columnData['rangevalues'];
                    $column->save();
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la sauvegarde de la structure: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Met à jour la description d'une colonne spécifique
     */
    public function updateColumnDescription(Request $request, $tableName, $columnName)
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

            // Récupérer la description de la table
            $tableDesc = TableDescription::where('dbid', $dbId)
                ->where('tablename', $tableName)
                ->first();

            if (!$tableDesc) {
                return response()->json(['error' => 'Table non trouvée'], 404);
            }

            // Mettre à jour la description de la colonne
            $column = TableStructure::where('id_table', $tableDesc->id)
                ->where('column', $columnName)
                ->first();

            if (!$column) {
                return response()->json(['error' => 'Colonne non trouvée'], 404);
            }

            $column->description = $validated['description'];
            $column->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la mise à jour de la description: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Met à jour les valeurs possibles d'une colonne spécifique
     */
    public function updateColumnPossibleValues(Request $request, $tableName, $columnName)
    {
        try {
            // Valider les données
            $validated = $request->validate([
                'possible_values' => 'nullable|string'
            ]);

            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description de la table
            $tableDesc = TableDescription::where('dbid', $dbId)
                ->where('tablename', $tableName)
                ->first();

            if (!$tableDesc) {
                return response()->json(['error' => 'Table non trouvée'], 404);
            }

            // Mettre à jour les valeurs possibles de la colonne
            $column = TableStructure::where('id_table', $tableDesc->id)
                ->where('column', $columnName)
                ->first();

            if (!$column) {
                return response()->json(['error' => 'Colonne non trouvée'], 404);
            }

            $column->rangevalues = $validated['possible_values'];
            $column->save();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la mise à jour des valeurs possibles: ' . $e->getMessage()], 500);
        }
    }


    public function apiDetails($tableName)
    {
        try {
            // Obtenir l'ID de la base de données actuelle depuis la session
            $dbId = session('current_db_id');
            \Log::info('API - Récupération des détails pour tableName: ' . $tableName . ', dbId: ' . $dbId);
            
            if (!$dbId) {
                return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
            }

            // Récupérer la description de la table
            $tableDesc = TableDescription::where('dbid', $dbId)
                ->where('tablename', $tableName)
                ->first();

            if (!$tableDesc) {
                return response()->json(['error' => 'Table non trouvée'], 404);
            }

            // Récupérer les colonnes de la table
            $columns = TableStructure::where('id_table', $tableDesc->id)
                ->get()
                ->map(function ($column) {
                    return [
                        'column_name' => $column->column,
                        'data_type' => $column->type,
                        'is_nullable' => $column->nullable == 1,
                        'is_primary_key' => $column->key === 'PK',
                        'is_foreign_key' => $column->key === 'FK',
                        'description' => $column->description,
                        'possible_values' => $column->rangevalues
                    ];
                });

            // Récupérer les index de la table
            $indexes = DB::table('table_index')
                ->where('id_table', $tableDesc->id)
                ->get()
                ->map(function ($index) {
                    return [
                        'index_name' => $index->name,
                        'index_type' => $index->type,
                        'columns' => $index->column,
                        'is_primary_key' => strpos($index->properties, 'PRIMARY KEY') !== false,
                        'is_unique' => strpos($index->properties, 'UNIQUE') !== false
                    ];
                });

            // Récupérer les relations de la table
            $relations = DB::table('table_relations')
                ->where('id_table', $tableDesc->id)
                ->get()
                ->map(function ($relation) {
                    // Analyse de la chaîne d'action pour extraire les règles DELETE et UPDATE
                    $deleteRule = 'NO ACTION';
                    $updateRule = 'NO ACTION';
                    
                    if ($relation->action) {
                        if (preg_match('/ON DELETE (\w+( \w+)?)/', $relation->action, $deleteMatches)) {
                            $deleteRule = $deleteMatches[1];
                        }
                        if (preg_match('/ON UPDATE (\w+( \w+)?)/', $relation->action, $updateMatches)) {
                            $updateRule = $updateMatches[1];
                        }
                    }
                    
                    return [
                        'constraint_name' => $relation->constraints,
                        'column_name' => $relation->column,
                        'referenced_table' => $relation->referenced_table,
                        'referenced_column' => $relation->referenced_column,
                        'delete_rule' => $deleteRule,
                        'update_rule' => $updateRule
                    ];
                });

            // Renvoyer la réponse JSON
            return response()->json([
                'description' => $tableDesc->description,
                'columns' => $columns,
                'indexes' => $indexes,
                'relations' => $relations
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur dans TableController::apiDetails', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Erreur lors de la récupération des détails de la table: ' . $e->getMessage()], 500);
        }
    }
}
