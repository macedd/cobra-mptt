<?php //defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Modified Preorder Tree Traversal Class.
 * 
 * A powerfull php MPTT implementation.
 *
 * Based on the work of the following guys:
 * 
 * @author Mathew Davies
 * @author Kiall Mac Innes
 * @author Paul Banks
 * @author Brotkin Ivan
 * @author Brandon Summers
 * @author Thiago Fernandes
 */

class Cobra_MPTT {

    /**
     * @access  public
     * @var     string  primary key column name
     */
    public $primary_column = 'id';

    /**
     * @access  public
     * @var     string  left column name
     */
    public $left_column = 'lft';

    /**
     * @access  public
     * @var     string  right column name
     */
    public $right_column = 'rgt';

    /**
     * @access  public
     * @var     string  level column name
     */
    public $level_column = 'lvl';

    /**
     * @access  public
     * @var     string  scope column name
     */
    public $scope_column = 'scope';

    /**
     * @access  public
     * @var     string  parent column name
     */
    public $parent_column = 'parent_id';

    /**
     * @access  public
     * @var     string  tells if instance was loaded from db or not
     */
    public $loaded = false;

    /**
     * @access  protected
     * @var     array  default sorting for queries
     */
    protected $_sorting = array();

    /**
     * @access  protected
     * @var     array  instance data row
     */
    protected $_data = array();

    /**
     * @access  protected
     * @var     array  instance objects cache
     */
    protected $_objects = array();

    /**
     * @access  protected
     * @var     object  instance database connection
     */
    protected $_db = NULL;

    /**
     * @access  public
     * @var     object  default database connection for instances creation
     */
    public static $db = NULL;

    /**
     * @access  protected
     * @var     string  table name user for the mptt instance process
     */
    protected $_table_name = NULL;

    /**
     * @access  public
     * @var     string  default table name for instances creation
     */
    public static $table_name = 'mptt';

    /**
     * @access  public
     * @var     string  db type for auto schema creation.
     */
    public static $schema_create = False;

    /**
     * Init the class database
     *
     * @access  public
     * @param   string  mptt table name in the database
     * @param   array|object   array with pdo parameters or instance of abstracted MpttDb
     * @return  void
     */
    public function __construct($table = null, $db = null)
    {
        if ( ! $this->_sorting)
            $this->_sorting = array($this->left_column, 'ASC');

        $this->db_init($table, $db);
    }

    public function __toString() {
        return var_export($this->_data, true);
    }

    /**
     * Checks if the current node has any children.
     * 
     * @access  public
     * @return  bool
     */
    public function has_children()
    {
        return ($this->size > 2);
    }

    /**
     * Is the current node a leaf node?
     *
     * @access  public
     * @return  bool
     */
    public function is_leaf()
    {
        return ( ! $this->has_children());
    }

    /**
     * Is the current node a descendant of the supplied node.
     *
     * @access  public
     * @param   Cobra_MPTT|int  Cobra_MPTT object or primary key value of target node
     * @return  bool
     */
    public function is_descendant($target)
    {
        if ( ! ($target instanceof $this))
            $target = $this->factory_item($target);
        elseif ( !$target->loaded )
            $target->reload();
        
        return (
                $this->left > $target->left
                AND $this->right < $target->right
                AND $this->scope = $target->scope
            );
    }

    /**
     * Checks if the current node is a direct child of the supplied node.
     * 
     * @access  public
     * @param   Cobra_MPTT|int  Cobra_MPTT object or primary key value of target node
     * @return  bool
     */
    public function is_child($target)
    {
        if ( ! ($target instanceof $this))
            $target = $this->factory_item($target);
        elseif ( !$target->loaded )
            $target->reload();

        return ((int) $this->parent_key === (int) $target->primary_key);
    }

    /**
     * Checks if the current node is a direct parent of a specific node.
     * 
     * @access  public
     * @param   Cobra_MPTT|int  Cobra_MPTT object or primary key value of child node
     * @return  bool
     */
    public function is_parent($target)
    {
        if ( ! ($target instanceof $this))
            $target = $this->factory_item($target);
        elseif ( !$target->loaded )
            $target->reload();

        return ((int) $this->primary_key === (int) $target->parent_key);
    }

