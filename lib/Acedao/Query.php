<?php

namespace Acedao;

use Acedao\Exception\MissingDependencyException;
use Acedao\Exception\MissingKeyException;
use Acedao\Exception\WrongParameterException;

class Query {

    /**
     * @var Container
     */
    private $container;

    protected $aliasesReferences = array();
    protected $aliasesTree = array();
    protected $relationTypes = array();
    protected $relationTableNames = array();

    public function __construct(Container $c) {
        $this->container = $c;
    }

    public function setAliasesReferences($refs) {
        $this->aliasesReferences = $refs;
    }

    public function setAliasesTree($tree) {
        $this->aliasesTree = $tree;
    }

    public function setRelationTypes($types) {
        $this->relationTypes = $types;
    }

    public function getAliasesTree() {
        return $this->aliasesTree;
    }

    public function getAliasesReferences() {
        return $this->aliasesReferences;
    }

    /**
     * Méthode pour extraire l'alias du nom d'une table suivi de son alias...
     *
     * @param string $combined Une chaîne contenant le nom de la table et l'éventuel alias.
     * @return array
     */
    public function extractAlias($combined) {
        $t = explode(' ', $combined);
        $table = $t[0];
        $alias = $table;
        if (isset($t[1]))
            $alias = $t[1];

        return array(
            'table' => $table,
            'alias' => $alias
        );
    }

    /**
     * Récupération des champs sélectionnés
     *
     * @param array $config
     * @return array
     * @throws Exception
     */
    public function getSelectedFields($config) {
        // check if table is registered
        if (!isset($config['table'])) {
            throw new MissingKeyException('$config' . " array does not contain a 'table' key. How can I be sure that your query will work ?");
        }
        if (!isset($this->container[$config['table']])) {
            throw new MissingDependencyException(sprintf("The table %s is not registered in the Container. Can't go on...", $config['table']));
        }

        $fields = isset($config['select']) ? $config['select'] : $this->container[$config['table']]->getDefaultFields();
        in_array('Acedao\Brick\Journalizer', class_uses($this->container[$config['table']])) ?
            $fields = array_merge($fields, $this->container[$config['table']]->getJournalizeFields()) : '';

        if (!in_array('id', $fields))
            array_unshift($fields, 'id');

        return $fields;
    }

    /**
     * Ajout de l'alias aux champs sélectionnés
     *
     * @param string $alias
     * @param array $select
     * @return array
     */
    public function aliaseSelectedFields($alias, $select) {
        $aliaseIt = function ($field) use ($alias) {
            return $alias . '.' . $field;
        };

        return array_map($aliaseIt, $select);
    }

    /**
     * Formattage d'un tableau de paramètres en s'assurant
     * qu'ils sont PDO compliants (donc les clés doivent commencer par ":").
     *
     * @param array $params
     * @return array
     */
    public function formatQueryParamsKeys($params) {
        if (count($params) == 0)
            return $params;

        $keys = array_map(function ($item) {
            if (substr($item, 0, 1) != ':')
                $item = ':' . $item;
            return $item;
        }, array_keys($params));
        return array_combine($keys, array_values($params));
    }

    /**
     * Ajout d'alias de nom aux champs sélectionnés (le truc après le "as")
     *
     * @param array $fields
     * @return array
     */
    public function nameAliasesSelectedFields($fields) {
        return array_map(function ($field) {
            $alias = str_replace('.', '__', $field);
            return $field . ' as ' . $alias;
        }, $fields);
    }

