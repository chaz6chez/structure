# structure

## v1.0.4


一个没什么用的验证器

有什么好的建议和想法，请联系250220719@qq.com
***
## 使用场景
 - 接口出参入参
 - 方法参数结构体
 - 数据库字段映射
 
## 说明
 - 新建类继承Structure下的Struct即可
````
namespace Example;

use Structure\Struct;

class User extends Struct{

}
````
 - 创建public属性
````
namespace Example;

use Structure\Struct;

class User extends Struct{
    public $id;
    public $name;
    public $sex;
}
````
 - Structure会对对注释逐行解释
````
    /**
     * @default num:7                  ->  若空值则默认为num类型7 
     * @required true|XXXXX            ->  该值为空时,提示XXXXX
     * @skip                           ->  跳过验证(不执行该字段所有限制条件,toArray()默认输出，toArray(true)时过滤)
     * @ghost                          ->  跳过输出(执行限制条件,toArray输出过滤该字段)
     * @rule string,min:10,max:20|XXXX ->  验证规则,使用filter库/使用方法/使用实例验证规则
     */
    public $name;
```` 

 - 将每一行分析四个区域
   - <a href="#标签区">a区：标签区</a>
   - <a href="#场景区">b区：场景区</a>
   - <a href="#验证区">c区：验证区</a>
   - <a href="#内容信息">d区：内容信息</a>
````
    a区   b区     c区       d区
 @验证方式[场景] 验证方式|错误提示语:错误码
       ↓           ↓         ↓     ↓
 @rule[check] string,min:1|XXXXXX:500                              
````

***
## <a id="标签区">标签区</a>：

| 标签名 | 方式 | 说明|
| :---: | :---:| :--- |
| @default | 类型、func、method | func与method是将返回值默认赋予该标签 |
| @required| true | 判断是否为必要值 |
| @operator| 无 | 与toArrayKey()方法配套 |
| @rule | 类型、func、method | 以func与method的bool返回类型判断验证 |
| @skip | 无 | 跳过验证 |
| @ghost| 无 | 跳过输出 |
| @key| 无 | 与toArrayKey()方法配套 |

### @default
- 将该属性标记默认模式
- 在使用任何输出方法时，如outputArray()，拥有该标签的属性都会使用验证区的执行方式进行默认赋值
- 如本身外部对该属性进行赋值，则默认不会生效
- 验证区可使用int|float|null|array|bool|string进行类型赋值
````
    /**
     * @default string:abc
     * @default int:123
     * @default float:1.1
     * @default null
     * @default array:{"a":"1"}
     * @default bool:true
     */
    public $name;
````
- 验证区可使用func、method进行方法赋值
````
    /**
     * @default func:is_array  会找到自定函数或内置函数
     * @default method:_set    会定位当前类的方法
     */
    public $name;
    public function _set(){
        return 'abc';
    }
````
### @required
- 将该属性标记要求验证
- 在使用validate()方法或是create()方法开启validate=true后，
不满足条件的属性，会将d区内容增加至error内容
````
    /**
     * @required true|XXXXX
     */
    public $name;
````
### @rule
- 将该属性标记规则模式
- 在使用validate()方法或是create()方法开启validate=true后，
  不满足条件的属性，会将d区内容增加至error内容
- 验证区可使用int|float|null|array|bool|string等进行类型判断，会调用Filter进行处理
````
        'array'  => 'Structure\Handle\Arrays',
        'assoc'  => 'Structure\Handle\Assoc',
        'bool'   => 'Structure\Handle\Booleans',
        'float'  => 'Structure\Handle\Floats',
        'int'    => 'Structure\Handle\Ints',
        'ip'     => 'Structure\Handle\IP',
        'object' => 'Structure\Handle\Object',
        'string' => 'Structure\Handle\Strings',
        'pool'   => 'Structure\Handle\Pool',    
        'map'    => 'Structure\Handle\Map',
        'url'    => 'Structure\Handle\URL',
        'regex'  => 'Structure\Handle\Regex',
        'chain'  => 'Structure\Handle\Chain',
````
 - 类型判断时，验证区内容会传入Filter进行解析
````
    /**
     * @rule string,min:10,max:20|XXXX
     */
    public $name;
````
- 验证区可使用func、method进行方法判断，当函数返回true时验证通过，false时会将d区信息加入error内容
````
    /**
     * @default func:is_array  会找到自定函数或内置函数，将当前属性值传入该函数
     * @default method:_set    会定位当前类的方法，将当前属性值传入该方法
     */
    public $name;
    public function _set($v){
        return $v === 'abc';
    }
````
### @operator
- 将该属性标记操作字段
- 使用输出的时候可以将属性值进行转化操作
- 需配合setOperator()方法
````
namespace Example;

use Structure\Struct;

class User extends Struct{
    /**
     * @operator true
     */
    public $id;
}

$user = User::factory()
# outputArray
$user->id = 'abc[>]|bcd[<]';
$user->setOperator($user::OPERATER_LOAD_OUTPUT)->outputArray();
# 以上会输出
[
    'id[>]' => 'abc',
    'id[<]' => 'bcd'
];

$user->id = '123,456[<>]';
$user->setOperator($user::OPERATER_LOAD_OUTPUT)->outputArray();
# 以上会输出
[
    'id[<>]' => [123,456],
];
````
### @mapping
- 将该属性标记映射处理
- 使用outputArrayUseMapping()输出的时候可以将属性字段进行转化输出
- 支持与@operator共存
- 多个@mapping仅生效第一个
````
    /**
     * @mapping key
     */
    public $name;

