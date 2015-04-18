<?php
for($i = 0; $i < 100; $i++) {
    $params['body'][] = array(
        'index' => array(
            '_id' => $i,
            "_index" => "my_index",
            "_type" => "my_type"
        )
    );

    $params['body'][] = array(
        'my_field' => 'my_value',
        'second_field' => 'some more values'
    );
}

echo "<pre>";
print_r ($params);