    /**
     * Build the query parts
     *
     * @param array $config
     * @return array
     * @throws Exception
     */
    public function prepareSelect($config) {
        // S'il n'y a pas de table de départ, c'est mal parti pour faire un select...
        if (!isset($config['from']))
            throw new Exception('Array $parts needs a [from] entry.');

        // tentative de récupération de l'alias principal
        $config = array_merge($config, $this->extractAlias($config['from']));

        // récupération des champs sélectionnés et aliasement de chacun
        $select = $this->getSelectedFields($config);
        $select = $this->aliaseSelectedFields($config['alias'], $select);

        // from part
        $from_table = $config['table'];
        if ($this->container[$from_table]->escapeTablename) {
            $from_table = "`" . $from_table . "`";
        }
        $from_part = $from_table . ' ' . $config['alias'];

        // initialisation du tableau de données de la query
        $data = array(
            'flataliases' => array(
                $config['table'] => $config['alias']
            ),
            'base' => array(
                'table' => $config['table'],
                'alias' => $config['alias']
            ),
            'parts' => array(
                'select' => $select,
                'from' => array($from_part),
                'leftjoin' => array(),
                'innerjoin' => array(),
                'where' => array(),
                'having' => array(),
                'groupby' => array(),
                'orderby' => array()
            ),
            'params' => array()
        );

        // données supplémentaires
        if (isset($config['join'])) {
            foreach ($config['join'] as $filtername => $options) {
                $this->joinTable($data, $filtername, $options, $config);
            }
        }

        // réduction
        if (isset($config['where'])) {
            foreach ($config['where'] as $filtername => $options) {
                $this->applyConditions($data, $filtername, $options);
            }
        }

        // tri
        if (isset($config['orderby'])) {
            foreach ($config['orderby'] as $filtername => $options) {
                $this->applySorts($data, $filtername, $options);
            }
        }

        // au cas où des filtres auraient flanqué les mêmes parties de requêtes
        $parts = array_map('array_unique', $data['parts']);

        // formattage des paramètres, s'il y en a...
        $params = $this->formatQueryParamsKeys($data['params']);

        // ajout des alias aux champs sélectionnés
        $parts['select'] = $this->nameAliasesSelectedFields($parts['select']);

        return array($parts, $params, $config);
    }

    /**
     * Convertit les parties de requête en requête.
     *
     * @param array $parts
     * @return string
     */
    public function prepareSelectSql($parts) {
        $sql = sprintf('SELECT %1$s FROM %2$s', implode(', ', $parts['select']), implode(', ', $parts['from']));
        if (count($parts['leftjoin']) > 0)
            $sql .= ' ' . implode(' ', $parts['leftjoin']);
        if (count($parts['innerjoin']) > 0)
            $sql .= ' INNER JOIN ' . implode(' ', $parts['innerjoin']);
        if (count($parts['where']) > 0)
            $sql .= ' WHERE ' . implode(' AND ', $parts['where']);
        if (count($parts['orderby']) > 0)
            $sql .= ' ORDER BY ' . implode(', ', $parts['orderby']);

        return $sql;
    }

    /**
     * Lance une requête SELECT sur la BD
     *
     * @param $config
     * @return array
     * @throws Exception
     */
    public function select($config) {
        list($parts, $params, $config) = $this->prepareSelect($config);

        // construction de la requête SQL
        $sql = $this->prepareSelectSql($parts);


//		echo $sql;
//		echo '<pre>';
//		print_r($params);
//		echo '</pre>';

        // récupération des résultats
        $results = $this->container['db']->all($sql, $params);

        // regroupement des résultats
        $formatted = $this->hydrate($results, $config);

        return $formatted;
    }

    /**
     * Suppression d'un ou plusieurs records
     *
     * @param array|int $config Une configuration de requête pour la suppression
     * @param int $id Un identifiant
     * @return int Le nombre de records supprimés
     */
    public function delete($config, $id = null) {
        if (!is_array($config) && $id) {
            $sqlStmt = "DELETE FROM `" . $config . "` WHERE `id` = :id";
            return $this->container['db']->execute($sqlStmt, array(':id' => $id));
        }

        // tentative de récupération de l'alias principal
        $config = array_merge($config, $this->extractAlias($config['from']));

        $from_part = $config['table'];
        if ($this->container[$from_part]->escapeTablename) {
            $from_part = "`" . $from_part . "`";
        }

        // initialisation du tableau de données de la query
        $data = array(
            'flataliases' => array($config['table'] => $config['alias']),
            'base' => array(
                'table' => $config['table'],
                'alias' => $config['alias']
            ),
            'parts' => array(
                'from' => array($from_part),
                'leftjoin' => array(),
                'innerjoin' => array(),
                'where' => array(),
                'having' => array(),
                'groupby' => array(),
                'orderby' => array()
            ),
            'params' => array()
        );

        // données supplémentaires
        if (isset($config['join'])) {
            foreach ($config['join'] as $filtername => $options) {
                $this->joinTable($data, $filtername, $options, $config);
            }
        }

        // réduction
        if (isset($config['where'])) {
            foreach ($config['where'] as $filtername => $options) {
                $this->applyConditions($data, $filtername, $options);
            }
        }

        // au cas où des filtres auraient flanqué les mêmes parties de requêtes
        $parts = array_map('array_unique', $data['parts']);

        // formattage des paramètres, s'il y en a...
        $params = $this->formatQueryParamsKeys($data['params']);

        // construction de la requête SQL
        $sql = sprintf('DELETE FROM %1$s', implode(', ', $parts['from']));
        if (count($parts['leftjoin']) > 0)
            $sql .= ' ' . implode(' ', $parts['leftjoin']);
        if (count($parts['innerjoin']) > 0)
            $sql .= ' INNER JOIN ' . implode(' ', $parts['innerjoin']);
        if (count($parts['where']) > 0)
            $sql .= ' WHERE ' . implode(' AND ', $parts['where']);

        // on vire l'alias principal
        $sql = str_replace($data['base']['alias'].'.', '', $sql);

        return $this->container['db']->execute($sql, $params);
    }

