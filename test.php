<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
$filter = 'string:coco,max:1,min:1,nice:1';
$parts = explode(',', $filter);
$filterName = strtolower(array_shift($parts));
$special = explode(':', $filterName);
$options = [];
foreach ($parts as $part) {
    $part = trim($part);
    if (empty($part)) {
        continue;
    }
    $partArr = explode(':', $part, 2);
    $options[$partArr[0]] = $partArr[1];
}
print_r( [$special,$filterName,$options]);
