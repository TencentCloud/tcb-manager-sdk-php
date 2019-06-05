<?php

namespace TcbManager\Tests\Services\Database;


use TcbManager\Services\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;
use TcbManager\Tests\TestBase;
use TencentCloudClient\Exception\TCException;

const DS = DIRECTORY_SEPARATOR;

class DatabaseManagerTest extends TestCase
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    private $tableName1 = "tcb_test_table_1";
    private $tableName2 = "tcb_test_table_2";
    private $tableName3 = "tcb_test_table_3";

    private $tableAlreadyExists = "tcb_already_exists";
    private $tableNeverExists = "tcb_never_exists";

    private $tableForDescribe = "tcb_table_for_describe";
    private $tableForList = "tcb_table_for_list";

    private $tableNeedUpdate = "tcb_table_need_update";

    private $tableNeedImport = "tcb_table_need_import";
    private $tableNeedExport = "tcb_table_need_export";

    private $dataFilePath = __DIR__.DS."data.csv";

    public function recreateTable(string $tableName)
    {
        $this->databaseManager->deleteTable($tableName);
        $this->databaseManager->createTable($tableName);
    }

    protected function setUp(): void
    {
        parent::setUp();

        TestBase::init();

        $this->databaseManager = TestBase::$tcb->getDatabaseManager();
    }

    public function assertHasRequestId($result)
    {
        $this->assertObjectHasAttribute("RequestId", $result);
    }

    public function testCreateTable()
    {
        $this->databaseManager->deleteTable($this->tableName1);

        $result = $this->databaseManager->createTable($this->tableName1);
        $this->assertHasRequestId($result);

        $this->expectException(TCException::class);
        $result = $this->databaseManager->createTable($this->tableName1);
        $this->assertHasRequestId($result);
    }

    public function testCreateTableIfNotExists()
    {
        $this->databaseManager->deleteTable($this->tableName2);

        $result = $this->databaseManager->createTableIfNotExists($this->tableName2);

        $this->assertHasRequestId($result);
        $this->assertEquals(true, $result->IsCreated);

        $result = $this->databaseManager->createTableIfNotExists($this->tableName2);

        $this->assertHasRequestId($result);
        $this->assertEquals(false, $result->IsCreated);
    }

    public function testDeleteTable()
    {
        $this->databaseManager->createTableIfNotExists($this->tableName3);

        $result = $this->databaseManager->deleteTable($this->tableName3);
        $this->assertHasRequestId($result);
        // 可删除不存在的表，并无两样
        $result = $this->databaseManager->deleteTable($this->tableName3);
        $this->assertHasRequestId($result);
    }

    public function testCheckTableExistsWithAlreadyExistsTable()
    {
        $this->databaseManager->createTableIfNotExists($this->tableAlreadyExists);

        $result = $this->databaseManager->checkTableExists($this->tableAlreadyExists);
        $this->assertHasRequestId($result);
        $this->assertEquals(true, $result->Exists);
    }

    public function testCheckTableExistsWithNeverExistsTable()
    {
        $result = $this->databaseManager->checkTableExists($this->tableNeverExists);
        $this->assertHasRequestId($result);
        $this->assertEquals(false, $result->Exists);
    }

    public function testDescribeTableAlreadyExists()
    {
        $this->databaseManager->createTableIfNotExists($this->tableForDescribe);

        $result = $this->databaseManager->describeTable($this->tableForDescribe);
        $this->assertHasRequestId($result);
        $this->assertEquals(1, $result->IndexNum);
        $this->assertEquals(1, count($result->Indexes));
    }

    public function testListTable()
    {
        $this->databaseManager->createTableIfNotExists($this->tableForList);

        $result = $this->databaseManager->listTables();
        $this->assertHasRequestId($result);
        $this->assertGreaterThan(0, $result->Tables);
        $this->assertGreaterThan(0, $result->Pager->Total);
        $this->assertEquals(0, $result->Pager->Offset);
        $this->assertEquals(100, $result->Pager->Limit);

        $exists = false;
        foreach ($result->Tables as $table) {
            if ($table->TableName === $this->tableForList) {
                $exists = true;
            }
        }
        $this->assertEquals(true, $exists);

        $MgoOffset = 0; $MgoLimit = 20;
        $result = $this->databaseManager->listTables([
            "MgoOffset" => $MgoOffset,
            "MgoLimit" => $MgoLimit,
        ]);
        $this->assertEquals($MgoOffset, $result->Pager->Offset);
        $this->assertEquals($MgoLimit, $result->Pager->Limit);
    }

    public function testUpdateTable()
    {
        $this->recreateTable($this->tableNeedUpdate);

        $result = $this->databaseManager->updateTable($this->tableNeedUpdate, [
            "CreateIndexes" => [
                [
                    "IndexName" => "index_a",
                    "MgoKeySchema" => [
                        "MgoIndexKeys" => [
                            // 2d要放最前面
                            ["Name" => "a_2d", "Direction" => "2d"],
                            ["Name" => "a_1", "Direction" => "1"],
                            ["Name" => "a_-1", "Direction" => "-1"],
                        ],
                        "MgoIsUnique" => false
                    ]
                ],
                [
                    "IndexName" => "index_b",
                    "MgoKeySchema" => [
                        "MgoIndexKeys" => [
                            ["Name" => "b_1", "Direction" => "2d"]
                        ],
                        "MgoIsUnique" => true
                    ]
                ],
                [
                    "IndexName" => "index_to_be_delete",
                    "MgoKeySchema" => [
                        "MgoIndexKeys" => [
                            ["Name" => "xxx", "Direction" => "2d"]
                        ],
                        "MgoIsUnique" => true
                    ]
                ],
            ]
        ]);
        $this->assertHasRequestId($result);

        $result = $this->databaseManager->checkIndexExists(
            $this->tableNeedUpdate,
            "index_to_be_delete"
        );
        $this->assertEquals(true, $result->Exists);

        $result = $this->databaseManager->updateTable($this->tableNeedUpdate, [
            "CreateIndexes" => [
                [
                    "IndexName" => "index_b_1",
                    "MgoKeySchema" => [
                        "MgoIndexKeys" => [
                            // 2d要放最前面
                            ["Name" => "b_2d", "Direction" => "2d"],
                            ["Name" => "b_1", "Direction" => "1"],
                            ["Name" => "b_-1", "Direction" => "-1"],
                        ],
                        "MgoIsUnique" => false
                    ]
                ]
            ],
            "DropIndexes" => [
                ["IndexName" => "index_to_be_delete"]
            ]
        ]);
        $this->assertHasRequestId($result);

        $result = $this->databaseManager->checkIndexExists(
            $this->tableNeedUpdate,
            "index_to_be_delete"
        );
        $this->assertEquals(false, $result->Exists);

        $result = $this->databaseManager->checkIndexExists(
            $this->tableNeedUpdate,
            "index_b_1"
        );
        $this->assertEquals(true, $result->Exists);
    }

    public function testImport()
    {
        $this->recreateTable($this->tableNeedImport);

        $result = $this->databaseManager->import(
            $this->tableNeedImport,
            [
                "FilePath" => $this->dataFilePath,
                // "ObjectKey" => "data.csv"
            ],
            [
                "ObjectKeyPrefix" => "db-imports",
                "FileType" => "csv",
                "StopOnError" => true,
                "ConflictMode" => "upsert"
            ]
        );
        $this->assertHasRequestId($result);
        $this->assertObjectHasAttribute("JobId", $result);

        $result = $this->databaseManager->migrateStatus($result->JobId);
        $this->assertHasRequestId($result);
        $this->assertObjectHasAttribute("Status", $result);
    }

    public function testExport()
    {
        $this->recreateTable($this->tableNeedExport);

        $result = $this->databaseManager->export(
            $this->tableNeedExport,
            [
                "ObjectKey" => $this->tableNeedExport.".json"
            ],
            [
                 "Fields" => "_id,name",
                 "Query" => '{"name":{"$exists":true}}',
                 "Sort" => '{"name": -1}',
                 "Skip" => 0,
                 "Limit" => 1000
            ]
        );

        $this->assertHasRequestId($result);
        $this->assertObjectHasAttribute("JobId", $result);

        $result = $this->databaseManager->migrateStatus($result->JobId);
        $this->assertHasRequestId($result);
        $this->assertObjectHasAttribute("Status", $result);
    }

    public function testDbDistribution()
    {
        $result = $this->databaseManager->distribution();
        $this->assertHasRequestId($result);
        $this->assertObjectHasAttribute("Collections", $result);
    }
}