    /**
     * Formattage des résultats de la requêtes SQL
     *
     * @param array $results Resultset PDO
     * @param array $config Configuration de la requête
     * @return array
     */
    public function hydrate($results, $config) {
        $formatted = array();
        foreach ($results as $line) {
            $record = array();
            $relations = array();
            $path_exclude = array();
            foreach ($line as $fieldname => $value) {
                $t = explode('__', $fieldname);
                if ($t[0] == $config['alias']) {
                    $record[$t[1]] = $value;
                } else {
                    $path = $this->getPathAlias($t[0]);
                    $path_test = implode('_', $path);

                    // si y a pas d'id, on considère que c'est un join sur rien...
                    if ($t[1] == 'id' && $value == null || in_array($path_test, $path_exclude)) {
                        $path_exclude[] = $path_test;
                        continue;
                    }

                    $path[] = $t[1];
                    $relations[] = array(
                        'path' => $path,
                        'value' => $value
                    );
                }
            }

            foreach ($relations as $relation) {
                $value = array(
                    array_pop($relation['path']) => $relation['value']
                );
                while ($key = array_pop($relation['path'])) {
                    $value = array($key => $value);
                }

                // and there, the magic happens
                $record = array_merge_recursive($record, $value);
            }

            // gestion des relations 1-n
            $this->manageRelationsType($record);

            // fusion des records
            if (!isset($formatted[$record['id']])) {
                $formatted[$record['id']] = $record;
            } else {
                $this->fusionRecords($formatted[$record['id']], $record, null);
            }
        }

        return $formatted;
    }

    /**
     * Inspection du nom du filtre pour savoir sur quelle table il s'applique
     *
     * @param $data
     * @param $filtername
     * @return array
     * @throws Exception
     */
    public function extractFilterAliasAndTable($data, $filtername) {
        $filter_array = explode('.', $filtername);
        $filtername = array_pop($filter_array);

        // si $filter_array n'est pas vide, c'est que le filtre a été appelé
        // sur un alias (probablement d'une autre table).
        if (count($filter_array) > 0) {
            $alias = array_pop($filter_array);
            if ($alias != $data['base']['alias']) {
                $path = $this->getPathAlias($alias);
                if (!$path) {
                    throw new Exception(sprintf("Alias [%s] does not exist. Can't go on.", $alias));
                }
                $tablename = array_pop($path);
            } else {
                $tablename = $data['base']['table'];
            }

            // si $tablename est en fait un nom de relation, il faut retrouver le vrai nom
            // de la table.
            if (isset($this->relationTableNames[$tablename])) {
                $tablename = $this->relationTableNames[$tablename];
            }

            // si $filter_array est vide, c'est qu'on appelle un filtre sur la table
            // de base de la requête.
        } else {
            $tablename = $data['base']['table'];
            $alias = $data['base']['alias'];
        }

        return array($filtername, $tablename, $alias);
    }

    /**
     * Récupération de la config du filtre
     *
     * @param Queriable $queriable
     * @param string $type Le type de filtre (where, orderby)
     * @param string $filtername Le nom du filtre
     * @return bool|array
     */
    public function retrieveFilter(Queriable $queriable, $type, $filtername) {
        $conditions = $queriable->getFilters($type);
        if (isset($conditions[$filtername])) {
            return $conditions[$filtername];
        }

        return false;
    }

