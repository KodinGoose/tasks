<?php

declare(strict_types=1);

class Task
{
    public int $id;
    public string $title;
    public bool $done;

    public function __construct(int $id, string $title, bool $done)
    {
        $this->id = $id;
        $this->title = $title;
        $this->done = $done;
    }

    public function modifyDB(DB $db, int $uid): true|null {
        return $db->logError($db->connection->execute_query(
            'UPDATE tasks SET title = ?, done = ? WHERE id = ? AND uid = ?',
            array($this->title, $this->done, $this->id, $uid)
        ));
    }

    public static function allDB(DB $db, int $uid): array|null
    {
        $data = ($ret = $db->logError($db->connection->execute_query(
            'SELECT id, title, done FROM tasks WHERE uid = ?',
            array($uid)
        ))) === null ? null : $ret;

        $tasks = array();
        foreach ($data as $row) {
            $tasks = new Task($row[0], $row[1], $row[2]);
        }
        return $tasks;
    }

    public static function newDB(DB $db, int $uid, string $title): true|null {
        return $db->logError($db->connection->execute_query(
            'INSERT INTO tasks (title, uid) VALUE (?, ?)',
            array($title, $uid)
        ));
    }

    public static function deleteDB(DB $db, int $uid, int $id): true|null {
        return $db->logError($db->connection->execute_query(
            'DELETE FROM tasks WHERE id = ? AND uid = ?',
            array($id, $uid)
        ));
    }

    public static function taskOwnedByUser(DB $db, int $uid, int $id): bool|null {
        return ($ret = $db->logError($db->connection->execute_query(
            'SELECT EXISTS (SELECT * FROM task WHERE id = ? AND uid = ?)',
            array($id, $uid)
        ))) === null ? null : $ret[0][0];
    }
}
