<?php

require __DIR__ . "/../../vendor/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class DatabaseTest extends \PHPUnit\Framework\TestCase 
{
    public function setUp() {
        // create the first request to check we can connect, can be added to
        // the mocks for any test that wants it
		$couchdb1 = '{"couchdb":"Welcome","uuid":"fce3d5aabfe189c988273c0ffa8d375b","version":"1.6.0","vendor":{"name":"Ubuntu","version":"15.10"}}';
		$this->db_response = new Response(200, [], $couchdb1);

        // offer a use_response for when selecting this database
        $egdb1 = '{"db_name":"egdb","update_seq":"0-g1AAAABXeJzLYWBgYMpgTmEQTM4vTc5ISXLIyU9OzMnILy7JAUklMiTV____PyuRAY-iPBYgydAApP5D1GYBAJmvHGw","sizes":{"file":8488,"external":0,"active":0},"purge_seq":0,"other":{"data_size":0},"doc_del_count":0,"doc_count":0,"disk_size":8488,"disk_format_version":6,"data_size":0,"compact_running":false,"instance_start_time":"0"}';
        $this->use_response = new Response(200, [], $egdb1);
    }

    public function testGetAllDocs() {
        $docs = '{"total_rows":2,"offset":0,"rows":[
{"id":"95613816b3a7490727388ebb470001a6","key":"95613816b3a7490727388ebb470001a6","value":{"rev":"1-71e39cb1ac06a5974a16c72b26969009"},"doc":{"_id":"95613816b3a7490727388ebb470001a6","_rev":"1-71e39cb1ac06a5974a16c72b26969009","sound":"squeak"}},
{"id":"95613816b3a7490727388ebb4700165a","key":"95613816b3a7490727388ebb4700165a","value":{"rev":"1-1ed93c4b346f531c5e7d4d69b755ee71"},"doc":{"_id":"95613816b3a7490727388ebb4700165a","_rev":"1-1ed93c4b346f531c5e7d4d69b755ee71","noise":"pop"}}
]}';
        $docs_response = new Response(200, [], $docs);

		$mock = new MockHandler([ $this->db_response, $this->use_response, $docs_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
        $database = $server->useDB(["name" => "egdb"]);
        $docs = $database->getAllDocs();

        $this->assertInternalType('array', $docs);
        $this->assertInstanceOf('\PHPCouchDB\Document', $docs[0]);
    }

    public function testGetAllDocsWithNoDocs() {
        $docs = '{"total_rows":0,"offset":0,"rows":[

]}';
        $docs_response = new Response(200, [], $docs);

		$mock = new MockHandler([ $this->db_response, $this->use_response, $docs_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
        $database = $server->useDB(["name" => "egdb"]);
        $docs = $database->getAllDocs();

        $this->assertInternalType('array', $docs);
        $this->assertEmpty($docs);
    }

    public function testCreateWithID() {
        $create = '{"ok":true,"id":"abcde12345","rev":"1-928ec193918889e122e7ad45cfd88e47"}';
        $create_response = new Response(201, [], $create);
        $fetch = '{"_id":"abcde12345","_rev":"1-928ec193918889e122e7ad45cfd88e47","noise":"howl"}';
        $fetch_response = new Response(200, [], $fetch);

		$mock = new MockHandler([ $this->db_response, $this->use_response, $create_response, $fetch_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
        $database = $server->useDB(["name" => "egdb"]);
        $doc = $database->create(["noise" => "howl", "id" => "abcde12345"]);

        $this->assertInstanceOf('PHPCouchDB\Document', $doc);
        $this->assertObjectHasAttribute('id', $doc);
        $this->assertEquals("abcde12345", $doc->id);
    }

    public function testCreateWithoutID() {
        $create = '{"ok":true,"id":"95613816b3a7490727388ebb47002c0f","rev":"1-928ec193918889e122e7ad45cfd88e47"}';
        $create_response = new Response(201, [], $create);
        $fetch = '{"_id":"95613816b3a7490727388ebb47002c0f","_rev":"1-928ec193918889e122e7ad45cfd88e47","noise":"howl"}';
        $fetch_response = new Response(200, [], $fetch);

		$mock = new MockHandler([ $this->db_response, $this->use_response, $create_response, $fetch_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
        $database = $server->useDB(["name" => "egdb"]);
        $doc = $database->create(["noise" => "howl"]);

        $this->assertInstanceOf('PHPCouchDB\Document', $doc);
        $this->assertObjectHasAttribute('id', $doc);
    }

    public function testGetDocById() {
        // create the doc with the id (which also triggers a fetch), then fetch it
        $create = '{"ok":true,"id":"95613816b3a7490727388ebb47002c0f","rev":"1-928ec193918889e122e7ad45cfd88e47"}';
        $create_response = new Response(201, [], $create);
        $fetch = '{"_id":"95613816b3a7490727388ebb47002c0f","_rev":"1-928ec193918889e122e7ad45cfd88e47","noise":"howl"}';
        $fetch_response = new Response(200, [], $fetch);

		$mock = new MockHandler([ $this->db_response, $this->use_response, $create_response, $fetch_response, $fetch_response ]);
		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
        $database = $server->useDB(["name" => "egdb"]);
        $doc = $database->create(["noise" => "crackle"]);

        $fetched_doc = $database->getDocById($doc->id);

        $this->assertInstanceOf('PHPCouchDB\Document', $fetched_doc);
        $this->assertObjectHasAttribute('id', $fetched_doc);
    }

    public function testGetName() {
		$mock = new MockHandler([ $this->db_response, $this->use_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
        $database = $server->useDB(["name" => "egdb"]);

        $this->assertInternalType('string', $database->getName());
    }

    public function testGetClient() {
		$mock = new MockHandler([ $this->db_response, $this->use_response ]);

		$handler = HandlerStack::create($mock);
		$client = new Client(['handler' => $handler]);

		// userland code starts
		$server = new \PHPCouchDB\Server(["client" => $client]);
        $database = $server->useDB(["name" => "egdb"]);

        $this->assertInstanceOf('\GuzzleHttp\ClientInterface', $database->getClient());
    }
}