    /**
     * Applique les conditions (where) à la query
     *
     * @param array $data Les données de la query
     * @param string $filtername Le nom du filtre
     * @param array $options Les options envoyées pour ce filtre
     * @throws Exception
     */
    public function applyConditions(&$data, $filtername, $options = null) {
        // détermination de la table sur laquelle le filtre doit s'appliquer
        list($filtername, $tablename, $alias) = $this->extractFilterAliasAndTable($data, $filtername);

        // récupération du service
        $service = $this->container[$tablename];

        // récupération du filtre
        if (false === ($filter = $this->retrieveFilter($service, 'where', $filtername))) {
            throw new Exception(sprintf("Asked filter [%s] does not exist on table [%s]", $filtername, $tablename));
        }

        // remplacement des alias sur les conditions
        foreach ($filter as &$query_part) {
            $query_part = $this->aliaseIt(
                array($tablename, 'this'),
                array($alias, $alias),
                $query_part
            );
        }

        // création du SQL
        $where_str = implode(' AND ', $filter);
        $data['parts']['where'][] = $where_str;

        // traitement des paramètres
        if (isset($options) && $options) {
            $this_condition_parameters = $this->mapFilterParametersNames($where_str, $options);
            $data['params'] = array_merge($data['params'], $this_condition_parameters);
        }
    }

    /**
     * Tentative de mapping entre les paramètres (:param1) de la query SQL et le tableau
     * d'options fournis.
     *
     * @param $sql
     * @param $options
     * @return array|bool
     * @throws Exception
     */
    public function mapFilterParametersNames($sql, $options) {
        if (!is_array($options)) {
            $options = array($options);
        }

        $result_preg = array();
        preg_match_all('/(\:\w+)/', $sql, $result_preg);
        $result_preg = array_unique($result_preg[0]);

        if (count($result_preg) == 0) {
            return array();
        }

        if (count($result_preg) > count($options)) {
            throw new WrongParameterException(sprintf("Not enough values provided (%s) to feed the query parameters (%s).", count($options), count($result_preg)));
        }

        // if keys were provided in the option array, we test these keys
        // against the sql part provided.
        $options_keys = array_keys($options);
        if (!is_int($options_keys[0])) {
            $result_preg_compare = array_flip($result_preg);
            $compare = array_intersect_key($options, $result_preg_compare);
            if (count($compare) != count($result_preg)) {
                throw new MissingKeyException(sprintf("Provided keys (%s) don't match needed keys (%s).", implode(', ', $options_keys), implode(', ', $result_preg)));
            }

            // $compare contains all what we want.
            // let's sort both arrays to be sure ton combine properly their values.
            sort($result_preg);
            $options = $compare;
            ksort($options);
        }

        $options = array_values($options);
        if (count($options) > count($result_preg)) {
            $options = array_slice($options, 0, count($result_preg));
        }
        $result = array_combine($result_preg, $options);

        return $result;
    }

    /**
     * Applique les tris (order by) à la query
     *
     * @param array $data Les données de la query
     * @param string $filtername Le nom du filtre
     * @param array $options Les options envoyées pour ce filtre
     * @throws Exception
     */
    public function applySorts(&$data, $filtername, $options) {
        list($filtername, $tablename, $alias) = $this->extractFilterAliasAndTable($data, $filtername);

        // récupération du service
        $service = $this->container[$tablename];

        // récupération du filtre
        if (false === ($filter = $this->retrieveFilter($service, 'orderby', $filtername))) {
            if ($this->container['config']['mode'] == 'strict') {
                throw new Exception(sprintf("Asked filter [%s] does not exist on table [%s]", $filtername, $tablename));
            }
            return;
        }

        // remplacement des alias sur les clause order by
        $aliases = $data['flataliases'][$tablename];
        if (!is_array($aliases)) {
            $aliases = array($aliases);
        }
        // s'il y a plusieurs alias pour la même table, on devrait avoir défini un mapping dans la config de la query.
        // sinon, c'est la merde -> exception.
        if (count($aliases) > 1) {
            if (isset($options['map']) && isset($options['map'][$tablename])) {
                $alias = $options['map'][$tablename];
            } else {
                throw new Exception(sprintf("The table [%s] as several aliases [%s]. You have to map the good one in the query call configuration.", $tablename, implode(', ', $aliases)));
            }
        } else {
            $alias = $aliases[0];
        }

        foreach ($filter as &$query_part) {
            $query_part = $this->aliaseIt(
                array($tablename, 'this'),
                array($alias, $alias),
                $query_part
            );
        }

        // création du SQL et gestion de la direction
        $orderby_str = implode(', ', $filter);
        $orderby_str = str_replace(':dir', $this->getSortDirection($options), $orderby_str);
        $data['parts']['orderby'][] = $orderby_str;
    }

