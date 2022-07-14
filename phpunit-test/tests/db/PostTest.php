<?php
/**
 * @group db
 */
class PostTest extends PHPUnit_DbUnit_Mysql_TestCase
{
    static $classGroups = array('db');

    protected function getDataSet()
    {
        return $this->createArrayDataSet(array(
            'post' => array(
                array(
                    'id'          => 1,
                    'name'        => 'joe',
                    'content'     => 'Hello buddy!',
                    'create_date' => '2022-05-23 11:15:23'
                ),
                array(
                    'id'          => 2,
                    'name'        => 'Join',
                    'content'     => 'I like it!',
                    'create_date' => '2022-05-23 11:14:20'
                ),
            ),
        ));
    }

    public function testAddBook()
    {
        $dsn      = 'mysql:host=127.0.0.1;port=3307;dbname=our_phpunit_test';
        $user     = 'root';
        $password = '123456';
        $pdo      = new PDO($dsn, $user, $password);
        $stmt     = $pdo->prepare('INSERT INTO post(name, content) values(:name, :content)');
        $stmt->execute(array(':name' => 'Sim', ':content' => 'Sim content'));

        $expectedDataSet = $this->createArrayDataSet(array(
            'post' => array(
                array(
                    'id'          => 1,
                    'name'        => 'joe',
                    'content'     => 'Hello buddy!',
                ),
                array(
                    'id'          => 2,
                    'name'        => 'Join',
                    'content'     => 'I like it!'
                ),
                array(
                    'id'          => 3,
                    'name'        => 'Sim',
                    'content'     => 'Sim content',
                ),
            )
        ));

        $dataSet = $this->getConnection()->createDataSet(array('post'));
        $filterDataSet = new PHPUnit_Extensions_Database_DataSet_DataSetFilter($dataSet);
        $filterDataSet->setExcludeColumnsForTable('post', array('create_date'));

        $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
    }

    public function testPostNotEmpty()
    {
        $this->assertTableNotEmpty('post');
    }

    public function testPostEmpty()
    {
        $deleteAllOp = new PHPUnit_Extensions_Database_Operation_DeleteAll();
        $deleteAllOp->execute(
            $this->getConnection(),
            $this->createArrayDataSet(array(
                'post' => array()
            ))
        );

        $this->assertTableEmpty('post');
    }
}
