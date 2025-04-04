<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TableDescription;
use App\Models\ViewDescription;
use App\Models\PsDescription;
use App\Models\FunctionDescription;
use App\Models\TriggerDescription;
use Illuminate\Support\Facades\Log;
use App\Models\TableStructure;

class DatabaseStructureController extends Controller
{
    public function index()
    {
        // Obtenir l'ID de la base de données actuelle depuis la session
        $dbId = session('current_db_id');
        Log::info('Récupération de la structure pour dbId: ' . $dbId);
        if (!$dbId) {
            Log::warning('Aucune base de données sélectionnée dans la session');
            return response()->json(['error' => 'Aucune base de données sélectionnée'], 400);
        }
        
        // Récupérer les différents objets de la base de données
        $tables = TableDescription::where('dbid', $dbId)
            ->select('id', 'tablename as name', 'description')
            ->get();
            Log::info('Tables trouvées: ' . $tables->count());

        $tables = $tables->map(function ($table) {
            $columns = TableStructure::where('id_table', $table->id)
                ->select('column as name', 'key', 'description')
                ->get();
                
            $table->columns = $columns;
                
            // Aussi, créer un texte de recherche qui combine toutes les colonnes
            $columnsText = $columns->pluck('name')->join(' ');
            $table->searchable_columns = $columnsText;
                
            // Indiquer si la table a une clé primaire ou étrangère
            $table->has_primary_key = $columns->contains(function ($column) {
                return $column->key === 'PK';
            });
                
            $table->has_foreign_key = $columns->contains(function ($column) {
                return $column->key === 'FK';
            });
                
            return $table;
        });
            
        $views = ViewDescription::where('dbid', $dbId)
            ->select('id', 'viewname as name', 'description')
            ->get();
            Log::info('Vues trouvées: ' . $views->count());
            
        $procedures = PsDescription::where('dbid', $dbId)
            ->select('id', 'psname as name', 'description')
            ->get();
            Log::info('procédures trouvées: ' . $procedures->count());
            
        $functions = FunctionDescription::where('dbid', $dbId)
            ->select('id', 'functionname as name', 'description')
            ->get();
            Log::info('fonctions trouvées: ' . $functions->count());
            
        $triggers = TriggerDescription::where('dbid', $dbId)
            ->select('id', 'triggername as name', 'description')
            ->get();
            Log::info('triggers trouvées: ' . $triggers->count());
        
        return response()->json([
            'tables' => $tables,
            'views' => $views,
            'procedures' => $procedures,
            'functions' => $functions,
            'triggers' => $triggers
        ]);
    }
}