    /**
     * Récupération de la direction du tri
     *
     * @param $options
     * @return string [asc, desc]
     * @throws Exception
     */
    public function getSortDirection($options) {
        if (!isset($options)) {
            return 'asc';
        }
        if (is_array($options)) {
            if (!isset($options['dir'])) {
                return 'asc';
            } else {
                return $options['dir'];
            }
        } else {
            if (in_array($options, array('asc', 'desc'))) {
                return $options;
            } else {
                if ($this->container['config']['mode'] == 'strict') {
                    throw new Exception(sprintf("Provided options in 'orderby' filter [%s] is not recognized. Use 'asc' or 'desc'.", $options));
                } else {
                    return 'asc';
                }
            }
        }
    }

    /**
     * Fuionne 2 tableaux récursivement
     *
     * @param $one
     * @param $two
     */
    public function fusionRecords(&$one, $two) {
        foreach ($one as $fieldname => &$content) {

            if ($content != $two[$fieldname] && is_array($content) && !isset($content['id'])) {
                // combien y en a là-dedans ? Plus d'un ? Dans ce cas, faut trouver lequel
                // ressemble le plus à notre $two[$fieldname]...
                if (count($content) > 1) {
                    $found = false;
                    foreach ($content as &$test_record) {
                        if ($test_record['id'] != $two[$fieldname][0]['id']) {
                            continue;
                        }

                        $found = true;
                        foreach ($test_record as $test_fieldname => &$test_content) {
                            if (is_array($test_content)) {
                                $this->fusionRecords($test_content, $two[$fieldname][0][$test_fieldname]);
                            }
                        }
                    }

                    if (!$found) {
                        $content[] = $two[$fieldname][0];
                    }
                } else {
                    $content = array_merge($content, $two[$fieldname]);
                }
            }
        }
    }

    /**
     * Gestion des relations 1-n
     *
     * @param $record
     */
    public function manageRelationsType(&$record) {
        foreach ($record as $fieldname => &$content) {

            // si $content est un tableau, alors on est dans une relation et $fieldname est un nom de table
            if (is_array($content)) {
                $this->manageRelationsType($content);
                if ($this->relationTypes[$fieldname] == 'many') {
                    $content = array($content);
                }
            }
        }
    }

    public function aliaseIt($tableNames, $aliases, $queryPart) {
        if (!is_array($tableNames))
            $tableNames = array($tableNames);
        $tableNames = array_map(function ($item) {
            return '[' . $item . ']';
        }, $tableNames);
        return str_replace($tableNames, $aliases, $queryPart);
    }