    /**
     * Checks if the current node is a sibling of a supplied node.
     * (Both have the same direct parent)
     * 
     * @access  public
     * @param   Cobra_MPTT|int  Cobra_MPTT object or primary key value of target node
     * @return  bool
     */
    public function is_sibling($target)
    {
        if ( ! ($target instanceof $this))
            $target = $this->factory_item($target);
        elseif ( !$target->loaded )
            $target->reload();
        
        if ((int) $this->primary_key === (int) $target->primary_key)
            return FALSE;

        return ((int) $this->parent_key === (int) $target->parent_key);
    }

    /**
     * Checks if the current node is a root node.
     * 
     * @access  public
     * @return  bool
     */
    public function is_root()
    {
        return ($this->left === 1);
    }

    /**
     * Checks if the current node is one of the parents of a specific node.
     * 
     * @access  public
     * @param   int|object  id or object of parent node
     * @return  bool
     */
    public function is_in_parents($target)
    {
        if ( ! ($target instanceof $this))
            $target = $this->factory_item($target);
        elseif ( !$target->loaded )
            $target->reload();

        return $target->is_descendant($this);
    }

    /**
     * Overloaded save method.
     * 
     * @access  public
     * @return  mixed
     */
    public function save()
    {
        if ($this->loaded === TRUE && $this->primary_key)
        {
            $sql = "UPDATE $this->_table_name
                    SET 
                    $this->left_column = $this->left,
                    $this->right_column = $this->right,
                    $this->level_column = $this->level,
                    $this->scope_column = $this->scope,
                    $this->parent_column = $this->parent_key
                    WHERE $this->primary_column = $this->primary_key
                    ";
            $this->_db->exec($sql);
        } elseif ( ! $this->primary_key ) {
            $sql = "INSERT INTO $this->_table_name
                    ($this->left_column, $this->right_column,
                        $this->level_column, $this->scope_column,
                        $this->parent_column)
                    VALUES (
                        $this->left,
                        $this->right,
                        $this->level,
                        $this->scope,
                        $this->parent_key
                        )
                    ";

            $this->_db->exec($sql);
            $this->primary_key = $this->_db->insert_id();
            $this->loaded = true;
        }

        return $this;
    }

    /**
     * Creates a new node as root, or moves a node to root
     *
     * @access  public
     * @param   int       the new scope
     * @return  Cobra_MPTT
     * @throws  Validation_Exception
     */
    public function make_root($scope = NULL)
    {
        // If node already exists, and already root, exit
        if ($this->loaded AND $this->is_root())
            return $this;

        // delete node space first
        if ($this->loaded)
        {
            $this->delete_space($this->left, $this->size);
        }

        if (is_null($scope))
        {
            // Increment next scope
            $scope = self::get_next_scope();
        }
        elseif ( ! $this->scope_available($scope))
        {
            return FALSE;
        }

        $this->scope = $scope;
        $this->level = 1;
        $this->left = 1;
        $this->right = 2;
        $this->parent_key = 0;

        return $this->save();
    }

    /**
     * Sets the parent_column value to the given targets column value. Returns the target Cobra_MPTT object.
     * 
     * @access  protected
     * @param   Cobra_MPTT|int  primary key value or Cobra_MPTT object of target node
     * @param   string        name of the targets nodes column to use
     * @return  Cobra_MPTT
     */
    protected function parent_from($target, $column = NULL)
    {
        if ( ! ($target instanceof $this))
            $target = $this->factory_item($target);
        elseif ( !$target->loaded )
            $target->reload();

        if ($column === NULL)
        {
            $column = $target->primary_column;
        }

        if ($target->loaded)
        {
            $this->parent_key = $target->_data[$column];
        }
        else
        {
            $this->parent_key = 0;
        }

        return $target;
    }

    /**
     * Inserts a new node as the first child of the target node.
     * 
     * @access  public
     * @param   Cobra_MPTT|int  primary key value or Cobra_MPTT object of target node
     * @return  Cobra_MPTT
     */
    public function insert_as_first_child($target)
    {
        $target = $this->parent_from($target);
        return $this->insert($target, $this->left_column, 1, 1);
    }
    
    /**
     * Inserts a new node as the last child of the target node.
     * 
     * @access  public
     * @param   Cobra_MPTT|int  primary key value or Cobra_MPTT object of target node
     * @return  Cobra_MPTT
     */
    public function insert_as_last_child($target)
    {
        $target = $this->parent_from($target, $this->primary_column);
        return $this->insert($target, $this->right_column, 0, 1);
    }
    
