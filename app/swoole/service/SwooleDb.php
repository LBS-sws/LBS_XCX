<?php
namespace app\swoole\service;

class SwooleDb
{
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $connection;
    private $table;
    private $fields = '*';
    private $joins = '';
    private $conditions = [];

    public function __construct($host, $dbname, $username, $password)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";

        try {
            $this->connection = new \PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new \Exception("数据库连接失败: " . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $this->connection = null;
    }

    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollback()
    {
        return $this->connection->rollBack();
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function select($fields = '*')
    {
        $this->fields = $fields;
        return $this;
    }

    public function join($table, $on)
    {
        $this->joins .= "INNER JOIN $table ON $on ";
        return $this;
    }

    public function where($conditions)
    {
        foreach ($conditions as $column => $value) {
            $this->conditions[$column] = $value;
        }
        return $this;
    }


    public function executeQuery($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        foreach ($params as $param => &$value) {
            $stmt->bindParam(":" . $param, $value);
        }
        try {
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception("查询执行失败: " . $e->getMessage());
        }
    }

    public function executeNonQuery($sql, $params = [])
    {
        $stmt = $this->connection->prepare($sql);
        foreach ($params as $param => &$value) {
            $stmt->bindParam(":" . $param, $value);
        }
        try {
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception("非查询操作执行失败: " . $e->getMessage());
        }
    }

    public function get()
    {
        $where = '';
        foreach ($this->conditions as $column => $value) {
            $where .= "$column = :$column AND ";
        }
        $where = rtrim($where, " AND ");

        $sql = "SELECT {$this->fields} FROM {$this->table} {$this->joins}";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        $sql .= " LIMIT 1"; // 添加限制返回一条记录

        $result = $this->executeQuery($sql, $this->conditions);

        return count($result) > 0 ? $result[0] : null; // 返回单条记录或 null
    }


    public function create($data)
    {
        $columns = implode(", ", array_keys($data));
        $values = ":" . implode(", :", array_keys($data));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
        $this->executeNonQuery($sql, $data);
    }

    public function update($data)
    {
        $set = "";
        foreach ($data as $column => $value) {
            $set .= "$column = :$column, ";
        }
        $set = rtrim($set, ", ");

        $where = '';
        foreach ($this->conditions as $column => $value) {
            $where .= "$column = :$column AND ";
        }
        $where = rtrim($where, " AND ");

        $sql = "UPDATE {$this->table} SET $set WHERE $where";
        $params = array_merge($data, $this->conditions);
        $this->executeNonQuery($sql, $params);
    }

    public function delete()
    {
        $where = '';
        foreach ($this->conditions as $column => $value) {
            $where .= "$column = :$column AND ";
        }
        $where = rtrim($where, " AND ");

        $sql = "DELETE FROM {$this->table} WHERE $where";
        $this->executeNonQuery($sql, $this->conditions);
    }

    public function selectJoin($tables, $fields, $conditions = [])
    {
        $joins = '';
        foreach ($tables as $table => $on) {
            $joins .= "INNER JOIN $table ON $on ";
        }

        $where = '';
        foreach ($conditions as $column => $value) {
            $where .= "$column = :$column AND ";
        }
        $where = rtrim($where, " AND ");

        $sql = "SELECT $fields FROM $joins";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        return $this->executeQuery($sql, $conditions);
    }
}
