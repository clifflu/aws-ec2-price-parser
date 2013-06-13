<?php

function need_fetch() {
    return false;
}

/**
 * Fetch data files from AWS
 * @return [type] [description]
 */
function fetch() {
    global $CONFIG;

    if (!need_fetch())
        return;

    foreach ($CONFIG['filelist']['files'] as $fn) {
        // urllib.urlretrieve(aws_url(fn), local_fn(fn))
        throw new Exception('todo');
    }
}