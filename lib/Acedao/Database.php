<?php
namespace Acedao;


class Database {

    /**
     * @var \PDO
     */
    public $dblol = null;
    private $config = array(
        'adapter' => 'mysql',
        'host' => 'localhost',
        'dbname' => '',
        'user' => 'root',
        'pass' => ''
    );


    /**
     * Constructor
     *
     * @param array $config
     * @throws Exception
     */
    public function __construct($config = array()) {
        $this->config = array_merge($this->config, $config);
        if (!$this->config['adapter'] || !$this->config['host'] || !$this->config['dbname'] || !$this->config['user']) {
            throw new Exception("Missing config parameters");
        }
    }

    /**
     * Execute an SQL statement
     *
     * @param string $sql
     * @param array $params
     * @return int|string Last insert ID (if insert statement) or affected rows (if update or delete statement)
     * @throws Exception
     */
    public function execute($sql = '', $params = array()) {
        $this->init();
        try {
            $sth = $this->prepare($sql, $params);
            if (preg_match('/insert/i', $sql)) {
                return $this->insertId();
            } else {
                return $sth->rowCount();
            }
        } catch (\PDOException $e) {
            throw new Exception("Query error: {$e->getMessage()} - {$sql}");
        }
    }

    public function insertId() {
        $this->init();
        $id = $this->dblol->lastInsertId();
        if ($id > 0) {
            return $id;
        }
        return false;
    }

    public function all($sql = false, $params = array()) {
        $this->init();
        try {
            $sth = $this->prepare($sql, $params);
            return $sth->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new Exception("Query error: {$e->getMessage()} - {$sql}");
        }
    }

    public function one($sql = false, $params = array()) {
        $this->init();
        try {
            $sth = $this->prepare($sql, $params);
            return $sth->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new Exception("Query error: {$e->getMessage()} - {$sql}");
        }
    }

    public function beginTransaction() {
        return $this->dblol->beginTransaction();
    }

    public function commit() {
        return $this->dblol->commit();
    }

    public function rollback() {
        return $this->dblol->rollBack();
    }

    private function prepare($sql, $params = array()) {
        try {
            $sth = $this->dblol->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
            $sth->execute($params);
            return $sth;
        } catch (\PDOException $e) {
            throw new Exception("Query error: {$e->getMessage()} - {$sql}");
        }
    }

    private function init() {
        if ($this->dblol)
            return;

        try {
            $this->dblol = new \PDO($this->config['adapter'] . ':host=' . $this->config['host'] . ';dbname=' . $this->config['dbname'], $this->config['user'], $this->config['pass']);
            $this->dblol->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            throw new Exception('Could not connect to database');
        }
    }
}