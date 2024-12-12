<?php

namespace Msx\Portal\Models;

use Msx\Portal\Database\Connection;

class Sesit { 
    private $connection;


    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    public function find($id) {
        $fivelive = 'false';
        if(isset($_REQUEST['fivelive']) && $_REQUEST['fivelive'] == '1') {
            $fivelive = 'true';
        } 

        $sql = "SELECT *, AS {$fivelive} AS 'fivelive' FROM sesit LEFT JOIN templ ON (sesit.cd_templ = templ.cd_templ) WHERE sesit.cd_sesit = ?";
        return (array) $this->connection->fetchAll($sql, [$id]);
    }

    public function getPublis($cd_sesit) {
        $sesit = $this->connection->fetchAll('SELECT * FROM sesit WHERE cd_sesit = ?', [$cd_sesit])[0];
        
        if($sesit['ds_sesit_sql'] != ""){
            $sql = $this->sqlReplaces($sesit);           
        } else {
            $sql = "SELECT *
            FROM publi 
                INNER JOIN sesit ON (publi.cd_sesit = sesit.cd_sesit) 
            WHERE publi.cd_sesit = ?
                AND now() >= publi.dt_publi_ini
                AND (now() <= publi.dt_publi_fim OR publi.dt_publi_fim is null)
                -- AND 
                --    (
                --        (
                --            (
                --                sesit.dt_sesit_publi is null OR 
                --                matia.dt_matia_incl > date_sub(sesit.dt_sesit_publi, interval ifnull(sesit.qt_sesit_publi,1) day)
                --            )  
                --            AND sesit.id_sesit_otimi = 1
                --        ) OR sesit.id_sesit_otimi is null
                --    )
            ORDER BY publi.dt_publi_ini DESC ";
        }

        $page = 0;
        if(isset($_REQUEST['p'])) {
            $page = (($_REQUEST['p'] == "" || $_REQUEST['p'] == 0 ? 0 : $_REQUEST['p']) - 1);
        }

        if(isset($sesit['qt_sesit_matia']) && $sesit['qt_sesit_matia'] != "" && $sesit['qt_sesit_matia'] > 0)
            $sql .= " LIMIT " . $sesit['qt_sesit_matia'];
        elseif(isset($sesit['qt_sesit_repag']) && $sesit['qt_sesit_repag'] != "" && $sesit['qt_sesit_repag'] > 0)
            $sql .= " LIMIT " . $page * $sesit['qt_sesit_repag'] . ", " . $sesit['qt_sesit_repag'];
        
        if($sesit['ds_sesit_sql'] != "")
            $map = (array) $this->connection->fetchAll($sql);
        else 
            $map = (array) $this->connection->fetchAll($sql, [$cd_sesit]);

        return $map;
    }

    public function sqlReplaces($sesit) {
        $sql = $sesit['ds_sesit_sql'];
        $codSite = $sesit['cd_site'];
        $dados = $this->connection->fetchAll('SELECT * FROM site WHERE cd_site = ?', [$codSite])[0];
        
        $dados['data_ini'] =  "'" . date("Y-m-d H:i:s", strtotime("-12 months")) . "'";
        $dados['data_fim'] =  "'" . date("Y-m-d H:i:s") . "'";
        
        foreach ($dados as $key => $v) {
            if(!is_null($v))
			    $sql = str_replace("%" . $key . "%", $v, $sql);
		}
        return $sql;
    }

    public function getMareps($cd_publi){
        $sql = "SELECT 
            matia.*,
            marep.*,
            IFNULL(marep.ds_marep_titlo, matia.ds_matia_titlo) AS ds_marep_titlo,
            REPLACE(IFNULL(matia.ds_matia_link, matia.ds_matia_path), '/_conteudo', '') AS ds_marep_link,
            REPLACE(IFNULL(matia.ds_matia_link, concat_ws('', poral.ds_poral_url, matia.ds_matia_path)), '/_conteudo', '') AS ds_matia_link,
            (SELECT ds_midia_link FROM midia WHERE cd_midia = matia.cd_midia) AS ds_midia_link
        FROM marep
            INNER JOIN matia ON (marep.cd_matia = matia.cd_matia)
            INNER JOIN site ON (matia.cd_site = site.cd_site)
            INNER JOIN poral ON (poral.cd_poral = site.cd_poral)
        WHERE marep.cd_publi = ? ";
        return (array) $this->connection->fetchAll($sql, [$cd_publi]);
    }

    public function getSesits($cd_site) {
        $fivelive = false;
        if(isset($_REQUEST['fivelive']) && $_REQUEST['fivelive'] == '1') {
            $fivelive = true;
        } 

        $sql = "SELECT sesit.*
        FROM site 
            INNER JOIN sesit ON (site.cd_site = sesit.cd_site )
            INNER JOIN setem ON (sesit.cd_setem = setem.cd_setem AND site.cd_templ_capa = setem.cd_templ)
        WHERE site.cd_site = ? 
        ORDER BY sesit.id_sesit"; 
        $map = (array) $this->connection->fetchAll($sql, [$cd_site]);

        if(count($map) > 0){
            foreach($map as $key => $value){
                $map[$key]['setips'] = $this->getSetips($value['cd_setem']);
                $map[$key]['putips'] = $this->getPutips($value['cd_setem']);
                $map[$key]['fivelive'] = $fivelive;
            }            
        }
        return $map;
    }

    public function getSetips($cd_setem) {
        $sql = "SELECT * FROM setip WHERE cd_setem = ? ";
        return (array) $this->connection->fetchAll($sql, [$cd_setem]);
    }

    public function getPutips($cd_setem) {
        $sql = "SELECT * FROM putip WHERE cd_setem = ? ";
        return (array) $this->connection->fetchAll($sql, [$cd_setem]);
    }
}