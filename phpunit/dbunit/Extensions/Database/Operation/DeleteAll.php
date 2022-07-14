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
 * Deletes all rows from all tables in a dataset.
 *
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_Extensions_Database_Operation_DeleteAll implements PHPUnit_Extensions_Database_Operation_IDatabaseOperation
{
    protected $useTransaction;

    public function __construct($transaction = true)
    {
        $this->useTransaction = $transaction;
    }

    public function execute(PHPUnit_Extensions_Database_DB_IDatabaseConnection $connection, PHPUnit_Extensions_Database_DataSet_IDataSet $dataSet)
    {
        $pdo = $connection->getConnection();
        if ($this->useTransaction) {
            $pdo->beginTransaction();
        }
        try {
            foreach ($dataSet->getReverseIterator() as $table) {
                $sql = 'DELETE FROM ' . $connection->quoteSchemaObject($table->getTableMetaData()->getTableName());
                $pdo->exec($sql);
            }
            if ($this->useTransaction) {
                $pdo->commit();
            }
        } catch (PDOException $e) {
            if ($this->useTransaction) {
                $pdo->rollBack();
            }
            throw new PHPUnit_Extensions_Database_Operation_Exception('DELETE_ALL', $sql, array(), $table, $e->getMessage());
        }
    }
}
