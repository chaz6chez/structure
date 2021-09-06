<?php
declare(strict_types=1);

const STRUCT_FILTER_NORMAL      = 0;
const STRUCT_FILTER_NULL        = 1; # 过滤NULL
const STRUCT_FILTER_EMPTY       = 2; # 过滤空字符串
const STRUCT_FILTER_ZERO        = 3; # 过滤0
const STRUCT_FILTER_KEY         = 4; # 过滤Key
const STRUCT_FILTER_KEY_REVERSE = 5; # 过滤Key反转


const STRUCT_TAG_DEFAULT  = 'default';
const STRUCT_TAG_RULE     = 'rule';
const STRUCT_TAG_REQUIRED = 'required';
const STRUCT_TAG_SKIP     = 'skip';
const STRUCT_TAG_GHOST    = 'ghost';
const STRUCT_TAG_OPERATOR = 'operator';
const STRUCT_TAG_MAPPING  = 'mapping';
const STRUCT_TAG_KEY      = 'key';