    /**
     * Inserts a new node as a previous sibling of the target node.
     * 
     * @access  public
     * @param   Cobra_MPTT|int  primary key value or Cobra_MPTT object of target node
     * @return  Cobra_MPTT
     */
    public function insert_as_prev_sibling($target)
    {
        $target = $this->parent_from($target, $this->parent_column);
        return $this->insert($target, $this->left_column, 0, 0);
    }
    
    /**
     * Inserts a new node as the next sibling of the target node.
     * 
     * @access  public
     * @param   Cobra_MPTT|int  primary key value or Cobra_MPTT object of target node
     * @return  Cobra_MPTT
     */
    public function insert_as_next_sibling($target)
    {
        $target = $this->parent_from($target, $this->parent_column);
        return $this->insert($target, $this->right_column, 1, 0);
    }
    
    /**
     * Insert the object
     *
     * @access  protected
     * @param   Cobra_MPTT|int  primary key value or Cobra_MPTT object of target node.
     * @param   string        target object property to take new left value from
     * @param   int           offset for left value
     * @param   int           offset for level value
     * @return  Cobra_MPTT
     * @throws  Validation_Exception
     */
    protected function insert($target, $copy_left_from, $left_offset, $level_offset)
    {
        // Insert should only work on new nodes.. if its already it the tree it needs to be moved!
        if ($this->loaded)
            return FALSE;
         
         
        if ( ! $target instanceof $this)
        {
            $target = $this->factory_item($target);
         
            if ( ! $target->loaded)
            {
                return FALSE;
            }
        }
        else
        {
            $target->reload();
        }
         
        $this->lock();

        $this->left = $target->_data[$copy_left_from] + $left_offset;
        $this->right = $this->left + 1;
        $this->level = $target->level + $level_offset;
        $this->scope = $target->scope;

        $this->create_space($this->left);
         
        try
        {
            $this->save();
        }
        catch (PDOException $e)
        {
            // We had a problem saving, make sure we clean up the tree
            $this->delete_space($this->left);
            $this->unlock();
            throw $e;
        }
         
        $this->unlock();

        $target->loaded = False;
         
        return $this;
    }

    /**
     * Deletes the current node and all descendants.
     * 
     * @access  public
     * @return  void
     */
    public function delete()
    {
        if ( !$this->loaded )
            $this->reload();

        $this->lock();

        try
        {
            $sql = "DELETE FROM $this->_table_name
                    WHERE
                        $this->left_column >= $this->left
                    AND $this->right_column <= $this->right
                    AND $this->scope_column = $this->scope
                    ";
            $this->_db->exec($sql);

            $this->delete_space($this->left, $this->size);
        }
        catch (PDOException $e)
        {
            $this->unlock();
            throw $e;
        }

        $this->unlock();
    }
    
    /**
     * Move to First Child
     *
     * Moves the current node to the first child of the target node.
     *
     * @param Model_MPTT|integer $target target node id or Model_MPTT object.
     * @return Model_MPTT
     */
    public function move_to_first_child($target)
    {
        $target = $this->parent_from($target, $this->primary_column);
        return $this->move($target, TRUE, 1, 1, TRUE);
    }
    
    /**
     * Move to Last Child
     *
     * Moves the current node to the last child of the target node.
     *
     * @param Model_MPTT|integer $target target node id or Model_MPTT object.
     * @return Model_MPTT
     */
    public function move_to_last_child($target)
    {
        $target = $this->parent_from($target, $this->primary_column);
        return $this->move($target, FALSE, 0, 1, TRUE);
    }
    
    /**
     * Move to Previous Sibling.
     *
     * Moves the current node to the previous sibling of the target node.
     *
     * @param Model_MPTT|integer $target target node id or Model_MPTT object.
     * @return Model_MPTT
     */
    public function move_to_prev_sibling($target)
    {
        $target = $this->parent_from($target, $this->parent_column);
        return $this->move($target, TRUE, 0, 0, FALSE);
    }
    
