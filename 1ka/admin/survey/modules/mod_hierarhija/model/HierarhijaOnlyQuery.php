<?php

/**
 * Avtor: Robert Šmalc
 * Date: 8/20/15
 */

namespace Hierarhija\Model;

class HierarhijaOnlyQuery
{

    protected $anketa;
    protected $cusom;
    protected $h_ravni_id;
    protected $select;
    protected $where;
    protected $user_id;


    /**
     * Query hierarhija struktura - pridobimo glavno strukturo
     *
     * @param int $anketa - ID
     * @param string $custom_select - SQL statement comma separated
     * @return mysqli query
     */
    public function queryStruktura($anketa, $select = null, $where = null, $order = 'str.level, hs.ime ASC')
    {
        return sisplet_query("
          SELECT
            str.id AS id,
            str.parent_id AS parent_id,
            str.level AS level,
            hr.anketa_id AS anketa_id,
            hr.id AS ravni_id,
            hr.level AS ravni_level,
            hr.ime AS ravni_ime,
            hs.ime AS sifrant_ime,
            hs.id AS sifrant_id
            $select
          FROM
            srv_hierarhija_struktura AS str
          LEFT JOIN
            srv_hierarhija_ravni AS hr ON str.hierarhija_ravni_id = hr.id
          LEFT JOIN
            srv_hierarhija_sifranti AS hs ON str.hierarhija_sifranti_id = hs.id
          WHERE
            hr.anketa_id='$anketa'
            $where
          ORDER BY
            $order
        ");
    }

    /**
     * Query hierarhija user-jev za sledečo raven(email)
     *
     * @param int $anketa - ID
     * @param string $custom_select - SQL statement comma separated
     * @return mysqli query
     */
    public function queryStrukturaUsers($anketa, $user = null)
    {
        return sisplet_query("
          SELECT
            hs.id AS id,
            users.id AS user_id,
            users.email AS email,
            users.name AS name,
            users.surname AS surname,
            hs.level AS level,
            hs.hierarhija_sifranti_id AS sifrant_id
          FROM
            srv_hierarhija_struktura_users AS hu
          LEFT JOIN
            srv_hierarhija_struktura AS hs ON hu.hierarhija_struktura_id = hs.id
          LEFT JOIN
            srv_hierarhija_ravni AS hr ON hs.hierarhija_ravni_id = hr.id
          LEFT JOIN
            users ON users.id = hu.user_id
          WHERE
            hr.anketa_id='$anketa'
            $user
          ORDER BY id ASC
        ");
    }

    /**
     * Query hierarhija sledeči user na katerem nivoju je vpisan - LEVEL
     *
     * @param int $anketa - ID
     * @param int $user_id
     * @return mysqli query
     */
    public static function queryStrukturaUsersLevel($anketa, $user_id, $order = 'ASC', $limit = null, $selec = null)
    {
        if (!is_null($limit) && $limit == true)
            $limit = 'LIMIT 0,1';

        return sisplet_query("
          SELECT
            hs.id AS struktura_id,
            hs.parent_id AS parent_id,
            hr.level AS level
            $selec
          FROM
            srv_hierarhija_struktura_users AS hu
          LEFT JOIN
            srv_hierarhija_struktura AS hs ON hu.hierarhija_struktura_id = hs.id
          LEFT JOIN
            srv_hierarhija_ravni AS hr ON hs.hierarhija_ravni_id = hr.id
          LEFT JOIN
            users ON users.id = hu.user_id
          WHERE
            hr.anketa_id='$anketa' AND users.id='$user_id'
          ORDER BY
            hr.level $order
           $limit
        ");
    }

    /**
     *  Vrnemo Group by users email ločenimi z vejico
     *
     * @param int $anketa
     * @return SQL query
     */
    public function queryStrukturaUsersGroupBy($anketa, $where = null)
    {
        return sisplet_query("
          SELECT
            hs.id AS id,
            GROUP_CONCAT(users.email) AS uporabniki,
            hs.level AS level,
            hu.user_id AS user_id
          FROM
            srv_hierarhija_struktura_users AS hu
          LEFT JOIN
            srv_hierarhija_struktura AS hs ON hu.hierarhija_struktura_id = hs.id
          LEFT JOIN
            srv_hierarhija_ravni AS hr ON hs.hierarhija_ravni_id = hr.id
          LEFT JOIN
            users ON users.id = hu.user_id
          WHERE
            hr.anketa_id = '$anketa'
            $where
          GROUP BY hs.id
          ORDER BY hs.level
        ");
    }

    /**
     *  Vrnemo strukturo z id-jem sifrantov, ravni in user-ji
     *
     * @param int $anketa
     * @return SQL query
     */
    public function queryStrukturaUsersOnlyId($anketa, $user_id)
    {
        return sisplet_query("
          SELECT
           hs.id AS struktur_id,
           hs.hierarhija_ravni_id AS ravni_id,
           hs.hierarhija_sifranti_id AS sifrant_id,
           hu.user_id AS user_id
          FROM
            srv_hierarhija_struktura AS hs
          LEFT JOIN
            srv_hierarhija_struktura_users AS hu ON hu.hierarhija_struktura_id=hs.id
          WHERE
            hs.anketa_id = '$anketa'
          AND
            hu.user_id = '$user_id'
        ");
    }


    /**
     *  Pridobimo vse šifrante iz baze srv_hierarhija_sifranti
     *
     * @param int $h_ravni_id -> ID srv_hierarhija_ravni
     * @return SQL results
     */
    public function getSamoSifrant($h_ravni_id, $id = false)
    {
        if ($id) {
            $sql_sifra = sisplet_query("SELECT * FROM srv_hierarhija_sifranti WHERE id = '$h_ravni_id' ORDER BY ime");
        } else {
            $sql_sifra = sisplet_query("SELECT * FROM srv_hierarhija_sifranti WHERE hierarhija_ravni_id = '$h_ravni_id' ORDER BY ime");
        }
        $results = null;
        if (!empty($sql_sifra) && mysqli_num_rows($sql_sifra) > 0)
            $results = $sql_sifra;

        return $results;
    }

    /**
     *  Pridobimo vse šifrante skupaj z ravnmi baze srv_hierarhija_sifranti in srv_hierarhija_ravni
     *
     * @param int $h_ravni_id -> ID srv_hierarhija_ravni
     * @return SQL results
     */
    public function getSifrantiRavni($anketa, $select = null, $where = null)
    {
        $sql = sisplet_query("
                SELECT
                  s.id AS id,
                  r.anketa_id AS anketa_id,
                  r.level AS level,
                  r.ime AS raven,
                  s.ime AS sifranti
                  $select
                FROM
                  srv_hierarhija_ravni AS r
                LEFT JOIN
                  srv_hierarhija_sifranti AS s
                ON
                  s.hierarhija_ravni_id = r.id
                WHERE
                 r.anketa_id = '$anketa'
                 $where
                ORDER BY level
              ");

        $results = null;
        if (!empty($sql) && $sql->num_rows > 0)
            $results = $sql;

        return $results;
    }

    /**
     * DB Tabela hierarhija_ravni
     *
     * @param int $anketa
     * @return SQL query
     */
    public function getRavni($anketa, $select = '*')
    {
        $sql = sisplet_query("
           SELECT
            $select
           FROM
            srv_hierarhija_ravni
           WHERE
            anketa_id = '$anketa'
           ORDER BY
            level ASC
        ");

        if (!empty($sql) && $sql->num_rows > 0)
            return $sql;

        return null;
    }

    /**
     * DB hierarhija_users preverimo pravice - type
     *
     * @pram int $user_id
     * @return query
     */
    public function queryHierarhijaUsers($user_id = null)
    {
        $where = 'WHERE user_id=' . (int)$user_id;

        if (is_null($user_id))
            $where = '';

        return sisplet_query("
            SELECT
              *
            FROM
              srv_hierarhija_users
            $where
         ");
    }

    /**
     * Pridobimo gru_id za vlogo in to uporabimo potem pri nivojih*
     * @return (int) $gru_id
     */
    public static function getGrupaId($anketa, $vrstni_red = null)
    {
        if (empty($anketa))
            die("Missing anketa ID");

        if (is_null($vrstni_red))
            $vrstni_red = 1;

        $sql = sisplet_query("SELECT id, vrstni_red FROM `srv_grupa` WHERE ank_id='" . $anketa . "' AND vrstni_red='" . $vrstni_red . "' ORDER BY vrstni_red LIMIT 0,1", 'obj');

        return $sql->id;
    }

    public static function getKodaRow($anketa, $struktura_id = null, $vloga = 'ucenec')
    {

        // V kolikor imamo specifično strukturo
        if (!is_null($struktura_id) && is_numeric($struktura_id))
            $struktura_id = " AND hierarhija_struktura_id='" . $struktura_id . "'";

        $sql = sisplet_query("SELECT * FROM srv_hierarhija_koda WHERE anketa_id='" . $anketa . "' " . $struktura_id . " AND vloga='" . $vloga . "'");

        if ($sql->num_rows > 0)
            return $sql->fetch_object();

    }

    /**
     * Check if error
     *
     * @param ($query) $sql
     * @return echo error
     */
    protected $sql;

    public function sqlError($sql)
    {
        if (!$sql)
            echo mysqli_error($GLOBALS['connect_db']);

    }


}