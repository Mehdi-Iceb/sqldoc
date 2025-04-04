<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\DbDescription;
use App\Services\DatabaseStructureService;


class ProjectController extends Controller
{

    public function index()
    {
        $projects = Project::where('user_id', auth()->id())->get();
        Log::info('Projects récupérés :', ['count' => $projects->count()]);
        return Inertia::render('Projects/Index', [
            'projects' => $projects
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'db_type' => 'required|in:sqlserver,mysql,postgres'
        ]);

        $project = $request->user()->projects()->create($validated);

        return redirect()->route('projects.connect', $project->id);
    }

    public function create()
    {
        return Inertia::render('Projects/Create', [
            'dbTypes' => [
                'mysql' => 'MySQL',
                'sqlserver' => 'SQL Server',
                'pgsql' => 'PostgreSQL'
            ]
        ]);
    }

    public function connect(Project $project)
    {
        return Inertia::render('Projects/Connect', [
            'project' => $project
        ]);
    }

    public function handleConnect(Request $request, Project $project)
    {
        try {
            $validated = $request->validate([
                'server' => 'required',
                'database' => 'required',
                'port' => $project->db_type !== 'sqlserver' ? 'required' : 'nullable',
                'authMode' => $project->db_type === 'sqlserver' ? 'required|in:windows,sql' : 'nullable',
                'username' => 'required_if:authMode,sql',
                'password' => 'required_if:authMode,sql',
                'description' => 'nullable|string|max:1000',
            ]);

            // Conversion du type pour le driver Laravel
            $driver = $project->db_type === 'sqlserver' ? 'sqlsrv' : $project->db_type;

            $config = [
                'driver' => $driver,
                'host' => $validated['server'],
                'database' => $validated['database'],
            ];

            if ($project->db_type === 'sqlserver') {
                if ($validated['authMode'] === 'windows') {
                    $config['trusted_connection'] = true;
                } else {
                    $config['username'] = $validated['username'] ?? '';
                    $config['password'] = $validated['password'] ?? '';
                }
            } else {
                $config['port'] = $validated['port'];
                $config['username'] = $validated['username'] ?? '';
                $config['password'] = $validated['password'] ?? '';
            }

            // Test de connexion
            $connectionName = "project_{$project->id}";
            Config::set("database.connections.{$connectionName}", $config);
            DB::connection($connectionName)->getPdo();


            //Enregistrement des informations de la base de données dans db_description
            try {
                $dbDescription = DbDescription::create([
                    'user_id' => auth()->id(),
                    'dbname' => $validated['database'],
                    'project_id' => $project->id,
                    'description' => $validated['description'] ?? null,
                ]);
                // EXTRACTION ET SAUVEGARDE DE LA STRUCTURE COMPLÈTE
                try {
                    // Appeler le service qui va extraire et sauvegarder toutes les informations
                    $databaseStructureService = new DatabaseStructureService();
                    $result = $databaseStructureService->extractAndSaveAllStructures($connectionName, $dbDescription->id);
                    
                    Log::info('Structure de la base de données extraite et sauvegardée avec succès', [
                        'success' => $result,
                        'project_id' => $project->id,
                        'database' => $validated['database']
                    ]);

                    
                    Log::info('Comptages après extraction:', [
                        'tables' => $tablesCount,
                        'views' => $viewsCount,
                    ]);
                    
                } catch (\Exception $structureException) {
                    // On continue même si l'extraction échoue
                    Log::error('Erreur lors de l\'extraction de la structure de la base de données', [
                        'error' => $structureException->getMessage(),
                        'trace' => $structureException->getTraceAsString()
                    ]);
                }
                Log::info('Session current_db_id définie à: ' . $dbDescription->id);
                session(['current_db_id' => $dbDescription->id]);
            } catch (\Exception $e) {
                Log::warning('Impossible d\'enregistrer dans db_description', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Définir en tant que connexion active
            session([
                'current_project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'connection' => $config,
                    'db_type' => $project->db_type
                ]
            ]);
            
            // Log pour débogage
            Log::info('Connexion réussie', [
                'project_id' => $project->id,
                'db_type' => $project->db_type,
                'driver' => $driver,
                'database' => $validated['database']
            ]);

            // Rediriger vers le tableau de bord du projet si c'est une requête normale
            if (!$request->wantsJson()) {
                return redirect()->route('dashboard');
            }

            // Sinon, retourner une réponse JSON
            return response()->json([
                'success' => true,
                'redirect' => route('dashboard')
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur de connexion à la base de données', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
            ]);
            
            if (!$request->wantsJson()) {
                return back()->with('error', 'Erreur de connexion: ' . $e->getMessage());
            }
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function open($id)
    {
        try {
            // Récupérer le projet
            $project = Project::findOrFail($id);
            
            // Rechercher la description de BD
            $dbDescription = DbDescription::where('project_id', $project->id)
                ->orWhere('project_id', $project->id)
                ->first();
            
            if (!$dbDescription) {
                return redirect()->route('projects.index')
                    ->with('error', 'Aucune base de données trouvée pour ce projet.');
            }
            
            // Définir les informations de connexion
            $connectionInfo = null;
            if (isset($project->connection_info) && !empty($project->connection_info)) {
                if (is_string($project->connection_info)) {
                    $connectionInfo = json_decode($project->connection_info, true);
                } else {
                    $connectionInfo = $project->connection_info;
                }
            } else {
                // Si aucune info de connexion, créer un tableau vide mais avec la structure minimale requise
                $connectionInfo = [
                    'driver' => $project->db_type === 'sqlserver' ? 'sqlsrv' : $project->db_type,
                    'host' => 'localhost',
                    'database' => $dbDescription->dbname,
                    'username' => '',
                    'password' => ''
                ];
            }
            
            // S'assurer que driver est défini
            if (!isset($connectionInfo['driver'])) {
                $connectionInfo['driver'] = $project->db_type === 'sqlserver' ? 'sqlsrv' : $project->db_type;
            }
            
            // Mettre à jour la session
            session([
                'current_project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'db_type' => $project->db_type,
                    'connection' => $connectionInfo
                ],
                'current_db_id' => $dbDescription->id
            ]);
            
            // Rediriger vers le tableau de bord
            return redirect()->route('dashboard')
                ->with('success', 'Projet "' . $project->name . '" ouvert avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'ouverture du projet', [
                'project_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('projects.index')
                ->with('error', 'Impossible d\'ouvrir le projet: ' . $e->getMessage());
        }
    }

    public function disconnect(Request $request)
    {
        try {
            // Récupérer les informations du projet en cours depuis la session
            $currentProject = session('current_project');
            
            if ($currentProject) {
                // Récupération de l'ID du projet et du nom de connexion
                $projectId = $currentProject['id'];
                $connectionName = "project_{$projectId}";
                
                // Log pour le débogage
                Log::info("Tentative de déconnexion", [
                    'connection' => $connectionName,
                    'db_type' => $currentProject['db_type'] ?? 'non défini'
                ]);
                
                // Fermer la connexion de base de données, quel que soit le type
                DB::disconnect($connectionName);
                
                // Supprimer la connexion de la configuration
                Config::set("database.connections.{$connectionName}", null);
                
                // Supprimer les informations de connexion de la session
                session()->forget('current_project');
                session()->save();
                
                // Message flash pour l'utilisateur
                session()->flash('success', 'Déconnecté de la base de données avec succès');
            } else {
                Log::warning("Tentative de déconnexion sans connexion active en session");
            }
            
            // Pour les requêtes AJAX, retourner une réponse JSON
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true]);
            }
            
            // Sinon, rediriger vers la liste des projets
            return redirect()->route('projects.index');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la déconnexion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            
            return redirect()->back()->with('error', 'Erreur lors de la déconnexion: ' . $e->getMessage());
        }
    }
}