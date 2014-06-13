<?php
namespace php_active_record;
require_library('SparqlClient');

class test_sparql_client extends SimpletestUnitBase
{
    const TEST_GRAPH_NAME = "http://php_active_record/testing/";

    function setUp()
    {
        parent::setUp();
        $this->sparql_client = SparqlClient::connection();
    }

    function tearDown()
    {
        $this->sparql_client->delete_graph(self::TEST_GRAPH_NAME);
        unset($this->sparql_client);
        parent::tearDown();
    }

    function testToUnderscore()
    {
        $this->assertEqual(SparqlClient::to_underscore("This is a test"), "this_is_a_test");
        $this->assertEqual(SparqlClient::to_underscore(" This is a test "), "_this_is_a_test_");
        $this->assertEqual(SparqlClient::to_underscore("This is a   test"), "this_is_a___test");
        $this->assertEqual(SparqlClient::to_underscore("this_is_a_test"), "this_is_a_test");
    }

    function testIsURI()
    {
        $this->assertTrue(SparqlClient::is_uri("http://eol.org"));
        $this->assertTrue(SparqlClient::is_uri("http://a"));
        $this->assertFalse(SparqlClient::is_uri("http://"));
        $this->assertFalse(SparqlClient::is_uri("http://eol.org<somethingelse>adf"));

        $this->assertTrue(SparqlClient::is_uri("<http://eol.org>"));
        $this->assertTrue(SparqlClient::is_uri("<http://a>"));
        $this->assertFalse(SparqlClient::is_uri("<http://>"));

        $this->assertTrue(SparqlClient::is_uri("ns:ok"));
        $this->assertTrue(SparqlClient::is_uri("ns:some_attri-bute91"));
        $this->assertTrue(SparqlClient::is_uri("ns:ATest"));
        $this->assertFalse(SparqlClient::is_uri("ns:"));
        $this->assertFalse(SparqlClient::is_uri("ns:asd asdf"));
        $this->assertFalse(SparqlClient::is_uri("ns:att[r]"));
    }

    function testEncloseValue()
    {
        $this->assertEqual(SparqlClient::enclose_value("http://eol.org"), "<http://eol.org>");
        $this->assertEqual(SparqlClient::enclose_value("<http://eol.org>"), "<http://eol.org>");
        $this->assertEqual(SparqlClient::enclose_value("this is a test"), "\"this is a test\"");
        $this->assertEqual(SparqlClient::enclose_value("http://eol. org"), "\"http://eol. org\"");
        $this->assertEqual(SparqlClient::enclose_value("eol:term"), "eol:term");
    }

    function textExpandNamespaces()
    {
        $this->assertEqual(SparqlClient::expand_namespaces("eol:something"), "http://eol.org/schema/terms/something");
        $this->assertEqual(SparqlClient::expand_namespaces("EOL:something"), "http://eol.org/schema/terms/something");
        $this->assertEqual(SparqlClient::expand_namespaces("dwc:something"), "http://rs.tdwg.org/dwc/terms/something");
        $this->assertEqual(SparqlClient::expand_namespaces("DWC:SOMETHING"), "http://rs.tdwg.org/dwc/terms/SOMETHING");
        $this->assertEqual(SparqlClient::expand_namespaces("http://eol.org/schema/terms/something"), "http://eol.org/schema/terms/something");
        $this->assertEqual(SparqlClient::expand_namespaces("<http://eol.org/schema/terms/something>"), "http://eol.org/schema/terms/something");
        $this->assertEqual(SparqlClient::expand_namespaces("this is a test"), "this is a test");
        $this->assertEqual(SparqlClient::expand_namespaces("unknown_namespace:something"), false);
        $this->assertEqual(SparqlClient::expand_namespaces("eol::test"), "eol::test");
    }

    function testConvert()
    {
        $this->assertEqual(SparqlClient::convert("test"), "test");
        $this->assertEqual(SparqlClient::convert("Test"), "Test");
        $this->assertEqual(SparqlClient::convert("Test & test"), "Test &amp; test");
        $this->assertEqual(SparqlClient::convert("Test & <test>"), "Test &amp; &lt;test&gt;");
        $this->assertEqual(SparqlClient::convert("Test\n \\& \r<tes't>"), "Test &amp; &lt;tes&apos;t&gt;");
        $this->assertEqual(SparqlClient::convert("Test \"test\""), "Test &quot;test&quot;");
    }