    /**
     * Ajout d'une jointure à la query
     *
     * @param array $data Les données de la query
     * @param string $filtername Le nom du filtre ([nom de table] [alias])
     * @param array $options Les options envoyées pour ce join
     * @param array $caller Les options envoyées pour la table qui appelle ce join
     */
    public function joinTable(&$data, $filtername, $options, $caller) {
        $table_info = $this->extractAlias($filtername);
        $joined_table = $table_info['table'];
        $joined_alias = $table_info['alias'];
        $local_table = $caller['table'];
        $local_alias = $caller['alias'];

        // load DAO services
        $basetable_dao = $this->container[$local_table];
        $jointable_dao = $this->container[$joined_table];

        $basetable_joins = $basetable_dao->getFilters('join');
        $jointable_joins = $jointable_dao->getFilters('join');

        $default_options = $this->retrieveFilter($basetable_dao, 'join', $joined_table);

        // select
        if (isset($options['select'])) {
            $fields = $options['select'];
        } elseif (isset($default_options['select'])) {
            $fields = $default_options['select'];
        } else {
            $fields = $jointable_dao->getDefaultFields();
        }

        if (!in_array('id', $fields))
            array_unshift($fields, 'id');

        // On applique l'alias aux champs à sélectionner et on plante le tout
        // dans les query parts...
        $fields = $this->aliaseSelectedFields($joined_alias, $fields);
        $data['parts']['select'] = array_merge($data['parts']['select'], $fields);


        // leftjoin
        if (isset($default_options['on'])) {

            $filter_table = $joined_table;
            if ($jointable_dao->escapeTablename) {
                $filter_table = "`" . $filter_table . "`";
            }
            if ($joined_alias) {
                $filter_table = $filter_table . ' ' . $joined_alias;
            }

            // alias
            foreach ($default_options['on'] as &$query_part) {
                $query_part = $this->aliaseIt(
                    array($joined_table, $local_table, 'this'),
                    array($joined_alias, $local_alias, $local_alias),
                    $query_part
                );
            }

            // add sql code
            $leftjoin_str[] = sprintf('LEFT JOIN %s ON %s', $filter_table, implode(' AND ', $default_options['on']));
            $data['parts']['leftjoin'] = array_merge($data['parts']['leftjoin'], $leftjoin_str);
            $data['parts']['select'][] = $local_alias . '.id';
            $data['parts']['select'][] = $joined_alias . '.id';

            // relation name
            $relation_name = false;
            if (isset($options['name'])) {
                $relation_name = $options['name'];
            }

            // handle aliases libraries
            $this->registerAlias($local_alias, $joined_alias, $joined_table, $basetable_joins, $jointable_joins, $relation_name);
            $this->addFlatAlias($data['flataliases'], $joined_table, $joined_alias);
        }


        /** ============== RECURSION ======================================== */

        // recherche de sous-filtres, soit directement dans les filtres, soit dans les options fournies par la requête de base
        $default_options['alias'] = $joined_alias;
        $default_options['table'] = $joined_table;

        if (isset($options['join'])) {
            foreach ($options['join'] as $subfilter_name => $subfilter_options) {
                $this->joinTable($data, $subfilter_name, $subfilter_options, $default_options);
            }
        }
        if (isset($default_options['join'])) {
            foreach ($default_options['join'] as $subfilter_name => $subfilter_options) {
                $this->joinTable($data, $subfilter_name, $subfilter_options, $default_options);
            }
        }

        /** ============ / RECURSION ======================================== */
    }

    /**
     * Référencement d'un alias dans la bibliothèque d'alias de la query
     *
     * @param string $localAlias L'alias parent
     * @param string $joinedAlias L'alias recherché
     * @param string $joinedTable Le nom de la table à aliaser
     * @param array $localTableFilters Les filtres 'join' de la table parente
     * @param array $joinedTableFilters Les filtres 'join' de la table jointe
     * @param string $relationName Le nom de la relation pour cet alias
     * @return bool
     */
    public function registerAlias($localAlias, $joinedAlias, $joinedTable, $localTableFilters, $joinedTableFilters, $relationName) {
        if (!isset($localTableFilters[$joinedTable])) {
            return false;
        }

        // on essaie déjà de trouver l'alias local dans l'arbre existant d'alias.
        // si on ne le trouve pas, on va créer cet alias au premier niveau de l'arbre des alias.
        if (!$this->getPathAlias($localAlias)) {
            $this->aliasesTree[$joinedAlias] = array(
                'table' => $joinedTable,
                'relation' => $relationName ? : $joinedTable,
                'type' => isset($localTableFilters[$joinedTable]['type']) ? $localTableFilters[$joinedTable]['type'] : 'one',
                'children' => array(),
                'parent' => null
            );

            // création d'un lien entre un nom de relation et un type de relation
            $this->relationTypes[$this->aliasesTree[$joinedAlias]['relation']] = $this->aliasesTree[$joinedAlias]['type'];

            // création d'un lien entre le nom de la relation et le nom de la table
            $this->relationTableNames[$this->aliasesTree[$joinedAlias]['relation']] = $joinedTable;

            // stockage d'une référence vers le nouvel alias
            $this->aliasesReferences[$joinedAlias] = & $this->aliasesTree[$joinedAlias];

            // si on le trouve, on met à jour l'arbre comme il se doit.
        } else {
            $child = array(
                'table' => $joinedTable,
                'relation' => $relationName ? : $joinedTable,
                'type' => isset($localTableFilters[$joinedTable]['type']) ? $localTableFilters[$joinedTable]['type'] : 'one',
                'children' => array()
            );
            // création d'un lien entre un nom de relation et un type de relation
            $this->relationTypes[$child['relation']] = $child['type'];

            // création d'un lien entre le nom de la relation et le nom de la table
            $this->relationTableNames[$child['relation']] = $joinedTable;

            $this->aliasesTreeAddChild($this->aliasesTree, $localAlias, $joinedAlias, $child);
        }

        return true;
    }