    /**
     * Move to Next Sibling.
     *
     * Moves the current node to the next sibling of the target node.
     *
     * @param Model_MPTT|integer $target target node id or Model_MPTT object.
     * @return Model_MPTT
     */
    public function move_to_next_sibling($target)
    {
        $target = $this->parent_from($target, $this->parent_column);
        return $this->move($target, FALSE, 1, 0, FALSE);
    }
    
    /**
     * Move
     *
     * @param Cobra_MPTT|integer $target target node id or Cobra_MPTT object.
     * @param bool $left_column use the left column or right column from target
     * @param integer $left_offset left value for the new node position.
     * @param integer $level_offset level
     * @param bool allow this movement to be allowed on the root node
     */
    protected function move($target, $left_column, $left_offset, $level_offset, $allow_root_target)
    {
        if ( ! $this->loaded)
            return FALSE;
      
        // store the changed parent id before reload
        $parent_id = $this->parent_key;

        // Make sure we have the most upto date version of this AFTER we lock
        $this->lock();
        $this->reload();
         
        // Catch any database or other excpetions and unlock
        try
        {
            if ( ! $target instanceof $this)
            {
                $target = $this->factory_item($target);
            }
            else
            {
                $target->reload();
            }

            // Stop $this being moved into a descendant or itself or disallow if target is root
            if ($target->is_descendant($this)
                OR $this->primary_key === $target->primary_key
                OR ($allow_root_target === FALSE AND $target->is_root()))
            {
                $this->unlock();
                return FALSE;
            }

            if ($level_offset > 0)
            {
                // We're moving to a child node so add 1 to left offset.
                $left_offset = ($left_column === TRUE) ? ($target->left + 1) : ($target->right + $left_offset);
            }
            else
            {
                $left_offset = ($left_column === TRUE) ? $target->left : ($target->right + $left_offset);
            }
            
            $level_offset = $target->level - $this->level + $level_offset;
            $size = $this->size;

            $this->create_space($left_offset, $size);

            $this->reload();

            $offset = ($left_offset - $this->left);
            
            $sql = "UPDATE $this->_table_name
                    SET 
                    $this->left_column = ($this->left_column + $offset),
                    $this->right_column = ($this->right_column + $offset),
                    $this->level_column = ($this->level_column + $level_offset),
                    $this->scope_column = $target->scope
                    WHERE
                        $this->left_column >= $this->left
                    AND $this->right_column <= $this->right
                    AND $this->scope_column = $this->scope
                    ";
            $this->_db->exec($sql);

            $this->delete_space($this->left, $size);
        }
        catch (PDOException $e)
        {
            // Unlock table and re-throw exception
            $this->unlock();
            throw $e;
        }

        // all went well so save the parent_id if changed
        if ($parent_id != $this->parent_key)
        {
            $this->reload();
            $this->parent_key = $parent_id;
            $this->save();
        }

        $this->unlock();

        $this->reload();
        $target->loaded = False;

        return $this;
    }

    /**
     * Returns the next available value for scope.
     *
     * @access  protected
     * @return  int
     **/
    protected function get_next_scope()
    {
        $sql = "SELECT IFNULL(MAX($this->scope_column), 0) as scope
                FROM $this->_table_name
                ";

        foreach ($this->_db->query($sql) as $row)
            $scope = $row;

        if ($scope AND intval($scope['scope']) > 0)
            return intval($scope['scope']) + 1;

        return 1;
    }

    /**
     * Returns the root node of the current object instance.
     * 
     * @access  public
     * @param   int             scope
     * @return  Cobra_MPTT|FALSE
     */
    public function root($scope = NULL)
    {
        if (is_null($scope) AND $this->loaded)
        {
            $scope = $this->scope;
        }
        elseif (is_null($scope) AND ! $this->loaded)
        {
            throw new Exception('root() must be called on a loaded Cobra_MPTT object instance.');
        }
        
        $sql = "SELECT * FROM $this->_table_name
                WHERE 
                    $this->left_column = 1
                AND $this->scope_column = $scope
                ORDER BY ". $this->_sorting[0] ." ". $this->_sorting[1] ."
                ";
        $result = $this->_db->query($sql);
        return $this->factory_set($result)[0];
    }

