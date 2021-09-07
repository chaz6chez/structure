<?php
declare(strict_types=1);

const STRUCT_FILTER_NORMAL           = 0;
const STRUCT_FILTER_NULL             = 1; # 过滤NULL
const STRUCT_FILTER_EMPTY            = 2; # 过滤空字符串
const STRUCT_FILTER_ZERO             = 3; # 过滤0
const STRUCT_FILTER_KEY              = 4; # 过滤Key
const STRUCT_FILTER_KEY_REVERSE      = 5; # 过滤Key反转
const STRUCT_FILTER_OPERATOR         = 6; # 过滤operator
const STRUCT_FILTER_OPERATOR_REVERSE = 7; # 过滤operator反转

const STRUCT_TRANSFER_OPERATOR  = 101; # 转换operator
const STRUCT_TRANSFER_MAPPING   = 102; # 转换mapping

const STRUCT_TAG_DEFAULT  = 'default';
const STRUCT_TAG_RULE     = 'rule';
const STRUCT_TAG_REQUIRED = 'required';
const STRUCT_TAG_SKIP     = 'skip';
const STRUCT_TAG_GHOST    = 'ghost';
const STRUCT_TAG_OPERATOR = 'operator';
const STRUCT_TAG_MAPPING  = 'mapping';
const STRUCT_TAG_KEY      = 'key';