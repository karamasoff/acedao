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
     * Requête de suppression d'un ou plusieurs records
     *
     * @param array $userConfig
     * @return mixed
     */
    public function delete($userConfig) {
        $config = array(
            'from' => $this->t()
        );
        $config = array_merge_recursive($userConfig, $config);

        return $this->query->delete($config);
    }
} 