    /**
     * Returns all root node's
     * 
     * @access  public
     * @return  Cobra_MPTT
     */
    public function roots()
    {
        $sql = "SELECT * FROM $this->_table_name
                WHERE 
                    $this->left_column = 1
                ORDER BY ". $this->_sorting[0] ." ". $this->_sorting[1] ."
                ";
        $result = $this->_db->query($sql);
        return $this->factory_set($result);
    }

    /**
     * Returns the parent node of the current node
     * 
     * @access  public
     * @return  Cobra_MPTT
     */
    public function parent()
    {
        if ( !$this->loaded )
            $this->reload();

        if ($this->is_root())
            return NULL;

        if ( ! in_array('parent', $this->_objects) )
        {
            $sql = "SELECT * FROM $this->_table_name
                    WHERE 
                    $this->primary_column = $this->parent_key
                    ORDER BY ". $this->_sorting[0] ." ". $this->_sorting[1] ."
                ";

            $result = $this->_db->query($sql);

            $this->_objects['parent'] = $this->factory_set($result)[0];
        }

        return $this->_objects['parent'];
    }

    /**
     * Returns all of the current nodes parents.
     * 
     * @access  public
     * @param   bool      include root node
     * @param   bool      include current node
     * @param   string    direction to order the left column by
     * @param   bool      retrieve the direct parent only
     * @return  Cobra_MPTT
     */
    public function parents($root = TRUE, $with_self = FALSE, $direction = 'ASC', $direct_parent_only = FALSE, $limit = FALSE)
    {
        $object_id = "parents_$root-$with_self-$direction-$direct_parent_only";
        if (! in_array($object_id, $this->_objects) )
        {
            if ( !$this->loaded )
                $this->reload();

            $suffix = $with_self ? '=' : '';

            $sql = "SELECT * FROM $this->_table_name
                    WHERE 
                        $this->left_column <$suffix $this->left
                    AND $this->right_column >$suffix $this->right
                    AND $this->scope_column = $this->scope
                    ";

            if ( ! $root)
            {
                $sql .= "AND $this->left_column != 1 \n";
            }

            if ($direct_parent_only)
            {
                $sql .= "AND $this->level_column = ($this->level - 1)
                        LIMIT 1 \n";
            }
            elseif ($limit)
            {
                $sql .= "LIMIT ". (int) $limit ." \n";
            }

            $sql .= "ORDER BY $this->left_column $direction";
            

            $result = $this->_db->query($sql);
            $this->_objects[$object_id] = $this->factory_set($result);
        }

        return $this->_objects[$object_id];
    }

    /**
     * Returns direct children of the current node.
     * 
     * @access  public
     * @param   bool     include the current node
     * @param   string   direction to order the left column by
     * @param   int      number of children to get
     * @return  Cobra_MPTT
     */
    public function children($self = FALSE, $direction = 'ASC', $limit = FALSE)
    {
        return $this->descendants($self, $direction, TRUE, FALSE, $limit);
    }

    /**
     * Returns a full hierarchical tree, with or without scope checking.
     * 
     * @access  public
     * @param   bool    only retrieve nodes with specified scope
     * @return  object
     */
    public function fulltree($scope = NULL)
    {
        $object_id = "fulltree_$scope";
        if (! in_array($object_id, $this->_objects))
        {
            $sql = "SELECT * FROM $this->_table_name
            ";

            if ( ! is_null($scope))
            {
                $sql .= "AND $this->scope_column = $scope
                         ORDER BY ". $this->_sorting[0] ." ". $this->_sorting[1] ."
                        ";
            }
            else
            {
                $sql .= "ORDER BY
                            $this->scope_column ASC,
                            $this->left_column ASC
                            ";
            }

            $result = $this->_db->query($sql);
            $this->_objects[$object_id] = $this->factory_set($result);
        }

        return $this->_objects[$object_id];
    }
    
    /**
     * Returns the siblings of the current node
     *
     * @access  public
     * @param   bool  include the current node
     * @param   string  direction to order the left column by
     * @return  Cobra_MPTT
     */
    public function siblings($self = FALSE, $direction = 'ASC')
    {
        $object_id = "siblings_$self-$direction";
        if (! in_array($object_id, $this->_objects))
        {
            if ( !$this->loaded )
                $this->reload();

            $parent = $this->parent;

            $sql = "SELECT * FROM $this->_table_name
                    WHERE
                        $this->left_column > $parent->left
                    AND $this->right_column < $parent->right
                    AND $this->scope_column = $this->scope
                    AND $this->level_column = $this->level
                    ";
             
            if ( ! $self)
            {
                $sql .= "AND $this->primary_column <> $this->primary_key \n";
            }
             
            $sql .= "ORDER BY $this->left_column $direction";

            $result = $this->_db->query($sql);
            $this->_objects[$object_id] = $this->_db_object($result);
        }

        return $this->_objects[$object_id];
    }

