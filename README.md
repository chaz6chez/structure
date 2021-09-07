# Structure - 2.0.0

**A useless validator**
****
If you have any good suggestions and 
comments, please contact **250220719@qq.com**
***

## 应用场景

 - 出入参的判断及过滤
 - 数据转化及映射
 
## 使用说明

- 继承 Structure\Struct 及实现结构体
- public属性接参

````injectablephp
namespace Example;

use Structure\Struct;

class User extends Struct{
    public $id;
    public $name;
    public $sex;
}
````

- 对要操作和转化的public属性进行注释

````injectablephp

    /**
     * @rule string,min:10,max:20|name format error:1001 
     */
    public $name;
````

- 标签分为四个区域
   - <a href="#标签区">a区：标签区</a>
   - b区：场景区
   - c区：验证区
   - d区：内容信息
```injectablephp
/**
 *  a区   b区        c区              d区
 * @标签 [场景]   验证方式   | 错误信息     : 错误码
 *      ↓           ↓           ↓             ↓
 * @rule[check] string,min:1|error message:error code  
 */                       
````

***
## <a id="标签区">标签区</a>：

- **转换类的标签配合 filter()在output() 方法内生效，
  会对包含该标签的属性执行转换或者过滤操作**
  

- **验证类的标签在 validate() 中生效返回布尔值，
  通过getError() 可以获得错误信息**

|标签名|方式|类型|说明|
| :---: | :---:|:-----:| :--- |
| <a href="#@default">@default</a> | Structure\Handler、func、method | 转换 | func与method是将返回值默认赋予该标签 |
| <a href="#@required">@required</a>| true |验证| 判断是否为必要值 |
| <a href="#@rule">@rule</a> | Structure\Handler、func、method |验证| 以func与method的bool返回类型判断验证 |
| <a href="#@skip">@skip</a> | 无 |验证| 跳过验证 |
| <a href="#@ghost">@ghost</a>| 无 |转换| 跳过输出 |
| <a href="#@key">@key</a>| 无 |转换| 标记钥匙属性|
| <a href="#@mapping">@mapping</a>| 映射键名 |转换| 映射键转换 |
| <a href="#@operator">@operator</a>| 无 |转换| 键值特殊转换 |

### <a id="@default">@default</a>
- 将该属性标记默认模式
- 当该属性值为null且具备@default标签时生效

````injectablephp
    /**
     * @default string:abc
     * @default int:123
     * @default float:1.1
     * @default object:Handler\Help
     * @default map:{"a":"1"}
     * @default array:{"1"}
     * @default bool:true
     */
    public $name;
````

- 验证区可使用func、method进行方法赋值

````injectablephp
    /**
     * @default func:is_array              会找到is_array函数
     * @default method:_set                会定位当前类的_set方法
     * @default method:Handler\Help,get    会定位Handler\Help类的get方法
     */
    public $name;
    public function _set(){
        return 'abc';
    }
````

### <a id="@required">@required</a>

````injectablephp
    /**
     * @required true|name cannot empty
     */
    public $name;
````

### <a id="@rule">@rule</a>

- 通过预置Handler进行验证

````injectablephp
    /**
     * @rule string,min:10,max:20|name format error
     * @rule int,min:10,max:20|name format error
     * @rule float,min:1.0,max:2.1,scale:3|name format error
     * @rule bool,true|name format error
     * @rule object,class:|name format error
     * @rule array,min:10,max:20,values:string|name format error
     * @rule map,min:1,max:5,keys:string,values:int|name format error
     * @rule url,path:true,query:true|name format error
     * @rule ip,ipv4:false,ipv6:false,private:false,reserved:false|name format error
     * @rule regex,min:10,max:20,regex:/.?/|name format error
     */
    public $name;
````

- 验证区可使用func、method进行方法判断

````injectablephp
    /**
     * @rule func:is_array              会找到is_array函数
     * @rule method:_set                会定位当前类的_set方法
     * @rule method:Handler\Help,get    会定位Handler\Help类的get方法
     */
    public $name;
    
    public function _set($value) : bool
    {
        return $value === 'abc';
    }
````
### <a id="@ghost">@ghost</a>
- output()不会输出该标签

````injectablephp
    # @ghost true
    $user->id = 'id';

    $user->name = 'name';
    $user->output();
    # 以上会输出
    [
        'name' => 'name'
    ];

    $user->output(true);
    # 以上会输出
    [
        'id' => 'id',
        'name' => 'name'
    ];
````

### <a id="@key">@key</a>
- 将该属性标记钥匙字段
- 通过 filter()->output() 可以做到仅输出钥匙字段

````injectablephp
    # @key true
    $user->id = 'id';

    $user->name = 'name';
    $user->filter(STRUCT_FILTER_KEY)->output();
    # 以上会输出
    [
        'name' => 'name'
    ];
    
    $user->transfer(STRUCT_FILTER_KEY_REVERSE)->output();
    # 以上会输出
    [
        'id' => 'id',
    ];
````

### <a id="@skip">@skip</a>
- 跳过验证，但不影响输出

### <a id="@operator">@operator</a>

- 该标签为medoo语法定制

- 通过 transfer()->output() 可以做到转换输出

````injectablephp
    # @operator true
    $user->id = 'abc[>]';
    $user->transfer(STRUCT_TRANSFER_OPERATOR)->output();
    # 以上会输出
    [
        'id[>]' => 'abc'
    ];

    $user->id = '123,456[<>]';
    $user->transfer(STRUCT_TRANSFER_OPERATOR)->output();
    # 以上会输出
    [
        'id[<>]' => ['123','456'],
    ];
````
#### **该标签在类型转换上并未完善，联合调用Medoo建议直接使用数组**

- 在int、float的数据下，会有如下所示的影响

````injectablephp
    # @operator true
    $user->id = '123[>]';
    $user->transfer(STRUCT_TRANSFER_OPERATOR)->output();
    # 以上会输出
    [
        'id[>]' => '123'
    ];
    # 并非期待的
    [
       'id[>]' => 123
    ];
    # 此种状况会影响数据库查询的索引
````


### <a id="@mapping">@mapping</a>
- 将该属性标记映射处理
- 通过 transfer()->output() 可以做到转换输出

````injectablephp
    # @mapping key
    $user->id = 123;

    $user->name = 'john';
    $user->output();
    # 输出
    [
        'id' => 123,
        'name' => 'john'
    ];

    $user->transfer(STRUCT_TRANSFER_MAPPING)->output();
    # 输出
    [
        'key' => 123,
        'name' => 'john'
    ];
````

***
## 方法

- 实例化

````injectablephp
    $user = User::factory([
        'id' => 1,
        'name' => 'john'
    ],'check');
````

- 输入

````injectablephp
    # 1.使用create输入数据
    $user->create([
        'id' => 1,
        'name' => 'john'
    ]); # return $this

    # 2.使用属性赋值输入数据
    $user->id = 1;
    $user->name = 'john';

    # 使用create可以保存原始数据，建议使用create输入数据
````

- 设置场景

````injectablephp
    $user->scene('check'); # return $this
````

- 转换
    
````injectablephp
    $user->transfer(STRUCT_TRANSFER_MAPPING); # return $this

    # STRUCT_TRANSFER_MAPPING
    # STRUCT_TRANSFER_OPERATOR

    # 接受可变长参数
    $user->transfer(
        STRUCT_TRANSFER_MAPPING,
        STRUCT_TRANSFER_OPERATOR
    ); # return $this
````

- 过滤

````injectablephp
    $user->filter(STRUCT_FILTER_NULL); # return $this

    # STRUCT_FILTER_NULL
    # STRUCT_FILTER_EMPTY
    # STRUCT_FILTER_ZERO
    # STRUCT_FILTER_KEY
    # STRUCT_FILTER_KEY_REVERSE
    # STRUCT_FILTER_OPERATOR
    # STRUCT_FILTER_OPERATOR_REVERSE

    # 接受可变长参数
    $user->filter(
        STRUCT_FILTER_NULL,
        STRUCT_FILTER_EMPTY
    ); # return $this
````

- 验证

````injectablephp
    $user->validate(); # return bool
    $user->hasError(); # return bool

    # true 有错误，验证未通过
    # false 无错误，验证通过
````

- 获取错误

````injectablephp
    $user->getError(); # return Structure\Error

    $user->getError()->getMessage();  # 错误信息 string
    $user->getError()->getCode();     # 错误码 string
    $user->getError()->getField();    # 字段名 string
    $user->getError()->getPosition(); # 错误定位 对应Handler对应的options字段

    $user->getErrors(); # return Structure\Error[]

````

- 输出

````injectablephp
    $user->output(); # return array

    $user->output(true); # 全量输出
````

- 清洗

````injectablephp
    $user->clean(); # 默认不装载raw数据

    $user->clean(true); # 装载raw数据
````

## 补充

- Handler 接受自定义注册

````injectablephp
    \Structure\Handler::register();
````



