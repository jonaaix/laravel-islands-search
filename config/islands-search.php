<?php

return [
    /*
    | How many hits a single source may contribute to one answer.
    */
    'limit_per_source' => 6,

    /*
    | The longest query the endpoint accepts; anything longer is rejected before a source sees it.
    */
    'max_query_length' => 100,

    /*
    | How many recently opened hits the modal keeps per user in the browser.
    */
    'recent_limit' => 8,
];
