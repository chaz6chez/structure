<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/9/6            #
# -------------------------- #
require __DIR__ . '/../vendor/autoload.php';
function dump($var){
    print_r($var);
    exit;
}
header("Content-type: text/html; charset=utf-8");
class Go{
    /**
     * @return array|null|string
     * @throws ReflectionException
     */
    public function to(){
        $a = \Test\Check::factory([
            'a' => '',
            'b' => '1',
        ]);
//        $a = \Example\User::factory([
//            'a' => '1234',
//            'b' => '1',
//        ]);
        $a->validate();
        if($a->hasError()){
            dump($a->getError());
        }
        print_r($a->toArray(TRUE));

    }

}

$a = new Go();
print_r($a->to());
