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
        $sql = 'SELECT * FROM sesit LEFT JOIN templ ON (sesit.cd_templ = templ.cd_templ) WHERE sesit.cd_sesit = ?';
        return (array) $this->connection->fetchAll($sql, [$id]);
    }

    public function getPublis($cd_sesit) {
        $sesit = $this->connection->fetchAll('SELECT * FROM sesit WHERE cd_sesit = ?', [$cd_sesit])[0];
        
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

        if(isset($sesit['qt_sesit_matia']) && $sesit['qt_sesit_matia'] != "" && $sesit['qt_sesit_matia'] > 0)
            $sql .= " LIMIT " . $sesit['qt_sesit_matia'];
        elseif(isset($sesit['qt_sesit_repag']) && $sesit['qt_sesit_repag'] != "" && $sesit['qt_sesit_repag'] > 0)
            $sql .= " LIMIT " . (($_REQUEST['p'] == "" || $_REQUEST['p'] == 0 ? 1 : $_REQUEST['p']) - 1) * $sesit['qt_sesit_repag'] . ", " . $sesit['qt_sesit_repag'];

        return (array) $this->connection->fetchAll($sql, [$cd_sesit]);
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
}