$user->name = 123;
$user->outputArrayUseMapping();
# 输出
[
    'key' => 123
]
````
### @ghost
- 将该属性标记幽灵字段
- 在使用输出方法，如outputArray()，则不会被输出
### @key
- 将该属性标记钥匙字段
- 在使用outputArrayByKey()时可以做到仅输出钥匙字段
### @skip
- 将该属性标记忽略字段
- 跳过验证，但不影响输出

***
## 方法

####factory($data[非必要],$scene[非必要])
- 实例化方法
````
$user = User::factory(['id'=>1],'check');
````

####setScene($scene)
- 设置场景
````
$user->serScene('check');
````
####setOperator($operator[默认值],$need[默认值])
- 设置operator标签处理方式
- $operator可使用一下常量
  - OPERATER_CLOASE        # 默认关闭
  - OPERATER_LOAD_OUTPUT   # 装载输出
  - OPERATER_FILTER_OUTPUT # 过滤输出
````
$user->setOperator($user::OPERATER_LOAD_OUTPUT);
````

####emptyToNull($bool[默认值])
- 设置属性值赋值时是否将空字符串转化为null
- 默认true
````
$user->emptyToNull(false);
````

####~~toArray($filterNull)~~
- **请使用outputArray**

####~~toArrayStrict($filterNull)~~
- **请使用outputArray**

####outputArray($filter[默认值],$output[默认值],$scene[非必要])
- 输出类方法
- 将public属性整合以array输出返回
- $filter可使用一下常量进行过滤操作
   - FILTER_NORMAL # 默认不过滤
   - FILTER_NULL   # 过滤NULL
   - FILTER_EMPTY  # 过滤空字符串
   - FILTER_STRICT # 严格过滤
   - FILTER_KEY    # 仅输出KEY字段
- $output可使用一下常量进行输出处理
   - OUTPUT_NORMAL  # 默认输出
   - OUTPUT_NULL    # 空字符串转NULL
   - OUTPUT_EMPTY   # NULL转空字符串
   - OUTPUT_KEY     # 仅输出KEY字段  
    
####outputArrayUseMapping($filter[默认值],$output[默认值],$scene[非必要])
   - 输出类方法
   - 对@mapping标签的属性字段进行转化
   - 将public属性整合以array输出返回
   - $filter可使用一下常量进行过滤操作
      - FILTER_NORMAL # 默认不过滤
      - FILTER_NULL   # 过滤NULL
      - FILTER_EMPTY  # 过滤空字符串
      - FILTER_STRICT # 严格过滤
      - FILTER_KEY    # 仅输出KEY字段
   - $output可使用一下常量进行输出处理
      - OUTPUT_NORMAL  # 默认输出
      - OUTPUT_NULL    # 空字符串转NULL
      - OUTPUT_EMPTY   # NULL转空字符串
      - OUTPUT_KEY     # 仅输出KEY字段   

####outputArrayByKey($filterNull[默认值],$scene[非必要])
- 输出类方法
- 将带有@key标签的public属性整合以array输出返回
- $filterNull true:过滤null值 false:不过滤 

####create($data,$validate[默认])
- 映射数据

####validate($data[非必要])
- 验证

####hasError($filed[非必要])
- 判断error区是否有错误
####getError()
- 获取第一条error区信息
####getErrors()
- 获取error区所有信息 array

####getCode()
- 获取error区错误码

####getCodes()
- 获取error区所有错误码 array

####clean($default[默认值])
- 对当前结构体初始化
- $default true：执行@default false：不执行


| 方法名 | 参数 | 说明 |
| :---: | :---: | :---| 
|   factory($data,$scene)| data:数据(可选) scene:场景(可选) | 实例化方法,可加载数据和场景 |
|   setScene($scene)| scene:场景 | 设置场景,在验证方法之前调用有效 |
|   toArray($filterNull)| filterNull:是否过滤空值(可选) | 数据以数组形式输出（不过滤空字符串） |
|   toArrayStrict($filterNull)| filterNull:是否过滤空值(可选) | 数据以数组形式输出（过滤空字符串） |
|   outputArrayByKey($filterNull,$scene)| filterNull:是否过滤空值(可选) scene:场景 | 数据以数组形式输出（不过滤空字符串） |
|   create($data,$validate)| data:数据 validate:是否执行验证(可选) | 输入数据,可执行验证 |
|   validate($data)| data:数据(可选) | 验证器方法,可加载数据 |
|   hasError($filed)| filed:条件(可选) | 错误确认，返回布尔 |
|   getError()| 无 | 获取第一条错误信息 |
|   getErrors()| 无 | 以数组形式获取所有错误信息 |
|   getCode()| 无 | 获取第一条错误码 |
|   getCodes()| 无 | 以数组形式获取所有错误码 |
|   clean($default)| default可选 | 初始化已创建的struct |
 
 
***
## 例子
- 方式一
````    
        $data = [
            'a' => '',
            'b' => '1',
        ];
        
        $structure = \Test\Check::factory();
        $structure->setScene('login');
        $structure->create($data,true);
        if($structure->hasError()){
            $this->response->error($structure->getError());
        }
        $this->response->success($structure->toArray());

````
- 方式二
````    
        // 与方式一数据内容相同
        $structure = \Test\Check::factory($data,'login');
        $structure->validate();
        // 与方式一判断相同
````
- 方式三
````    
        // 与方式一数据内容相同
        $structure = \Test\Check::factory();
        $structure->setScene('login');
        $structure->validate($data);
        // 与方式一判断相同
````
***
## 补充

1. 所有验证标签均可使用[XXX]场景化区分.
2. Filter类可继承使用，内置的注册方法可以添加自定义注册类，更多方法自己摸索.