    function testAppendNamespacesToQuery()
    {
        $this->assertEqual(SparqlClient::append_namespaces_to_query("anything"), "PREFIX anage: <http://anage.org/schema/terms/>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX obis: <http://iobis.org/schema/terms/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX dc: <http://purl.org/dc/terms/>
PREFIX dwct: <http://rs.tdwg.org/dwc/dwctype/>
PREFIX dwc: <http://rs.tdwg.org/dwc/terms/>
PREFIX eolreference: <http://eol.org/schema/reference/>
PREFIX eolterms: <http://eol.org/schema/terms/>
PREFIX eol: <http://eol.org/schema/>
anything");
    }

    function testInsertData()
    {
        $data = array("<http://eol.org/this> <http://eol.org/is> \"a test\"");
        $return = $this->sparql_client->insert_data(array('data' => $data, 'graph_name' => self::TEST_GRAPH_NAME));
        $this->assertTrue($return !== false);
        $results = $this->sparql_client->query("SELECT ?s ?p ?o FROM <". self::TEST_GRAPH_NAME ."> WHERE { ?s ?p ?o }");
        $this->assertEqual(count($results), 1);
        $this->assertEqual($results[0]->s->value, "http://eol.org/this");
        $this->assertEqual($results[0]->p->value, "http://eol.org/is");
        $this->assertEqual($results[0]->o->value, "a test");
    }

    function testInsertMultipleData()
    {
        $data = array(
            "<http://eol.org/this> <http://eol.org/is> \"another test\"",
            "<http://eol.org/and> <http://eol.org/one> \"more\"");
        $return = $this->sparql_client->insert_data(array('data' => $data, 'graph_name' => self::TEST_GRAPH_NAME));
        $this->assertTrue($return !== false);
        $results = $this->sparql_client->query("SELECT ?s ?p ?o FROM <". self::TEST_GRAPH_NAME ."> WHERE { ?s ?p ?o }");
        $this->assertEqual(count($results), 2);
        $this->assertEqual($results[0]->s->value, "http://eol.org/this");
        $this->assertEqual($results[0]->p->value, "http://eol.org/is");
        $this->assertEqual($results[0]->o->value, "another test");
        $this->assertEqual($results[1]->s->value, "http://eol.org/and");
        $this->assertEqual($results[1]->p->value, "http://eol.org/one");
        $this->assertEqual($results[1]->o->value, "more");
    }

    function testDeleteData()
    {
        $data = array(
            "<http://eol.org/this> <http://eol.org/is> \"another test\"",
            "<http://eol.org/and> <http://eol.org/one> \"more\"");
        $return = $this->sparql_client->insert_data(array('data' => $data, 'graph_name' => self::TEST_GRAPH_NAME));
        $this->assertTrue($return !== false);
        $results = $this->sparql_client->query("SELECT ?s ?p ?o FROM <". self::TEST_GRAPH_NAME ."> WHERE { ?s ?p ?o }");
        $this->assertEqual(count($results), 2);
        $this->sparql_client->delete_data(array('data' => $data[0], 'graph_name' => self::TEST_GRAPH_NAME));
        $results = $this->sparql_client->query("SELECT ?s ?p ?o FROM <". self::TEST_GRAPH_NAME ."> WHERE { ?s ?p ?o }");
        $this->assertEqual(count($results), 1);
        $this->assertEqual($results[0]->s->value, "http://eol.org/and");
        $this->assertEqual($results[0]->p->value, "http://eol.org/one");
        $this->assertEqual($results[0]->o->value, "more");
    }

    function testDeleteURI()
    {
        $data = array(
            "<http://eol.org/this> <http://eol.org/is> \"another test\"",
            "<http://eol.org/and> <http://eol.org/one> \"more\"");
        $return = $this->sparql_client->insert_data(array('data' => $data, 'graph_name' => self::TEST_GRAPH_NAME));
        $this->assertTrue($return !== false);
        $results = $this->sparql_client->query("SELECT ?s ?p ?o FROM <". self::TEST_GRAPH_NAME ."> WHERE { ?s ?p ?o }");
        $this->assertEqual(count($results), 2);
        $this->sparql_client->delete_data(array('data' => $data[0], 'graph_name' => self::TEST_GRAPH_NAME));
        $results = $this->sparql_client->query("SELECT ?s ?p ?o FROM <". self::TEST_GRAPH_NAME ."> WHERE { ?s ?p ?o }");
        $this->assertEqual(count($results), 1);
        $this->assertEqual($results[0]->s->value, "http://eol.org/and");
        $this->assertEqual($results[0]->p->value, "http://eol.org/one");
        $this->assertEqual($results[0]->o->value, "more");
    }

}

?>