    /**
     * Returns the leaves of the current node.
     * 
     * @access  public
     * @param   bool  include the current node
     * @param   string  direction to order the left column by
     * @return  Cobra_MPTT
     */
    public function leaves($self = FALSE, $direction = 'ASC')
    {
        return $this->descendants($self, $direction, TRUE, TRUE);
    }
    
    /**
     * Returns the descendants of the current node.
     *
     * @access  public
     * @param   bool      include the current node
     * @param   string    direction to order the left column by.
     * @param   bool      include direct children only
     * @param   bool      include leaves only
     * @param   int       number of results to get
     * @return  Cobra_MPTT
     */
    public function descendants($self = FALSE, $direction = 'ASC', $direct_children_only = FALSE, $leaves_only = FALSE, $limit = FALSE)
    {
        $object_id = "descendants_$self-$direction-$direct_children_only-$leaves_only-$limit";
        if (! in_array($object_id, $this->_objects))
        {
            if ( !$this->loaded )
                $this->reload();

            $left_operator = $self ? '>=' : '>';
            $right_operator = $self ? '<=' : '<';
            
            $sql = "SELECT * FROM $this->_table_name
                    WHERE
                        $this->left_column $left_operator $this->left
                    AND $this->right_column $right_operator $this->right
                    AND $this->scope_column = $this->scope
                    ";

            if ($direct_children_only)
            {
                if ($self)
                {
                    $sql .= "AND (
                                   $this->level_column = $this->level
                                OR $this->level_column = ($this->level + 1)
                            )\n";
                }
                else
                {
                    $sql .= "AND $this->level_column = ($this->level + 1) \n";
                }
            }
            
            if ($leaves_only)
            {
                $sql .= "AND $this->right_column = ($this->left_column + 1) \n";
            }
            
            if ($limit !== FALSE)
            {
                $sql .= "LIMIT $limit \n";
            }
            
            $sql .= "ORDER BY $this->left_column $direction \n";
                    
            $result = $this->_db->query($sql);
            $this->_objects[$object_id] = $this->factory_set($result);
        }
        return $this->_objects[$object_id];
    }

    /**
     * Adds space to the tree for adding or inserting nodes.
     * 
     * @access  protected
     * @param   int    start position
     * @param   int    size of the gap to add [optional]
     * @return  void
     */
    protected function create_space($start, $size = 2)
    {
        $sql = "UPDATE $this->_table_name
                SET
                    $this->left_column = ($this->left_column + $size)
                WHERE
                    $this->left_column >= $start
                AND $this->scope_column = $this->scope
                ";
        $this->_db->exec($sql);

        $sql = "UPDATE $this->_table_name
                SET
                    $this->right_column = ($this->right_column + $size)
                WHERE
                    $this->right_column >= $start
                AND $this->scope_column = $this->scope
                ";
        $this->_db->exec($sql);
    }

    /**
     * Removes space from the tree after deleting or moving nodes.
     * 
     * @access  protected
     * @param   int    start position
     * @param   int    size of the gap to remove [optional]
     * @return  void
     */
    protected function delete_space($start, $size = 2)
    {
        $sql = "UPDATE $this->_table_name
                SET
                    $this->left_column = ($this->left_column - $size)
                WHERE
                    $this->left_column >= $start
                AND $this->scope_column = $this->scope
                ";
        $this->_db->exec($sql);

        $sql = "UPDATE $this->_table_name
                SET
                    $this->right_column = ($this->right_column - $size)
                WHERE
                    $this->right_column >= $start
                AND $this->scope_column = $this->scope
                ";
        $this->_db->exec($sql);
    }

    /**
     * Locks the current table.
     * 
     * @access  protected
     * @return  void
     */
    protected function lock()
    {
        $sql = "LOCK TABLE $this->_table_name WRITE";
        try {
            $this->_db->exec($sql);
        } catch ( PDOException $e ) {
            if ( !strpos($e->getMessage(), 'syntax error') )
                throw $e;
            // no lock support, continue
        }
    }

    /**
     * Unlocks the current table.
     * 
     * @access  protected
     * @return  void
     */
    protected function unlock()
    {
        $sql = "UNLOCK TABLES";
        try {
            $this->_db->exec($sql);
        } catch ( PDOException $e ) {
            if ( !strpos($e->getMessage(), 'syntax error') )
                throw $e;
            // no lock support, continue
        }
    }

    /**
     * Checks if the supplied scope is available.
     * 
     * @access  protected
     * @param   int        scope to check availability of
     * @return  bool
     */
    protected function scope_available($scope)
    {
        $sql = "SELECT COUNT(1) total FROM $this->_table_name
                WHERE $this->scope_column = $scope";
        $result = $this->_db->query($sql);

        return (bool) ($result[0]['total'] == 0);
    }

    /**
     * Rebuilds the tree using the parent_column. Order of the tree is not guaranteed
     * to be consistent with structure prior to reconstruction. This method will reduce the
     * tree structure to eliminating any holes. If you have a child node that is outside of
     * the left/right constraints it will not be moved under the root.
     *
     * @access  public
     * @param   int       left    Starting value for left branch
     * @param   Cobra_MPTT  target  Target node to use as root
     * @return  int
     */
    public function rebuild_tree($left = 1, $target = NULL)
    {
        // check if using target or self as root and load if not loaded
        if (is_null($target) AND ! $this->loaded)
        {
            return FALSE;
        }
        elseif (is_null($target))
        {
            $target = $this;
        }

        if ( ! $target->loaded)
        {
            $target = $this->factory_item($target);
        }

        // Use the current node left value for entire tree
        if (is_null($left))
        {
            $left = $target->left;
        }

        $target->lock();
        $right = $left + 1;
        $children = $target->children();

        foreach ($children as $child)
        {
            $right = $child->rebuild_tree($right);
        }

        $target->left = $left;
        $target->right = $right;
        $target->save();
        $target->unlock();

        return $right + 1;
    }

    /**
     * Magic get function, maps field names to class functions.
     * 
     * @access  public
     * @param   string  name of the field to get
     * @return  mixed
     */
    public function __get($column)
    {
        switch ($column)
        {
            case 'parent':
                return $this->parent();
            case 'parents':
                return $this->parents();
            case 'children':
                return $this->children();
            case 'first_child':
                return $this->children(FALSE, 'ASC', 1);
            case 'last_child':
                return $this->children(FALSE, 'DESC', 1);
            case 'siblings':
                return $this->siblings();
            case 'root':
                return $this->root();
            case 'roots':
                return $this->roots();
            case 'leaves':
                return $this->leaves();
            case 'descendants':
                return $this->descendants();
            case 'fulltree':
                return $this->fulltree();
            case 'size':
                return $this->right - $this->left + 1;
            case 'count':
                return ($this->size() - 2) / 2;
            case 'left':
                return (INT) @$this->_data[$this->left_column];
            case 'right':
                return (INT) @$this->_data[$this->right_column];
            case 'scope':
                return (INT) @$this->_data[$this->scope_column];
            case 'level':
                return (INT) @$this->_data[$this->level_column];
            case 'primary_key':
                return (int) @$this->_data[$this->primary_column];
            case 'parent_key':
                return (int) @$this->_data[$this->parent_column];
            default:
                return $this->$column;
        }
    }
    
    public function __set($column, $value)
    {
        switch ($column)
        {
            case 'left':
                $this->_data[$this->left_column] = $value;
                break;
            case 'right':
                $this->_data[$this->right_column] = $value;
                break;
            case 'scope':
                $this->_data[$this->scope_column] = $value;
                break;
            case 'level':
                $this->_data[$this->level_column] = $value;
                break;
            case 'primary_key':
                $this->_data[$this->primary_column] = $value;
                break;
            case 'parent_key':
                $this->_data[$this->parent_column] = $value;
                break;
            default:
                $this->$column = $value;
        }
    }

    public function reload()
    {
        if ( (int) $this->primary_key > 0 )
            return $this->factory_item( $this->primary_key, true );
        else
            return false;
    }


    /**
     * Converts a database resultset into MPTT objects
     * 
     * @access  public
     * @param   array  result of a query call
     * @return  array  model instances
     */
    public function factory_set( $resultset, $self = False )
    {
        $set = array();

        foreach ($resultset as $row) {
            $obj = $this->factory_item($row, $self);
            $obj->loaded = True;
            $set[] = $obj;
        }

        return $set;
    }

    /**
     * Generate Mptt model objects
     *
     * @access  public
     * @param   array|int  a db row or primary_key value
     * @param   bool       load item from database
     * @return  mixed
     */
    public function factory_item( $item, $self = False )
    {
        // Load the item from database
        if ( is_numeric($item) || $item instanceof self )
        {
            if ($item instanceof self)
                $id = $item->primary_key;
            else
                $id = $item;

            $sql = "SELECT * FROM $this->_table_name
                    WHERE
                        $this->primary_column = $id
                    ";

            $result = $this->_db->query($sql);
            return $this->factory_set($result, $self)[0];
        }

        // Convert the database item (assoc array) into a Mptt model
        
        if ($self)
            $instance = $this;
        else
            $instance = clone $this;

        $instance->_data = array();
        $instance->_objects = array();

        $columns = array('primary_column','left_column', 'right_column', 'level_column', 'scope_column', 'parent_column');
        foreach ($columns as $col)
        {
            if (array_key_exists($this->$col, $item))
                $instance->_data[$this->$col] = $item[$this->$col];
            else
                $instance->_data[$this->$col] = null;
        }

        return $instance;
    }

    public static function factory( $item = null ) {
        $instance = new self();

        if ($item)
            $instance = $instance->factory_item($item, True);

        return $instance;
    }

    /*
    * Setup the database instance
    */
    public function db_init($table = null, $db = null, $schema = False)
    {
        // Table Name
        if ( $table )
            $this->_table_name = $table;
        elseif ( self::$table_name )
            $this->_table_name = self::$table_name;

        // Database Object
        if ( is_array($db) ) // PDO Dsn
            $this->_db = new PDO_MpttDb($db[0], $db[1], $db[2]);
        elseif ( $db instanceof Cobra_MpttDb ) // Custom Db Class
            $this->_db = $db;
        elseif ( self::$db instanceof Cobra_MpttDb ) // Custom Db Class
            $this->_db = self::$db;
        else
            throw new Exception("Cobra_MpttDb required", 1);

        // Table schema creation
        if ( $schema || is_string(self::$schema_create) )
        {
            if (is_string($schema))
                self::$schema_create = $schema;

            $this->db_schema();
        }
    }

    public function db_schema()
    {
        switch (self::$schema_create) {
            case 'mysql':
                $sql = "CREATE TABLE `$this->_table_name` (
                            `$this->primary_column` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            `$this->parent_column` INT UNSIGNED NULL,
                            `$this->left_column` INT UNSIGNED NOT NULL,
                            `$this->right_column` INT UNSIGNED NOT NULL,
                            `$this->level_column` INT UNSIGNED NOT NULL,
                            `$this->scope_column` INT UNSIGNED NOT NULL
                            ) ENGINE=INNODB;";
                break;
            case 'sqlite':
                $sql = "CREATE TABLE `$this->_table_name` (
                            `$this->primary_column` INTEGER PRIMARY KEY AUTOINCREMENT,
                            `$this->parent_column` INT NULL,
                            `$this->left_column` INT NOT NULL,
                            `$this->right_column` INT NOT NULL,
                            `$this->level_column` INT NOT NULL,
                            `$this->scope_column` INT NOT NULL
                            );";
                break;
        }
        
        $this->_db->exec($sql);
        self::$schema_create = True;
    }

} // End PDO MPTT

interface Cobra_MpttDb {
    public function exec($sql);
    public function query($sql);
    public function insert_id();
}

class PDO_MpttDb extends PDO implements Cobra_MpttDb
{
    public function __construct($dsn, $username = null, $password = null, $driver_options = null)
    {
        parent::__construct($dsn, $username, $password, $driver_options);
        $this->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    }

    public function exec($sql)
    {
        return parent::exec($sql);
    }

    public function query($sql)
    {
        return parent::query($sql, PDO::FETCH_ASSOC);
    }

    public function insert_id()
    {
        return self::lastInsertId();
    }
}
