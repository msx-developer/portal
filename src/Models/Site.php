<?php

namespace Msx\Portal\Models;

use Msx\Portal\Database\Connection;

use Msx\Portal\Helpers\FiveliveHelper;

class Site { 

    private $connection;
    public function __construct()
    {
        $this->connection = Connection::getInstance();
    }

    public function find($id) {
        $sql = 'SELECT * FROM site  INNER JOIN poral ON (site.cd_poral = poral.cd_poral) WHERE site.cd_site = ?';
        $map = (array) $this->connection->fetchAll($sql, [$id])[0];

        if($map) {
            $map['tpblos'] = $this->getTpblos($map['cd_site']);
            $map['fivelive'] = array(
                'scriptTop' => FiveliveHelper::scriptTop(),
                'scriptBottom' =>FiveliveHelper::scriptBottom($id),
            );
        }

        return $map;
    }

    public function getSites() {
        $sql = 'SELECT * FROM site INNER JOIN poral ON (site.cd_poral = poral.cd_poral) WHERE site.cd_poral = ?';
        $map = (array) $this->connection->fetchAll($sql, [$_SESSION['msx']['portal']]);

        return $map;
    }

    public function getTpblos($cd_site) {
        $sql = "SELECT *
        FROM tpblo
            INNER JOIN site ON (site.cd_templ_capa = tpblo.cd_templ)
        WHERE site.cd_site = ?
        ORDER BY tpblo.id_tpblo_order";
        return (array) $this->connection->fetchAll($sql, [$cd_site]);
    }
}