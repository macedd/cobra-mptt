<?php

/**
 * Tests cobra-mptt functionality
 * 
 * @package    Cobra_MPTT
 * @author     Brandon Summers <brandon@evolutionpixels.com>
 * @author     Thiago Fernandes <thiago@internetbudi.com.br>
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */

#require_once 'PHPUnit/Extensions/Database/TestCase.php';
require_once dirname(__FILE__) .'/../cobra-mptt.php';

class Cobra_MPTT_Test extends PHPUnit_Extensions_Database_TestCase {

    protected function getConnection()
    {
        if ( ! isset($this->db) )
        {
            $pdo = new PDO_MpttDb('sqlite::memory:');

            // Configure Cobra Defaults
            Cobra_MPTT::$db = $pdo;
            Cobra_MPTT::$table_name = 'test_cobra_mptt';
            Cobra_MPTT::$schema_create = 'sqlite';

            // Start an item to init the database schema
            Cobra_MPTT::factory();
        }

        return $this->createDefaultDBConnection($this->db, ':memory:');;
    }

    protected function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/dataset.xml');
    }

    /**
     * Tests if a node has children.
     *
     * @test
     * @covers Cobra_MPTT::has_children
     */
    public function test_has_children()
    {
        $root_node = Cobra_MPTT::factory(1);
        
        $this->assertTrue($root_node->has_children());
        
        $no_children_node = Cobra_MPTT::factory(2);
        
        $this->assertFalse($no_children_node->has_children());
    }

    /**
     * Tests if a node is a leaf.
     *
     * @test
     * @covers Cobra_MPTT::is_leaf
     */
    public function test_is_leaf()
    {
        $non_leaf_node = Cobra_MPTT::factory(1);
        
        $this->assertFalse($non_leaf_node->is_leaf());
        
        $leaf_node = Cobra_MPTT::factory(2);
        
        $this->assertTrue($leaf_node->is_leaf());
    }

    /**
     * Tests if a node is a descendant.
     *
     * @test
     * @covers Cobra_MPTT::is_descendant
     */
    public function test_is_descendant()
    {
        $root_node = Cobra_MPTT::factory(1);
        $node_1 = Cobra_MPTT::factory(2);
        $node_2 = Cobra_MPTT::factory(3);
        $node_3 = Cobra_MPTT::factory(4);
        $node_4 = Cobra_MPTT::factory(5);
        
        $this->assertTrue($node_1->is_descendant($root_node));
        $this->assertTrue($node_2->is_descendant($root_node));
        $this->assertTrue($node_3->is_descendant($root_node));
        $this->assertTrue($node_3->is_descendant($node_2));
        $this->assertTrue($node_4->is_descendant($node_2));
        
        $this->assertFalse($node_4->is_descendant($node_1));
        $this->assertFalse($node_2->is_descendant($node_3));
        $this->assertFalse($node_1->is_descendant($node_4));
    }

    /**
     * Tests if a node is a child.
     *
     * @test
     * @covers Cobra_MPTT::is_child
     */
    public function test_is_child()
    {
        $root_node = Cobra_MPTT::factory(1);
        $node_1 = Cobra_MPTT::factory(2);
        $node_2 = Cobra_MPTT::factory(3);
        $node_3 = Cobra_MPTT::factory(4);
        $node_4 = Cobra_MPTT::factory(5);
        
        $this->assertTrue($node_1->is_child($root_node));
        $this->assertTrue($node_2->is_child($root_node));
        $this->assertTrue($node_3->is_child($node_2));
        $this->assertTrue($node_4->is_child($node_3));
        
        $this->assertFalse($node_3->is_child($root_node));
        $this->assertFalse($node_4->is_child($node_2));
    }

    /**
     * Tests if a node is a parent.
     *
     * @test
     * @covers Cobra_MPTT::is_parent
     */
    public function test_is_parent()
    {
        $root_node = Cobra_MPTT::factory(1);
        $node_1 = Cobra_MPTT::factory(2);
        $node_2 = Cobra_MPTT::factory(3);
        $node_3 = Cobra_MPTT::factory(4);
        $node_4 = Cobra_MPTT::factory(5);
        
        $this->assertTrue($root_node->is_parent($node_1));
        $this->assertTrue($root_node->is_parent($node_2));
        $this->assertTrue($node_2->is_parent($node_3));
        $this->assertTrue($node_3->is_parent($node_4));
        
        $this->assertFalse($root_node->is_parent($node_3));
        $this->assertFalse($root_node->is_parent($node_3));
        $this->assertFalse($node_1->is_parent($node_2));
        $this->assertFalse($node_2->is_parent($node_4));
    }

    /**
     * Tests if a node is a sibling.
     *
     * @test
     * @covers Cobra_MPTT::is_sibling
     */
    public function test_is_sibling()
    {
        $node_1 = Cobra_MPTT::factory(2);
        $node_2 = Cobra_MPTT::factory(3);
        $node_3 = Cobra_MPTT::factory(4);
        $node_4 = Cobra_MPTT::factory(5);
        
        $this->assertTrue($node_1->is_sibling($node_2));
        $this->assertTrue($node_2->is_sibling($node_1));
        
        $this->assertFalse($node_3->is_sibling($node_4));
        $this->assertFalse($node_4->is_sibling($node_3));
    }

    /**
     * Tests if a node is a root.
     *
     * @test
     * @covers Cobra_MPTT::is_root
     */
    public function test_is_root()
    {
        $root_node = Cobra_MPTT::factory(1);
        $node_1 = Cobra_MPTT::factory(2);
        $node_2 = Cobra_MPTT::factory(3);
        
        $this->assertTrue($root_node->is_root());
        
        $this->assertFalse($node_1->is_root());
        $this->assertFalse($node_2->is_root());
    }

    /**
     * Tests if a node is one of the parents of a node.
     *
     * @test
     * @covers Cobra_MPTT::is_in_parents
     */
    public function test_is_in_parents()
    {
        $root_node = Cobra_MPTT::factory(1);
        $node_1 = Cobra_MPTT::factory(2);
        $node_2 = Cobra_MPTT::factory(3);
        $node_3 = Cobra_MPTT::factory(4);
        $node_4 = Cobra_MPTT::factory(5);
        
        $this->assertTrue($root_node->is_in_parents($node_1));
        $this->assertTrue($root_node->is_in_parents($node_2));
        $this->assertTrue($node_2->is_in_parents($node_3));
        $this->assertTrue($node_3->is_in_parents($node_4));
        
        $this->assertFalse($node_1->is_in_parents($node_2));
        $this->assertFalse($node_1->is_in_parents($node_4));
    }

    /**
     * Tests if a the creation/moving of a root node.
     *
     * @test
     * @covers Cobra_MPTT::make_root
     */
    public function test_make_root()
    {
        $new_root_node = Cobra_MPTT::factory()->make_root();
        $this->assertTrue($new_root_node->is_root());
        
        $node_1 = Cobra_MPTT::factory(2)->make_root();
        $this->assertTrue($node_1->is_root());
        
        $node_2 = Cobra_MPTT::factory(5)->make_root();
        $this->assertTrue($node_2->is_root());
        
        // Make sure the space was deleted correctly
        $node_3 = Cobra_MPTT::factory(4);
        $this->assertEquals(3, $node_3->left);
        $this->assertEquals(4, $node_3->right);
    }

    /**
     * Tests inserting a node as a first child.
     *
     * @test
     * @covers Cobra_MPTT::insert_as_first_child
     */
    public function test_insert_as_first_child()
    {
        $node_3 = Cobra_MPTT::factory(3);
        $node_4 = Cobra_MPTT::factory(4);
        
        $child_node = Cobra_MPTT::factory()->insert_as_first_child($node_3);
        
        $node_3->reload();
        $node_4->reload();
        
        $this->assertTrue($child_node->is_child($node_3));

        // Make sure the parent_key was set correctly
        $this->assertEquals(3, $child_node->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(5, $child_node->left);
        $this->assertEquals(11, $node_3->right);
        $this->assertEquals(7, $node_4->left);
    }

    /**
     * Tests inserting a node as a last child.
     *
     * @test
     * @covers Cobra_MPTT::insert_as_last_child
     */
    public function test_insert_as_last_child()
    {
        $node_3 = Cobra_MPTT::factory(3);
        $node_4 = Cobra_MPTT::factory(4);
        
        $child_node = Cobra_MPTT::factory()->insert_as_last_child($node_3);
        
        $node_3->reload();
        $node_4->reload();
        
        $this->assertTrue($child_node->is_child($node_3));

        // Make sure the parent_key was set correctly
        $this->assertEquals(3, $child_node->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(9, $child_node->left);
        $this->assertEquals(11, $node_3->right);
    }

    /**
     * Tests inserting a node as a previous sibling.
     *
     * @test
     * @covers Cobra_MPTT::insert_as_prev_sibling
     */
    public function test_insert_as_prev_sibling()
    {
        $node_3 = Cobra_MPTT::factory(3);
        $node_4 = Cobra_MPTT::factory(4);
        
        $new_node = Cobra_MPTT::factory()->insert_as_prev_sibling($node_4);
        
        $node_3->reload();
        $node_4->reload();
        
        $this->assertTrue($new_node->is_child($node_3));
        
        // Make sure the parent_key was set correctly
        $this->assertEquals(3, $new_node->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(5, $new_node->left);
        $this->assertEquals(10, $node_4->right);
        $this->assertEquals(11, $node_3->right);
    }

    /**
     * Tests inserting a node as a previous sibling.
     *
     * @test
     * @covers Cobra_MPTT::insert_as_next_sibling
     */
    public function test_insert_as_next_sibling()
    {
        $node_3 = Cobra_MPTT::factory(3);
        $node_4 = Cobra_MPTT::factory(4);
        
        $new_node = Cobra_MPTT::factory()->insert_as_next_sibling($node_4);
        
        $node_3->reload();
        $node_4->reload();
        
        // Make sure the parent_key was set correctly
        $this->assertEquals(3, $new_node->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(9, $new_node->left);
        $this->assertEquals(8, $node_4->right);
        $this->assertEquals(11, $node_3->right);
    }

    /**
     * Tests deleting a node.
     *
     * @test
     * @covers Cobra_MPTT::delete
     */
    public function test_delete()
    {
        $node_4 = Cobra_MPTT::factory(4);
        $node_4->delete();
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(6, Cobra_MPTT::factory(1)->right);
        $this->assertEquals(5, Cobra_MPTT::factory(3)->right);
    }

    /**
     * Tests moving a node to first child above it's current position.
     *
     * @test
     * @covers Cobra_MPTT::move_to_first_child
     */
    public function test_move_to_first_child_above()
    {
        $node_3 = Cobra_MPTT::factory(3);
        $node_2 = Cobra_MPTT::factory(2);
        
        $node_2->move_to_first_child($node_3);
        
        $node_3->reload();
        $node_4 = Cobra_MPTT::factory(4);

        // Make sure the parent_key was set correctly
        $this->assertEquals(3, $node_2->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(3, $node_2->left);
        $this->assertEquals(4, $node_2->right);
        $this->assertEquals(9, $node_3->right);
        $this->assertEquals(5, $node_4->left);
        $this->assertEquals(8, $node_4->right);
    }

    /**
     * Tests moving a node to first child below it's current position.
     *
     * @test
     * @covers Cobra_MPTT::move_to_first_child
     */
    public function test_move_to_first_child_below()
    {
        $root_node = Cobra_MPTT::factory(1);
        $node_3 = Cobra_MPTT::factory(3);
        
        $node_3->move_to_first_child($root_node);
        
        $node_2 = Cobra_MPTT::factory(2);

        // Make sure the parent_key was set correctly
        $this->assertEquals(1, $node_3->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(2, $node_3->left);
        $this->assertEquals(7, $node_3->right);
        $this->assertEquals(8, $node_2->left);
        $this->assertEquals(9, $node_2->right);
    }

    /**
     * Tests moving a node to last child above it's current position.
     *
     * @test
     * @covers Cobra_MPTT::move_to_last_child
     */
    public function test_move_to_last_child_above()
    {
        $node_5 = Cobra_MPTT::factory(5);
        $node_3 = Cobra_MPTT::factory(3);
        
        $node_5->move_to_last_child($node_3);
        
        $node_3->reload();
        $node_4 = Cobra_MPTT::factory(4);

        // Make sure the parent_key was set correctly
        $this->assertEquals(3, $node_5->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(7, $node_5->left);
        $this->assertEquals(8, $node_5->right);
        $this->assertEquals(9, $node_3->right);
        $this->assertEquals(5, $node_4->left);
        $this->assertEquals(6, $node_4->right);
    }

    /**
     * Tests moving a node to last child below it's current position.
     *
     * @test
     * @covers Cobra_MPTT::move_to_last_child
     */
    public function test_move_to_last_child_below()
    {
        $node_2 = Cobra_MPTT::factory(2);
        $node_3 = Cobra_MPTT::factory(3);
        
        $node_2->move_to_last_child($node_3);
        
        $node_3->reload();
        $node_4 = Cobra_MPTT::factory(4);

        // Make sure the parent_key was set correctly
        $this->assertEquals(3, $node_2->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(7, $node_2->left);
        $this->assertEquals(8, $node_2->right);
        $this->assertEquals(2, $node_3->left);
        $this->assertEquals(9, $node_3->right);
        $this->assertEquals(3, $node_4->left);
        $this->assertEquals(6, $node_4->right);
    }

    /**
     * Tests moving a node to last child above it's current position.
     *
     * @test
     * @covers Cobra_MPTT::move_to_prev_sibling
     */
    public function test_move_to_prev_sibling_above()
    {
        $node_5 = Cobra_MPTT::factory(5);
        $node_3 = Cobra_MPTT::factory(3);
        
        $node_5->move_to_prev_sibling($node_3);
        
        $node_3->reload();
        $node_4 = Cobra_MPTT::factory(4);

        // Make sure the parent_key was set correctly
        $this->assertEquals(1, $node_5->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(4, $node_5->left);
        $this->assertEquals(5, $node_5->right);
        $this->assertEquals(6, $node_3->left);
        $this->assertEquals(9, $node_3->right);
        $this->assertEquals(7, $node_4->left);
        $this->assertEquals(8, $node_4->right);
    }

    /**
     * Tests moving a node to last child below it's current position.
     *
     * @test
     * @covers Cobra_MPTT::move_to_prev_sibling
     */
    public function test_move_to_prev_sibling_below()
    {
        $node_2 = Cobra_MPTT::factory(2);
        $node_5 = Cobra_MPTT::factory(5);
        
        $node_2->move_to_prev_sibling($node_5);
        
        $node_5->reload();
        $node_3 = Cobra_MPTT::factory(3);

        // Make sure the parent_key was set correctly
        $this->assertEquals(4, $node_2->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(4, $node_2->left);
        $this->assertEquals(5, $node_2->right);
        $this->assertEquals(2, $node_3->left);
        $this->assertEquals(9, $node_3->right);
        $this->assertEquals(6, $node_5->left);
        $this->assertEquals(7, $node_5->right);
    }

    /**
     * Tests moving a node to last child above it's current position.
     *
     * @test
     * @covers Cobra_MPTT::move_to_next_sibling
     */
    public function test_move_to_next_sibling_above()
    {
        $node_5 = Cobra_MPTT::factory(5);
        $node_3 = Cobra_MPTT::factory(3);
        
        $node_5->move_to_next_sibling($node_3);
        
        $node_3->reload();
        $node_4 = Cobra_MPTT::factory(4);

        // Make sure the parent_key was set correctly
        $this->assertEquals(1, $node_5->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(8, $node_5->left);
        $this->assertEquals(9, $node_5->right);
        $this->assertEquals(4, $node_3->left);
        $this->assertEquals(7, $node_3->right);
        $this->assertEquals(5, $node_4->left);
        $this->assertEquals(6, $node_4->right);
    }

    /**
     * Tests moving a node to last child below it's current position.
     *
     * @test
     * @covers Cobra_MPTT::move_to_next_sibling
     */
    public function test_move_to_next_sibling_below()
    {
        $node_2 = Cobra_MPTT::factory(2);
        $node_5 = Cobra_MPTT::factory(5);
        
        $node_2->move_to_next_sibling($node_5);
        
        $node_5->reload();
        $node_3 = Cobra_MPTT::factory(3);

        // Make sure the parent_key was set correctly
        $this->assertEquals(4, $node_2->parent_key);
        
        // Make sure the space was adjusted correctly
        $this->assertEquals(6, $node_2->left);
        $this->assertEquals(7, $node_2->right);
        $this->assertEquals(2, $node_3->left);
        $this->assertEquals(9, $node_3->right);
        $this->assertEquals(4, $node_5->left);
        $this->assertEquals(5, $node_5->right);
    }

    /**
     * Provides test data for test_root()
     *
     * @return array
     */
    public function provider_root()
    {
        // $node_id, $scope, $root_id
        return array(
            array(NULL, 1, 1),
            array(5, NULL, 1),
        );
    }

    /**
     * Tests retrieval of a root node.
     *
     * @test
     * @dataProvider provider_root
     * @param int $node_id ID of the node to retrieve the root on.
     * @param int $scope Scope of root to retrieve.
     * @param int $root_id Expected root id.
     * @covers Cobra_MPTT::root
     */
    public function test_root($node_id, $scope, $root_id)
    {
        $root = Cobra_MPTT::factory($node_id)->root($scope);

        // Make sure the parent_key was set correctly
        $this->assertEquals($root_id, $root->primary_key);
    }

    /**
     * root() should throw an exception if an empty object and no scope is used
     *
     * @test
     * @expectedException Exception
     * @covers Cobra_MPTT::root
     */
    public function test_root_exception()
    {
        $root = Cobra_MPTT::factory()->root();
    }

    /**
     * Tests retrieval of all root nodes.
     *
     * @test
     * @covers Cobra_MPTT::roots
     */
    public function test_roots()
    {
        $roots = Cobra_MPTT::factory()->roots;
        //$roots = $roots->as_array();
        
        $this->assertEquals(1, sizeof($roots));
        $this->assertEquals(1, $roots[0]->left);
        $this->assertEquals(10, $roots[0]->right);
    }

    /**
     * Tests fetching child nodes
     *
     * @test
     * @covers Cobra_MPTT::children
     */
    public function test_children()
    {
        $root_node = Cobra_MPTT::factory(1);
        
        $this->assertTrue($root_node->loaded);
        
        $children = $root_node->children();
        
        // Ensure we have 2 children
        $this->assertEquals(2, count($children));

        // Ensure the first child has ID = 2
        $this->assertEquals(2, $children[0]->primary_key);

        // Ensure the second child has ID = 3
        $this->assertEquals(3, $children[1]->primary_key);
    }

}