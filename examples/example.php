<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <title>Cobra MPTT example</title>
        <meta http-equiv="content-type" content="text/html;charset=UTF-8">
        <meta http-equiv="Content-Script-Type" content="text/javascript">
        <meta http-equiv="Content-Style-Type" content="text/css">
    </head>
    <body>
        <h2>Cobra MPTT database example</h2>
        <p></p>

        <?php
        function table_show($pdo, $where = false) {
            $sql = 'SELECT * FROM mptt';
            if ($where)
                $sql .= " WHERE $where";
            $result = $pdo->query($sql);

            echo "<pre>Table Rows:\n";
            foreach ($result as $row)
                echo json_encode($row) ."\n";
            echo '</pre>';
        }

        require '../cobra-mptt.php';

        $pdo = new PDO_MpttDb('sqlite::memory:');

        // Prepare the class (configuration)
        Cobra_MPTT::$db = $pdo;
        Cobra_MPTT::$schema_create = 'sqlite';

        // add 'Food' as a topmost node (id=1)
        $food = Cobra_MPTT::factory()->make_root();

        // 'Fruit' and 'Grain' are direct descendants of 'Food'
        $fruit = Cobra_MPTT::factory()->insert_as_first_child($food); //(id=2)
        $grain = Cobra_MPTT::factory()->insert_as_last_child($food);  //(id=3)

        // 'Red' and 'Yellow' are direct descendants of 'Fruit'
        $red = Cobra_MPTT::factory()->insert_as_first_child($fruit); //(id=4)
        $yellow = Cobra_MPTT::factory()->insert_as_first_child($fruit); //(id=5)

        // add a fruit of each color
        $cherry = Cobra_MPTT::factory()->insert_as_first_child($red); //(id=6)
        $banana = Cobra_MPTT::factory()->insert_as_first_child($yellow); //(id=7)

        // add two kinds of grain
        $whole = Cobra_MPTT::factory()->insert_as_first_child($grain); //(id=8)
        $refined = Cobra_MPTT::factory()->insert_as_last_child($grain); //(id=9)

        // add tomato to food (id=10)
        $tomato = Cobra_MPTT::factory()->insert_as_first_child($food);
        // Ah, but tomato is a fruit
        $tomato->move_to_first_child($fruit);
        // Not only fruit, but red
        $tomato->move_to_last_child($red);

        // get children of 'Red'
        echo("<pre>Red Children\n");
        foreach ($red->reload()->children() as $item)
            echo $item;
        echo('</pre>');

        // get children of 'Grain'
        echo("<pre>Grain Children\n");
        foreach ($grain->reload()->children() as $item)
            echo $item;
        echo('</pre>');

        // get children of 'Fruit'
        echo("<pre>Fruit Children\n");
        foreach ($fruit->children() as $item)
            echo $item;
        echo('</pre>');

        // data in the database as a multidimensional array
        echo("<pre>Full Tree\n");
        foreach ($food->reload()->fulltree() as $item)
            echo $item;
        echo('</pre>');

        ?>

    </body>
</html>
