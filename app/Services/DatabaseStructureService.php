<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DbDescription;
use App\Models\TableDescription;
use App\Models\TableStructure;
use App\Models\TableIndex;
use App\Models\TableRelation;
use App\Models\ViewDescription;
use App\Models\ViewInformation;
use App\Models\ViewColumn;
use App\Models\PsDescription;
use App\Models\PsInformation;
use App\Models\PsParameter;
use App\Models\FunctionDescription;
use App\Models\FuncInformation;
use App\Models\FuncParameter;
use App\Models\TriggerDescription;
use App\Models\TriggerInformation;

class DatabaseStructureService
{
    /**
     * Extrait et sauvegarde toute la structure de la base de données
     *
     * @param string $connectionName Nom de la connexion à utiliser
     * @param int $dbId ID de l'entrée dans db_description
     * @return bool
     */

    private function formatDataType($column)
    {
         if (!isset($column->data_type)) {
             return 'unknown';
         }
         
         $type = $column->data_type;
         
         if (in_array(strtolower($type), ['varchar', 'nvarchar', 'char', 'nchar', 'binary', 'varbinary'])) {
             if (isset($column->max_length)) {
                 // Pour nvarchar/nchar, la longueur est en caractères, pas en octets
                 $maxLength = in_array(strtolower($type), ['nvarchar', 'nchar']) 
                     ? $column->max_length / 2 
                     : $column->max_length;
                 
                 $type .= "(" . ($maxLength == -1 ? 'MAX' : $maxLength) . ")";
             }
         } else if (in_array(strtolower($type), ['decimal', 'numeric'])) {
             if (isset($column->precision) && isset($column->scale)) {
                 $type .= "({$column->precision},{$column->scale})";
             }
         }
         
         return $type;
    }

