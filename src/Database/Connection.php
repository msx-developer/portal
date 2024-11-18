<?php

namespace Msx\Portal\Database;

use \PDO;
class Connection{

    private static $instance = null;
    private $pdo;

    /**
     * Private constructor to avoid direct instantiation
     */
    private function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';

        if (!isset($config['database'])) {
            throw new \Exception('Database configuration not found');
        }

        if (!isset($config['database']['type'])) {
            throw new \Exception('Database type not found');
        }   

        if (!isset($config['database']['dbname'])) {
            throw new \Exception('Database name not found');
        }

        if (!isset($config['database']['host'])) {
            throw new \Exception('Database host not found');
        }

        if (!isset($config['database']['user'])) {
            throw new \Exception('Database user not found');
        }

        if (!isset($config['database']['password'])) {        
            throw new \Exception('Database password not found');
        }     

        if (!isset($config['database']['port'])) {        
            throw new \Exception('Database password not found');
        }  

        if(!isset($config['portal'])) {
            throw new \Exception('Portal not found');
        }
                
        extract($config['database']);
        
        $dsn = "{$type}:dbname={$dbname};host={$host};port={$port};charset={$charset}"; 
        $user = $user;
        $password = $password;

        try {
            $this->pdo = new PDO($dsn, $user, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("set names utf8");
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Gets the single instance of self.
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Executes a query
     *
     * @param string $sql  The query to be executed
     * @param array  $params  An array of parameters to be passed to the query
     *
     * @return PDOStatement
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }   

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        $map = (array) $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //$stmt->debugDumpParams();
        $stmt->closeCursor();
        return $map;
    }

}