    /**
     * Stockage de la correspondance alias-table dans un simple tableau sans hiérarchie
     *
     * @param $reference
     * @param $table
     * @param $alias
     */
    public function addFlatAlias(&$reference, $table, $alias) {
        $reference[$table][] = $alias;
    }

    /**
     * Ajout d'un élément dans l'arbre des alias
     *
     * @param $reference array L'arbre des alias, passé en référence
     * @param $alias string L'alias dans lequel on veut ajouter un élément
     * @param $childAlias string L'alias de l'élément à ajouter
     * @param $child array L'élément à ajouter
     * @return bool
     */
    public function aliasesTreeAddChild(&$reference, $alias, $childAlias, $child) {
        if (isset($reference[$alias])) {
            $child['parent'] = & $reference[$alias];
            $reference[$alias]['children'][$childAlias] = $child;

            // stockage d'une référence vers le nouvel alias
            $this->aliasesReferences[$childAlias] = & $reference[$alias]['children'][$childAlias];
            return true;
        }

        foreach ($reference as &$ref) {
            if (false !== ($result = $this->aliasesTreeAddChild($ref['children'], $alias, $childAlias, $child))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Récupération du path vers un alias
     *
     * @param string $alias L'alias à trouver
     * @return array|false
     */
    public function getPathAlias($alias) {

        if (!isset($this->aliasesReferences[$alias])) {
            return false;
        }

        return $this->get($this->aliasesReferences[$alias]);
    }

    public function get($part, $result = array()) {
        if ($part === null) {
            return $result;
        }

        array_unshift($result, $part['relation']);
        return $this->get($part['parent'], $result);
    }

    /**
     * Prépare and run an SQL query to insert a record
     *
     * @param string $tableName
     * @param array $data
     * @return int
     */
    final private function insert($tableName, $data) {

        $sqlStmt = "INSERT INTO `" . $tableName . "` ";

        $user_data = array();
        foreach ($data as $key => $value) {
            $pdo_key = ':' . $key;
            $user_data[$pdo_key] = $value;
        }
        $sqlStmt .= '(`' . implode('`,`', array_keys($data)) . '`) VALUES (' . implode(',', array_keys($user_data)) . ')';

        return $this->container['db']->execute($sqlStmt, $user_data);
    }

    /**
     * Prépare and run an SQL query to update a record
     *
     * @param string $tableName
     * @param array $data
     * @return int
     */
    final private function update($tableName, $data) {

        $sqlStmt = "UPDATE `" . $tableName . "` SET ";

        $updates = array();
        $user_data = array();
        foreach ($data as $key => $value) {
            $pdo_key = ':' . $key;
            $user_data[$pdo_key] = $value;
            if ($key !== 'id') {
                $updates[] = '`' . $key . '` = ' . $pdo_key;
            }
        }
        $sqlStmt .= implode(', ', $updates) . ' WHERE `id` = :id';

        return $this->container['db']->execute($sqlStmt, $user_data);
    }

    final public function save($tableName, $data) {
        if (array_key_exists('id', $data) && $data['id']) {
            if (in_array('Acedao\Brick\Journalizer', class_uses($this->container[$tableName]))) {
                $data['updated_by'] = $this->container[$tableName]->getJournalizeUser();
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            return $this->update($tableName, $data);
        } elseif (count($data) > 0) {
            if (in_array('Acedao\Brick\Journalizer', class_uses($this->container[$tableName]))) {
                $data['created_by'] = $this->container[$tableName]->getJournalizeUser();
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            return $this->insert($tableName, $data);
        }

        return false;
    }
}