    public function extractAndSaveAllStructures($connectionName, $dbId)
    {
        try {
            // Déterminer le type de base de données pour adapter les requêtes
            $databaseType = DB::connection($connectionName)->getDriverName();
            Log::info("Extraction de la structure pour: {$databaseType}");

            // 1. Extraire et sauvegarder les tables
            $this->extractAndSaveTables($connectionName, $dbId, $databaseType);
            
            // 2. Extraire et sauvegarder les vues
            $this->extractAndSaveViews($connectionName, $dbId, $databaseType);
            
            // 3. Extraire et sauvegarder les fonctions
            $this->extractAndSaveFunctions($connectionName, $dbId, $databaseType);
            
            // 4. Extraire et sauvegarder les procédures stockées
            $this->extractAndSaveProcedures($connectionName, $dbId, $databaseType);
            
            // 5. Extraire et sauvegarder les triggers
            $this->extractAndSaveTriggers($connectionName, $dbId, $databaseType);
            
            Log::info("Extraction de la structure terminée avec succès pour la base de données ID: {$dbId}");
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveAllStructures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Extrait et sauvegarde les tables de la base de données
     */
    private function extractAndSaveTables($connectionName, $dbId, $databaseType)
    {
        try {
            Log::info('Début extraction des tables...');
            
            if ($databaseType === 'sqlsrv') {
                $this->extractAndSaveSqlServerTables($connectionName, $dbId);
            } elseif ($databaseType === 'mysql') {
                $this->extractAndSaveMySqlTables($connectionName, $dbId);
            } elseif ($databaseType === 'pgsql') {
                $this->extractAndSavePostgreSqlTables($connectionName, $dbId);
            } else {
                Log::warning('Type de base de données non supporté pour l\'extraction des tables', ['type' => $databaseType]);
            }
            
            Log::info('Fin extraction des tables');
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les tables de SQL Server
     */
    private function extractAndSaveSqlServerTables($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des tables
            $tables = DB::connection($connectionName)->select("
                SELECT 
                    t.name AS table_name,
                    s.name AS schema_name,
                    ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description,
                    CONVERT(VARCHAR(50), SCHEMA_NAME(t.schema_id) + '.' + t.name) AS full_name,
                    t.create_date,
                    t.modify_date
                FROM 
                    sys.tables t
                INNER JOIN 
                    sys.schemas s ON t.schema_id = s.schema_id
                LEFT JOIN 
                    sys.extended_properties ep ON ep.major_id = t.object_id 
                    AND ep.minor_id = 0 
                    AND ep.name = 'MS_Description'
                WHERE 
                    t.is_ms_shipped = 0
                ORDER BY 
                    s.name, t.name
            ");
            
            Log::info('Tables SQL Server trouvées: ' . count($tables));
            
            foreach ($tables as $table) {
                try {
                    // Créer une entrée dans table_description
                    $tableDescription = TableDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'tablename' => $table->table_name
                        ],
                        [
                            'language' => 'fr',
                            'description' => $table->description ?? null,
                            'updated_at' => now()
                        ]
                    );
                    
                    // Récupérer les colonnes de la table
                    $columns = DB::connection($connectionName)->select("
                        SELECT 
                            c.name AS column_name,
                            t.name AS data_type,
                            c.max_length,
                            c.precision,
                            c.scale,
                            c.is_nullable,
                            CASE WHEN pk.column_id IS NOT NULL THEN 'PK' ELSE '' END AS key_type,
                            ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                        FROM 
                            sys.columns c
                        INNER JOIN 
                            sys.types t ON c.user_type_id = t.user_type_id
                        INNER JOIN 
                            sys.tables tbl ON c.object_id = tbl.object_id
                        LEFT JOIN 
                            (SELECT 
                                ic.column_id, ic.object_id
                             FROM 
                                sys.indexes i
                             INNER JOIN 
                                sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
                             WHERE 
                                i.is_primary_key = 1) pk 
                            ON pk.column_id = c.column_id AND pk.object_id = c.object_id
                        LEFT JOIN 
                            sys.extended_properties ep ON ep.major_id = c.object_id 
                            AND ep.minor_id = c.column_id 
                            AND ep.name = 'MS_Description'
                        WHERE 
                            tbl.name = ?
                        ORDER BY 
                            c.column_id
                    ", [$table->table_name]);
                    
                    // Supprimer les anciennes colonnes pour cette table
                    TableStructure::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouvelles colonnes
                    foreach ($columns as $column) {
                        $dataType = $this->formatDataType($column);
                        
                        TableStructure::create([
                            'id_table' => $tableDescription->id,
                            'column' => $column->column_name,
                            'type' => $dataType,
                            'nullable' => $column->is_nullable ? 1 : 0,
                            'key' => $column->key_type,
                            'description' => $column->description ?? null
                        ]);
                    }
                    
                    // Récupérer les index de la table
                    $indexes = DB::connection($connectionName)->select("
                        SELECT 
                            i.name AS index_name,
                            i.type_desc AS index_type,
                            STRING_AGG(c.name, ', ') WITHIN GROUP (ORDER BY ic.key_ordinal) AS column_names,
                            i.is_unique,
                            i.is_primary_key
                        FROM 
                            sys.indexes i
                        INNER JOIN 
                            sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
                        INNER JOIN 
                            sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
                        INNER JOIN 
                            sys.tables t ON i.object_id = t.object_id
                        WHERE 
                            t.name = ?
                            AND i.name IS NOT NULL
                        GROUP BY 
                            i.name, i.type_desc, i.is_unique, i.is_primary_key
                        ORDER BY 
                            i.is_primary_key DESC, i.name
                    ", [$table->table_name]);
                    
                    // Supprimer les anciens index pour cette table
                    TableIndex::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouveaux index
                    foreach ($indexes as $index) {
                        $properties = [];
                        if ($index->is_primary_key) $properties[] = 'PRIMARY KEY';
                        if ($index->is_unique) $properties[] = 'UNIQUE';
                        
                        TableIndex::create([
                            'id_table' => $tableDescription->id,
                            'name' => $index->index_name,
                            'type' => $index->index_type,
                            'column' => $index->column_names,
                            'properties' => implode(', ', $properties)
                        ]);
                    }
                    
                    // Récupérer les relations de clés étrangères
                    $foreignKeys = DB::connection($connectionName)->select("
                        SELECT 
                            fk.name AS constraint_name,
                            COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS column_name,
                            OBJECT_NAME(fkc.referenced_object_id) AS referenced_table,
                            COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS referenced_column,
                            CASE 
                                WHEN fk.delete_referential_action = 1 THEN 'CASCADE'
                                WHEN fk.delete_referential_action = 2 THEN 'SET NULL'
                                WHEN fk.delete_referential_action = 3 THEN 'SET DEFAULT'
                                ELSE 'NO ACTION'
                            END AS delete_action
                        FROM 
                            sys.foreign_keys fk
                        INNER JOIN 
                            sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
                        INNER JOIN 
                            sys.tables t ON fk.parent_object_id = t.object_id
                        WHERE 
                            t.name = ?
                        ORDER BY 
                            fk.name
                    ", [$table->table_name]);
                    
                    // Supprimer les anciennes relations pour cette table
                    TableRelation::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouvelles relations
                    foreach ($foreignKeys as $fk) {
                        TableRelation::create([
                            'id_table' => $tableDescription->id,
                            'constraints' => $fk->constraint_name,
                            'column' => $fk->column_name,
                            'referenced_table' => $fk->referenced_table,
                            'referenced_column' => $fk->referenced_column,
                            'action' => $fk->delete_action
                        ]);
                    }
                    
                    Log::info('Table extraite et sauvegardée: ' . $table->table_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la table', [
                        'table' => $table->table_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveSqlServerTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les tables de MySQL
     */
    private function extractAndSaveMySqlTables($connectionName, $dbId)
    {
        try {
            // Récupérer le nom de la base de données
            $database = DB::connection($connectionName)->getDatabaseName();
            
            // Récupérer la liste des tables
            $tables = DB::connection($connectionName)->select("
                SELECT 
                    TABLE_NAME AS table_name,
                    TABLE_SCHEMA AS schema_name,
                    TABLE_COMMENT AS description,
                    CREATE_TIME AS create_date,
                    UPDATE_TIME AS modify_date
                FROM 
                    INFORMATION_SCHEMA.TABLES
                WHERE 
                    TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY 
                    TABLE_NAME
            ", [$database]);
            
            Log::info('Tables MySQL trouvées: ' . count($tables));
            
            foreach ($tables as $table) {
                try {
                    // Créer une entrée dans table_description
                    $tableDescription = TableDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'tablename' => $table->table_name
                        ],
                        [
                            'language' => 'fr',
                            'description' => $table->description ?? null,
                            'updated_at' => now()
                        ]
                    );
                    
                    // Récupérer les colonnes de la table
                    $columns = DB::connection($connectionName)->select("
                        SELECT 
                            COLUMN_NAME AS column_name,
                            DATA_TYPE AS data_type,
                            CHARACTER_MAXIMUM_LENGTH AS max_length,
                            NUMERIC_PRECISION AS precision,
                            NUMERIC_SCALE AS scale,
                            IS_NULLABLE = 'YES' AS is_nullable,
                            COLUMN_KEY AS key_type,
                            COLUMN_COMMENT AS description
                        FROM 
                            INFORMATION_SCHEMA.COLUMNS
                        WHERE 
                            TABLE_SCHEMA = ? AND TABLE_NAME = ?
                        ORDER BY 
                            ORDINAL_POSITION
                    ", [$database, $table->table_name]);
                    
                    // Supprimer les anciennes colonnes pour cette table
                    TableStructure::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouvelles colonnes
                    foreach ($columns as $column) {
                        // Convertir le format de clé MySQL en format interne
                        $keyType = '';
                        if ($column->key_type === 'PRI') $keyType = 'PK';
                        else if ($column->key_type === 'MUL') $keyType = 'FK';
                        else if ($column->key_type === 'UNI') $keyType = 'UK';
                        
                        $dataType = $this->formatMySqlDataType($column);
                        
                        TableStructure::create([
                            'id_table' => $tableDescription->id,
                            'column' => $column->column_name,
                            'type' => $dataType,
                            'nullable' => $column->is_nullable ? 1 : 0,
                            'key' => $keyType,
                            'description' => $column->description ?? null
                        ]);
                    }
                    
                    // Récupérer les index de la table
                    $indexes = DB::connection($connectionName)->select("
                        SELECT 
                            INDEX_NAME AS index_name,
                            CASE 
                                WHEN INDEX_TYPE = 'FULLTEXT' THEN 'FULLTEXT'
                                WHEN NON_UNIQUE = 0 AND INDEX_NAME = 'PRIMARY' THEN 'PRIMARY KEY'
                                WHEN NON_UNIQUE = 0 THEN 'UNIQUE'
                                ELSE 'INDEX'
                            END AS index_type,
                            GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS column_names,
                            INDEX_NAME = 'PRIMARY' AS is_primary_key,
                            NON_UNIQUE = 0 AS is_unique
                        FROM 
                            INFORMATION_SCHEMA.STATISTICS
                        WHERE 
                            TABLE_SCHEMA = ? AND TABLE_NAME = ?
                        GROUP BY 
                            INDEX_NAME, INDEX_TYPE, NON_UNIQUE
                        ORDER BY 
                            INDEX_NAME
                    ", [$database, $table->table_name]);
                    
                    // Supprimer les anciens index pour cette table
                    TableIndex::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouveaux index
                    foreach ($indexes as $index) {
                        $properties = [];
                        if ($index->is_primary_key) $properties[] = 'PRIMARY KEY';
                        if ($index->is_unique) $properties[] = 'UNIQUE';
                        
                        TableIndex::create([
                            'id_table' => $tableDescription->id,
                            'name' => $index->index_name,
                            'type' => $index->index_type,
                            'column' => $index->column_names,
                            'properties' => implode(', ', $properties)
                        ]);
                    }
                    
                    // Récupérer les relations de clés étrangères
                    $foreignKeys = DB::connection($connectionName)->select("
                        SELECT 
                            CONSTRAINT_NAME AS constraint_name,
                            COLUMN_NAME AS column_name,
                            REFERENCED_TABLE_NAME AS referenced_table,
                            REFERENCED_COLUMN_NAME AS referenced_column,
                            'CASCADE' AS delete_action
                        FROM 
                            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                        WHERE 
                            TABLE_SCHEMA = ? AND TABLE_NAME = ?
                            AND REFERENCED_TABLE_NAME IS NOT NULL
                        ORDER BY 
                            CONSTRAINT_NAME
                    ", [$database, $table->table_name]);
                    
                    // Supprimer les anciennes relations pour cette table
                    TableRelations::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouvelles relations
                    foreach ($foreignKeys as $fk) {
                        TableRelations::create([
                            'id_table' => $tableDescription->id,
                            'constraints' => $fk->constraint_name,
                            'column' => $fk->column_name,
                            'referenced_table' => $fk->referenced_table,
                            'referenced_column' => $fk->referenced_column,
                            'action' => $fk->delete_action
                        ]);
                    }
                    
                    Log::info('Table extraite et sauvegardée: ' . $table->table_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la table MySQL', [
                        'table' => $table->table_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveMySqlTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les tables de PostgreSQL
     */
    private function extractAndSavePostgreSqlTables($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des tables
            $tables = DB::connection($connectionName)->select("
                SELECT 
                    c.relname AS table_name,
                    n.nspname AS schema_name,
                    COALESCE(d.description, '') AS description
                FROM 
                    pg_class c
                JOIN 
                    pg_namespace n ON n.oid = c.relnamespace
                LEFT JOIN 
                    pg_description d ON d.objoid = c.oid AND d.objsubid = 0
                WHERE 
                    c.relkind = 'r'
                    AND n.nspname NOT IN ('pg_catalog', 'information_schema')
                ORDER BY 
                    n.nspname, c.relname
            ");
            
            Log::info('Tables PostgreSQL trouvées: ' . count($tables));
            
            foreach ($tables as $table) {
                try {
                    // Créer une entrée dans table_description
                    $tableDescription = TableDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'tablename' => $table->table_name
                        ],
                        [
                            'language' => 'fr',
                            'description' => $table->description ?? null,
                            'updated_at' => now()
                        ]
                    );
                    
                    // Récupérer les colonnes de la table
                    $columns = DB::connection($connectionName)->select("
                        SELECT 
                            a.attname AS column_name,
                            pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type,
                            CASE 
                                WHEN a.atttypmod > 0 THEN a.atttypmod - 4
                                ELSE NULL
                            END AS max_length,
                            NULL AS precision,
                            NULL AS scale,
                            NOT a.attnotnull AS is_nullable,
                            CASE 
                                WHEN pk.contype = 'p' THEN 'PK'
                                WHEN fk.conname IS NOT NULL THEN 'FK'
                                ELSE ''
                            END AS key_type,
                            COALESCE(d.description, '') AS description
                        FROM 
                            pg_catalog.pg_attribute a
                        JOIN 
                            pg_catalog.pg_class c ON c.oid = a.attrelid
                        JOIN 
                            pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                        LEFT JOIN 
                            pg_catalog.pg_description d ON d.objoid = a.attrelid AND d.objsubid = a.attnum
                        LEFT JOIN (
                            SELECT 
                                contype, conrelid, conkey, conname
                            FROM 
                                pg_catalog.pg_constraint
                            WHERE 
                                contype = 'p'
                        ) pk ON pk.conrelid = c.oid AND a.attnum = ANY(pk.conkey)
                        LEFT JOIN (
                            SELECT 
                                conname, conrelid, conkey
                            FROM 
                                pg_catalog.pg_constraint
                            WHERE 
                                contype = 'f'
                        ) fk ON fk.conrelid = c.oid AND a.attnum = ANY(fk.conkey)
                        WHERE 
                            c.relname = ?
                            AND n.nspname = ?
                            AND a.attnum > 0
                            AND NOT a.attisdropped
                        ORDER BY 
                            a.attnum
                    ", [$table->table_name, $table->schema_name]);
                    
                    // Supprimer les anciennes colonnes pour cette table
                    TableStructure::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouvelles colonnes
                    foreach ($columns as $column) {
                        TableStructure::create([
                            'id_table' => $tableDescription->id,
                            'column' => $column->column_name,
                            'type' => $column->data_type,
                            'nullable' => $column->is_nullable ? 1 : 0,
                            'key' => $column->key_type,
                            'description' => $column->description ?? null
                        ]);
                    }
                    
                    // Récupérer les index de la table
                    $indexes = DB::connection($connectionName)->select("
                        SELECT 
                            i.relname AS index_name,
                            am.amname AS index_type,
                            array_to_string(array_agg(a.attname ORDER BY indseq.ord), ', ') AS column_names,
                            ix.indisprimary AS is_primary_key,
                            ix.indisunique AS is_unique
                        FROM 
                            pg_index ix
                        JOIN 
                            pg_class i ON i.oid = ix.indexrelid
                        JOIN 
                            pg_class t ON t.oid = ix.indrelid
                        JOIN 
                            pg_am am ON am.oid = i.relam
                        JOIN 
                            pg_namespace n ON n.oid = t.relnamespace
                        JOIN 
                            pg_attribute a ON a.attrelid = t.oid
                        JOIN 
                            LATERAL unnest(ix.indkey) WITH ORDINALITY AS indseq(key, ord) 
                            ON a.attnum = indseq.key
                        WHERE 
                            t.relname = ?
                            AND n.nspname = ?
                        GROUP BY 
                            i.relname, am.amname, ix.indisprimary, ix.indisunique
                        ORDER BY 
                            ix.indisprimary DESC, i.relname
                    ", [$table->table_name, $table->schema_name]);
                    
                    // Supprimer les anciens index pour cette table
                    TableIndex::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouveaux index
                    foreach ($indexes as $index) {
                        $properties = [];
                        if ($index->is_primary_key) $properties[] = 'PRIMARY KEY';
                        if ($index->is_unique) $properties[] = 'UNIQUE';
                        
                        TableIndex::create([
                            'id_table' => $tableDescription->id,
                            'name' => $index->index_name,
                            'type' => $index->index_type,
                            'column' => $index->column_names,
                            'properties' => implode(', ', $properties)
                        ]);
                    }
                    
                    // Récupérer les relations de clés étrangères
                    $foreignKeys = DB::connection($connectionName)->select("
                        SELECT 
                            con.conname AS constraint_name,
                            att.attname AS column_name,
                            cl.relname AS referenced_table,
                            att2.attname AS referenced_column,
                            CASE con.confdeltype
                                WHEN 'a' THEN 'NO ACTION'
                                WHEN 'r' THEN 'RESTRICT'
                                WHEN 'c' THEN 'CASCADE'
                                WHEN 'n' THEN 'SET NULL'
                                WHEN 'd' THEN 'SET DEFAULT'
                            END AS delete_action
                        FROM (
                            SELECT 
                                conname, conrelid, confrelid, conkey, confkey, confdeltype
                            FROM 
                                pg_constraint
                            WHERE 
                                contype = 'f'
                        ) con
                        JOIN 
                            pg_class cl ON cl.oid = con.confrelid
                        JOIN 
                            pg_class cl2 ON cl2.oid = con.conrelid
                        JOIN 
                            pg_attribute att ON att.attrelid = con.conrelid AND att.attnum = con.conkey[1]
                        JOIN 
                            pg_attribute att2 ON att2.attrelid = con.confrelid AND att2.attnum = con.confkey[1]
                        WHERE 
                            cl2.relname = ?
                        ORDER BY 
                            con.conname
                    ", [$table->table_name]);
                    
                    // Supprimer les anciennes relations pour cette table
                    TableRelations::where('id_table', $tableDescription->id)->delete();
                    
                    // Sauvegarder les nouvelles relations
                    foreach ($foreignKeys as $fk) {
                        TableRelations::create([
                            'id_table' => $tableDescription->id,
                            'constraints' => $fk->constraint_name,
                            'column' => $fk->column_name,
                            'referenced_table' => $fk->referenced_table,
                            'referenced_column' => $fk->referenced_column,
                            'action' => $fk->delete_action
                        ]);
                    }
                    
                    Log::info('Table extraite et sauvegardée: ' . $table->table_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la table PostgreSQL', [
                        'table' => $table->table_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSavePostgreSqlTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les vues de la base de données
     */
    private function extractAndSaveViews($connectionName, $dbId, $databaseType)
    {
        try {
            Log::info('Début extraction des vues...');
            
            if ($databaseType === 'sqlsrv') {
                $this->extractAndSaveSqlServerViews($connectionName, $dbId);
            } elseif ($databaseType === 'mysql') {
                $this->extractAndSaveMySqlViews($connectionName, $dbId);
            } elseif ($databaseType === 'pgsql') {
                $this->extractAndSavePostgreSqlViews($connectionName, $dbId);
            } else {
                Log::warning('Type de base de données non supporté pour l\'extraction des vues', ['type' => $databaseType]);
            }
            
            Log::info('Fin extraction des vues');
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveViews', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les vues de SQL Server
     */
    private function extractAndSaveSqlServerViews($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des vues
            $views = DB::connection($connectionName)->select("
                SELECT 
                    v.name AS view_name,
                    s.name AS schema_name,
                    OBJECT_DEFINITION(v.object_id) AS definition,
                    v.create_date,
                    v.modify_date
                FROM 
                    sys.views v
                INNER JOIN 
                    sys.schemas s ON v.schema_id = s.schema_id
                WHERE 
                    v.is_ms_shipped = 0
                ORDER BY 
                    s.name, v.name
            ");
            
            Log::info('Vues SQL Server trouvées: ' . count($views));
            
            foreach ($views as $view) {
                try {
                    // Créer une entrée dans view_description
                    $viewDescription = ViewDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'viewname' => $view->view_name
                        ]
                    );
                    
                    // Récupérer les colonnes de la vue
                    $columns = DB::connection($connectionName)->select("
                        SELECT 
                            c.name AS column_name,
                            t.name AS data_type,
                            c.max_length,
                            c.precision,
                            c.scale,
                            c.is_nullable
                        FROM 
                            sys.columns c
                        INNER JOIN 
                            sys.types t ON c.user_type_id = t.user_type_id
                        INNER JOIN 
                            sys.views v ON c.object_id = v.object_id
                        WHERE 
                            v.name = ?
                        ORDER BY 
                            c.column_id
                    ", [$view->view_name]);
                    
                    // Supprimer les anciennes informations pour cette vue
                    ViewInformation::where('id_view', $viewDescription->id)->delete();
                    ViewColumn::where('id_view', $viewDescription->id)->delete();
                    
                    // Sauvegarder les informations de la vue
                    ViewInformation::create([
                        'id_view' => $viewDescription->id,
                        'schema_name' => $view->schema_name,
                        'definition' => $view->definition
                    ]);
                    
                    // Sauvegarder les colonnes
                    foreach ($columns as $column) {
                        $dataType = $this->formatDataType($column);
                        
                        ViewColumn::create([
                            'id_view' => $viewDescription->id,
                            'name' => $column->column_name,
                            'type' => $dataType,
                            'nullable' => $column->is_nullable ? 1 : 0
                        ]);
                    }
                    
                    Log::info('Vue extraite et sauvegardée: ' . $view->view_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la vue', [
                        'view' => $view->view_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveSqlServerViews', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les vues de MySQL
     */
    private function extractAndSaveMySqlViews($connectionName, $dbId)
    {
        try {
            // Récupérer le nom de la base de données
            $database = DB::connection($connectionName)->getDatabaseName();
            
            // Récupérer la liste des vues
            $views = DB::connection($connectionName)->select("
                SELECT 
                    TABLE_NAME AS view_name,
                    TABLE_SCHEMA AS schema_name,
                    VIEW_DEFINITION AS definition,
                    CREATE_TIME AS create_date,
                    LAST_ALTERED AS modify_date
                FROM 
                    INFORMATION_SCHEMA.VIEWS
                WHERE 
                    TABLE_SCHEMA = ?
                ORDER BY 
                    TABLE_NAME
            ", [$database]);
            
            Log::info('Vues MySQL trouvées: ' . count($views));
            
            foreach ($views as $view) {
                try {
                    // Créer une entrée dans view_description
                    $viewDescription = ViewDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'viewname' => $view->view_name
                        ]
                    );
                    
                    // Récupérer les colonnes de la vue
                    $columns = DB::connection($connectionName)->select("
                        SELECT 
                            COLUMN_NAME AS column_name,
                            DATA_TYPE AS data_type,
                            CHARACTER_MAXIMUM_LENGTH AS max_length,
                            NUMERIC_PRECISION AS precision,
                            NUMERIC_SCALE AS scale,
                            IS_NULLABLE = 'YES' AS is_nullable
                        FROM 
                            INFORMATION_SCHEMA.COLUMNS
                        WHERE 
                            TABLE_SCHEMA = ? AND TABLE_NAME = ?
                        ORDER BY 
                            ORDINAL_POSITION
                    ", [$database, $view->view_name]);
                    
                    // Supprimer les anciennes informations pour cette vue
                    ViewInformation::where('id_view', $viewDescription->id)->delete();
                    ViewColumn::where('id_view', $viewDescription->id)->delete();
                    
                    // Sauvegarder les informations de la vue
                    ViewInformation::create([
                        'id_view' => $viewDescription->id,
                        'schema_name' => $view->schema_name,
                        'definition' => $view->definition
                    ]);
                    
                    // Sauvegarder les colonnes
                    foreach ($columns as $column) {
                        $dataType = $this->formatMySqlDataType($column);
                        
                        ViewColumn::create([
                            'id_view' => $viewDescription->id,
                            'name' => $column->column_name,
                            'type' => $dataType,
                            'nullable' => $column->is_nullable ? 1 : 0
                        ]);
                    }
                    
                    Log::info('Vue extraite et sauvegardée: ' . $view->view_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la vue MySQL', [
                        'view' => $view->view_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveMySqlViews', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les fonctions de la base de données
     */
    private function extractAndSaveFunctions($connectionName, $dbId, $databaseType)
    {
        try {
            Log::info('Début extraction des fonctions...');
            
            if ($databaseType === 'sqlsrv') {
                $this->extractAndSaveSqlServerFunctions($connectionName, $dbId);
            } elseif ($databaseType === 'mysql') {
                $this->extractAndSaveMySqlFunctions($connectionName, $dbId);
            } elseif ($databaseType === 'pgsql') {
                $this->extractAndSavePostgreSqlFunctions($connectionName, $dbId);
            } else {
                Log::warning('Type de base de données non supporté pour l\'extraction des fonctions', ['type' => $databaseType]);
            }
            
            Log::info('Fin extraction des fonctions');
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveFunctions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les fonctions de SQL Server
     */
    private function extractAndSaveSqlServerFunctions($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des fonctions
            $functions = DB::connection($connectionName)->select("
                SELECT 
                    o.name AS function_name,
                    s.name AS schema_name,
                    o.type_desc AS function_type,
                    OBJECT_DEFINITION(o.object_id) AS definition,
                    o.create_date,
                    o.modify_date
                FROM 
                    sys.objects o
                INNER JOIN 
                    sys.schemas s ON o.schema_id = s.schema_id
                WHERE 
                    o.type IN ('FN', 'IF', 'TF')
                    AND o.is_ms_shipped = 0
                ORDER BY 
                    s.name, o.name
            ");
            
            Log::info('Fonctions SQL Server trouvées: ' . count($functions));
            
            foreach ($functions as $function) {
                try {
                    // Créer une entrée dans function_description
                    $functionDescription = FunctionDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'functionname' => $function->function_name
                        ],
                        [
                            'language' => 'fr',
                            'updated_at' => now()
                        ]
                    );
                    
                    // Récupérer le type de retour et les paramètres
                    $returnType = DB::connection($connectionName)->select("
                        SELECT 
                            t.name AS type_name
                        FROM 
                            sys.objects o
                        JOIN 
                            sys.parameters p ON p.object_id = o.object_id AND p.parameter_id = 0
                        JOIN 
                            sys.types t ON p.user_type_id = t.user_type_id
                        WHERE 
                            o.name = ?
                    ", [$function->function_name]);
                    
                    $returnTypeName = count($returnType) > 0 ? $returnType[0]->type_name : 'unknown';
                    
                    // Récupérer les paramètres
                    $parameters = DB::connection($connectionName)->select("
                        SELECT 
                            p.name AS parameter_name,
                            t.name AS data_type,
                            p.max_length,
                            p.precision,
                            p.scale,
                            p.is_output,
                            p.parameter_id,
                            p.has_default_value,
                            p.default_value
                        FROM 
                            sys.parameters p
                        INNER JOIN 
                            sys.types t ON p.user_type_id = t.user_type_id
                        INNER JOIN 
                            sys.objects o ON p.object_id = o.object_id
                        WHERE 
                            o.name = ?
                            AND p.parameter_id > 0
                        ORDER BY 
                            p.parameter_id
                    ", [$function->function_name]);
                    
                    // Supprimer les anciennes informations pour cette fonction
                    FuncInformation::where('id_func', $functionDescription->id)->delete();
                    FuncParameter::where('id_func', $functionDescription->id)->delete();
                    
                    // Sauvegarder les informations de la fonction
                    FuncInformation::create([
                        'id_func' => $functionDescription->id,
                        'type' => $function->function_type,
                        'return_type' => $returnTypeName,
                        'creation_date' => $function->create_date,
                        'last_change_date' => $function->modify_date,
                        'definition' => $function->definition
                    ]);
                    
                    // Sauvegarder les paramètres
                    foreach ($parameters as $param) {
                        $dataType = $this->formatDataType($param);
                        
                        FuncParameter::create([
                            'id_func' => $functionDescription->id,
                            'name' => $param->parameter_name,
                            'type' => $dataType,
                            'output' => $param->is_output ? 'OUTPUT' : 'INPUT',
                            'definition' => null
                        ]);
                    }
                    
                    Log::info('Fonction extraite et sauvegardée: ' . $function->function_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la fonction', [
                        'function' => $function->function_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveSqlServerFunctions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les fonctions de MySQL
     */
    private function extractAndSaveMySqlFunctions($connectionName, $dbId)
    {
        try {
            // Récupérer le nom de la base de données
            $database = DB::connection($connectionName)->getDatabaseName();
            
            // Récupérer la liste des fonctions
            $functions = DB::connection($connectionName)->select("
                SELECT 
                    ROUTINE_NAME AS function_name,
                    ROUTINE_SCHEMA AS schema_name,
                    DTD_IDENTIFIER AS return_type,
                    ROUTINE_DEFINITION AS definition,
                    CREATED AS create_date,
                    LAST_ALTERED AS modify_date
                FROM 
                    INFORMATION_SCHEMA.ROUTINES
                WHERE 
                    ROUTINE_SCHEMA = ? AND ROUTINE_TYPE = 'FUNCTION'
                ORDER BY 
                    ROUTINE_NAME
            ", [$database]);
            
            Log::info('Fonctions MySQL trouvées: ' . count($functions));
            
            foreach ($functions as $function) {
                try {
                    // Créer une entrée dans function_description
                    $functionDescription = FunctionDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'functionname' => $function->function_name
                        ],
                        [
                            'language' => 'fr',
                            'updated_at' => now()
                        ]
                    );
                    
                    // Récupérer les paramètres
                    $parameters = DB::connection($connectionName)->select("
                        SELECT 
                            PARAMETER_NAME AS parameter_name,
                            DATA_TYPE AS data_type,
                            CHARACTER_MAXIMUM_LENGTH AS max_length,
                            NUMERIC_PRECISION AS precision,
                            NUMERIC_SCALE AS scale,
                            PARAMETER_MODE IN ('OUT', 'INOUT') AS is_output,
                            ORDINAL_POSITION AS parameter_id
                        FROM 
                            INFORMATION_SCHEMA.PARAMETERS
                        WHERE 
                            SPECIFIC_SCHEMA = ? AND SPECIFIC_NAME = ?
                            AND PARAMETER_NAME IS NOT NULL
                        ORDER BY 
                            ORDINAL_POSITION
                    ", [$database, $function->function_name]);
                    
                    // Supprimer les anciennes informations pour cette fonction
                    FuncInformation::where('id_func', $functionDescription->id)->delete();
                    FuncParameter::where('id_func', $functionDescription->id)->delete();
                    
                    // Sauvegarder les informations de la fonction
                    FuncInformation::create([
                        'id_func' => $functionDescription->id,
                        'type' => 'FUNCTION',
                        'return_type' => $function->return_type,
                        'creation_date' => $function->create_date,
                        'last_change_date' => $function->modify_date
                    ]);
                    
                    // Sauvegarder les paramètres
                    foreach ($parameters as $param) {
                        $dataType = $this->formatMySqlDataType($param);
                        
                        FuncParameter::create([
                            'id_func' => $functionDescription->id,
                            'name' => $param->parameter_name,
                            'type' => $dataType,
                            'output' => $param->is_output ? 'OUTPUT' : 'INPUT',
                            'definition' => null
                        ]);
                    }
                    
                    Log::info('Fonction extraite et sauvegardée: ' . $function->function_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la fonction MySQL', [
                        'function' => $function->function_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveMySqlFunctions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extrait et sauvegarde les fonctions de PostgreSQL
     */
    private function extractAndSavePostgreSqlFunctions($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des fonctions
            $functions = DB::connection($connectionName)->select("
                SELECT 
                    p.proname AS function_name,
                    n.nspname AS schema_name,
                    pg_get_function_result(p.oid) AS return_type,
                    pg_get_functiondef(p.oid) AS definition
                FROM 
                    pg_proc p
                JOIN 
                    pg_namespace n ON n.oid = p.pronamespace
                WHERE 
                    p.prokind = 'f'
                    AND n.nspname NOT IN ('pg_catalog', 'information_schema')
                ORDER BY 
                    n.nspname, p.proname
            ");
            
            Log::info('Fonctions PostgreSQL trouvées: ' . count($functions));
            
            foreach ($functions as $function) {
                try {
                    // Créer une entrée dans function_description
                    $functionDescription = FunctionDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'functionname' => $function->function_name
                        ],
                        [
                            'language' => 'fr',
                            'updated_at' => now()
                        ]
                    );
                    
                    // Récupérer les paramètres (simplifiés car PostgreSQL a une structure plus complexe)
                    $parametersText = DB::connection($connectionName)->select("
                        SELECT 
                            pg_get_function_arguments(p.oid) AS args
                        FROM 
                            pg_proc p
                        JOIN 
                            pg_namespace n ON n.oid = p.pronamespace
                        WHERE 
                            p.proname = ? 
                            AND n.nspname = ?
                    ", [$function->function_name, $function->schema_name]);
                    
                    $argsText = count($parametersText) > 0 ? $parametersText[0]->args : '';
                    $paramArray = explode(',', $argsText);
                    
                    // Supprimer les anciennes informations pour cette fonction
                    FuncInformation::where('id_func', $functionDescription->id)->delete();
                    FuncParameter::where('id_func', $functionDescription->id)->delete();
                    
                    // Sauvegarder les informations de la fonction
                    FuncInformation::create([
                        'id_func' => $functionDescription->id,
                        'type' => 'FUNCTION',
                        'return_type' => $function->return_type,
                        'creation_date' => now(),
                        'last_change_date' => now()
                    ]);
                    
                    // Sauvegarder les paramètres (traitement simplifié)
                    foreach ($paramArray as $index => $paramText) {
                        if (empty(trim($paramText))) continue;
                        
                        // Tentative d'extraction du nom et du type
                        $parts = explode(' ', trim($paramText));
                        if (count($parts) >= 2) {
                            $paramName = $parts[0];
                            $paramType = $parts[1];
                            $isOut = stripos($paramText, 'OUT') !== false || stripos($paramText, 'INOUT') !== false;
                            
                            FuncParameter::create([
                                'id_func' => $functionDescription->id,
                                'name' => $paramName,
                                'type' => $paramType,
                                'output' => $isOut ? 'OUTPUT' : 'INPUT',
                                'definition' => null
                            ]);
                        }
                    }
                    
                    Log::info('Fonction extraite et sauvegardée: ' . $function->function_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la fonction PostgreSQL', [
                        'function' => $function->function_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSavePostgreSqlFunctions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
 * Extrait et sauvegarde les procédures stockées de la base de données
 */
    private function extractAndSaveProcedures($connectionName, $dbId, $databaseType)
    {
        try {
            Log::info('Début extraction des procédures stockées...');
            
            if ($databaseType === 'sqlsrv') {
                $this->extractAndSaveSqlServerProcedures($connectionName, $dbId);
            } elseif ($databaseType === 'mysql') {
                $this->extractAndSaveMySqlProcedures($connectionName, $dbId);
            } elseif ($databaseType === 'pgsql') {
                $this->extractAndSavePostgreSqlProcedures($connectionName, $dbId);
            } else {
                Log::warning('Type de base de données non supporté pour l\'extraction des procédures stockées', ['type' => $databaseType]);
            }
            
            Log::info('Fin extraction des procédures stockées');
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveProcedures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

/**
 * Extrait et sauvegarde les procédures stockées de SQL Server
 */
    private function extractAndSaveSqlServerProcedures($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des procédures stockées
            $procedures = DB::connection($connectionName)->select("
                SELECT 
                    p.name AS procedure_name,
                    s.name AS schema_name,
                    OBJECT_DEFINITION(p.object_id) AS definition,
                    p.create_date,
                    p.modify_date
                FROM 
                    sys.procedures p
                INNER JOIN 
                    sys.schemas s ON p.schema_id = s.schema_id
                WHERE 
                    p.is_ms_shipped = 0
                ORDER BY 
                    s.name, p.name
            ");
            
            Log::info('Procédures SQL Server trouvées: ' . count($procedures));
            
            foreach ($procedures as $procedure) {
                try {
                    // Créer une entrée dans ps_description
                    $psDescription = \App\Models\PsDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'psname' => $procedure->procedure_name
                        ],
                        [
                            'language' => 'fr',
                            'updated_at' => now()
                        ]
                    );
                    
                    // Récupérer les paramètres
                    $parameters = DB::connection($connectionName)->select("
                        SELECT 
                            p.name AS parameter_name,
                            t.name AS data_type,
                            p.max_length,
                            p.precision,
                            p.scale,
                            p.is_output,
                            p.parameter_id,
                            p.has_default_value,
                            p.default_value
                        FROM 
                            sys.parameters p
                        INNER JOIN 
                            sys.types t ON p.user_type_id = t.user_type_id
                        INNER JOIN 
                            sys.procedures ON p.object_id = procedures.object_id
                        WHERE 
                            procedures.name = ?
                        ORDER BY 
                            p.parameter_id
                    ", [$procedure->procedure_name]);
                    
                    // Supprimer les anciennes informations pour cette procédure
                    \App\Models\PsInformation::where('id_ps', $psDescription->id)->delete();
                    \App\Models\PsParameter::where('id_ps', $psDescription->id)->delete();
                    
                    // Sauvegarder les informations de la procédure
                    \App\Models\PsInformation::create([
                        'id_ps' => $psDescription->id,
                        'schema' => $procedure->schema_name,
                        'creation_date' => $procedure->create_date,
                        'last_change_date' => $procedure->modify_date,
                        'definition' => $procedure->definition
                    ]);
                    
                    // Sauvegarder les paramètres
                    foreach ($parameters as $param) {
                        $dataType = $this->formatDataType($param);
                        
                        \App\Models\PsParameter::create([
                            'id_ps' => $psDescription->id,
                            'name' => $param->parameter_name,
                            'type' => $dataType,
                            'output' => $param->is_output ? 'OUTPUT' : 'INPUT',
                            'definition' => null,
                            'default_value' => $param->has_default_value ? $param->default_value : null
                        ]);
                    }
                    
                    Log::info('Procédure extraite et sauvegardée: ' . $procedure->procedure_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction de la procédure', [
                        'procedure' => $procedure->procedure_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveSqlServerProcedures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

/**
 * Extrait et sauvegarde les procédures stockées de MySQL
 */
    private function extractAndSaveMySqlProcedures($connectionName, $dbId)
    {
        try {
            // Récupérer le nom de la base de données
            $database = DB::connection($connectionName)->getDatabaseName();
            
            // Récupérer la liste des procédures stockées
            $procedures = DB::connection($connectionName)->select("
                SELECT 
                    ROUTINE_NAME AS procedure_name,
                    ROUTINE_SCHEMA AS schema_name,
                    ROUTINE_DEFINITION AS definition,
                    CREATED AS create_date,
                    LAST_ALTERED AS modify_date
                FROM 
                    INFORMATION_SCHEMA.ROUTINES
                WHERE 
                    ROUTINE_SCHEMA = ? AND ROUTINE_TYPE = 'PROCEDURE'
                ORDER BY 
                    ROUTINE_NAME
            ", [$database]);
            
            Log::info('Procédures MySQL trouvées: ' . count($procedures));
            
            // Implémentation pour MySQL (similaire à SQL Server)
            // ...
            
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveMySqlProcedures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

/**
 * Extrait et sauvegarde les procédures stockées de PostgreSQL
 */
    private function extractAndSavePostgreSqlProcedures($connectionName, $dbId)
    {
        try {
            // Implémentation pour PostgreSQL
            // ...
            
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSavePostgreSqlProcedures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
 * Extrait et sauvegarde les triggers de la base de données
 */
    private function extractAndSaveTriggers($connectionName, $dbId, $databaseType)
    {
        try {
            Log::info('Début extraction des triggers...');
            
            if ($databaseType === 'sqlsrv') {
                $this->extractAndSaveSqlServerTriggers($connectionName, $dbId);
            } elseif ($databaseType === 'mysql') {
                $this->extractAndSaveMySqlTriggers($connectionName, $dbId);
            } elseif ($databaseType === 'pgsql') {
                $this->extractAndSavePostgreSqlTriggers($connectionName, $dbId);
            } else {
                Log::warning('Type de base de données non supporté pour l\'extraction des triggers', ['type' => $databaseType]);
            }
            
            Log::info('Fin extraction des triggers');
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveTriggers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

/**
 * Extrait et sauvegarde les triggers de SQL Server
 */
    private function extractAndSaveSqlServerTriggers($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des triggers
            $triggers = DB::connection($connectionName)->select("
                SELECT 
                    t.name AS trigger_name,
                    OBJECT_NAME(t.parent_id) AS table_name,
                    t.is_instead_of_trigger,
                    CASE 
                        WHEN is_insert_trigger = 1 THEN 'INSERT'
                        WHEN is_update_trigger = 1 THEN 'UPDATE'
                        WHEN is_delete_trigger = 1 THEN 'DELETE'
                        ELSE 'MULTIPLE'
                    END AS trigger_event,
                    t.is_disabled,
                    t.create_date,
                    OBJECT_DEFINITION(t.object_id) AS trigger_definition
                FROM 
                    sys.triggers t
                WHERE 
                    t.is_ms_shipped = 0
                ORDER BY 
                    t.name
            ");
            
            Log::info('Triggers SQL Server trouvés: ' . count($triggers));
            
            foreach ($triggers as $trigger) {
                try {
                    // Créer une entrée dans trigger_description
                    $triggerDescription = \App\Models\TriggerDescription::updateOrCreate(
                        [
                            'dbid' => $dbId,
                            'triggername' => $trigger->trigger_name
                        ],
                        [
                            'language' => 'fr',
                            'updated_at' => now()
                        ]
                    );
                    
                    // Déterminer le type de trigger
                    $triggerType = $trigger->is_instead_of_trigger ? 'INSTEAD OF' : 'AFTER';
                    
                    // Supprimer les anciennes informations pour ce trigger
                    \App\Models\TriggerInformation::where('id_trigger', $triggerDescription->id)->delete();
                    
                    // Sauvegarder les informations du trigger
                    \App\Models\TriggerInformation::create([
                        'id_trigger' => $triggerDescription->id,
                        'table' => $trigger->table_name,
                        'type' => $triggerType,
                        'event' => $trigger->trigger_event,
                        'state' => $trigger->is_disabled ? 0 : 1,
                        'creation_date' => $trigger->create_date,
                        'definition' => $trigger->trigger_definition
                    ]);
                    
                    Log::info('Trigger extrait et sauvegardé: ' . $trigger->trigger_name);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'extraction du trigger', [
                        'trigger' => $trigger->trigger_name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveSqlServerTriggers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

/**
 * Extrait et sauvegarde les triggers de MySQL
 */
    private function extractAndSaveMySqlTriggers($connectionName, $dbId)
    {
        try {
            // Récupérer le nom de la base de données
            $database = DB::connection($connectionName)->getDatabaseName();
            
            // Récupérer la liste des triggers
            $triggers = DB::connection($connectionName)->select("
                SELECT 
                    TRIGGER_NAME AS trigger_name,
                    EVENT_OBJECT_TABLE AS table_name,
                    ACTION_TIMING AS trigger_type,
                    EVENT_MANIPULATION AS trigger_event,
                    0 AS is_disabled,
                    CREATED AS create_date,
                    ACTION_STATEMENT AS trigger_definition
                FROM 
                    INFORMATION_SCHEMA.TRIGGERS
                WHERE 
                    TRIGGER_SCHEMA = ?
                ORDER BY 
                    TRIGGER_NAME
            ", [$database]);
            
            Log::info('Triggers MySQL trouvés: ' . count($triggers));
            
            // Implémentation pour MySQL (similaire à SQL Server)
            // ...
            
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveMySqlTriggers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

/**
 * Extrait et sauvegarde les triggers de PostgreSQL
 */
    private function extractAndSavePostgreSqlTriggers($connectionName, $dbId)
    {
        try {
            // Implémentation pour PostgreSQL
            // ...
            
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSavePostgreSqlTriggers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

}