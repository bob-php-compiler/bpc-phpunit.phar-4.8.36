<?php
/*
 * This file is part of DBUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Executes a truncate against all tables in a dataset.
 *
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_Extensions_Database_Operation_Truncate implements PHPUnit_Extensions_Database_Operation_IDatabaseOperation
{
    protected $useTransaction;
    protected $useCascade;

    public function __construct($transaction = true, $cascade = false)
    {
        $this->useTransaction = $transaction;
        $this->useCascade     = $cascade;
    }

    public function setCascade($cascade = TRUE)
    {
        $this->useCascade = $cascade;
    }

    public function execute(PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection, PHPUnit_Extensions_Database_DataSet_IDataSet $dataSet)
    {
        $pdo = $connection->getConnection();
        $isMysql = $this->isMysql($connection);
        if ($isMysql) {
            $this->disableForeignKeyChecksForMysql($pdo);
        }
        if ($this->useTransaction) {
            $pdo->beginTransaction();
        }
        try {
            foreach ($dataSet->getReverseIterator() as $table) {
                $sql = $connection->getTruncateCommand() . ' ' . $connection->quoteSchemaObject($table->getTableMetaData()->getTableName());
                if ($this->useCascade && $connection->allowsCascading()) {
                    $sql .= ' CASCADE';
                }
                $pdo->exec($sql);
            }
            if ($this->useTransaction) {
                $pdo->commit();
            }
        } catch (Exception $e) {
            if ($this->useTransaction) {
                $pdo->rollBack();
            }
            if ($isMysql) {
                $this->enableForeignKeyChecksForMysql($pdo);
            }
            if ($e instanceof PDOException) {
                throw new PHPUnit_Extensions_Database_Operation_Exception('TRUNCATE', $sql, array(), $table, $e->getMessage());
            }
            throw $e;
        }

        if ($isMysql) {
            $this->enableForeignKeyChecksForMysql($pdo);
        }
    }

    private function disableForeignKeyChecksForMysql(PDO $pdo)
    {
        $pdo->exec('SET @PHPUNIT_OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS');
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    }

    private function enableForeignKeyChecksForMysql(PDO $pdo)
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=@PHPUNIT_OLD_FOREIGN_KEY_CHECKS');
    }

    private function isMysql(PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection)
    {
        return $connection->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql';
    }
}
