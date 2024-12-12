<?php 

namespace Msx\Portal\Database;

use Elasticsearch\ClientBuilder;
use \Exception;

class ElasticSearchClient {

    public static $indice_matia = "matia";
    public static $indice_midia = "midia";
    public static $indice_autor = "autor";
    public static $indice_pdcao = "pdcao";

    private static $instance = null;
    private $client;
    private $prefix = "";
    public $indices = [];

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';

        if (!isset($config['elasticsearch'])) {
            throw new \Exception('Elasticsearch configuration not found');
        }

        if (!isset($config['elasticsearch']['host'])) {
            throw new \Exception('Elasticsearch host not found');
        }

        if (!isset($config['elasticsearch']['user'])) {
            throw new \Exception('Elasticsearch user not found');
        }

        if (!isset($config['elasticsearch']['password'])) {        
            throw new \Exception('Elasticsearch password not found');
        }     

        if (!isset($config['elasticsearch']['port'])) {        
            throw new \Exception('Elasticsearch password not found');
        }  

        if (!isset($config['elasticsearch']['prefix'])) {        
            throw new \Exception('Elasticsearch prefix not found');
        } 

        try {
            $this->client = ClientBuilder::create()
                ->setHosts([$config['elasticsearch']['host'] . ":" . $config['elasticsearch']['port']]) 
                ->setBasicAuthentication($config['elasticsearch']['user'], $config['elasticsearch']['password']) 
                ->build();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
		}

        $this->prefix = $config['elasticsearch']['prefix'];

        $this->indices = [
            self::$indice_matia => $this->prefix . self::$indice_matia,    
            self::$indice_midia => $this->prefix . self::$indice_midia,    
            self::$indice_autor => $this->prefix . self::$indice_autor,    
            self::$indice_pdcao => $this->prefix . self::$indice_pdcao
        ];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->client;
    }

    // Prevenir clonagem do objeto
    private function __clone() {}

    // Prevenir desserialização
    public function __wakeup() {}

    public static function getIndices() {
        return self::$instance->indices;
    }
}