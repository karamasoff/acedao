<?php
namespace Acedao\Brick;


use Acedao\Container;
use Acedao\Exception\MissingKeyException;

trait Dao {

    /**
     * @var array Les filtres possibles sur cette table
     */
    public $filters;

    /**
     * @var string Le nom de la table
     */
    public $tablename;

    /**
     * @var bool Faut-il échapper le nom de la table ?
     */
    public $escapeTablename = false;

    /**
     * @var \Acedao\Query
     */
    protected $query;

    /**
     * @var \Acedao\Container
     */
    protected $container;

    /**
     * Constructeur spécialisé pour chaque DAO (à surcharger)
     */
    public function construct() {
    }

    /**
     * Initialisateur du DAO (automatiquement appelé dans la Factory)
     *
     * @param Container $container
     */
    public function init(Container $container) {
        $this->container = $container;
        $this->query = $container['query'];
    }

    public function setTableName($tablename) {
        $this->tablename = $tablename;
    }

    public function loadFilters() {
        $this->filters = $this->defineFilters();
    }

    /**
     * Définition des filtres par défaut pour ce DAO (aucun, quoi).
     *
     * @return array
     */
    public function defineFilters() {
        return array(
            'join' => array(),
            'where' => array(),
            'orderby' => array()
        );
    }

    /**
     * Implémentation bidon de la méthode des champs autorisés sur ce DAO
     *
     * @return array
     */
    public function getAllowedFields() {
        return array();
    }

    /**
     * Récupération des à sélectionner par défaut dans les requêtes
     * @return array
     */
    public function getDefaultFields() {
        return $this->getAllowedFields();
    }

    /**
     * Récupération d'un groupe de filtres ou de la liste complète des filtres
     *
     * @param string $key Type de filtre (join, where, orderby)
     * @return array
     * @throws \Acedao\Exception\MissingKeyException
     */
    public function getFilters($key = null) {
        if ($key === null) {
            return $this->filters;
        }

        if (!isset($this->filters[$key])) {
            throw new MissingKeyException(sprintf("The key you gave (%s) is not defined in this object filters.", $key));
        }

        return $this->filters[$key];
    }

    /**
     * Récupération du nom de la table
     *
     * @param string $alias
     * @return string
     */
    public function t($alias = null) {
        $tablename = $this->tablename;
        if ($alias) {
            $tablename .= ' ' . $alias;
        }
        return $tablename;
    }

    /**
     * Enregistrement des données d'un DAO
     *
     * @param array $data
     * @param array $allowedFields Les champs à considérer (prend le pas sur la méthode getAllowedFields())
     * @param bool $debug
     * @return int
     */
    public function save(array $data, array $allowedFields = null, $debug = false) {
        if ($debug) {
            echo 'Données envoyées:';
            echo '<pre>';
            print_r($data);
            echo '</pre>';

            if ($allowedFields) {
                echo 'Champs autorisés:';
                echo '<pre>';
                print_r($allowedFields);
                echo '</pre>';
            }
        }

        // filter $data against the allowed fields array
        if ($allowedFields && is_array($allowedFields) && count($allowedFields) > 0) {
            $allowedFields = array_intersect($allowedFields, $this->getAllowedFields());
        } else {
            $allowedFields = $this->getAllowedFields();
        }
        $filtered_data = array_intersect_key($data, array_flip($allowedFields));

        if (isset($filtered_data['id']) && !$filtered_data['id']) {
            unset($filtered_data['id']);
        }

        if ($debug) {
            echo 'Données filtrées:';
            echo '<pre>';
            print_r($filtered_data);
            echo '</pre>';
        }

        // Check if there is still some data to save...
        if (array_key_exists('id', $filtered_data) && count($filtered_data) > 1 || !array_key_exists('id', $filtered_data) && count($filtered_data) > 0) {
            return $this->query->save($this->t(), $filtered_data, $debug);
        }
        return true;
    }

    /**
     * Requête select
     *
     * @param  array   $config Données du select
     * @param  boolean $debug
     * @return mixed
     */
    public function select(array $config, $debug = false) {
        return $this->query->select($config, $debug);
    }

    /**
     * Requête de suppression d'un ou plusieurs records
     *
     * @param array|int $userConfig
     * @return mixed
     */
    public function delete($userConfig) {
        $config = array(
            'from' => $this->t()
        );

        if (!is_array($userConfig)) {
            $userConfig = array('where' => array('id' => $userConfig));
        }
        $config = array_merge_recursive($userConfig, $config);

        return $this->query->delete($config);
    }

    /**
     * Démarrer une transaction
     */
    public function beginTransaction() {
        return $this->container['db']->beginTransaction();
    }

    public function commit() {
        return $this->container['db']->commit();
    }

    public function rollback() {
        return $this->container['db']->rollback();
    }
}
