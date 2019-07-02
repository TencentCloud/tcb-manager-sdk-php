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

    private $collectionName1 = "tcb_test_collection_1";
    private $collectionName2 = "tcb_test_collection_2";
    private $collectionName3 = "tcb_test_collection_3";

    private $collectionAlreadyExists = "tcb_already_exists";
    private $collectionNeverExists = "tcb_never_exists";

    private $collectionForDescribe = "tcb_collection_for_describe";
    private $collectionForList = "tcb_collection_for_list";

    private $collectionNeedUpdate = "tcb_collection_need_update";

    private $collectionNeedImport = "tcb_collection_need_import";
    private $collectionNeedExport = "tcb_collection_need_export";

    private $dataFilePath = __DIR__.DS."data.csv";

    public function recreateTable(string $tableName)
    {
        $this->databaseManager->deleteCollection($tableName);
        $this->databaseManager->createCollection($tableName);
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

    public function testDB()
    {
        $db = $this->databaseManager->db();

        $this->databaseManager->deleteCollection("users");

        $db->createCollection("users");

        $collection = $db->collection("users");

        $countResult = $collection->count();
        $this->assertEquals(0, $countResult["total"]);

        $collection->add(['name' => 'ben']);

        $queryResult = $collection->where([
            'name'=> "ben"
        ])->get();

        $this->assertEquals(1, count($queryResult["data"]));

        $countResult = $collection->count();

        $this->assertEquals(1, $countResult["total"]);
    }

    public function testCreateTable()
    {
        $this->databaseManager->deleteCollection($this->collectionName1);

        $result = $this->databaseManager->createCollection($this->collectionName1);
        $this->assertHasRequestId($result);

        $this->expectException(TCException::class);
        $result = $this->databaseManager->createCollection($this->collectionName1);
        $this->assertHasRequestId($result);
    }

    public function testCreateTableIfNotExists()
    {
        $this->databaseManager->deleteCollection($this->collectionName2);

        $result = $this->databaseManager->createCollectionIfNotExists($this->collectionName2);

        $this->assertHasRequestId($result);
        $this->assertEquals(true, $result->IsCreated);

        $result = $this->databaseManager->createCollectionIfNotExists($this->collectionName2);

        $this->assertHasRequestId($result);
        $this->assertEquals(false, $result->IsCreated);
    }

    public function testDeleteTable()
    {
        $this->databaseManager->createCollectionIfNotExists($this->collectionName3);

        $result = $this->databaseManager->deleteCollection($this->collectionName3);
        $this->assertHasRequestId($result);
        // 可删除不存在的表，并无两样
        $result = $this->databaseManager->deleteCollection($this->collectionName3);
        $this->assertHasRequestId($result);
    }

    public function testCheckTableExistsWithAlreadyExistsTable()
    {
        $this->databaseManager->createCollectionIfNotExists($this->collectionAlreadyExists);

        $result = $this->databaseManager->checkCollectionExists($this->collectionAlreadyExists);
        $this->assertHasRequestId($result);
        $this->assertEquals(true, $result->Exists);
    }

    public function testCheckTableExistsWithNeverExistsTable()
    {
        $result = $this->databaseManager->checkCollectionExists($this->collectionNeverExists);
        $this->assertHasRequestId($result);
        $this->assertEquals(false, $result->Exists);
    }

    public function testDescribeTableAlreadyExists()
    {
        $this->databaseManager->createCollectionIfNotExists($this->collectionForDescribe);

        $result = $this->databaseManager->describeCollection($this->collectionForDescribe);
        $this->assertHasRequestId($result);
        $this->assertEquals(1, $result->IndexNum);
        $this->assertEquals(1, count($result->Indexes));
    }

    public function testListTable()
    {
        $this->databaseManager->createCollectionIfNotExists($this->collectionForList);

        $result = $this->databaseManager->listCollections();

        $this->assertHasRequestId($result);
        $this->assertGreaterThan(0, $result->Collections);
        $this->assertGreaterThan(0, $result->Pager->Total);
        $this->assertEquals(0, $result->Pager->Offset);
        $this->assertEquals(100, $result->Pager->Limit);

        $exists = false;
        foreach ($result->Collections as $collection) {
            if ($collection->CollectionName === $this->collectionForList) {
                $exists = true;
            }
        }
        $this->assertEquals(true, $exists);

        $MgoOffset = 0; $MgoLimit = 20;
        $result = $this->databaseManager->listCollections([
            "MgoOffset" => $MgoOffset,
            "MgoLimit" => $MgoLimit,
        ]);
        $this->assertEquals($MgoOffset, $result->Pager->Offset);
        $this->assertEquals($MgoLimit, $result->Pager->Limit);
    }

    public function testUpdateTable()
    {
        $this->recreateTable($this->collectionNeedUpdate);

        $result = $this->databaseManager->updateCollection($this->collectionNeedUpdate, [
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
            $this->collectionNeedUpdate,
            "index_to_be_delete"
        );
        $this->assertEquals(true, $result->Exists);

        $result = $this->databaseManager->updateCollection($this->collectionNeedUpdate, [
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
            $this->collectionNeedUpdate,
            "index_to_be_delete"
        );
        $this->assertEquals(false, $result->Exists);

        $result = $this->databaseManager->checkIndexExists(
            $this->collectionNeedUpdate,
            "index_b_1"
        );
        $this->assertEquals(true, $result->Exists);
    }

    public function testImport()
    {
        $this->recreateTable($this->collectionNeedImport);

        $result = $this->databaseManager->import(
            $this->collectionNeedImport,
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
        $this->recreateTable($this->collectionNeedExport);

        $result = $this->databaseManager->export(
            $this->collectionNeedExport,
            [
                "ObjectKey" => $this->collectionNeedExport.".json"
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
