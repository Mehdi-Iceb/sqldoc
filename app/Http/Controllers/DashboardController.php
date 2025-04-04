<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DbDescription;
use App\Models\TableDescription;
use App\Models\TableStructure;
use App\Models\TableRelation;
use App\Models\ViewDescription;
use App\Models\PsDescription;
use App\Models\FunctionDescription;
use App\Models\TriggerDescription;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtenir l'ID de la base de données actuelle depuis la session
        $dbId = session('current_db_id');
        if (!$dbId) {
            return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
        }

        // Récupérer les informations de la base de données
        $dbInfo = DbDescription::find($dbId);
        if (!$dbInfo) {
            return response()->json(['error' => 'Base de données non trouvée'], 404);
        }

        // Compter les objets de base de données
        $tablesCount = TableDescription::where('dbid', $dbId)->count();
        $viewsCount = ViewDescription::where('dbid', $dbId)->count();
        $proceduresCount = PsDescription::where('dbid', $dbId)->count();
        $functionsCount = FunctionDescription::where('dbid', $dbId)->count();
        $triggersCount = TriggerDescription::where('dbid', $dbId)->count();

        // Compter les colonnes, clés primaires et étrangères
        $tableIds = TableDescription::where('dbid', $dbId)->pluck('id')->toArray();
        
        $columnsCount = TableStructure::whereIn('id_table', $tableIds)->count();
        $primaryKeysCount = TableStructure::whereIn('id_table', $tableIds)
            ->where('key', 'PK')
            ->count();
        $foreignKeysCount = TableRelation::whereIn('id_table', $tableIds)->count();

        // Compter les objets documentés
        $documentedTablesCount = TableDescription::where('dbid', $dbId)
            ->whereNotNull('description')
            ->whereRaw("DATALENGTH(description) > 0")
            ->count();
            
        $documentedColumnsCount = TableStructure::whereIn('id_table', $tableIds)
            ->whereNotNull('description')
            ->whereRaw("DATALENGTH(description) > 0")
            ->count();
        
        $documentedViewsCount = ViewDescription::where('dbid', $dbId)
            ->whereNotNull('description')
            ->whereRaw("DATALENGTH(description) > 0")
            ->count();
            
        $documentedProceduresCount = PsDescription::where('dbid', $dbId)
            ->whereNotNull('description')
            ->whereRaw("DATALENGTH(description) > 0")
            ->count();
            
        $documentedFunctionsCount = FunctionDescription::where('dbid', $dbId)
            ->whereNotNull('description')
            ->whereRaw("DATALENGTH(description) > 0")
            ->count();
            
        $documentedTriggersCount = TriggerDescription::where('dbid', $dbId)
            ->whereNotNull('description')
            ->whereRaw("DATALENGTH(description) > 0")
            ->count();

        // Trouver les tables les plus référencées
        $mostReferencedTables = DB::table('table_relations')
            ->join('table_description', 'table_relations.referenced_table', '=', 'table_description.tablename')
            ->where('table_description.dbid', $dbId)
            ->select('table_description.id', 'table_description.tablename as name')
            ->selectRaw('count(*) as references_count')
            ->groupBy('table_description.id', 'table_description.tablename')
            ->orderByDesc('references_count')
            ->limit(10)
            ->get();

        // Ensuite, récupérez les descriptions séparément
        $tableIds = $mostReferencedTables->pluck('id')->toArray();
        $descriptions = TableDescription::whereIn('id', $tableIds)
            ->select('id', 'description')
            ->get()
            ->keyBy('id');

        // Combine les deux résultats
        $mostReferencedTables = $mostReferencedTables->map(function ($table) use ($descriptions) {
            $description = isset($descriptions[$table->id]) ? $descriptions[$table->id]->description : null;
            return [
                'id' => $table->id,
                'name' => $table->name,
                'references_count' => $table->references_count,
                'is_documented' => !empty($description)
            ];
        });

        // Construire le tableau de bord
        $dashboardData = [
            'database_name' => $dbInfo->dbname,
            'database_description' => $dbInfo->description,
            'tables_count' => $tablesCount,
            'views_count' => $viewsCount,
            'procedures_count' => $proceduresCount,
            'functions_count' => $functionsCount,
            'triggers_count' => $triggersCount,
            'columns_count' => $columnsCount,
            'primary_keys_count' => $primaryKeysCount,
            'foreign_keys_count' => $foreignKeysCount,
            'documented_tables_count' => $documentedTablesCount,
            'documented_columns_count' => $documentedColumnsCount,
            'documented_views_count' => $documentedViewsCount,
            'documented_procedures_count' => $documentedProceduresCount,
            'documented_functions_count' => $documentedFunctionsCount,
            'documented_triggers_count' => $documentedTriggersCount,
            'most_referenced_tables' => $mostReferencedTables
        ];

        return response()->json($dashboardData);
    }
}