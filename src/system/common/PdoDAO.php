<?php
require_once('LogAdapter.php');

/**
 * DBアクセスクラス
 *
 * DBアクセスを一元管理する
 *
 * @package jp.takaya
 * @access  public
 * @author  Shuichi Takaya <takayashuichi@hotmail.com>
 * @create  2005/12/01
 * @version $Id:$
 **/
class PdoDAO {

    /**
     * コネクションフィールド
     * @var Object
     * @access public
     */
    var $connect = null;

    /**
     * ロガー
     * @var Object
     * @access public
     */
    var $logger = null;

    /**
     * テーブル名
     * @var Object
     * @access public
     */
    var $table = null;

    /**
     * LastIDを取得するSQL
     * @var Object
     * @access public
     */
    var $rowsql = null;

    /**
     * コンストラクタ
     */
    function PdoDAO() {
        $this->table = strtolower(get_class($this));
    }

    /**
     * コネクション取得
     * @access public
     */
    function getConnection($host = null) {
        if ($this->connect == null) {
            try {
                if ($host == null) {
                    $host = DB_HOST;
                }

                $this->logger = new LogAdapter(__CLASS__ . ':' .  __LINE__);

                // MYSQLで文字コードがUTF8の場合、文字コードをセットする
                if (false !== strpos($host, 'mysql') && DB_CODE == 'UTF-8') {
                    if (!$this->connect = new PDO($host, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'))) {
                        $this->logger->error('データベースに接続できません。');
                    }
                } else {
                    if (!$this->connect = new PDO($host, DB_USER, DB_PASS)) {
                        $this->logger->error('データベースに接続できません。');
                    }
                }

                // ラストレコードを取得するSQLを生成
                if (false !== strpos($host, 'pgsql:')) {
                    $this->rowsql = 'select last_insert_id() as rowid';
                } elseif (false !== strpos($host, 'sqlite:')) {
                    $this->rowsql = 'select last_insert_rowid() as rowid';
                } elseif (false !== strpos($host, 'mysql:')) {
                    $this->rowsql = 'select last_insert_id() as rowid';
                } else {
                    $this->rowsql = 'select max(id) as rowid from ' . $this->table;
                }

                $this->connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                $this->logger->error($e->getMessage());
                if (RUN_DEBUG == 'ON') {
                    print $e->getMessage();
                }
                return null;
            }
        }
    }

    /**
     * コネクション開放
     * @access    public
     */
    function freeConnection() {
        if ($this->connect <> null) {
            $this->connect = null;
        }
    }

    /**
     * ラストIDを取得
     * @access   public
     * @param    String  SQL文
     * @return   Object  処理結果
     */
    function getLastId() {
        try {
            if ($this->connect == null) {
                $this->getConnection();
            }
            $result = $this->connect->query($this->rowsql);
            if (!$result) {
                return 0;
            }
            $data = $result->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e){
            $this->logger->error($e->getMessage());
            if (RUN_DEBUG == 'ON') {
                print $e->getMessage();
            }
            return 0;
        }
        return $data['rowid'];
    }

    /**
     * DBクエリー発行
     * @access   public
     * @param    String  SQL文
     * @return   Object  処理結果
     */
    function execute($query) {
        try {
            if ($this->connect == null) {
                $this->getConnection();
            }
            $this->logger->info($query);
            $query = mb_convert_encoding($query, DB_CODE, mb_internal_encoding());
            $result = $this->connect->query($query);
            if (!$result) {
                $this->logger->error('SQLエラー');
            }
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            if (RUN_DEBUG == 'ON') {
                print $e->getMessage();
            }
            return null;
        }
        return $result;
    }

    /**
     * DBカウント検索
     * @access   public
     * @param    String   SQL      SQL文
     * @param    $limit   Number   行数
     * @param    $offset  Number   オフセット
     * @return   Integer  レコードカウント
     */
    function rows($query) {
        try {

            if ($this->connect == null) {
                $this->getConnection();
            }

            $sql = explode('where', $query);

            // 検索条件にフラグを設定
            if (1 == count($sql)) {
                $query = $sql[0] . " where deleted != '1'";
            } else {
                $query = $sql[0];
                for ($i = 1; $i < count($sql); $i++) {
                    $query .= " where deleted != '1' and " . $sql[$i];
                }
            }

            $sql = explode('from', $query);
            $query = 'select count(*) as recordcount ';
            for ($i = 1; $i < count($sql); $i++) {
                $query .= 'from ' . $sql[$i];
            }

            // クエリー発行
            $this->logger->info($query);
            $query = mb_convert_encoding($query, DB_CODE, mb_internal_encoding());
            $result = $this->connect->query($query);
            if (!$result) {
                return 0;
            }

            $data = $result->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            return 0;
        }
        return $data['recordcount'];
    }

    /**
     * DBカウント検索
     * @access   public
     * @param    String   SQL      SQL文
     * @param    $limit   Number   行数
     * @param    $offset  Number   オフセット
     * @return   Integer  レコードカウント
     */
    function count($where = null) {
        try {

            if ($this->connect == null) {
                $this->getConnection();
            }

            $query = 'select count(*) as recordcount from '
                   . $this->table . " where deleted != '1' ";

            // 条件文を設定
            if ($where != null) {
                $sql = explode('where', $where);

                // 検索条件にフラグを設定
                $query .= ' and ' . $sql[0];
                for ($i = 1; $i < count($sql); $i++) {
                    $query .= " where deleted != '1' and " . $sql[$i];
                }
            }

            // クエリー発行
            $this->logger->info($query);
            $query = mb_convert_encoding($query, DB_CODE, mb_internal_encoding());
            $result = $this->connect->query($query);
            if (!$result) {
                return 0;
            }

            $data = $result->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            return 0;
        }
        return $data['recordcount'];
    }

    /**
     * DB検索
     * @access   public
     * @param    String  where     where文
     * @param    $limit   Number   行数
     * @param    $offset  Number   オフセット
     * @return   Array   処理結果
     */
    function find($where = null, $limit = null, $offset = null) {
        try {

            if ($this->connect == null) {
                $this->getConnection();
            }

            $query = 'select * from ' . $this->table
                   . " where deleted != '1' ";

            // 条件文を設定
            if ($where != null) {
                $sql = explode('where', $where);

                // 検索条件にフラグを設定
                $query .= ' and ' . $sql[0];
                for ($i = 1; $i < count($sql); $i++) {
                    $query .= " where deleted != '1' and " . $sql[$i];
                }
            }

            // リミットを設定
            if ($limit != null) {
                $query .= " limit $limit ";
            }

            // オフセットを設定
            if ($offset != null) {
                $query .= " offset $offset ";
            }

            // クエリー発行
            $this->logger->info($query);
            $query = mb_convert_encoding($query, DB_CODE, mb_internal_encoding());
            $result = $this->connect->query($query);
            $data = array();

            if (!$result) {
                return $data;
            }

            // 連想配列で取得
            $data = $result->fetchAll(PDO::FETCH_ASSOC);
            mb_convert_variables(mb_internal_encoding(), DB_CODE, $data);
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            if (RUN_DEBUG == 'ON') {
                print $e->getMessage();
            }
            return null;
        }

        $this->logger->debug(print_r($data, true));
        return $data;
    }

    /**
     * DB検索
     * @access   public
     * @param    String   SQL      SQL文
     * @param    $limit   Number   行数
     * @param    $offset  Number   オフセット
     * @return   Array    処理結果
     */
    function select($query, $limit = null, $offset = null) {
        try {

            if ($this->connect == null) {
                $this->getConnection();
            }

            $sql = explode('where', $query);

            // 検索条件にフラグを設定
            if (1 == count($sql)) {
                $query = $sql[0] . " where deleted != '1'";
            } else {
                $query = $sql[0];
                for ($i = 1; $i < count($sql); $i++) {
                    $query .= " where deleted != '1' and " . $sql[$i];
                }
            }

            // リミットを設定
            if ($limit != null) {
                $query .= " limit $limit ";
            }

            // オフセットを設定
            if ($offset != null) {
                $query .= " offset $offset ";
            }

            // クエリー発行
            $this->logger->info($query);
            $query = mb_convert_encoding($query, DB_CODE, mb_internal_encoding());
            $result = $this->connect->query($query);
            $data = array();

            if (!$result) {
                return $data;
            }

            // 連想配列で取得
            $data = $result->fetchAll(PDO::FETCH_ASSOC);
            mb_convert_variables(mb_internal_encoding(), DB_CODE, $data);
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            if (RUN_DEBUG == 'ON') {
                print $e->getMessage();
            }
            return null;
        }

        $this->logger->debug(print_r($data, true));
        return $data;
    }

    /**
     * DBインサート
     * @access   public
     * @param    Array    データ
     * @param    String   テーブル
     * @return   Object   処理結果
     */
    function insert($data, $table = null) {
        try {

            if ($this->connect == null) {
                $this->getConnection();
            }

            if ($table == null) {
                $table = $this->table;
            }

            // 日付を取得
            $date = date('Y-m-d H:i:s');
            $column = '';
            $entry  = '';

            // エントリデータを作成
            foreach ($data as $name => $value) {
                $value = inSql($value);
                $column .= "$name,";
                $entry  .= "'$value',";
            }

            // SQL文生成
            $column = substr($column, 0, strlen($column) -1);
            $entry  = substr($entry,  0, strlen($entry)  -1);

            // クエリー作成
            $query = 'insert into ' . $table
                   . '(created,modified,deleted,' . $column
                   . ')values'
                   . "('$date','$date','0',$entry)";

            $this->logger->info($query);
            $query = mb_convert_encoding($query, DB_CODE, mb_internal_encoding());
            $result = $this->connect->query($query);

            if (!$result) {
                $this->logger->error('SQLエラー');
            }
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            if (RUN_DEBUG == 'ON') {
                print $e->getMessage();
            }
            return null;
        }
        return $result;
    }

    /**
     * DBアップデート
     * @access   public
     * @param    Array    データ
     * @param    String   テーブル
     * @return   Object   処理結果
     */
    function update($data, $table = null) {
        try {

            if ($this->connect == null) {
                $this->getConnection();
            }

            if ($table == null) {
                $table = $this->table;
            }

            // 日付を取得
            $date = date('Y-m-d H:i:s');
            $sql = "update $table set ";
            $where = '';

            // 更新データを作成
            foreach ($data as $name => $value) {
                // idは別処理
                if ($name == 'id') {
                    if (is_array($value) && count($value) > 0) {
                        $where = 'where id in (';
                        for ($i = 0; $i < count($value); $i++) {
                            $where .= inSql($value[$i]) . ',';
                        }
                        $where = substr($where, 0, -1);
                        $where .= ')';
                    } else {
                        $where = 'where id = ' . inSql($value);
                    }
                } else {
                    $sql .= "$name = '$value',";
                }
            }

            // SQL文生成
            $query = "$sql modified = '$date' " . $where;
            $this->logger->info($query);
            $query = mb_convert_encoding($query, DB_CODE, mb_internal_encoding());
            $result = $this->connect->query($query);

            if (!$result) {
                $this->logger->error('SQLエラー');
            }
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            if (RUN_DEBUG == 'ON') {
                print $e->getMessage();
            }
            return null;
        }
        return $result;
    }

    /**
     * DBデリート
     * @access   public
     * @param    Object   データ
     * @param    String   テーブル
     * @return   Object   処理結果
     */
    function delete($data, $table = null) {
        try {

            if ($this->connect == null) {
                $this->getConnection();
            }

            if ($table == null) {
                $table = $this->table;
            }

            // 日付を取得
            $date = date('Y-m-d H:i:s');

            // 配列要素チェック
            if (is_array($data)) {
                for ($i = 0, $where = ''; $i < count($data); $i++) {
                    if ($where == '') {
                        $where = 'where id in (' . $data[$i];
                    } else {
                        $where .= ',' . $data[$i];
                    }
                }
                $where .= ')';
            } else {
                $where = "where id = $data";
            }

            // タイムスタンプを挿入しフラグを立てる
            $query = "update $table "
                   . "set modified = '$date',"
                   . "deleted = '1' "
                   . $where;

            $this->logger->info($query);
            $query = mb_convert_encoding($query, DB_CODE, mb_internal_encoding());
            $result = $this->connect->query($query);

            if (!$result) {
                $this->logger->error('SQLエラー');
            }
        } catch (PDOException $e) {
            $this->logger->error($e->getMessage());
            if (RUN_DEBUG == 'ON') {
                print $e->getMessage();
            }
            return null;
        }
        return $result;
    }
}
?>
