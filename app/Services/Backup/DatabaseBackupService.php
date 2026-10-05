<?php

namespace App\Services\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DatabaseBackupService
{
    public function create(?string $connection = null): string
    {
        $db = DB::connection($connection);
        if ($db->getDriverName() !== 'mysql' || $db->transactionLevel() !== 0) {
            throw new DatabaseBackupException('Backup yêu cầu MySQL và không được chạy trong transaction đang mở.');
        }
        $directory = config('backup.path');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new DatabaseBackupException('Không tạo được thư mục backup.');
        }
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $db->getDatabaseName());
        $path = $directory.'/'.$name.'-'.now(config('backup.timezone'))->format('Y-m-d_H-i-s').'-'.Str::uuid().'.sql.gz';
        $temporary = tempnam($directory, '.backup-');
        if ($temporary === false) {
            throw new DatabaseBackupException('Không tạo được file backup tạm.');
        }
        chmod($temporary, 0600);
        $stream = null;
        try {
            $stream = gzopen($temporary, 'wb6');
            if ($stream === false) {
                throw new DatabaseBackupException('Không mở được file backup nén.');
            }
            $this->dump($db, fn (string $sql) => $this->write($stream, $sql));
            if (! gzclose($stream)) {
                $stream = null;
                throw new DatabaseBackupException('Không hoàn tất được file backup nén.');
            }
            $stream = null;
            if (! rename($temporary, $path)) {
                throw new DatabaseBackupException('Không lưu được file backup hoàn chỉnh.');
            }

            return $path;
        } catch (Throwable $error) {
            if (is_resource($stream)) {
                gzclose($stream);
            }
            if (is_file($temporary)) {
                unlink($temporary);
            }
            throw $error;
        }
    }

    public function dump(Connection $db, callable $write): void
    {
        // Fail rather than silently omit database objects this portable dumper cannot restore.
        $unsupported = $db->selectOne('SELECT (SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()) + (SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_SCHEMA = DATABASE()) AS total', [], false);
        if ((int) $unsupported->total !== 0) {
            throw new DatabaseBackupException('Database có routine/event: cần công cụ backup hỗ trợ các đối tượng này.');
        }
        $tables = $db->select('SHOW TABLE STATUS', [], false);
        foreach ($tables as $table) {
            if ($table->Engine !== 'InnoDB') {
                throw new DatabaseBackupException('Backup nhất quán chỉ hỗ trợ bảng InnoDB; database đang có view hoặc engine khác.');
            }
        }
        $triggers = $db->select('SHOW TRIGGERS', [], false);
        $timezone = $db->selectOne('SELECT @@session.time_zone AS timezone', [], false)->timezone;
        try {
            $db->statement("SET time_zone='+00:00'");
            $db->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $db->statement('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
            $write("-- Laravel database backup\n-- Created at ".now()->toIso8601String()."\nSET @BACKUP_OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;\nSET @BACKUP_OLD_SQL_MODE=@@SQL_MODE;\nSET @BACKUP_OLD_TIME_ZONE=@@TIME_ZONE;\nSET TIME_ZONE='+00:00';\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
            foreach ($tables as $table) {
                $quoted = $this->identifier($table->Name);
                $definition = (array) $db->selectOne('SHOW CREATE TABLE '.$quoted, [], false);
                $write($definition['Create Table'].";\n");
                $columns = array_values(array_filter($db->select('SHOW FULL COLUMNS FROM '.$quoted, [], false),
                    fn ($column) => ! str_contains($column->Extra, 'VIRTUAL GENERATED') && ! str_contains($column->Extra, 'STORED GENERATED')));
                $fields = implode(', ', array_map(fn ($column) => $this->identifier($column->Field), $columns));
                $rows = 0;
                // Disable PDO buffering for this cursor, restoring the setting afterwards.
                $pdo = $db->getPdo();
                $buffered = $pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
                $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                try {
                    foreach ($db->cursor('SELECT '.$fields.' FROM '.$quoted, [], false) as $row) {
                        $values = array_map(fn ($column) => $this->literal($row->{$column->Field}, $column->Type), $columns);
                        $write('INSERT INTO '.$quoted.' ('.$fields.') VALUES ('.implode(', ', $values).");\n");
                        $rows++;
                    }
                } finally {
                    $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered);
                }
                $write('-- Rows: '.$table->Name.' = '.$rows."\n\n");
            }
            foreach ($triggers as $trigger) {
                $definition = (array) $db->selectOne('SHOW CREATE TRIGGER '.$this->identifier($trigger->Trigger), [], false);
                $write('SET SQL_MODE='.$this->literal($definition['sql_mode'], 'text').";\n");
                $write("DELIMITER ;;\n".$definition['SQL Original Statement'].";;\nDELIMITER ;\n\n");
            }
            $write("SET FOREIGN_KEY_CHECKS=@BACKUP_OLD_FOREIGN_KEY_CHECKS;\nSET SQL_MODE=@BACKUP_OLD_SQL_MODE;\nSET TIME_ZONE=@BACKUP_OLD_TIME_ZONE;\n-- Backup complete\n");
            $db->statement('COMMIT');
        } catch (Throwable $error) {
            $db->statement('ROLLBACK');
            throw $error;
        } finally {
            $db->statement('SET time_zone=?', [$timezone]);
        }
    }

    public function literal(mixed $value, string $type): string
    {
        if ($value === null) {
            return 'NULL';
        }
        $hex = "X'".bin2hex((string) $value)."'";

        // Convert textual/numeric values to text before assignment. Bare hex would
        // otherwise turn the string '123' into an incorrect integer on restoration.
        return preg_match('/^(?:binary|varbinary|.*blob|bit)\b/i', $type)
            ? $hex : 'CONVERT('.$hex.' USING utf8mb4)';
    }

    private function identifier(string $name): string
    {
        return '`'.str_replace('`', '``', $name).'`';
    }

    private function write($stream, string $data): void
    {
        while ($data !== '') {
            $length = gzwrite($stream, $data);
            if ($length === false || $length === 0) {
                throw new DatabaseBackupException('Không ghi được file backup.');
            }
            $data = substr($data, $length);
        }
    }
}
