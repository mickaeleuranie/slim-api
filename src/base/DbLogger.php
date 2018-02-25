<?php

/**
 * This file is part of the Slim API package
 *
 * @author Mickaël Euranie <contact@mickaeleuranie.com>
 */

namespace api\base;

use api\Api;

class DbLogger
{
    /**
     * Log data into log_error table
     * +------------+--------------+------+-----+---------+----------------+
     * | Field      | Type         | Null | Key | Default | Extra          |
     * +------------+--------------+------+-----+---------+----------------+
     * | id         | int(11)      | NO   | PRI | NULL    | auto_increment |
     * | level      | varchar(128) | YES  |     | NULL    |                |
     * | category   | varchar(128) | YES  |     | NULL    |                |
     * | code       | varchar(20)  | NO   |     | NULL    |                |
     * | date       | datetime     | YES  |     | NULL    |                |
     * | message    | mediumtext   | YES  |     | NULL    |                |
     * | details    | text         | YES  |     | NULL    |                |
     * | user_id    | int(11)      | YES  |     | NULL    |                |
     * | controller | varchar(128) | YES  |     | NULL    |                |
     * | action     | varchar(128) | YES  |     | NULL    |                |
     * | referer    | varchar(256) | YES  |     | NULL    |                |
     * | ip         | varchar(50)  | YES  |     | NULL    |                |
     * +------------+--------------+------+-----+---------+----------------+
     */
    public function error($data)
    {
        // check data format
        $availableColumns = [
            'id',
            'level',
            'category',
            'code',
            'date',
            'message',
            'details',
            'user_id',
            'controller',
            'action',
            'referer',
            'ip',
        ];
        $columns = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $availableColumns)) {
                $columns[] = $key;
                $params[':' . $key] = $value;
            }
        }

        if (empty($params[':date'])) {
            $now = new \DateTime;
            $columns[] = 'date';
            $params[':date'] = $now->format('Y-m-d H:i:s');
        }

        $sql = 'INSERT INTO log_error (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_keys($params)) . ');';
        $q = Api::$pdo->prepare($sql);
        $q->execute($params);
    }

    /**
     * Log data into log_action table
     * +------------+--------------+------+-----+---------+----------------+
     * | Field      | Type         | Null | Key | Default | Extra          |
     * +------------+--------------+------+-----+---------+----------------+
     * | id         | int(11)      | NO   | PRI | NULL    | auto_increment |
     * | level      | varchar(128) | YES  |     | NULL    |                |
     * | category   | varchar(128) | YES  |     | NULL    |                |
     * | date       | datetime     | YES  |     | NULL    |                |
     * | message    | mediumtext   | YES  |     | NULL    |                |
     * | user_id    | int(10)      | YES  |     | NULL    |                |
     * | controller | varchar(128) | YES  |     | NULL    |                |
     * | action     | varchar(128) | YES  |     | NULL    |                |
     * | referer    | text         | YES  |     | NULL    |                |
     * | ip         | varchar(50)  | YES  |     | NULL    |                |
     * +------------+--------------+------+-----+---------+----------------+
     */
    public function action($data)
    {
        // check data format
        $availableColumns = [
            'id',
            'level',
            'category',
            'date',
            'message',
            'user_id',
            'controller',
            'action',
            'referer',
            'ip',
        ];
        $columns = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $availableColumns)) {
                $columns[] = $key;
                $params[':' . $key] = $value;
            }
        }

        if (empty($params[':date'])) {
            $now = new \DateTime;
            $columns[] = 'date';
            $params[':date'] = $now->format('Y-m-d H:i:s');
        }

        $sql = 'INSERT INTO log_action (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_keys($params)) . ');';
        $q = Api::$pdo->prepare($sql);
        $q->execute($params);
    }

    /**
     * Log data into logs table
     * +------------+--------------+------+-----+---------+----------------+
     * | Field      | Type         | Null | Key | Default | Extra          |
     * +------------+--------------+------+-----+---------+----------------+
     * | id         | int(11)      | NO   | PRI | NULL    | auto_increment |
     * | level      | varchar(128) | YES  |     | NULL    |                |
     * | category   | varchar(128) | YES  |     | NULL    |                |
     * | logtime    | int(11)      | YES  |     | NULL    |                |
     * | message    | mediumtext   | YES  |     | NULL    |                |
     * +------------+--------------+------+-----+---------+----------------+
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = 'info', $category = 'api')
    {
        $now = new \DateTime;
        $params = [
            ':level'    => $level,
            ':category' => $category,
            ':logtime'  => $now->getTimestamp(),
            ':message'  => $message,
        ];
        $sql = 'INSERT INTO log (`level`, `category`, `logtime`, `message`) VALUES (' . implode(', ', array_keys($params)) . ');';
        $q = Api::$pdo->prepare($sql);
        $q->execute($params);
    }

    /**
     * Log warning logs table
     * @param string $message
     */
    public function warning($message)
    {
        return $this->log($message, 'warning');
    }

    /**
     * Log info logs table
     * @param string $message
     */
    public function info($message)
    {
        return $this->log($message, 'info');
    }
}
