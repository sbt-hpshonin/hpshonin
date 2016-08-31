<?php
/**
 * データベースアクセスクラス
 * @author keiohnishi
 *
 */
class DbComponent extends Component {

    var $dbh;
    var $result;

    // 接続
    function connect($DbServer, $DbName, $DbUser, $DbPasswd) {
    	$this->dbh = new PDO("mysql:dbname=".$DbName.";host=".$DbServer, $DbUser, $DbPasswd);
        if(!$this->dbh) {
            die("Can not connect ".$DbServer." : ".mysql_error());
        }
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // 切断
    function close() {
        $this->dbh = null;
    }

    // クエリ送信
    function query($sql){
        $ret = $this->dbh->query($sql);
        if(!$ret){
            die("Invalid query");
        }
        return $ret;
    }

    // フェッチ
    function fetch($result){
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    // クエリ送信とフェッチ
    function queryFetch($sql){
        if(!is_null($sql)){
            $this->result = $this->query($sql);
            if(!$this->result){
                return FALSE;
            }
        }

        return $this->fetch($this->result);
    }

    // 実行
    function execute($sql){
        $this->result = $this->dbh->exec($sql);
        return $this->result;
    }

    // id取得
    function lastInsertId(){
    	$id = $this->dbh->lastInsertId();
    	return $id;
    }

    // トランザクション開始(bool)
    function beginTransaction(){
        return $this->dbh->beginTransaction();
    }

    // ロールバック(bool)
    function rollBack(){
        return $this->dbh->rollBack();
    }

    // コミット(bool)
    function commit(){
        return $this->dbh->commit();
    }
}