#structure

## 解析
````
 @rule[login] func:_check|XXXXXX
    ↓     ↓        ↓         ↓
 验证规则  ↓        ↓         ↓
       执行场景     ↓         ↓
                验证方式      ↓
                           提示语
````
## 标签
````
/**
 * @default num:7                  ->  若空值则默认为num类型7
 * @required true|XXXXX            ->  该值为空时,提示XXXXX
 * @skip                           ->  跳过验证(不执行该字段所有限制条件,toArray()默认输出，toArray(true)时过滤)
 * @ghost                          ->  跳过输出(执行限制条件,toArray输出过滤该字段)
 * @rule string,min:10,max:20|XXXX ->  验证规则,使用filter库/使用方法/使用实例验证规则
 */
````
## 例子
````    
        // function start
        
        $data = [
            'a' => '',
            'b' => '1',
        ];
        $structure = \Test\Check::factory($data);
        $structure->validate();
        if($structure->hasError()){
            $this->response->error($structure->getError());
        }
        $this->response->success($structure->toArray());
        
        // function end
````
## 说明

````
# 1.所有验证标签均可使用[XXX]场景化区分.
# 2.filter库可修改库类文件提供默认验证规则.
# 3.修改Handle下类库文件的_defaultOptions数据,可以更改默认规则.
# 4.default标签可使用func和method,与rule区别是,
# rule使用其返回true or false来进行判断,default直接使用其返回值.
````