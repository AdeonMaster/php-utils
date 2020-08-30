<?php
  namespace Adeon;

  class MYSQLHandle {
    static $handle = NULL;
    static $totalInstances = 0;

    function __construct($dbServerName, $dbUserName, $dbUserPassword, $dbName) {
      if(!self::$handle) {
        self::$handle = new \mysqli($dbServerName, $dbUserName, $dbUserPassword, $dbName);
      }

      ++self::$totalInstances;

      $this->results = (object) [
        'status' => null,
        'sqlQuery' => null,
        'error' => null,
        'errorCode' => 0,
        'numRows' => 0,
        'affectedRows' => 0,
        'rows' => []
      ];
    }

    function __destruct() {
      --self::$totalInstances;
      if(!self::$totalInstances) {
        if(self::$handle) {
          self::$handle->close();
        }
      }
    }

    function escape_string($string) {
      return is_string($string)
        ? mysqli_real_escape_string(self::$handle, $string)
        : $string;
    }

    function escape_all($array) {
      foreach ($array as &$element) {
        if(is_string($element)) {
          $element = mysqli_real_escape_string(self::$handle, $element);
        }
      }
    }

    function clearResults() {
      $this->results = (object) [
        'status' => null,
        'sqlQuery' => null,
        'error' => null,
        'errorCode' => 0,
        'numRows' => 0,
        'affectedRows' => 0,
        'rows' => []
      ];
    }

    function _execute($sql) {
      $this->clearResults();

      $this->results->sqlQuery = $sql;

      if(self::$handle->connect_error) {
        $this->results->error = 'Error while connecting to database';
        $this->results->errorCode = self::$handle->errno;
      } else {
        $result = self::$handle->query($sql);
        if($result === false) {
          $this->results->error = self::$handle->error;
          $this->results->errorCode = self::$handle->errno;
        } else {
          $this->results->affectedRows = self::$handle->affected_rows;
          if($result !== true) {
            $this->results->numRows = $result->num_rows;
            if ($this->results->numRows > 0) {
              while($row = $result->fetch_assoc()) {
                $this->results->rows[] = $row;
              }
            }
          }
        }
      }

      $this->results->status = !($result === false);

      return $this->results;
    }

    function query($sql) {
      return $this->_execute($sql);
    }

    function _escape_type($value) {
      if(is_string($value)) {
        return "'$value'";
      } else if(is_bool($value)) {
        return $value ? "true" : "false";
      } else if(is_null($value)) {
        return "NULL";
      } else {
        return $value;
      }
    }

    function select(
      $table,
      $fields, 
      $where = null, 
      $orderBy = null, 
      $orderDirection = null, 
      $limit = null, 
      $offset = null
    ) {
      $fields_piece = is_string($fields)
        ? $fields
        : implode(', ', array_map(function($value, $key) {
          return is_string($key) ? $key.' as '.$value : $value;
        }, $fields, array_keys($fields)));

      $sql = "SELECT $fields_piece FROM $table";

      if($where !== null) {
        $where_pieces = '';
        if(is_string($where)) {
          $where_pieces = $where;
        } else if(is_array($where)) {
          $where_pieces = implode(' AND ', array_map(function($value, $key) {
            return is_array($value)
              ? $value[1]
                ? $key.' = '.$value[0]
                : $key.' = '.self::_escape_type($value[0])
              : $key.' = '.self::_escape_type($value);
          }, $where, array_keys($where)));
        } else {
          throw new Error("Wrong type");
        }

        $sql .= " WHERE $where_pieces";
      }

      if($orderBy !== null) {
        $order_peace = implode(', ', $orderBy);

        $sql .= " ORDER BY $order_peace";

        if($orderDirection !== null) {
          $sql .= " $orderDirection";
        }
      }

      if($limit !== null) {
        $sql .= " LIMIT $limit";

        if($offset !== null) {
          $sql .= ", $offset";
        }
      }

      return $this->_execute($sql);
    }

    function delete($table, $where) {
      $where_pieces = '';
      if(is_string($where)) {
        $where_pieces = $where;
      } else if(is_array($where)) {
        $where_pieces = implode(' AND ', array_map(function($value, $key) {
          return is_array($value)
            ? $value[1]
              ? $key.' = '.$value[0]
              : $key.' = '.self::_escape_type($value[0])
            : $key.' = '.self::_escape_type($value);
        }, $where, array_keys($where)));
      } else {
        throw new Error("Wrong type");
      }

      $sql = "DELETE FROM $table WHERE $where_pieces";

      return $this->_execute($sql);
    }

    function insert($table, $fields) {
      $keys = implode(', ', array_keys($fields));
      $values = implode(', ', array_map(function($value) {
        return is_array($value)
          ?  $value[1]
            ? $value[0]
            : self::_escape_type($value[0])
          : self::_escape_type($value);
      }, $fields));

      $sql = "INSERT INTO $table ($keys) VALUES ($values)";

      return $this->_execute($sql);
    }		

    function update($table, $fields, $where) {
      $fields_piece = implode(', ', array_map(function($value, $key) {
        return is_array($value)
          ? $value[1]
            ? $key.' = '.$value[0]
            : $key.' = '.self::_escape_type($value[0])
          : $key.' = '.self::_escape_type($value);
      }, $fields, array_keys($fields)));

      $where_pieces = '';
      if(is_string($where)) {
        $where_pieces = $where;
      } else if(is_array($where)) {
        $where_pieces = implode(' AND ', array_map(function($value, $key) {
          return is_array($value)
            ? $value[1]
              ? $key.' = '.$value[0]
              : $key.' = '.self::_escape_type($value[0])
            : $key.' = '.self::_escape_type($value);
        }, $where, array_keys($where)));
      } else {
        throw new Error("Wrong WHERE param type");
      }

      $sql = "UPDATE $table SET $fields_piece WHERE $where_pieces";

      return $this->_execute($sql);
    }
  }
?>